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
}
