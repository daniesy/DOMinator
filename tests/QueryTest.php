<?php
use PHPUnit\Framework\TestCase;
use Daniesy\DOMinator\DOMinator;
use Daniesy\DOMinator\HtmlQuery;

class QueryTest extends TestCase {
    public function testQuerySelectorAllTag() {
        $html = '<div><span>A</span><span>B</span><p>C</p></div>';
        $root = DOMinator::read($html);
        $spans = $root->querySelectorAll('span');
        $this->assertCount(2, $spans);
        $this->assertEquals('A', $spans->item(0)->innerText);
        $this->assertEquals('B', $spans->item(1)->innerText);
    }

    public function testQuerySelectorAllClass() {
        $html = '<div><span class="foo bar">A</span><span class="bar">B</span><span class="foo">C</span></div>';
        $root = DOMinator::read($html);
        $foo = $root->querySelectorAll('.foo');
        $this->assertCount(2, $foo);
        $this->assertEquals('A', $foo->item(0)->innerText);
        $this->assertEquals('C', $foo->item(1)->innerText);
        $bar = $root->querySelectorAll('.bar');
        $this->assertCount(2, $bar);
    }

    public function testQuerySelectorAllId() {
        $html = '<div><span id="x">A</span><span id="y">B</span></div>';
        $root = DOMinator::read($html);
        $x = $root->querySelectorAll('#x');
        $this->assertCount(1, $x);
        $this->assertEquals('A', $x->item(0)->innerText);
        $y = $root->querySelectorAll('#y');
        $this->assertCount(1, $y);
        $this->assertEquals('B', $y->item(0)->innerText);
    }

    public function testQuerySelectorAllAttribute() {
        $html = '<div><span data-x="1">A</span><span data-x="2">B</span><span data-x="1">C</span></div>';
        $root = DOMinator::read($html);
        $x1 = $root->querySelectorAll('[data-x=1]');
        $this->assertCount(2, $x1);
        $this->assertEquals('A', $x1->item(0)->innerText);
        $this->assertEquals('C', $x1->item(1)->innerText);
        $x2 = $root->querySelectorAll('[data-x=2]');
        $this->assertCount(1, $x2);
        $this->assertEquals('B', $x2->item(0)->innerText);
    }

    public function testQuerySelectorAllNoMatch() {
        $html = '<div><span>A</span></div>';
        $root = DOMinator::read($html);
        $none = $root->querySelectorAll('.notfound');
        $this->assertCount(0, $none);
    }

    public function testQuerySelectorAllNested() {
        $html = '<div><section><span class="foo">A</span></section><span class="foo">B</span></div>';
        $root = DOMinator::read($html);
        $foo = $root->querySelectorAll('.foo');
        $this->assertCount(2, $foo);
        $this->assertEquals('A', $foo->item(0)->innerText);
        $this->assertEquals('B', $foo->item(1)->innerText);
    }

    public function testQuerySelectorAllMultipleClasses() {
        $html = '<div><span class="foo bar">A</span><span class="foo">B</span><span class="bar">C</span></div>';
        $root = DOMinator::read($html);
        $foo = $root->querySelectorAll('.foo');
        $bar = $root->querySelectorAll('.bar');
        $this->assertCount(2, $foo);
        $this->assertCount(2, $bar);
        $this->assertEquals('A', $foo->item(0)->innerText);
        $this->assertEquals('B', $foo->item(1)->innerText);
        $this->assertEquals('A', $bar->item(0)->innerText);
        $this->assertEquals('C', $bar->item(1)->innerText);
    }

    public function testQuerySelectorAllNestedTags() {
        $html = '<div><section><span>A</span></section><span>B</span></div>';
        $root = DOMinator::read($html);
        $spans = $root->querySelectorAll('span');
        $this->assertCount(2, $spans);
        $this->assertEquals('A', $spans->item(0)->innerText);
        $this->assertEquals('B', $spans->item(1)->innerText);
    }

    public function testQuerySelectorAllAttributeNoValue() {
        $html = '<div><input type="checkbox" checked><input type="checkbox"></div>';
        $root = DOMinator::read($html);
        $checked = $root->querySelectorAll('[checked=]');
        $this->assertCount(1, $checked);
        $this->assertEquals('input', $checked->item(0)->tag);
    }

    public function testQuerySelectorAllWithHyphenAttribute() {
        $html = '<div><span data-x="1">A</span><span data-x="2">B</span></div>';
        $root = DOMinator::read($html);
        $x1 = $root->querySelectorAll('[data-x=1]');
        $this->assertCount(1, $x1);
        $this->assertEquals('A', $x1->item(0)->innerText);
    }

    public function testQuerySelectorAllIdAndClass() {
        $html = '<div><span id="foo" class="bar">A</span><span id="bar">B</span></div>';
        $root = DOMinator::read($html);
        $foo = $root->querySelectorAll('#foo');
        $bar = $root->querySelectorAll('.bar');
        $this->assertCount(1, $foo);
        $this->assertCount(1, $bar);
        $this->assertEquals('A', $foo->item(0)->innerText);
        $this->assertEquals('A', $bar->item(0)->innerText);
    }

    public function testQuerySelectorAllNoMatchComplex() {
        $html = '<div><span class="foo">A</span></div>';
        $root = DOMinator::read($html);
        $none = $root->querySelectorAll('p.bar');
        $this->assertCount(0, $none);
    }

    public function testQuerySelectorAllRootLevel() {
        $html = '<span>A</span><span>B</span>';
        $root = DOMinator::read($html);
        $spans = $root->querySelectorAll('span');
        $this->assertCount(2, $spans);
    }

    public function testQuerySelectorAllTextNodesIgnored() {
        $html = '<div>Text<span>A</span>Text2</div>';
        $root = DOMinator::read($html);
        $spans = $root->querySelectorAll('span');
        $this->assertCount(1, $spans);
        $this->assertEquals('A', $spans->item(0)->innerText);
    }

    public function testQuerySelectorAllOnChildNode() {
        $html = '<div><section><span class="foo">A</span></section><span class="foo">B</span></div>';
        $root = DOMinator::read($html);
        $section = $root->querySelectorAll('section')->item(0);
        $foo = $section->querySelectorAll('.foo');
        $this->assertCount(1, $foo);
        $this->assertEquals('A', $foo->item(0)->innerText);
    }

    public function testQuerySelectorReturnsFirstMatch() {
        $html = '<div><span class="foo">A</span><span class="foo">B</span></div>';
        $root = DOMinator::read($html);
        $node = $root->querySelector('.foo');
        $this->assertNotNull($node);
        $this->assertEquals('A', $node->innerText);
    }

    public function testQuerySelectorReturnsNullIfNoMatch() {
        $html = '<div><span class="foo">A</span></div>';
        $root = DOMinator::read($html);
        $node = $root->querySelector('.bar');
        $this->assertNull($node);
    }

    public function testGetElementsByTagNameFindsAll() {
        $html = '<div><span>A</span><span>B</span><p>C</p></div>';
        $root = DOMinator::read($html);
        $spans = $root->getElementsByTagName('span');
        $this->assertCount(2, $spans);
        $this->assertEquals('A', $spans->item(0)->innerText);
        $this->assertEquals('B', $spans->item(1)->innerText);
    }

    public function testGetElementsByTagNameCaseInsensitive() {
        $html = '<div><SPAN>A</SPAN><span>B</span></div>';
        $root = DOMinator::read($html);
        $spans = $root->getElementsByTagName('span');
        $this->assertCount(2, $spans);
    }

    public function testQuerySelectorAllAttributePresence() {
        $html = '<input placeholder="x"><input><input placeholder="y">';
        $root = DOMinator::read($html);
        $withPlaceholder = $root->querySelectorAll('[placeholder]');
        $this->assertCount(2, $withPlaceholder);
        $this->assertEquals('input', $withPlaceholder->item(0)->tag);
        $this->assertEquals('input', $withPlaceholder->item(1)->tag);
        $this->assertEquals('x', $withPlaceholder->item(0)->attributes['placeholder']);
        $this->assertEquals('y', $withPlaceholder->item(1)->attributes['placeholder']);
    }
}
