<?php
namespace Daniesy\DOMinator;

use Daniesy\DOMinator\Nodes\Node;
use Daniesy\DOMinator\Nodes\TextNode;
use Daniesy\DOMinator\Nodes\CommentNode;
use Daniesy\DOMinator\Nodes\ScriptNode;
use Daniesy\DOMinator\Nodes\StyleNode;

class DOMinator {
    private static array $voidElements = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    public static function read(string $html, bool $normalizeWhitespace = false): Node {
        $html = trim($html);
        $xmlDeclaration = '';
        if (preg_match('/^<\?xml[^>]*\?>/i', $html, $m)) {
            $xmlDeclaration = $m[0];
            $html = ltrim(substr($html, strlen($m[0])));
        }
        $doctype = '';
        if (preg_match('/^<!DOCTYPE[^>]*>/i', $html, $m)) {
            $doctype = $m[0];
            $html = ltrim(substr($html, strlen($m[0])));
        }
        $root = new Node('root');
        $root->xmlDeclaration = $xmlDeclaration;
        $stack = [$root];
        $offset = 0;
        $len = strlen($html);
        while ($offset < $len) {
            $substr = substr($html, $offset);
            // Comment
            if (preg_match('/^<!--(.*?)-->/s', $substr, $m)) {
                $node = new CommentNode('', [], false, $m[1]);
                end($stack)->appendChild($node);
                $offset += strlen($m[0]);
            }
            // CDATA
            elseif (preg_match('/^<!\[CDATA\[(.*?)\]\]>/s', $substr, $m)) {
                $node = new Node('', [], false, $m[1], false, true);
                end($stack)->appendChild($node);
                $offset += strlen($m[0]);
            }
            // Script/style raw content
            // Handle script and style nodes specially to preserve their content
            elseif (preg_match('/^<(script)([^>]*)>([\s\S]*?)<\/script>/i', $substr, $m)) {
                $attrs = self::parseAttributes($m[2]);
                $node = new ScriptNode($attrs, $m[3]);
                end($stack)->appendChild($node);
                $offset += strlen($m[0]);
            }
            elseif (preg_match('/^<(style)([^>]*)>([\s\S]*?)<\/style>/i', $substr, $m)) {
                $attrs = self::parseAttributes($m[2]);
                $node = new StyleNode($attrs, $m[3]);
                end($stack)->appendChild($node);
                $offset += strlen($m[0]);
            }
            // Self-closing or void element
            else if (substr($substr, 0, 1) === '<') {
                // Robust tag boundary parser: find the closing '>' not inside quotes
                $inSingle = $inDouble = false;
                $i = 1;
                $lenSub = strlen($substr);
                for (; $i < $lenSub; $i++) {
                    $c = $substr[$i];
                    if ($c === "'" && !$inDouble) {
                        $inSingle = !$inSingle;
                    } elseif ($c === '"' && !$inSingle) {
                        $inDouble = !$inDouble;
                    } elseif ($c === '>' && !$inSingle && !$inDouble) {
                        break;
                    }
                }
                if ($i < $lenSub) {
                    $tagChunk = substr($substr, 0, $i + 1);
                    // Check if this is a closing tag (e.g., </div>)
                    if (preg_match('/^<\s*\//', $tagChunk)) {
                        // Handle as closing tag
                        if (preg_match('/^<\s*\/([@a-zA-Z0-9_\-:.]+)\s*>$/', $tagChunk, $m)) {
                            $closeTag = strtolower($m[1]);
                            // Pop the stack until we find the matching tag or root
                            for ($j = count($stack) - 1; $j > 0; $j--) {
                                if ($stack[$j]->tag === $closeTag) {
                                    $stack = array_slice($stack, 0, $j);
                                    break;
                                }
                            }
                        }
                        $offset += strlen($tagChunk);
                        continue;
                    }
                    if (preg_match('/^<([@a-zA-Z0-9_:.-]+)(.*?)(\/)?\s*>$/s', rtrim($tagChunk, '>') . '>', $m)) {
                        $tag = strtolower($m[1]);
                        $attrStr = $m[2];
                        $attrs = self::parseAttributes($attrStr);
                        $namespace = '';
                        if (strpos($tag, ':') !== false) {
                            [$namespace, $tag] = explode(':', $tag, 2);
                        }
                        $isVoid = in_array($tag, self::$voidElements) || substr($tagChunk, -2) === '/>';
                        $autoCloseTags = ['li', 'p', 'td', 'th', 'tr', 'option', 'dt', 'dd'];
                        if (in_array($tag, $autoCloseTags)) {
                            if (end($stack)->tag === $tag) {
                                array_pop($stack);
                            }
                        }
                        $node = new Node($tag, $attrs, false, '', false, false, $namespace);
                        end($stack)->appendChild($node);
                        $offset += strlen($tagChunk);
                        if (!$isVoid) {
                            $stack[] = $node;
                        }
                        continue;
                    } else {
                        // Tag chunk did not match a tag, skip one character to avoid infinite loop
                        $offset++;
                        continue;
                    }
                } else {
                    // No closing > found, skip one character to avoid infinite loop
                    $offset++;
                    continue;
                }
            }
            // Closing tag
            elseif (preg_match('/^<\/([@a-zA-Z0-9_\-:.]+)>/', $substr, $m)) {
                $closeTag = strtolower($m[1]);
                // Pop the stack until we find the matching tag or root
                for ($i = count($stack) - 1; $i > 0; $i--) {
                    if ($stack[$i]->tag === $closeTag) {
                        $stack = array_slice($stack, 0, $i);
                        break;
                    }
                }
                $offset += strlen($m[0]);
            }
            // Text node
            elseif (preg_match('/^([^<]+)/', $substr, $m)) {
                $text = $m[1];
                if ($normalizeWhitespace) {
                    // Collapse all whitespace (space, tab, newline, etc) to a single space
                    $text = preg_replace('/\s+/', ' ', $text);
                }
                $textNode = new TextNode('', [], true, html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
                end($stack)->appendChild($textNode);
                $offset += strlen($m[1]);
            } else {
                $offset++;
            }
        }
        // Handle any unclosed tags at the end (auto-close)
        while (count($stack) > 1) {
            array_pop($stack);
        }
        if (count($root->children) === 1 && $root->children->item(0)->tag === 'html') {
            $htmlNode = $root->children->item(0);
            $htmlNode->doctype = $doctype;
            $htmlNode->xmlDeclaration = $xmlDeclaration;
            return $htmlNode;
        }
        $root->doctype = $doctype;
        $root->xmlDeclaration = $xmlDeclaration;
        return $root;
    }

    private static function parseSingleNode(string $html): Node {
        $html = trim($html);
        if (preg_match('/^<([a-zA-Z0-9\-]+)([^>]*)>([\s\S]*)<\/\1>$/', $html, $m)) {
            $tag = $m[1];
            $attrStr = $m[2];
            $inner = $m[3];
            $attrs = self::parseAttributes($attrStr);
            $node = new Node($tag, $attrs);
            $childrenRoot = self::read($inner);
            foreach ($childrenRoot->children as $child) {
                $node->appendChild($child);
            }
            return $node;
        }
        return new Node('root');
    }

    /**
     * Parse HTML attributes from a string
     * 
     * @param string $str The attribute string to parse
     * @return array An associative array of attribute name => value pairs
     */
    private static function parseAttributes(string $str): array {
        $attrs = [];
        // Match key="value", key='value', key=value, or key (boolean attribute)
        if (preg_match_all('/([@a-zA-Z0-9_\-:.]+)(?:\s*=\s*("[^"]*"|\'[^"]*\'|[^\s>]+))?/', $str, $m, PREG_SET_ORDER)) {
            foreach ($m as $a) {
                $name = $a[1];
                if (isset($a[2]) && $a[2] !== '') {
                    $val = html_entity_decode(trim($a[2], "'\""), ENT_QUOTES | ENT_HTML5);
                } else {
                    $val = '';
                }
                $attrs[$name] = $val;
            }
        }
        return $attrs;
    }
}
