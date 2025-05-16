<?php
namespace Daniesy\DOMinator;
require_once __DIR__ . '/HtmlNode.php';

// Query HtmlNode tree using CSS selectors

class HtmlQuery {
    private $root;
    public function __construct($root) {
        $this->root = $root;
    }

    public function querySelectorAll($selector) {
        $results = [];
        $this->traverse($this->root, $selector, $results);
        return $results;
    }

    private function traverse($node, $selector, &$results) {
        if ($this->matches($node, $selector)) {
            $results[] = $node;
        }
        foreach ($node->children as $child) {
            $this->traverse($child, $selector, $results);
        }
    }

    private function matches($node, $selector) {
        if ($node->isText) return false;
        // tag
        if (preg_match('/^[a-zA-Z0-9\-]+$/', $selector)) {
            return $node->tag === $selector;
        }
        // .class
        if (preg_match('/^\.([a-zA-Z0-9\-_]+)/', $selector, $m)) {
            return isset($node->attributes['class']) && in_array($m[1], preg_split('/\s+/', $node->attributes['class']));
        }
        // #id
        if (preg_match('/^#([a-zA-Z0-9\-_]+)/', $selector, $m)) {
            return isset($node->attributes['id']) && $node->attributes['id'] === $m[1];
        }
        // [attr=value]
        if (preg_match('/^\[([a-zA-Z0-9\-:]+)=([a-zA-Z0-9\-_]*)\]/', $selector, $m)) {
            // Match if attribute exists and value matches (including empty string for boolean attributes)
            return isset($node->attributes[$m[1]]) && $node->attributes[$m[1]] === $m[2];
        }
        return false;
    }
}
