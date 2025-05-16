<?php
namespace Daniesy\HtmlParser;
require_once __DIR__ . '/HtmlNode.php';

class HtmlParser {
    private static $voidElements = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    public static function parse($html, $normalizeWhitespace = false) {
        $html = trim($html);
        $doctype = '';
        if (preg_match('/^<!DOCTYPE[^>]*>/i', $html, $m)) {
            $doctype = $m[0];
            $html = ltrim(substr($html, strlen($m[0])));
        }
        $root = new HtmlNode('root');
        $stack = [$root];
        $offset = 0;
        $len = strlen($html);
        while ($offset < $len) {
            $substr = substr($html, $offset);
            // Comment
            if (preg_match('/^<!--(.*?)-->/s', $substr, $m)) {
                $node = new HtmlNode('', [], false, $m[1], true);
                end($stack)->appendChild($node);
                $offset += strlen($m[0]);
            }
            // CDATA
            elseif (preg_match('/^<!\[CDATA\[(.*?)\]\]>/s', $substr, $m)) {
                $node = new HtmlNode('', [], false, $m[1], false, true);
                end($stack)->appendChild($node);
                $offset += strlen($m[0]);
            }
            // Script/style raw content
            elseif (preg_match('/^<(script|style)([^>]*)>([\s\S]*?)<\/\1>/i', $substr, $m)) {
                $attrs = self::parseAttributes($m[2]);
                $node = new HtmlNode(strtolower($m[1]), $attrs);
                $textNode = new HtmlNode('', [], true, $m[3]);
                $node->appendChild($textNode);
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
                $node = new HtmlNode($tag, $attrs, false, '', false, false, $namespace);
                end($stack)->appendChild($node);
                $offset += strlen($m[0]);
                if (!$isVoid) {
                    $stack[] = $node;
                }
            }
            // Closing tag
            elseif (preg_match('/^<\/([a-zA-Z0-9\-:]+)>/', $substr, $m)) {
                array_pop($stack);
                $offset += strlen($m[0]);
            }
            // Text node
            elseif (preg_match('/^([^<]+)/', $substr, $m)) {
                $text = $m[1];
                if ($normalizeWhitespace) {
                    // Collapse all whitespace (space, tab, newline, etc) to a single space
                    $text = preg_replace('/\s+/', ' ', $text);
                }
                $textNode = new HtmlNode('', [], true, html_entity_decode($text));
                end($stack)->appendChild($textNode);
                $offset += strlen($m[1]);
            } else {
                $offset++;
            }
        }
        if (count($root->children) === 1 && $root->children[0]->tag === 'html') {
            $htmlNode = $root->children[0];
            $htmlNode->doctype = $doctype;
            return $htmlNode;
        }
        $root->doctype = $doctype;
        return $root;
    }

    private static function parseSingleNode($html) {
        $html = trim($html);
        if (preg_match('/^<([a-zA-Z0-9\-]+)([^>]*)>([\s\S]*)<\/\1>$/', $html, $m)) {
            $tag = $m[1];
            $attrStr = $m[2];
            $inner = $m[3];
            $attrs = self::parseAttributes($attrStr);
            $node = new HtmlNode($tag, $attrs);
            $childrenRoot = self::parse($inner);
            foreach ($childrenRoot->children as $child) {
                $node->appendChild($child);
            }
            return $node;
        }
        return new HtmlNode('root');
    }

    private static function parseAttributes($str) {
        $attrs = [];
        // Match key="value", key='value', key=value, or key (boolean attribute)
        if (preg_match_all('/([a-zA-Z0-9\-:]+)(?:\s*=\s*("[^"]*"|\'[^"]*\'|[^\s>]+))?/', $str, $m, PREG_SET_ORDER)) {
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
