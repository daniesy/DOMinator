<?php

namespace Daniesy\DOMinator\Nodes;

use Daniesy\DOMinator\Nodes\Node;
use Daniesy\DOMinator\NodeList;

class CommentNode extends Node {
    public bool $isComment = true;

    /**
     * Create a new comment node
     * 
     * @param string $tag The tag name (typically empty for comments)
     * @param array $attributes An array of attributes (typically empty for comments)
     * @param bool $isText Whether the node is a text node (should be false for comments)
     * @param string $innerText The text content of the comment
     * @param bool $isComment Whether the node is a comment (should be true)
     * @param bool $isCdata Whether the node is a CDATA section (should be false for comments)
     * @param string $namespace The namespace (typically empty for comments)
     */
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
}
