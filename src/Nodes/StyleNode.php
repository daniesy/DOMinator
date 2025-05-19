<?php

namespace Daniesy\DOMinator\Nodes;

class StyleNode extends Node {
    public function __construct(array $attributes = [], string $innerText = '') {
        parent::__construct('style', $attributes);
        if ($innerText !== '') {
            $this->appendChild(new TextNode('', [], true, $innerText));
        }
    }

    public function getInnerText(): string {
        $text = '';
        foreach ($this->children as $child) {
            $text .= $child->getInnerText();
        }
        return $text;
    }
}
