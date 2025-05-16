<?php
use PHPUnit\Framework\TestCase;
use Daniesy\DOMinator\DOMinator;
use Daniesy\DOMinator\Query;

class QueryTest extends TestCase {
    public function testQuerySelectorAllTag() {
        $html = '<div><span>A</span><span>B</span><p>C</p></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $spans = $query->querySelectorAll('span');
        $this->assertCount(2, $spans);
        $this->assertEquals('A', $spans[0]->getInnerText());
        $this->assertEquals('B', $spans[1]->getInnerText());
    }

    public function testQuerySelectorAllClass() {
        $html = '<div><span class="foo bar">A</span><span class="bar">B</span><span class="foo">C</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $foo = $query->querySelectorAll('.foo');
        $this->assertCount(2, $foo);
        $this->assertEquals('A', $foo[0]->getInnerText());
        $this->assertEquals('C', $foo[1]->getInnerText());
        $bar = $query->querySelectorAll('.bar');
        $this->assertCount(2, $bar);
    }

    public function testQuerySelectorAllId() {
        $html = '<div><span id="x">A</span><span id="y">B</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $x = $query->querySelectorAll('#x');
        $this->assertCount(1, $x);
        $this->assertEquals('A', $x[0]->getInnerText());
        $y = $query->querySelectorAll('#y');
        $this->assertCount(1, $y);
        $this->assertEquals('B', $y[0]->getInnerText());
    }

    public function testQuerySelectorAllAttribute() {
        $html = '<div><span data-x="1">A</span><span data-x="2">B</span><span data-x="1">C</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $x1 = $query->querySelectorAll('[data-x=1]');
        $this->assertCount(2, $x1);
        $this->assertEquals('A', $x1[0]->getInnerText());
        $this->assertEquals('C', $x1[1]->getInnerText());
        $x2 = $query->querySelectorAll('[data-x=2]');
        $this->assertCount(1, $x2);
        $this->assertEquals('B', $x2[0]->getInnerText());
    }

    public function testQuerySelectorAllNoMatch() {
        $html = '<div><span>A</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $none = $query->querySelectorAll('.notfound');
        $this->assertCount(0, $none);
    }

    public function testQuerySelectorAllNested() {
        $html = '<div><section><span class="foo">A</span></section><span class="foo">B</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $foo = $query->querySelectorAll('.foo');
        $this->assertCount(2, $foo);
        $this->assertEquals('A', $foo[0]->getInnerText());
        $this->assertEquals('B', $foo[1]->getInnerText());
    }

    public function testQuerySelectorAllMultipleClasses() {
        $html = '<div><span class="foo bar">A</span><span class="foo">B</span><span class="bar">C</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $foo = $query->querySelectorAll('.foo');
        $bar = $query->querySelectorAll('.bar');
        $this->assertCount(2, $foo);
        $this->assertCount(2, $bar);
        $this->assertEquals('A', $foo[0]->getInnerText());
        $this->assertEquals('B', $foo[1]->getInnerText());
        $this->assertEquals('A', $bar[0]->getInnerText());
        $this->assertEquals('C', $bar[1]->getInnerText());
    }

    public function testQuerySelectorAllNestedTags() {
        $html = '<div><section><span>A</span></section><span>B</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $spans = $query->querySelectorAll('span');
        $this->assertCount(2, $spans);
        $this->assertEquals('A', $spans[0]->getInnerText());
        $this->assertEquals('B', $spans[1]->getInnerText());
    }

    public function testQuerySelectorAllAttributeNoValue() {
        $html = '<div><input type="checkbox" checked><input type="checkbox"></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $checked = $query->querySelectorAll('[checked=]');
        $this->assertCount(1, $checked);
        $this->assertEquals('input', $checked[0]->tag);
    }

    public function testQuerySelectorAllWithHyphenAttribute() {
        $html = '<div><span data-x="1">A</span><span data-x="2">B</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $x1 = $query->querySelectorAll('[data-x=1]');
        $this->assertCount(1, $x1);
        $this->assertEquals('A', $x1[0]->getInnerText());
    }

    public function testQuerySelectorAllIdAndClass() {
        $html = '<div><span id="foo" class="bar">A</span><span id="bar">B</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $foo = $query->querySelectorAll('#foo');
        $bar = $query->querySelectorAll('.bar');
        $this->assertCount(1, $foo);
        $this->assertCount(1, $bar);
        $this->assertEquals('A', $foo[0]->getInnerText());
        $this->assertEquals('A', $bar[0]->getInnerText());
    }

    public function testQuerySelectorAllNoMatchComplex() {
        $html = '<div><span class="foo">A</span></div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $none = $query->querySelectorAll('p.bar');
        $this->assertCount(0, $none);
    }

    public function testQuerySelectorAllRootLevel() {
        $html = '<span>A</span><span>B</span>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $spans = $query->querySelectorAll('span');
        $this->assertCount(2, $spans);
    }

    public function testQuerySelectorAllTextNodesIgnored() {
        $html = '<div>Text<span>A</span>Text2</div>';
        $root = DOMinator::parse($html);
        $query = new Query($root);
        $spans = $query->querySelectorAll('span');
        $this->assertCount(1, $spans);
        $this->assertEquals('A', $spans[0]->getInnerText());
    }
}
