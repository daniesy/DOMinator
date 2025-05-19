<?php 

namespace Daniesy\DOMinator\Nodes;

use Daniesy\DOMinator\Nodes\Node;

class TextNode extends Node {
    public bool $isText = true;

    public function toHtml(bool $minify = true, int $level = 0): string
    {
        // For pretty-printing, do not indent text nodes at all (no ltrim)
        $parentTag = $this->parent ? strtolower($this->parent->tag) : '';
        $preserve = in_array($parentTag, ['pre', 'textarea', 'script', 'style', 'title'], true);
        if ($minify || $preserve) {
            return $this->contents;
        }
        // Collapse all whitespace to a single space for pretty-print, unless parent is preserve
        $text = preg_replace('/[ \t\r\n]+/', ' ', $this->contents);
        return $text;
    }
}
