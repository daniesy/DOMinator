<?php

namespace Daniesy\DOMinator\Traits;

use Daniesy\DOMinator\Nodes\Node;
use Daniesy\DOMinator\NodeList;

trait QueriesNodes {
    /**
     * Returns all descendant elements matching the selector (CSS-like).
     *
     * @return NodeList A NodeList containing all matching elements
     */
    public function querySelectorAll(string $selector): NodeList {
        // Support comma-separated selectors (OR logic) in document order
        if (strpos($selector, ',') !== false) {
            $selectors = array_map('trim', explode(',', $selector));
            $results = [];
            $this->traverseQueryMulti($this, $selectors, $results);
            return new NodeList($results);
        }
        $results = [];
        $this->traverseQuery($this, $selector, $results);
        return new NodeList($results);
    }

    /**
     * Returns the first descendant element matching the selector, or null if none found.
     */
    public function querySelector(string $selector): ?self {
        // Support comma-separated selectors (OR logic) in document order
        if (strpos($selector, ',') !== false) {
            $selectors = array_map('trim', explode(',', $selector));
            $results = [];
            $this->traverseQueryMulti($this, $selectors, $results, true);
            return $results[0] ?? null;
        }
        $results = [];
        $this->traverseQuery($this, $selector, $results, true);
        return $results[0] ?? null;
    }

    /**
     * Returns all descendant elements with the given tag name (case-insensitive).
     *
     * @return NodeList A NodeList containing all matching elements
     */
    public function getElementsByTagName(string $tag): NodeList {
        $results = [];
        $this->traverseTag($this, strtolower($tag), $results);
        return new NodeList($results);
    }

    // --- Internal Query Helpers ---
    private function traverseQuery(Node $node, string $selector, array &$results, bool $firstOnly = false): void {
        if ($this->matchesQuery($node, $selector)) {
            $results[] = $node;
            if ($firstOnly) return;
        }
        foreach ($node->children as $child) {
            if ($firstOnly && $results) return;
            $this->traverseQuery($child, $selector, $results, $firstOnly);
        }
    }

    private function traverseQueryMulti(Node $node, array $selectors, array &$results, bool $firstOnly = false): void {
        foreach ($selectors as $sel) {
            if ($this->matchesQuery($node, $sel)) {
                if (!in_array($node, $results, true)) {
                    $results[] = $node;
                    if ($firstOnly) return;
                }
            }
        }
        foreach ($node->children as $child) {
            if ($firstOnly && $results) return;
            $this->traverseQueryMulti($child, $selectors, $results, $firstOnly);
        }
    }

    private function traverseTag(Node $node, string $tag, array &$results): void {
        if (!$node->isText && strtolower($node->tag) === $tag) {
            $results[] = $node;
        }
        foreach ($node->children as $child) {
            $this->traverseTag($child, $tag, $results);
        }
    }

    private function matchesQuery(Node $node, string $selector): bool {
        if ($node->isText) return false;
        // tag with attribute selector, e.g. link[rel="icon"]
        if (preg_match('/^([a-zA-Z0-9\-]+)\[(.+)\]$/', $selector, $m)) {
            $tag = $m[1];
            $attrSelector = '[' . $m[2] . ']';
            if ($node->tag !== $tag) return false;
            // Recursively call matchesQuery for the attribute selector part only
            return $this->matchesQuery($node, $attrSelector);
        }
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
        // [attr=value] exact match
        if (preg_match('/^\[([a-zA-Z0-9\-:]+)=["\'`]?([^"]*)["\'`]?\]$/', $selector, $m)) {
            return isset($node->attributes[$m[1]]) && $node->attributes[$m[1]] === $m[2];
        }
        // [attr~=value] space-separated word match
        if (preg_match('/^\[([a-zA-Z0-9\-:]+)~=["\'`]?([^"]*)["\'`]?\]$/', $selector, $m)) {
            return isset($node->attributes[$m[1]]) && in_array($m[2], preg_split('/\s+/', $node->attributes[$m[1]]));
        }
        // [attr*=value] substring match
        if (preg_match('/^\[([a-zA-Z0-9\-:]+)\*=["\'`]?([^"]*)["\'`]?\]$/', $selector, $m)) {
            return isset($node->attributes[$m[1]]) && strpos($node->attributes[$m[1]], $m[2]) !== false;
        }
        // [attr] attribute presence
        if (preg_match('/^\[([a-zA-Z0-9\-:]+)\]$/', $selector, $m)) {
            return isset($node->attributes[$m[1]]);
        }
        return false;
    }
}
