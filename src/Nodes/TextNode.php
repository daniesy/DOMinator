<?php 

namespace Daniesy\DOMinator\Nodes;

use Daniesy\DOMinator\Nodes\Node;

class TextNode extends Node {
    public bool $isText = true;

    /**
     * Convert the text node to HTML
     * 
     * @param bool $minify Whether to minify the output
     * @param int $level The current indentation level
     * @return string The HTML representation of this text node
     */
    public function toHtml(bool $minify = true, int $level = 0): string
    {
        // Check if parent tag is one of those where whitespace should be preserved
        $parentTag = $this->parent ? strtolower($this->parent->tag) : '';
        $preserve = in_array($parentTag, ['pre', 'textarea', 'script', 'style', 'title'], true);
        
        // If minifying or parent requires preserving whitespace, return content as-is
        if ($minify || $preserve) {
            return $this->contents;
        }
        
        // For pretty-print, collapse whitespace to a single space
        $text = preg_replace('/[ \t\r\n]+/', ' ', $this->contents);
        return $text;
    }
}
