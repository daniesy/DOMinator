<?php
namespace Daniesy\HtmlParser;
require_once __DIR__ . '/HtmlNode.php';

class HtmlParser {
    public static function parse($html) {
        $html = trim($html);
        // Handle doctype
        $doctype = '';
        if (stripos($html, '<!DOCTYPE') === 0) {
            $end = strpos($html, '>');
            $doctype = substr($html, 0, $end + 1);
            $html = ltrim(substr($html, $end + 1));
        }
        $root = new HtmlNode('root');
        $stack = [$root];
        $offset = 0;
        $len = strlen($html);
        while ($offset < $len) {
            if (preg_match('/^<([a-zA-Z0-9\-]+)([^>]*)>/', substr($html, $offset), $m)) {
                $tag = $m[1];
                $attrStr = $m[2];
                $attrs = self::parseAttributes($attrStr);
                $node = new HtmlNode($tag, $attrs);
                end($stack)->appendChild($node);
                $stack[] = $node;
                $offset += strlen($m[0]);
            } elseif (preg_match('/^<\/([a-zA-Z0-9\-]+)>/', substr($html, $offset), $m)) {
                array_pop($stack);
                $offset += strlen($m[0]);
            } elseif (preg_match('/^([^<]+)/', substr($html, $offset), $m)) {
                $text = $m[1];
                $textNode = new HtmlNode('', [], true, $text);
                end($stack)->appendChild($textNode);
                $offset += strlen($text);
            } else {
                $offset++;
            }
        }
        // If the root has only one child and it's <html>, return that node
        if (count($root->children) === 1 && $root->children[0]->tag === 'html') {
            $htmlNode = $root->children[0];
            $htmlNode->doctype = $doctype;
            return $htmlNode;
        }
        // Otherwise, store doctype in root
        $root->doctype = $doctype;
        return $root;
    }

    // Helper for parsing a single node (used for <html> document)
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
        if (preg_match_all('/([a-zA-Z0-9\-:]+)\s*=\s*(["\"][^"\"]*["\"]|[^\s>]+)/', $str, $m, PREG_SET_ORDER)) {
            foreach ($m as $a) {
                $name = $a[1];
                $val = trim($a[2], "'\"");
                $attrs[$name] = $val;
            }
        }
        return $attrs;
    }
}
