<?php

namespace Daniesy\DOMinator;

use Countable;
use Iterator;
use IteratorAggregate;
use ArrayIterator;
use Daniesy\DOMinator\Nodes\Node;

class NodeList implements IteratorAggregate, Countable {
    /**
     * @var Node[] The array of nodes
     */
    private array $nodes;

    /**
     * @var int The number of nodes in the list
     */
    public int $length;

    /**
     * NodeList constructor.
     *
     * @param Node[] $nodes Array of Node objects
     */
    public function __construct(array $nodes = []) {
        $this->nodes = $nodes;
        $this->length = count($nodes);
    }

    /**
     * Add a node to the list
     *
     * @param Node $node
     * @return $this
     */
    public function add(Node $node): self
    {
        $this->nodes[] = $node;
        $this->length++;
        return $this;
    }

    /**
     * Remove a node from the list
     */
    public function remove(Node $node): self
    {
        $this->nodes = array_filter($this->nodes, fn($n) => $n !== $node);
        $this->length--;
        return $this;
    }

    /**
     * Returns the node at the specified index, or null if the index is out of range.
     *
     * @param int $index The index of the node to return
     * @return Node|null The node at the specified index, or null if the index is out of range
     */
    public function item(int $index): ?Node {
        return $this->nodes[$index] ?? null;
    }

    /**
     * Returns the first node in the list, or null if the list is empty.
     *
     * @return Node|null The first node in the list, or null if the list is empty
     */
    public function first(): ?Node {
        return $this->length > 0 ? $this->nodes[0] : null;
    }

    /**
     * Returns the last node in the list, or null if the list is empty.
     *
     * @return Node|null The last node in the list, or null if the list is empty
     */
    public function last(): ?Node {
        return $this->length > 0 ? $this->nodes[$this->length - 1] : null;
    }

    /**
     * Returns the number of nodes in the list.
     *
     * @return int The number of nodes in the list
     */
    public function count(): int {
        return $this->length;
    }

    /**
     * Returns an iterator for the nodes in the list.
     *
     * @return Iterator An iterator for the nodes in the list
     */
    public function getIterator(): Iterator {
        return new ArrayIterator($this->nodes);
    }
    
    /**
     * Returns the nodes as an array (PHP 8.4 feature)
     * 
     * @return array<int, Node> The nodes as an array
     */
    public function toArray(): array {
        return $this->nodes;
    }
}