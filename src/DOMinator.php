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
            elseif (preg_match('/^<([a-zA-Z0-9\-:]+)([^>]*)\s*\/?>/', $substr, $m)) {
                $tag = strtolower($m[1]);
                $attrStr = $m[2];
                $attrs = self::parseAttributes($attrStr);
                $namespace = '';
                if (strpos($tag, ':') !== false) {
                    [$namespace, $tag] = explode(':', $tag, 2);
                }
                $isVoid = in_array($tag, self::$voidElements) || substr($m[0], -2) === '/>';
                // Handle unclosed tags for certain elements (li, p, td, th, tr, option, etc.)
                $autoCloseTags = ['li', 'p', 'td', 'th', 'tr', 'option', 'dt', 'dd'];
                if (in_array($tag, $autoCloseTags)) {
                    // If the top of the stack is the same tag, close it first
                    if (end($stack)->tag === $tag) {
                        array_pop($stack);
                    }
                }
                $node = new Node($tag, $attrs, false, '', false, false, $namespace);
                end($stack)->appendChild($node);
                $offset += strlen($m[0]);
                if (!$isVoid) {
                    $stack[] = $node;
                }
            }
            // Closing tag
            elseif (preg_match('/^<\/([a-zA-Z0-9\-:]+)>/', $substr, $m)) {
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
                $textNode = new TextNode('', [], true, html_entity_decode($text));
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
        if (preg_match_all('/([a-zA-Z0-9\-:]+)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+))?/', $str, $m, PREG_SET_ORDER)) {
            foreach ($m as $a) {
                $name = $a[1];
                if (isset($a[2]) && $a[2] !== '') {
                    $val = html_entity_decode(trim($a[2], "'\""));
                } else {
                    $val = '';
                }
                $attrs[$name] = $val;
            }
        }
        return $attrs;
    }
}
