<?php
use PHPUnit\Framework\TestCase;
use Daniesy\HtmlParser\HtmlNode;

class HtmlNodeTest extends TestCase {
    public function testTextNode() {
        $node = new HtmlNode('', [], true, 'Hello');
        $this->assertTrue($node->isText);
        $this->assertEquals('Hello', $node->getInnerText());
        $this->assertEquals('Hello', html_entity_decode(strip_tags($node->toHtml())));
    }

    public function testElementNodeAttributes() {
        $node = new HtmlNode('div', ['id' => 'x', 'class' => 'foo']);
        $this->assertEquals('div', $node->tag);
        $this->assertEquals('x', $node->attributes['id']);
        $this->assertEquals('foo', $node->attributes['class']);
        $node->setAttribute('data-test', 'y');
        $this->assertEquals('y', $node->attributes['data-test']);
        $node->removeAttribute('id');
        $this->assertArrayNotHasKey('id', $node->attributes);
    }

    public function testAppendAndRemoveChild() {
        $parent = new HtmlNode('div');
        $child = new HtmlNode('span');
        $parent->appendChild($child);
        $this->assertCount(1, $parent->children);
        $this->assertSame($parent, $child->parent);
        $child->remove();
        $this->assertCount(0, $parent->children);
        $this->assertNull($child->parent);
    }

    public function testSetAndGetInnerText() {
        $node = new HtmlNode('div');
        $node->setInnerText('abc');
        $this->assertEquals('abc', $node->getInnerText());
        $this->assertTrue($node->children[0]->isText);
        $node->setInnerText('def');
        $this->assertEquals('def', $node->getInnerText());
    }

    public function testToHtmlElementAndText() {
        $node = new HtmlNode('div', ['id' => 'x']);
        $node->appendChild(new HtmlNode('', [], true, 'Hello'));
        $this->assertStringContainsString('<div id="x">', $node->toHtml());
        $this->assertStringContainsString('Hello', $node->toHtml());
        $this->assertStringContainsString('</div>', $node->toHtml());
    }

    public function testCommentNode() {
        $node = new HtmlNode('', [], false, 'a comment', true);
        $this->assertTrue($node->isComment);
        $this->assertEquals('<!--a comment-->', $node->toHtml());
    }

    public function testCdataNode() {
        $node = new HtmlNode('', [], false, 'raw <b>data</b>', false, true);
        $this->assertTrue($node->isCdata);
        $this->assertEquals('<![CDATA[raw <b>data</b>]]>', $node->toHtml());
    }

    public function testNamespace() {
        $node = new HtmlNode('circle', ['r' => '10'], false, '', false, false, 'svg');
        $this->assertEquals('svg', $node->namespace);
        $this->assertEquals('circle', $node->tag);
    }

    public function testMultipleChildrenAndNesting() {
        $parent = new HtmlNode('ul');
        $li1 = new HtmlNode('li');
        $li2 = new HtmlNode('li');
        $li1->appendChild(new HtmlNode('', [], true, 'A'));
        $li2->appendChild(new HtmlNode('', [], true, 'B'));
        $parent->appendChild($li1);
        $parent->appendChild($li2);
        $this->assertCount(2, $parent->children);
        $this->assertEquals('A', $parent->children[0]->children[0]->innerText);
        $this->assertEquals('B', $parent->children[1]->children[0]->innerText);
    }

    public function testSetInnerTextOnTextNode() {
        $node = new HtmlNode('', [], true, 'foo');
        $node->setInnerText('bar');
        $this->assertEquals('bar', $node->getInnerText());
    }

    public function testSetInnerTextOnElementWithChildren() {
        $node = new HtmlNode('div');
        $node->appendChild(new HtmlNode('span', [], true, 'x'));
        $node->setInnerText('y');
        $this->assertEquals('y', $node->getInnerText());
        $this->assertCount(1, $node->children);
        $this->assertTrue($node->children[0]->isText);
    }

    public function testToHtmlWithNestedElements() {
        $div = new HtmlNode('div', ['id' => 'main']);
        $span = new HtmlNode('span', ['class' => 'foo']);
        $span->appendChild(new HtmlNode('', [], true, 'abc'));
        $div->appendChild($span);
        $this->assertStringContainsString('<div id="main">', $div->toHtml());
        $this->assertStringContainsString('<span class="foo">', $div->toHtml());
        $this->assertStringContainsString('abc', $div->toHtml());
    }

    public function testToHtmlEscaping() {
        $node = new HtmlNode('div', ['title' => 'a<b>c']);
        $node->appendChild(new HtmlNode('', [], true, 'x < y & z'));
        $html = $node->toHtml();
        $this->assertStringContainsString('title="a<b>c"', $html);
        $this->assertStringContainsString('x < y & z', $html);
    }

    public function testRemoveOnRootNode() {
        $root = new HtmlNode('root');
        $root->remove();
        $this->assertNull($root->parent);
    }

    public function testCommentAndCdataMixed() {
        $node = new HtmlNode('div');
        $comment = new HtmlNode('', [], false, 'c', true);
        $cdata = new HtmlNode('', [], false, 'd', false, true);
        $node->appendChild($comment);
        $node->appendChild($cdata);
        $html = $node->toHtml();
        $this->assertStringContainsString('<!--c-->', $html);
        $this->assertStringContainsString('<![CDATA[d]]>', $html);
    }

    public function testNamespaceAndAttributesToHtml() {
        $node = new HtmlNode('circle', ['r' => '10'], false, '', false, false, 'svg');
        $html = $node->toHtml();
        $this->assertStringContainsString('<circle r="10">', $html);
    }
}
