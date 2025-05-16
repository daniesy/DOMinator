<?php
use PHPUnit\Framework\TestCase;
use Daniesy\HtmlParser\HtmlParser;
use Daniesy\HtmlParser\HtmlQuery;
use Daniesy\HtmlParser\HtmlNode;

class HtmlParserTest extends TestCase {
    public function testInvalidHtml() {
        $html = '<span><span>Test</span></span>';
        $root = HtmlParser::parse($html);
        $result = $root->toHtml();
        $this->assertEquals($html, $result);

        $html = '<a><table><tr><td>Test</td></tr></table></a>';
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

    public function testSelfClosingAndVoidElements() {
        $html = '<div>foo<br/><img src="a.png"><hr></div>';
        $root = HtmlParser::parse($html);
        $this->assertEquals('div', $root->children[0]->tag);
        $this->assertEquals('br', $root->children[0]->children[1]->tag);
        $this->assertEquals('img', $root->children[0]->children[2]->tag);
        $this->assertEquals('hr', $root->children[0]->children[3]->tag);
    }

    public function testHtmlComments() {
        $html = '<div><!-- comment here --><span>ok</span></div>';
        $root = HtmlParser::parse($html);
        $comment = $root->children[0]->children[0];
        $this->assertTrue($comment->isComment);
        $this->assertEquals(' comment here ', $comment->innerText);
        $this->assertEquals('<!-- comment here -->', $comment->toHtml());
    }

    public function testScriptAndStyleRawContent() {
        $html = '<script>if (a < b) { alert("x"); }</script><style>body { color: red; }</style>';
        $root = HtmlParser::parse($html);
        $this->assertEquals('script', $root->children[0]->tag);
        $this->assertEquals('if (a < b) { alert("x"); }', $root->children[0]->children[0]->innerText);
        $this->assertEquals('style', $root->children[1]->tag);
        $this->assertEquals('body { color: red; }', $root->children[1]->children[0]->innerText);
    }

    public function testEntityDecoding() {
        $html = '<div title="&amp; &lt; &gt;">&amp; &lt; &gt;</div>';
        $root = HtmlParser::parse($html);
        $div = $root->children[0];
        $this->assertEquals('& < >', $div->attributes['title']);
        $this->assertEquals('& < >', $div->children[0]->innerText);
    }

    public function testCdataSection() {
        $html = '<![CDATA[Some <b>unparsed</b> data]]>';
        $root = HtmlParser::parse($html);
        $cdata = $root->children[0];
        $this->assertTrue($cdata->isCdata);
        $this->assertEquals('Some <b>unparsed</b> data', $cdata->innerText);
        $this->assertEquals('<![CDATA[Some <b>unparsed</b> data]]>', $cdata->toHtml());
    }

    public function testAttributeQuoting() {
        $html = "<div a='1' b=2 c=\"3\">x</div>";
        $root = HtmlParser::parse($html);
        $div = $root->children[0];
        $this->assertEquals('1', $div->attributes['a']);
        $this->assertEquals('2', $div->attributes['b']);
        $this->assertEquals('3', $div->attributes['c']);
    }

    public function testDoctypeVariants() {
        $html = "<!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'><svg></svg>";
        $root = HtmlParser::parse($html);
        $this->assertStringContainsString('DOCTYPE svg', $root->doctype);
    }

    public function testNamespaceSupport() {
        $html = '<svg:rect width="100" height="100"/>';
        $root = HtmlParser::parse($html);
        $svg = $root->children[0];
        $this->assertEquals('svg', $svg->namespace);
        $this->assertEquals('rect', $svg->tag);
    }

    public function testWhitespaceNormalization() {
        // Use real newline and tab characters
        $html = "<div>   a   b\n\t   c </div>";
        $html = str_replace(['\\n', '\\t'], ["\n", "\t"], $html);
        $root = HtmlParser::parse($html, true);
        $this->assertEquals(' a b c ', $root->children[0]->children[0]->innerText);
    }

    public function testMalformedHtmlRecovery() {
        $html = '<div><span>foo';
        $root = HtmlParser::parse($html);
        $this->assertEquals('div', $root->children[0]->tag);
        $this->assertEquals('span', $root->children[0]->children[0]->tag);
        $this->assertEquals('foo', $root->children[0]->children[0]->children[0]->innerText);
    }

    public function testMultipleNestedElements() {
        $html = '<div><section><article><p>Text</p></article></section></div>';
        $root = HtmlParser::parse($html);
        $this->assertEquals('div', $root->children[0]->tag);
        $this->assertEquals('section', $root->children[0]->children[0]->tag);
        $this->assertEquals('article', $root->children[0]->children[0]->children[0]->tag);
        $this->assertEquals('p', $root->children[0]->children[0]->children[0]->children[0]->tag);
        $this->assertEquals('Text', $root->children[0]->children[0]->children[0]->children[0]->children[0]->innerText);
    }

    public function testUnclosedTags() {
        $html = '<ul><li>One<li>Two<li>Three</ul>';
        $root = HtmlParser::parse($html);
        $ul = $root->children[0];
        $this->assertEquals('ul', $ul->tag);
        $this->assertCount(3, $ul->children);
        $this->assertEquals('One', $ul->children[0]->children[0]->innerText);
        $this->assertEquals('Two', $ul->children[1]->children[0]->innerText);
        $this->assertEquals('Three', $ul->children[2]->children[0]->innerText);
    }

    public function testAttributesWithoutValues() {
        $html = '<input type="checkbox" checked>';
        $root = HtmlParser::parse($html);
        $input = $root->children[0];
        $this->assertEquals('input', $input->tag);
        $this->assertEquals('checkbox', $input->attributes['type']);
        $this->assertArrayHasKey('checked', $input->attributes);
        $this->assertEquals('', $input->attributes['checked']);
    }

    public function testMultipleClasses() {
        $html = '<div class="foo bar baz">x</div>';
        $root = HtmlParser::parse($html);
        $div = $root->children[0];
        $this->assertEquals('foo bar baz', $div->attributes['class']);
    }

    public function testDeeplyNested() {
        $html = str_repeat('<div>', 50) . 'end' . str_repeat('</div>', 50);
        $root = HtmlParser::parse($html);
        $node = $root->children[0];
        for ($i = 0; $i < 49; $i++) {
            $this->assertEquals('div', $node->tag);
            $node = $node->children[0];
        }
        $this->assertEquals('div', $node->tag);
        $this->assertEquals('end', $node->children[0]->innerText);
    }

    public function testHtmlWithCommentsAndCdata() {
        $html = '<div><!--comment--><![CDATA[raw]]></div>';
        $root = HtmlParser::parse($html);
        $div = $root->children[0];
        $this->assertTrue($div->children[0]->isComment);
        $this->assertTrue($div->children[1]->isCdata);
        $this->assertEquals('comment', trim($div->children[0]->innerText));
        $this->assertEquals('raw', $div->children[1]->innerText);
    }

    public function testScriptWithTagsInside() {
        $html = '<script>if (x < 1) { document.write("<b>bold</b>"); }</script>';
        $root = HtmlParser::parse($html);
        $script = $root->children[0];
        $this->assertEquals('script', $script->tag);
        $this->assertStringContainsString('<b>bold</b>', $script->children[0]->innerText);
    }

    public function testHtmlEntitiesInAttributes() {
        $html = '<a href="test.php?x=1&amp;y=2">link</a>';
        $root = HtmlParser::parse($html);
        $a = $root->children[0];
        $this->assertEquals('test.php?x=1&y=2', $a->attributes['href']);
    }

    public function testNamespaceAndAttributes() {
        $html = '<svg:circle cx="50" cy="50" r="40"/>';
        $root = HtmlParser::parse($html);
        $svg = $root->children[0];
        $this->assertEquals('svg', $svg->namespace);
        $this->assertEquals('circle', $svg->tag);
        $this->assertEquals('50', $svg->attributes['cx']);
        $this->assertEquals('50', $svg->attributes['cy']);
        $this->assertEquals('40', $svg->attributes['r']);
    }
}
