<?php
use PHPUnit\Framework\TestCase;
use Daniesy\HtmlParser\HtmlParser;
use Daniesy\HtmlParser\HtmlQuery;
use Daniesy\HtmlParser\HtmlNode;

class HtmlParserTest extends TestCase {
    public function testInvalidHtml() {
        $html = '<p><p>Test</p></p>';
        $root = HtmlParser::parse($html);
        $result = $root->toHtml();
        $this->assertEquals($html, $result);
    }

    public function testHtmlDocument() {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body><div id="main"><p>Hello <b>World</b></p></div></body></html>';
        $root = HtmlParser::parse($html);
        $this->assertEquals('html', $root->tag);
        $this->assertCount(2, $root->children); // <head> and <body>
        $this->assertEquals('head', $root->children[0]->tag);
        $this->assertEquals('body', $root->children[1]->tag);
        $this->assertEquals($html, $root->toHtml());
    }

    public function testParseAndExport() {
        $html = '<div id="main"><p>Hello <b>World</b></p></div>';
        $root = HtmlParser::parse($html);
        $exported = '';
        foreach ($root->children as $child) {
            $exported .= $child->toHtml();
        }
        $this->assertStringContainsString('<div id="main">', $exported);
        $this->assertStringContainsString('<p>Hello <b>World</b></p>', $exported);
    }

    public function testQuerySelectorAllByClass() {
        $html = '<div><span class="foo">A</span><span class="bar">B</span></div>';
        $root = HtmlParser::parse($html);
        $query = new HtmlQuery($root);
        $nodes = $query->querySelectorAll('.foo');
        $this->assertCount(1, $nodes);
        $this->assertEquals('span', $nodes[0]->tag);
        $this->assertEquals('A', $nodes[0]->getInnerText());
    }

    public function testSetInnerText() {
        $html = '<div><span class="foo">A</span></div>';
        $root = HtmlParser::parse($html);
        $query = new HtmlQuery($root);
        $nodes = $query->querySelectorAll('.foo');
        $nodes[0]->setInnerText('B');
        $this->assertEquals('B', $nodes[0]->getInnerText());
    }

    public function testSetAndRemoveAttribute() {
        $html = '<div><span class="foo">A</span></div>';
        $root = HtmlParser::parse($html);
        $query = new HtmlQuery($root);
        $nodes = $query->querySelectorAll('span');
        $nodes[0]->setAttribute('id', 'test');
        $this->assertEquals('test', $nodes[0]->attributes['id']);
        $nodes[0]->removeAttribute('id');
        $this->assertArrayNotHasKey('id', $nodes[0]->attributes);
    }

    public function testRemoveNode() {
        $html = '<div><span class="foo">A</span><span>B</span></div>';
        $root = HtmlParser::parse($html);
        $query = new HtmlQuery($root);
        $nodes = $query->querySelectorAll('.foo');
        $nodes[0]->remove();
        $spans = $query->querySelectorAll('span');
        $this->assertCount(1, $spans);
        $this->assertEquals('B', $spans[0]->getInnerText());
    }
}
