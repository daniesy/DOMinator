<?php

namespace Daniesy\DOMinator\Traits;

use Daniesy\DOMinator\Node;

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
            $this->parent->children = array_filter(
                $this->parent->children,
                fn($c) => $c !== $this
            );
            $this->parent = null;
        }
    }

    /**
     * Sets or updates an attribute.
     */
    public function setAttribute(string $name, $value): void {
        $this->attributes[$name] = $value;
    }

    /**
     * Removes an attribute.
     */
    public function removeAttribute(string $name): void {
        unset($this->attributes[$name]);
    }

    /**
     * Sets the inner text of this node.
     */
    public function setInnerText(string $text): void {
        if ($this->isText) {
            $this->innerText = $text;
        } else {
            $this->children = [];
            if ($text !== '') {
                $textNode = new Node('', [], true, $text);
                $this->appendChild($textNode);
            }
        }
    }
}
