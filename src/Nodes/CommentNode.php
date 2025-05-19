<?php

namespace Daniesy\DOMinator\Nodes;

use Daniesy\DOMinator\Nodes\Node;
use Daniesy\DOMinator\NodeList;

class CommentNode extends Node {
    public bool $isComment = true;

    public function __construct(
        string $tag = '',
        array $attributes = [],
        bool $isText = false,
        string $innerText = '',
        bool $isComment = true,
        bool $isCdata = false,
        string $namespace = ''
    ) {
        parent::__construct($tag, $attributes, $isText, $innerText, $isComment, $isCdata, $namespace);
        $this->children = new NodeList();
    }

    public static function advancedRemove(CommentNode $node, bool $removeContent = false): void {
        if ($node->parent) {
            if ($removeContent) {
                $siblings = $node->parent->children;
                if ($siblings instanceof NodeList) {
                    $siblingsArr = iterator_to_array($siblings);
                } else {
                    $siblingsArr = $siblings;
                }
                $idx = array_search($node, $siblingsArr, true);
                if ($idx !== false) {
                    if ($idx > 0 && isset($siblingsArr[$idx-1]) && $siblingsArr[$idx-1] instanceof TextNode) {
                        $siblingsArr[$idx-1]->innerText = str_replace($node->innerText, '', $siblingsArr[$idx-1]->innerText);
                    }
                    if (isset($siblingsArr[$idx+1]) && $siblingsArr[$idx+1] instanceof TextNode) {
                        $siblingsArr[$idx+1]->innerText = str_replace($node->innerText, '', $siblingsArr[$idx+1]->innerText);
                    }
                }
            }
            if (is_object($node->parent->children) && method_exists($node->parent->children, 'remove')) {
                $node->parent->children->remove($node);
            } elseif (is_array($node->parent->children)) {
                $node->parent->children = array_filter(
                    $node->parent->children,
                    fn($c) => $c !== $node
                );
            }
            $node->parent = null;
        }
    }

    public function remove(bool $removeContent = false): void {
        if ($this->parent) {
            if ($removeContent) {
                $siblings = $this->parent->children;
                if ($siblings instanceof NodeList) {
                    $siblingsArr = iterator_to_array($siblings);
                } else {
                    $siblingsArr = $siblings;
                }
                $idx = array_search($this, $siblingsArr, true);
                if ($idx !== false) {
                    if ($idx > 0 && isset($siblingsArr[$idx-1]) && $siblingsArr[$idx-1] instanceof TextNode) {
                        $siblingsArr[$idx-1]->innerText = str_replace($this->innerText, '', $siblingsArr[$idx-1]->innerText);
                    }
                    if (isset($siblingsArr[$idx+1]) && $siblingsArr[$idx+1] instanceof TextNode) {
                        $siblingsArr[$idx+1]->innerText = str_replace($this->innerText, '', $siblingsArr[$idx+1]->innerText);
                    }
                }
            }
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
}
