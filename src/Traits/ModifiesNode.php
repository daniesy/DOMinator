<?php

namespace Daniesy\DOMinator\Traits;

use Daniesy\DOMinator\Nodes\Node;
use Daniesy\DOMinator\Nodes\CommentNode;
use Daniesy\DOMinator\Nodes\TextNode;
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
        if ($this instanceof CommentNode) {
            // Call CommentNode's own remove, not parent::remove
            CommentNode::advancedRemove($this, $removeContent);
            return;
        }
        if ($this->parent) {
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
        // Always set innerText for text nodes
        if ($this->isText) {
            $this->innerText = $text;
            return;
        }
        $this->children = new NodeList;
        if ($text !== '') {
            $textNode = new TextNode('', [], true, $text);
            $this->appendChild($textNode);
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
