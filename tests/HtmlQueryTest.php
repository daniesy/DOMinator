<?php
use PHPUnit\Framework\TestCase;
use Daniesy\DOMinator\HtmlParser;
use Daniesy\DOMinator\HtmlQuery;

class HtmlQueryTest extends TestCase {
    public function testQuerySelectorAllTag() {
        $html = '<div><span>A</span><span>B</span><p>C</p></div>';
        $root = HtmlParser::parse($html);
        $spans = $root->querySelectorAll('span');
        $this->assertCount(2, $spans);
        $this->assertEquals('A', $spans->item(0)->getInnerText());
        $this->assertEquals('B', $spans->item(1)->getInnerText());
    }

    public function testQuerySelectorAllClass() {
        $html = '<div><span class="foo bar">A</span><span class="bar">B</span><span class="foo">C</span></div>';
        $root = HtmlParser::parse($html);
        $foo = $root->querySelectorAll('.foo');
        $this->assertCount(2, $foo);
        $this->assertEquals('A', $foo->item(0)->getInnerText());
        $this->assertEquals('C', $foo->item(1)->getInnerText());
        $bar = $root->querySelectorAll('.bar');
        $this->assertCount(2, $bar);
    }

    public function testQuerySelectorAllId() {
        $html = '<div><span id="x">A</span><span id="y">B</span></div>';
        $root = HtmlParser::parse($html);
        $x = $root->querySelectorAll('#x');
        $this->assertCount(1, $x);
        $this->assertEquals('A', $x->item(0)->getInnerText());
        $y = $root->querySelectorAll('#y');
        $this->assertCount(1, $y);
        $this->assertEquals('B', $y->item(0)->getInnerText());
    }

    public function testQuerySelectorAllAttribute() {
        $html = '<div><span data-x="1">A</span><span data-x="2">B</span><span data-x="1">C</span></div>';
        $root = HtmlParser::parse($html);
        $x1 = $root->querySelectorAll('[data-x=1]');
        $this->assertCount(2, $x1);
        $this->assertEquals('A', $x1->item(0)->getInnerText());
        $this->assertEquals('C', $x1->item(1)->getInnerText());
        $x2 = $root->querySelectorAll('[data-x=2]');
        $this->assertCount(1, $x2);
        $this->assertEquals('B', $x2->item(0)->getInnerText());
    }

    public function testQuerySelectorAllNoMatch() {
        $html = '<div><span>A</span></div>';
        $root = HtmlParser::parse($html);
        $none = $root->querySelectorAll('.notfound');
        $this->assertCount(0, $none);
    }

    public function testQuerySelectorAllNested() {
        $html = '<div><section><span class="foo">A</span></section><span class="foo">B</span></div>';
        $root = HtmlParser::parse($html);
        $foo = $root->querySelectorAll('.foo');
        $this->assertCount(2, $foo);
        $this->assertEquals('A', $foo->item(0)->getInnerText());
        $this->assertEquals('B', $foo->item(1)->getInnerText());
    }

    public function testQuerySelectorAllMultipleClasses() {
        $html = '<div><span class="foo bar">A</span><span class="foo">B</span><span class="bar">C</span></div>';
        $root = HtmlParser::parse($html);
        $foo = $root->querySelectorAll('.foo');
        $bar = $root->querySelectorAll('.bar');
        $this->assertCount(2, $foo);
        $this->assertCount(2, $bar);
        $this->assertEquals('A', $foo->item(0)->getInnerText());
        $this->assertEquals('B', $foo->item(1)->getInnerText());
        $this->assertEquals('A', $bar->item(0)->getInnerText());
        $this->assertEquals('C', $bar->item(1)->getInnerText());
    }

    public function testQuerySelectorAllNestedTags() {
        $html = '<div><section><span>A</span></section><span>B</span></div>';
        $root = HtmlParser::parse($html);
        $spans = $root->querySelectorAll('span');
        $this->assertCount(2, $spans);
        $this->assertEquals('A', $spans->item(0)->getInnerText());
        $this->assertEquals('B', $spans->item(1)->getInnerText());
    }

    public function testQuerySelectorAllAttributeNoValue() {
        $html = '<div><input type="checkbox" checked><input type="checkbox"></div>';
        $root = HtmlParser::parse($html);
        $checked = $root->querySelectorAll('[checked=]');
        $this->assertCount(1, $checked);
        $this->assertEquals('input', $checked->item(0)->tag);
    }

    public function testQuerySelectorAllWithHyphenAttribute() {
        $html = '<div><span data-x="1">A</span><span data-x="2">B</span></div>';
        $root = HtmlParser::parse($html);
        $x1 = $root->querySelectorAll('[data-x=1]');
        $this->assertCount(1, $x1);
        $this->assertEquals('A', $x1->item(0)->getInnerText());
    }

    public function testQuerySelectorAllIdAndClass() {
        $html = '<div><span id="foo" class="bar">A</span><span id="bar">B</span></div>';
        $root = HtmlParser::parse($html);
        $foo = $root->querySelectorAll('#foo');
        $bar = $root->querySelectorAll('.bar');
        $this->assertCount(1, $foo);
        $this->assertCount(1, $bar);
        $this->assertEquals('A', $foo->item(0)->getInnerText());
        $this->assertEquals('A', $bar->item(0)->getInnerText());
    }

    public function testQuerySelectorAllNoMatchComplex() {
        $html = '<div><span class="foo">A</span></div>';
        $root = HtmlParser::parse($html);
        $none = $root->querySelectorAll('p.bar');
        $this->assertCount(0, $none);
    }

    public function testQuerySelectorAllRootLevel() {
        $html = '<span>A</span><span>B</span>';
        $root = HtmlParser::parse($html);
        $spans = $root->querySelectorAll('span');
        $this->assertCount(2, $spans);
    }

    public function testQuerySelectorAllTextNodesIgnored() {
        $html = '<div>Text<span>A</span>Text2</div>';
        $root = HtmlParser::parse($html);
        $spans = $root->querySelectorAll('span');
        $this->assertCount(1, $spans);
        $this->assertEquals('A', $spans->item(0)->getInnerText());
    }

    public function testQuerySelectorAllOnChildNode() {
        $html = '<div><section><span class="foo">A</span></section><span class="foo">B</span></div>';
        $root = HtmlParser::parse($html);
        $section = $root->querySelectorAll('section')->item(0);
        $foo = $section->querySelectorAll('.foo');
        $this->assertCount(1, $foo);
        $this->assertEquals('A', $foo->item(0)->getInnerText());
    }

    public function testQuerySelectorReturnsFirstMatch() {
        $html = '<div><span class="foo">A</span><span class="foo">B</span></div>';
        $root = HtmlParser::parse($html);
        $node = $root->querySelector('.foo');
        $this->assertNotNull($node);
        $this->assertEquals('A', $node->getInnerText());
    }

    public function testQuerySelectorReturnsNullIfNoMatch() {
        $html = '<div><span class="foo">A</span></div>';
        $root = HtmlParser::parse($html);
        $node = $root->querySelector('.bar');
        $this->assertNull($node);
    }

    public function testGetElementsByTagNameFindsAll() {
        $html = '<div><span>A</span><span>B</span><p>C</p></div>';
        $root = HtmlParser::parse($html);
        $spans = $root->getElementsByTagName('span');
        $this->assertCount(2, $spans);
        $this->assertEquals('A', $spans->item(0)->getInnerText());
        $this->assertEquals('B', $spans->item(1)->getInnerText());
    }

    public function testGetElementsByTagNameCaseInsensitive() {
        $html = '<div><SPAN>A</SPAN><span>B</span></div>';
        $root = HtmlParser::parse($html);
        $spans = $root->getElementsByTagName('span');
        $this->assertCount(2, $spans);
    }
}
