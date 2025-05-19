<?php
namespace Daniesy\DOMinator\Nodes;

use Daniesy\DOMinator\Traits\HandlesAttributes;
use Daniesy\DOMinator\Traits\QueriesNodes;
use Daniesy\DOMinator\Traits\ModifiesNode;
use Daniesy\DOMinator\NodeList;
use Daniesy\DOMinator\CssParser;

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
                // Skip whitespace-only text nodes when pretty printing
                if (!$minify && $child->isText && trim($child->innerText) === '') {
                    continue;
                }
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
            // For text nodes, don't add indentation in pretty print mode
            // If it's only whitespace and we're in pretty print mode, skip it
            $text = htmlspecialchars_decode(htmlspecialchars($this->innerText));
            if (!$minify && trim($text) === '') {
                return '';
            }
            return $text;
        }
        $attr = '';
        foreach ($this->attributes as $k => $v) {
            $attr .= ' ' . $k . '="' . htmlspecialchars_decode(htmlspecialchars($v)) . '"';
        }
        $html .= $indent . "<{$this->tag}{$attr}>";
        if ($this->children->length) {
            // Special handling for elements with only text content
            if ($this->children->length === 1 && $this->children->item(0)?->isText) {
                // For "title" tag in SVG, compress whitespace
                if ($this->tag === 'title' && $this->parent && $this->parent->tag === 'svg') {
                    $html .= trim(preg_replace('/\s+/', ' ', $this->children->item(0)->innerText));
                } else {
                    $html .= $this->children->item(0)->innerText;
                }
            } else {
                if (!$minify) $html .= $newline;
                foreach ($this->children as $child) {
                    // Skip whitespace-only text nodes when pretty printing
                    if (!$minify && $child->isText && trim($child->innerText) === '') {
                        continue;
                    }
                    $html .= $child->toHtml($minify, $level + 1);
                    if (!$minify) $html .= $newline;
                }
                $html .= $minify ? '' : $indent;
            }
        } else {
            $html .= htmlspecialchars($this->innerText);
        }
        $html .= "</{$this->tag}>";
        return $html;
    }

    public function toInlinedHtml(bool $minify = true): string
    {
        $styleNodes = [];
        $allCssRules = [];
        // Collect all <style> nodes and parse their CSS
        $this->collectStyleNodes($styleNodes);
        foreach ($styleNodes as $styleNode) {
            $css = '';
            foreach ($styleNode->children as $child) {
                $css .= $child->innerText;
            }
            $parsed = CssParser::parse($css);
            $allCssRules[] = [ 'node' => $styleNode, 'rules' => $parsed ];
        }
        // Map: selector => [rule, props, raw, styleNode]
        $selectorMap = [];
        foreach ($allCssRules as $block) {
            foreach ($block['rules'] as $rule) {
                if ($rule['type'] === 'rule') {
                    $selectorMap[] = [
                        'selector' => $rule['selector'],
                        'props' => $rule['props'],
                        'raw' => $rule['raw'],
                        'styleNode' => $block['node'],
                    ];
                }
            }
        }
        // Inline styles and track which rules were inlined
        $inlined = [];
        $this->applyAdvancedInlineStyles($selectorMap, $inlined);
        // Deep clone, removing only inlined rules from <style> tags
        $cloned = $this->deepCloneWithAdvancedStyleRemoval($allCssRules, $inlined);
        return $cloned->toHtml($minify);
    }

    private function collectStyleNodes(array &$styleNodes)
    {
        if ($this->tag === 'style') {
            $styleNodes[] = $this;
        }
        foreach ($this->children as $child) {
            $child->collectStyleNodes($styleNodes);
        }
    }

    // Applies all matching rules to this node and children, tracks inlined rules
    private function applyAdvancedInlineStyles(array $selectorMap, array &$inlined)
    {
        if ($this->tag && !$this->isText && !$this->isComment && !$this->isCdata) {
            $matchedProps = [];
            foreach ($selectorMap as $entry) {
                if (CssParser::matches($entry['selector'], $this)) {
                    foreach ($entry['props'] as $k => $v) {
                        $matchedProps[$k] = $v;
                    }
                    $inlined[$entry['styleNode']->id ?? spl_object_id($entry['styleNode'])][$entry['raw']] = true;
                }
            }
            if ($matchedProps) {
                $this->attributes['style'] = '';
                foreach ($matchedProps as $k => $v) {
                    $this->attributes['style'] .= $k . ': ' . $v . ';';
                }
            }
        }
        foreach ($this->children as $child) {
            $child->applyAdvancedInlineStyles($selectorMap, $inlined);
        }
    }

    // Deep clone, but for <style> nodes, remove only rules that were actually inlined
    private function deepCloneWithAdvancedStyleRemoval(array $allCssRules, array $inlined): self|null
    {
        if ($this->tag === 'style') {
            $css = '';
            foreach ($this->children as $child) {
                $css .= $child->innerText;
            }
            $parsed = CssParser::parse($css);
            $kept = [];
            foreach ($parsed as $rule) {
                if ($rule['type'] === 'at') {
                    $kept[] = $rule['raw'];
                } elseif ($rule['type'] === 'rule') {
                    $sid = $this->id ?? spl_object_id($this);
                    if (!isset($inlined[$sid][$rule['raw']])) {
                        $kept[] = $rule['raw'];
                    }
                }
            }
            $newCss = implode(' ', $kept);
            if ($newCss === '') {
                return null;
            }
            $clone = new self('style', $this->attributes);
            if ($newCss !== '') {
                $clone->appendChild(new self('', [], true, $newCss));
            }
            return $clone;
        }
        $clone = new self(
            $this->tag,
            $this->attributes,
            $this->isText,
            $this->isText ? $this->innerText : '',
            $this->isComment,
            $this->isCdata,
            $this->namespace
        );
        $clone->doctype = $this->doctype ?? '';
        $clone->xmlDeclaration = $this->xmlDeclaration ?? '';
        foreach ($this->children as $child) {
            $clonedChild = $child->deepCloneWithAdvancedStyleRemoval($allCssRules, $inlined);
            if ($clonedChild !== null) {
                $clone->appendChild($clonedChild);
            }
        }
        return $clone;
    }
}