<?php

namespace Daniesy\DOMinator\Nodes;

class ScriptNode extends Node {
    public function __construct(array $attributes = [], string $innerText = '') {
        parent::__construct('script', $attributes);
        if ($innerText !== '') {
            $this->appendChild(new TextNode('', [], true, $innerText));
        }
    }
}
