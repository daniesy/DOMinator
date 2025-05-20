<?php

namespace Daniesy\DOMinator\Nodes;

use Daniesy\DOMinator\Nodes\Node;
use Daniesy\DOMinator\Nodes\TextNode;

class StyleNode extends Node {
    public function __construct(array $attributes = [], string $innerText = '') {
        parent::__construct('style', $attributes);
        if ($innerText !== '') {
            $this->appendChild(new TextNode('', [], true, $innerText));
        }
    }
}
