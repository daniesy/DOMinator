<?php
namespace Daniesy\DOMinator;

use Daniesy\DOMinator\Nodes\Node;

class CssParser {
    public static function parse($css) {
        $rules = [];
        $offset = 0;
        $len = strlen($css);
        while ($offset < $len) {
            // Skip whitespace and comments
            while (preg_match('/\s+|\/\*.*?\*\//As', $css, $m, 0, $offset)) {
                $offset += strlen($m[0]);
            }
            if ($offset >= $len) break;
            $chunk = substr($css, $offset);
            // At-rule (starts with @)
            if (preg_match('/^@([a-zA-Z\-]+)[^\{]*\{/', $chunk, $m, PREG_OFFSET_CAPTURE)) {
                $start = strlen($m[0][0]);
                $braceCount = 1;
                $i = $start;
                $clen = strlen($chunk);
                while ($i < $clen && $braceCount > 0) {
                    if ($chunk[$i] === '{') $braceCount++;
                    elseif ($chunk[$i] === '}') $braceCount--;
                    $i++;
                }
                $block = substr($chunk, 0, $i);
                $rules[] = [
                    'type' => 'at',
                    'raw' => $block,
                    'selector' => null,
                    'props' => null,
                ];
                $offset += strlen($block);
                continue;
            }
            // Normal rule (exclude at-rules)
            if (preg_match('/^((?!@)[^\{]+)\{([^\}]*)\}/s', $chunk, $m)) {
                $selector = trim($m[1]);
                $props = trim($m[2]);
                $rules[] = [
                    'type' => 'rule',
                    'selector' => $selector,
                    'props' => self::parseProps($props),
                    'raw' => $m[0],
                ];
                $offset += strlen($m[0]);
                continue;
            }
            // Unmatched text, skip
            $offset++;
        }
        return $rules;
    }

    public static function parseProps($props) {
        $result = [];
        foreach (preg_split('/;(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/', $props) as $prop) {
            $prop = trim($prop);
            if ($prop === '') continue;
            $parts = explode(':', $prop, 2);
            if (count($parts) === 2) {
                $result[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $result;
    }

    /**
     * Returns true if the selector matches the node (supports tag, .class, #id, descendant, child)
     * 
     * @param string $selector The CSS selector to match against
     * @param Node $node The node to check
     * @return bool True if the node matches the selector
     */
    public static function matches(string $selector, Node $node): bool {
        $parts = preg_split('/\s+(?![^\[]*\])/', trim($selector));
        return self::matchSelectorParts($parts, $node);
    }

    private static function matchSelectorParts($parts, $node) {
        if (empty($parts)) return true;
        $part = array_pop($parts);
        if (!self::matchSimpleSelector($part, $node)) return false;
        if (empty($parts)) return true;
        // Descendant combinator (space)
        $parent = $node->parent;
        while ($parent) {
            if (self::matchSelectorParts($parts, $parent)) return true;
            $parent = $parent->parent;
        }
        return false;
    }

    private static function matchSimpleSelector($selector, $node) {
        $selector = trim($selector);
        if ($selector === '*') return true;
        if (preg_match('/^([a-zA-Z0-9\-_]+)$/', $selector)) {
            return $node->tag === strtolower($selector);
        }
        if (preg_match('/^#([a-zA-Z0-9\-_]+)$/', $selector, $m)) {
            return isset($node->attributes['id']) && $node->attributes['id'] === $m[1];
        }
        if (preg_match('/^\.([a-zA-Z0-9\-_]+)$/', $selector, $m)) {
            if (!isset($node->attributes['class'])) return false;
            $classes = preg_split('/\s+/', $node->attributes['class']);
            return in_array($m[1], $classes);
        }
        // Compound selector: div.foo#bar
        if (preg_match_all('/([a-zA-Z0-9\-_]+|\.[a-zA-Z0-9\-_]+|#[a-zA-Z0-9\-_]+)/', $selector, $m)) {
            foreach ($m[0] as $simple) {
                if (!self::matchSimpleSelector($simple, $node)) return false;
            }
            return true;
        }
        return false;
    }
}
