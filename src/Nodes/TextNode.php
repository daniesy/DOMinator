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
        $parentTag = strtolower($this->parent?->tag ?? '');
        if (in_array($parentTag, ['script', 'style'], true)) {
            return $this->contents;
        }
        $text = str_replace('&#039;', '&apos;', htmlspecialchars($this->contents, ENT_QUOTES | ENT_HTML5));
        // Replace UTF-8 non-breaking space (\xC2\xA0) with &nbsp;
        $text = str_replace("\xC2\xA0", "&nbsp;", $text);
        return match (true) {
            $minify => $text,
            in_array($parentTag, ['pre', 'textarea', 'title'], true) => $this->contents,
            default => preg_replace('/[ \t\r\n]+/', ' ', $text),
        };
    }
}
