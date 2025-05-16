<?php
namespace Daniesy\DOMinator;

// Represents a node in the HTML tree (element or text)
class HtmlNode {
    public array $children = [];
    public ?HtmlNode $parent = null;
    public string $doctype = '';

    public function __construct(
        public string $tag = '',
        public array $attributes = [],
        public bool $isText = false,
        public string $innerText = '',
        public bool $isComment = false,
        public bool $isCdata = false,
        public string $namespace = ''
    ) {}

    public function appendChild($child) {
        $child->parent = $this;
        $this->children[] = $child;
    }

    public function remove() {
        if ($this->parent) {
            $this->parent->children = array_filter(
                $this->parent->children,
                fn($c) => $c !== $this
            );
            $this->parent = null;
        }
    }

    public function setAttribute($name, $value) {
        $this->attributes[$name] = $value;
    }

    public function removeAttribute($name) {
        unset($this->attributes[$name]);
    }

    public function setInnerText($text) {
        if ($this->isText) {
            $this->innerText = $text;
        } else {
            $this->children = [];
            if ($text !== '') {
                $textNode = new HtmlNode('', [], true, $text);
                $this->appendChild($textNode);
            }
        }
    }

    public function getInnerText() {
        if ($this->isText) {
            return $this->innerText;
        }
        $text = '';
        foreach ($this->children as $child) {
            $text .= $child->getInnerText();
        }
        return $text;
    }

    public function toHtml() {
        // Special handling: if this is the artificial root node, only export its children
        if ($this->tag === 'root') {
            $html = '';
            if (isset($this->doctype)) {
                $html .= $this->doctype;
            }
            foreach ($this->children as $child) {
                $html .= $child->toHtml();
            }
            return $html;
        }
        // If this is the <html> node and has a doctype, prepend it
        $html = '';
        if ($this->tag === 'html' && isset($this->doctype)) {
            $html .= $this->doctype;
        }
        if ($this->isComment) {
            return '<!--' . $this->innerText . '-->';
        }
        if ($this->isCdata) {
            return '<![CDATA[' . $this->innerText . ']]>';
        }
        if ($this->isText) {
            return htmlspecialchars_decode(htmlspecialchars($this->innerText));
        }
        $attr = '';
        foreach ($this->attributes as $k => $v) {
            $attr .= ' ' . $k . '="' . htmlspecialchars_decode(htmlspecialchars($v)) . '"';
        }
        $html .= "<{$this->tag}{$attr}>";
        if ($this->children) {
            foreach ($this->children as $child) {
                $html .= $child->toHtml();
            }
        } else {
            $html .= htmlspecialchars($this->innerText);
        }
        $html .= "</{$this->tag}>";
        return $html;
    }

    // --- DOM-like Query Methods ---

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
