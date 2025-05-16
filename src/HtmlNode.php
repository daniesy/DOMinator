<?php
namespace Daniesy\DOMinator;

use Daniesy\DOMinator\Traits\QueriesNodes;
use Daniesy\DOMinator\Traits\ModifiesNode;

// Represents a node in the HTML tree (element or text)
class HtmlNode {
    use QueriesNodes, ModifiesNode;

    public array $children = [];
    public ?HtmlNode $parent = null;
    public string $doctype = '';

    public function __construct(
        public string $tag = '',
        public array $attributes = [],
        public bool $isText = false,
        public string $innerText = '',
        public bool $isComment = false,
        public bool $isCdata = false,
        public string $namespace = ''
    ) {}

    public function appendChild($child) {
        $child->parent = $this;
        $this->children[] = $child;
    }

    public function getInnerText() {
        if ($this->isText) {
            return $this->innerText;
        }
        $text = '';
        foreach ($this->children as $child) {
            $text .= $child->getInnerText();
        }
        return $text;
    }

    public function toHtml() {
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
        if ($this->children) {
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
