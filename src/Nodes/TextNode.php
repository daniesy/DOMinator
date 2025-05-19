<?php 

namespace Daniesy\DOMinator\Nodes;

use Daniesy\DOMinator\Nodes\Node;

class TextNode extends Node {
    public bool $isText = true;

    public function setInnerText(string $text): void {
        $this->innerText = $text;
    }
}
