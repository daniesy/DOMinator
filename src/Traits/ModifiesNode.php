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
        $this->children->add($child);
    }

    /**
     * Sets the inner text of this node.
     */
    protected function setInnerText(string $text): void {
        if ($this->isText || $this->isComment || $this->isCdata) {
            $this->contents = $text;
            return;
        }
        // Remove all children and add a single text node if not empty
        $this->children = new \Daniesy\DOMinator\NodeList();
        if ($text !== '') {
            $textNode = new \Daniesy\DOMinator\Nodes\TextNode('', [], true, $text);
            $this->appendChild($textNode);
        }
    }

    /**
     * Gets the inner text of this node.
     * For text, comment, or cdata nodes, returns their innerText directly.
     * For element nodes, recursively concatenates all descendant text.
     */
    protected function getInnerText(): string {
        // For text, comment, or cdata nodes, return their innerText directly
        if ($this->isText || $this->isComment || $this->isCdata) {
            return $this->contents;
        }
        // For element nodes, recursively concatenate all descendant text
        $text = '';
        foreach ($this->children as $child) {
            $text .= $child->getInnerText();
        }
        return $text;
    }

    /**
     * Recursively collects all text nodes (for modification).
     * @return Node[] Array of text nodes (isText === true)
     */
    public function getAllTextNodes(): array {
        return $this->collectNodesByType('isText');
    }

    /**
     * Returns all comment nodes (<!-- ... -->) in the subtree.
     * @return Node[]
     */
    public function getAllCommentNodes(): array {
        return $this->collectNodesByType('isComment');
    }
    
    /**
     * Generic method to collect nodes by their type property
     * @param string $typeProperty Property to check (isText, isComment, etc.)
     * @return Node[] Array of matching nodes
     */
    private function collectNodesByType(string $typeProperty): array {
        $result = [];
        $this->traverseCollectNodes($this, $result, $typeProperty);
        return $result;
    }
    
    /**
     * Generic traversal method for collecting nodes by type
     */
    private function traverseCollectNodes(Node $node, array &$result, string $typeProperty): void {
        if ($node && isset($node->$typeProperty) && $node->$typeProperty === true) {
            $result[] = $node;
        }
        if ($node && isset($node->children)) {
            foreach ($node->children as $child) {
                if ($child) {
                    $this->traverseCollectNodes($child, $result, $typeProperty);
                }
            }
        }
    }

    /**
     * Removes this node from its parent.
     */
    public function remove(): void {
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
     * Internal helper to insert nodes relative to the current node
     * @param Node $newNode The node to insert
     * @param bool $before True to insert before this node, false to insert after
     */
    private function insertRelative(Node $newNode, bool $before): void {
        if (!$this->parent) return;
        
        $siblings = $this->parent->children;
        $newNodes = [];
        $inserted = false;
        
        foreach ($siblings as $sibling) {
            if ($before && $sibling === $this && !$inserted) {
                $newNode->parent = $this->parent;
                $newNodes[] = $newNode;
                $inserted = true;
            }
            
            $newNodes[] = $sibling;
            
            if (!$before && $sibling === $this) {
                $newNode->parent = $this->parent;
                $newNodes[] = $newNode;
            }
        }
        
        $this->parent->children = new NodeList($newNodes);
    }

    /**
     * Inserts a node before this node in the parent's children list.
     * @param Node $newNode
     */
    public function insertBefore(Node $newNode): void {
        $this->insertRelative($newNode, true);
    }

    /**
     * Inserts a node after this node in the parent's children list.
     * @param Node $newNode
     */
    public function insertAfter(Node $newNode): void {
        $this->insertRelative($newNode, false);
    }

}
