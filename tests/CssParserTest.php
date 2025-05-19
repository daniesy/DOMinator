<?php

use PHPUnit\Framework\TestCase;
use Daniesy\DOMinator\CssParser;
use Daniesy\DOMinator\Nodes\Node;

class CssParserTest extends TestCase {
    public function testParseSimpleRules() {
        $css = 'div { color: red; } .foo { font-weight: bold; } #bar { text-align: center; }';
        $rules = CssParser::parse($css);
        $this->assertCount(3, $rules);
        $this->assertEquals('div', $rules[0]['selector']);
        $this->assertEquals(['color' => 'red'], $rules[0]['props']);
        $this->assertEquals('.foo', $rules[1]['selector']);
        $this->assertEquals(['font-weight' => 'bold'], $rules[1]['props']);
        $this->assertEquals('#bar', $rules[2]['selector']);
        $this->assertEquals(['text-align' => 'center'], $rules[2]['props']);
    }

    public function testParseAtRules() {
        $css = '@media (max-width:600px) { body { background: #fff; } } @font-face { font-family: test; src: url(test.woff); }';
        $rules = CssParser::parse($css);
        $this->assertCount(2, $rules);
        $this->assertEquals('at', $rules[0]['type']);
        $this->assertStringContainsString('@media', $rules[0]['raw']);
        $this->assertEquals('at', $rules[1]['type']);
        $this->assertStringContainsString('@font-face', $rules[1]['raw']);
    }

    public function testParseCompoundSelector() {
        $css = 'div.foo#bar { color: green; }';
        $rules = CssParser::parse($css);
        $this->assertCount(1, $rules);
        $this->assertEquals('div.foo#bar', $rules[0]['selector']);
        $this->assertEquals(['color' => 'green'], $rules[0]['props']);
    }

    public function testParseMultipleProperties() {
        $css = 'div { color: red; font-weight: bold; background: #fff; }';
        $rules = CssParser::parse($css);
        $this->assertEquals(['color' => 'red', 'font-weight' => 'bold', 'background' => '#fff'], $rules[0]['props']);
    }

    public function testMatchesTagClassId() {
        $node = new Node('div', ['class' => 'foo bar', 'id' => 'bar']);
        $this->assertTrue(CssParser::matches('div', $node));
        $this->assertTrue(CssParser::matches('.foo', $node));
        $this->assertTrue(CssParser::matches('.bar', $node));
        $this->assertTrue(CssParser::matches('#bar', $node));
        $this->assertFalse(CssParser::matches('.baz', $node));
        $this->assertFalse(CssParser::matches('span', $node));
        $this->assertFalse(CssParser::matches('#baz', $node));
    }

    public function testMatchesCompoundSelector() {
        $node = new Node('div', ['class' => 'foo bar', 'id' => 'bar']);
        $this->assertTrue(CssParser::matches('div.foo#bar', $node));
        $this->assertFalse(CssParser::matches('span.foo#bar', $node));
        $this->assertFalse(CssParser::matches('div.foo#baz', $node));
    }

    public function testMatchesDescendantCombinator() {
        $parent = new Node('section');
        $child = new Node('div', ['class' => 'foo']);
        $child->parent = $parent;
        $this->assertTrue(CssParser::matches('section div', $child));
        $this->assertTrue(CssParser::matches('section .foo', $child));
        $this->assertFalse(CssParser::matches('article div', $child));
    }

    public function testMatchesUniversalSelector() {
        $node = new Node('div', ['class' => 'foo']);
        $this->assertTrue(CssParser::matches('*', $node));
    }
}
