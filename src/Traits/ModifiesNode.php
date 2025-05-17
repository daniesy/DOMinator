<?php

namespace Daniesy\DOMinator\Traits;

use Daniesy\DOMinator\Node;
use Daniesy\DOMinator\NodeList;

trait ModifiesNode {
    /**
     * Appends a child node to this node.
     */
    public function appendChild(Node $child): void {
        $child->parent = $this;
        $this->children[] = $child;
    }

    /**
     * Removes this node from its parent.
     */
    public function remove(): void {
        if ($this->parent) {
            $this->parent->children->remove($this);
            $this->parent = null;
        }
    }

    /**
     * Sets the inner text of this node.
     */
    public function setInnerText(string $text): void {
        if ($this->isText) {
            $this->innerText = $text;
        } else {
            $this->children = new NodeList;
            if ($text !== '') {
                $textNode = new Node('', [], true, $text);
                $this->appendChild($textNode);
            }
        }
    }
}
