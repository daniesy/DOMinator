<?php
namespace Daniesy\DOMinator;

// Represents a node in the HTML tree (element or text)
class Node {
    public $tag;
    public $attributes = [];
    public $children = [];
    public $parent = null;
    public $innerText = '';
    public $isText = false;
    public $doctype = '';

    public function __construct($tag = '', $attributes = [], $isText = false, $innerText = '') {
        $this->tag = $tag;
        $this->attributes = $attributes;
        $this->isText = $isText;
        $this->innerText = $innerText;
    }

    public function appendChild($child) {
        $child->parent = $this;
        $this->children[] = $child;
    }

    public function remove() {
        if ($this->parent) {
            $this->parent->children = array_filter(
                $this->parent->children,
                fn($c) => $c !== $this
            );
            $this->parent = null;
        }
    }

    public function setAttribute($name, $value) {
        $this->attributes[$name] = $value;
    }

    public function removeAttribute($name) {
        unset($this->attributes[$name]);
    }

    public function setInnerText($text) {
        if ($this->isText) {
            $this->innerText = $text;
        } else {
            $this->children = [];
            if ($text !== '') {
                $textNode = new Node('', [], true, $text);
                $this->appendChild($textNode);
            }
        }
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
        if ($this->isText) {
            return htmlspecialchars($this->innerText);
        }
        $attr = '';
        foreach ($this->attributes as $k => $v) {
            $attr .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
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
