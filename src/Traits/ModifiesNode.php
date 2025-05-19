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
     * If this node is a comment and $removeContent is true, also removes the comment's innerText from the parent text nodes.
     * @param bool $removeContent If true, also remove the comment's content from adjacent text nodes.
     */
    public function remove(bool $removeContent = false): void {
        if ($this->parent) {
            // Remove comment content from adjacent text nodes if requested
            if ($removeContent && isset($this->isComment) && $this->isComment) {
                $siblings = $this->parent->children;
                // Convert NodeList to array for index lookup
                if ($siblings instanceof NodeList) {
                    $siblingsArr = iterator_to_array($siblings);
                } else {
                    $siblingsArr = $siblings;
                }
                $idx = array_search($this, $siblingsArr, true);
                if ($idx !== false) {
                    // Remove comment content from previous text node
                    if ($idx > 0 && isset($siblingsArr[$idx-1]) && isset($siblingsArr[$idx-1]->isText) && $siblingsArr[$idx-1]->isText) {
                        $siblingsArr[$idx-1]->innerText = str_replace($this->innerText, '', $siblingsArr[$idx-1]->innerText);
                    }
                    // Remove comment content from next text node
                    if (isset($siblingsArr[$idx+1]) && isset($siblingsArr[$idx+1]->isText) && $siblingsArr[$idx+1]->isText) {
                        $siblingsArr[$idx+1]->innerText = str_replace($this->innerText, '', $siblingsArr[$idx+1]->innerText);
                    }
                }
            }
            // Remove this node from parent's children
            if (is_object($this->parent->children) && method_exists($this->parent->children, 'remove')) {
                $this->parent->children->remove($this);
            } elseif (is_array($this->parent->children)) {
                $this->parent->children = array_filter(
                    $this->parent->children,
                    fn($c) => $c !== $this
                );
            }
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

    /**
     * Recursively collects all text nodes (for modification).
     * @return Node[] Array of text nodes (isText === true)
     */
    public function getAllTextNodes(): array {
        $result = [];
        $this->collectTextNodes($this, $result);
        return $result;
    }

    private function collectTextNodes(Node $node, array &$result): void {
        if ($node->isText) {
            $result[] = $node;
        }
        if (isset($node->children)) {
            foreach ($node->children as $child) {
                $this->collectTextNodes($child, $result);
            }
        }
    }

    /**
     * Returns all comment nodes (<!-- ... -->) in the subtree.
     * @return Node[]
     */
    public function getAllCommentNodes(): array {
        $result = [];
        $this->collectCommentNodes($this, $result);
        return $result;
    }

    private function collectCommentNodes(Node $node, array &$result): void {
        if (isset($node->isComment) && $node->isComment) {
            $result[] = $node;
        }
        if (isset($node->children)) {
            foreach ($node->children as $child) {
                $this->collectCommentNodes($child, $result);
            }
        }
    }
}
