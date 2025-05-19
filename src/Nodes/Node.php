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
    public string $xmlDeclaration = '';

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
    
    public function toHtml(bool $minify = true, int $level = 0): string
    {
        $indent = ($minify ? '' : str_repeat("    ", $level));
        $newline = $minify ? '' : "\n";
        // Special handling: if this is the artificial root node, only export its children
        if ($this->tag === 'root') {
            $html = '';
            if (isset($this->xmlDeclaration) && $this->xmlDeclaration) {
                $html .= $this->xmlDeclaration . $newline;
            }
            if (isset($this->doctype) && $this->doctype) {
                $html .= $this->doctype . $newline;
            }
            foreach ($this->children as $child) {
                $html .= $child->toHtml($minify, $minify ? 0 : $level);
                if (!$minify) $html .= $newline;
            }
            return $minify ? $html : rtrim($html, "\n");
        }
        // If this is the <html> node and has a doctype or xmlDeclaration, prepend them
        $html = '';
        if (isset($this->xmlDeclaration) && $this->xmlDeclaration) {
            $html .= $this->xmlDeclaration . $newline;
        }
        if ($this->tag === 'html' && isset($this->doctype) && $this->doctype) {
            $html .= $this->doctype . $newline;
        }
        if ($this->isComment) {
            return $indent . '<!--' . $this->innerText . '-->';
        }
        if ($this->isCdata) {
            return $indent . '<![CDATA[' . $this->innerText . ']]>';
        }
        if ($this->isText) {
            return $indent . htmlspecialchars_decode(htmlspecialchars($this->innerText));
        }
        $attr = '';
        foreach ($this->attributes as $k => $v) {
            $attr .= ' ' . $k . '="' . htmlspecialchars_decode(htmlspecialchars($v)) . '"';
        }
        $html .= $indent . "<{$this->tag}{$attr}>";
        if ($this->children->length) {
            if (!$minify) $html .= $newline;
            foreach ($this->children as $child) {
                $html .= $child->toHtml($minify, $level + 1);
                if (!$minify) $html .= $newline;
            }
            $html .= $minify ? '' : $indent;
        } else {
            $html .= htmlspecialchars($this->innerText);
        }
        $html .= "</{$this->tag}>";
        return $html;
    }
}