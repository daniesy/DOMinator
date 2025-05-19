<?php
namespace Daniesy\DOMinator\Nodes;

use Daniesy\DOMinator\Traits\HandlesAttributes;
use Daniesy\DOMinator\Traits\QueriesNodes;
use Daniesy\DOMinator\Traits\ModifiesNode;
use Daniesy\DOMinator\NodeList;

// Represents a node in the HTML tree (element or text)
/**
 * @property string $innerText
 */

class Node {
    use QueriesNodes, ModifiesNode, HandlesAttributes;

    public NodeList $children;
    public ?Node $parent = null;
    public string $doctype = '';

    public function __construct(
        public string $tag = '',
        public array $attributes = [],
        public bool $isText = false,
        protected string $contents = '',
        public bool $isComment = false,
        public bool $isCdata = false,
        public string $namespace = ''
    ) {
        $this->children = new NodeList();
    }

    public function __get($name) {
        if ($name === 'innerText') {
            return $this->getInnerText(); // Use trait's method
        }
        return $this->$name ?? null;
    }
    
    public function __set($name, $value) {
        if ($name === 'innerText') {
            $this->setInnerText($value); // Use trait's method
            return;
        }
        $this->$name = $value;
    }
    
    public function toHtml(): string
    {
        // Special handling: if this is the artificial root node, only export its children
        if ($this->tag === 'root') {
            $html = '';
            if (isset($this->doctype)) {
                $html .= $this->doctype;
            }
            foreach ($this->children as $child) {
                $html .= $child->toHtml();
            }
            return $html;
        }
        // If this is the <html> node and has a doctype, prepend it
        $html = '';
        if ($this->tag === 'html' && isset($this->doctype)) {
            $html .= $this->doctype;
        }
        if ($this->isComment) {
            return '<!--' . $this->innerText . '-->';
        }
        if ($this->isCdata) {
            return '<![CDATA[' . $this->innerText . ']]>';
        }
        if ($this->isText) {
            return htmlspecialchars_decode(htmlspecialchars($this->innerText));
        }
        $attr = '';
        foreach ($this->attributes as $k => $v) {
            $attr .= ' ' . $k . '="' . htmlspecialchars_decode(htmlspecialchars($v)) . '"';
        }
        $html .= "<{$this->tag}{$attr}>";
        if ($this->children->length) {
            foreach ($this->children as $child) {
                $html .= $child->toHtml();
            }
        } else {
            $html .= htmlspecialchars($this->innerText);
        }
        $html .= "</{$this->tag}>";
        return $html;
    }
}