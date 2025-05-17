<?php
use PHPUnit\Framework\TestCase;
use Daniesy\DOMinator\Node;

class HtmlNodeTest extends TestCase {
    public function testTextNode() {
        $node = new Node('', [], true, 'Hello');
        $this->assertTrue($node->isText);
        $this->assertEquals('Hello', $node->getInnerText());
        $this->assertEquals('Hello', html_entity_decode(strip_tags($node->toHtml())));
    }

    public function testElementNodeAttributes() {
        $node = new Node('div', ['id' => 'x', 'class' => 'foo']);
        $this->assertEquals('div', $node->tag);
        $this->assertEquals('x', $node->attributes['id']);
        $this->assertEquals('foo', $node->attributes['class']);
        $node->setAttribute('data-test', 'y');
        $this->assertEquals('y', $node->attributes['data-test']);
        $node->removeAttribute('id');
        $this->assertArrayNotHasKey('id', $node->attributes);
    }

    public function testAppendAndRemoveChild() {
        $parent = new Node('div');
        $child = new Node('span');
        $parent->appendChild($child);
        $this->assertCount(1, $parent->children);
        $this->assertSame($parent, $child->parent);
        $child->remove();
        $this->assertCount(0, $parent->children);
        $this->assertNull($child->parent);
    }

    public function testSetAndGetInnerText() {
        $node = new Node('div');
        $node->setInnerText('abc');
        $this->assertEquals('abc', $node->getInnerText());
        $this->assertTrue($node->children[0]->isText);
        $node->setInnerText('def');
        $this->assertEquals('def', $node->getInnerText());
    }

    public function testToHtmlElementAndText() {
        $node = new Node('div', ['id' => 'x']);
        $node->appendChild(new Node('', [], true, 'Hello'));
        $this->assertStringContainsString('<div id="x">', $node->toHtml());
        $this->assertStringContainsString('Hello', $node->toHtml());
        $this->assertStringContainsString('</div>', $node->toHtml());
    }

    public function testCommentNode() {
        $node = new Node('', [], false, 'a comment', true);
        $this->assertTrue($node->isComment);
        $this->assertEquals('<!--a comment-->', $node->toHtml());
    }

    public function testCdataNode() {
        $node = new Node('', [], false, 'raw <b>data</b>', false, true);
        $this->assertTrue($node->isCdata);
        $this->assertEquals('<![CDATA[raw <b>data</b>]]>', $node->toHtml());
    }

    public function testNamespace() {
        $node = new Node('circle', ['r' => '10'], false, '', false, false, 'svg');
        $this->assertEquals('svg', $node->namespace);
        $this->assertEquals('circle', $node->tag);
    }

    public function testMultipleChildrenAndNesting() {
        $parent = new Node('ul');
        $li1 = new Node('li');
        $li2 = new Node('li');
        $li1->appendChild(new Node('', [], true, 'A'));
        $li2->appendChild(new Node('', [], true, 'B'));
        $parent->appendChild($li1);
        $parent->appendChild($li2);
        $this->assertCount(2, $parent->children);
        $this->assertEquals('A', $parent->children[0]->children[0]->innerText);
        $this->assertEquals('B', $parent->children[1]->children[0]->innerText);
    }

    public function testSetInnerTextOnTextNode() {
        $node = new Node('', [], true, 'foo');
        $node->setInnerText('bar');
        $this->assertEquals('bar', $node->getInnerText());
    }

    public function testSetInnerTextOnElementWithChildren() {
        $node = new Node('div');
        $node->appendChild(new Node('span', [], true, 'x'));
        $node->setInnerText('y');
        $this->assertEquals('y', $node->getInnerText());
        $this->assertCount(1, $node->children);
        $this->assertTrue($node->children[0]->isText);
    }

    public function testToHtmlWithNestedElements() {
        $div = new Node('div', ['id' => 'main']);
        $span = new Node('span', ['class' => 'foo']);
        $span->appendChild(new Node('', [], true, 'abc'));
        $div->appendChild($span);
        $this->assertStringContainsString('<div id="main">', $div->toHtml());
        $this->assertStringContainsString('<span class="foo">', $div->toHtml());
        $this->assertStringContainsString('abc', $div->toHtml());
    }

    public function testToHtmlEscaping() {
        $node = new Node('div', ['title' => 'a<b>c']);
        $node->appendChild(new Node('', [], true, 'x < y & z'));
        $html = $node->toHtml();
        $this->assertStringContainsString('title="a<b>c"', $html);
        $this->assertStringContainsString('x < y & z', $html);
    }

    public function testRemoveOnRootNode() {
        $root = new Node('root');
        $root->remove();
        $this->assertNull($root->parent);
    }

    public function testCommentAndCdataMixed() {
        $node = new Node('div');
        $comment = new Node('', [], false, 'c', true);
        $cdata = new Node('', [], false, 'd', false, true);
        $node->appendChild($comment);
        $node->appendChild($cdata);
        $html = $node->toHtml();
        $this->assertStringContainsString('<!--c-->', $html);
        $this->assertStringContainsString('<![CDATA[d]]>', $html);
    }

    public function testNamespaceAndAttributesToHtml() {
        $node = new Node('circle', ['r' => '10'], false, '', false, false, 'svg');
        $html = $node->toHtml();
        $this->assertStringContainsString('<circle r="10">', $html);
    }
}
