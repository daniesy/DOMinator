<?php

namespace Daniesy\DOMinator;

use Countable;
use Iterator;
use IteratorAggregate;
use ArrayIterator;

class NodeList implements IteratorAggregate, Countable {
    /**
     * @var HtmlNode[] The array of nodes
     */
    private array $nodes;

    /**
     * @var int The number of nodes in the list
     */
    public readonly int $length;

    /**
     * NodeList constructor.
     *
     * @param HtmlNode[] $nodes Array of HtmlNode objects
     */
    public function __construct(array $nodes = []) {
        $this->nodes = $nodes;
        $this->length = count($nodes);
    }

    /**
     * Returns the node at the specified index, or null if the index is out of range.
     *
     * @param int $index The index of the node to return
     * @return HtmlNode|null The node at the specified index, or null if the index is out of range
     */
    public function item(int $index): ?HtmlNode {
        return $this->nodes[$index] ?? null;
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
}