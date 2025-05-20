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
        $parentTag = $this->parent?->tag ?? '';
        
        return match (true) {
            $minify => $this->contents,
            in_array(strtolower($parentTag), ['pre', 'textarea', 'script', 'style', 'title'], true) => $this->contents,
            default => preg_replace('/[ \t\r\n]+/', ' ', $this->contents),
        };
    }
}
