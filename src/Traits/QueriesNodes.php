<?php

namespace Daniesy\DOMinator\Traits;

use Daniesy\DOMinator\HtmlNode;

trait QueriesNodes {
    /**
     * Returns all descendant elements matching the selector (CSS-like).
     */
    public function querySelectorAll(string $selector): array {
        $results = [];
        $this->traverseQuery($this, $selector, $results);
        return $results;
    }

    /**
     * Returns the first descendant element matching the selector, or null if none found.
     */
    public function querySelector(string $selector): ?self {
        $results = [];
        $this->traverseQuery($this, $selector, $results, true);
        return $results[0] ?? null;
    }

    /**
     * Returns all descendant elements with the given tag name (case-insensitive).
     */
    public function getElementsByTagName(string $tag): array {
        $results = [];
        $this->traverseTag($this, strtolower($tag), $results);
        return $results;
    }

    // --- Internal Query Helpers ---
    private function traverseQuery(HtmlNode $node, string $selector, array &$results, bool $firstOnly = false): void {
        if ($this->matchesQuery($node, $selector)) {
            $results[] = $node;
            if ($firstOnly) return;
        }
        foreach ($node->children as $child) {
            if ($firstOnly && $results) return;
            $this->traverseQuery($child, $selector, $results, $firstOnly);
        }
    }

    private function traverseTag(HtmlNode $node, string $tag, array &$results): void {
        if (!$node->isText && strtolower($node->tag) === $tag) {
            $results[] = $node;
        }
        foreach ($node->children as $child) {
            $this->traverseTag($child, $tag, $results);
        }
    }

    private function matchesQuery(HtmlNode $node, string $selector): bool {
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
            return isset($node->attributes[$m[1]]) && $node->attributes[$m[1]] === $m[2];
        }
        return false;
    }
}
