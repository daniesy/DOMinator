<?php

use PHPUnit\Framework\TestCase;
use Daniesy\DOMinator\DOMinator;
use Daniesy\DOMinator\Nodes\StyleNode;
use Daniesy\DOMinator\Nodes\ScriptNode;

class DOMinatorTest extends TestCase
{
    public function testInvalidHtml()
    {
        $html = '<span><span>Test</span></span>';
        $root = DOMinator::read($html);
        $result = $root->toHtml();
        $this->assertEquals($html, $result);

        $html = '<a><table><tr><td>Test</td></tr></table></a>';
        $root = DOMinator::read($html);
        $result = $root->toHtml();
        $this->assertEquals($html, $result);
    }

    public function testHtmlDocument()
    {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body><div id="main"><p>Hello <b>World</b></p></div></body></html>';
        $root = DOMinator::read($html);
        $this->assertEquals('html', $root->tag);
        $this->assertCount(2, $root->children); // <head> and <body>
        $this->assertEquals('head', $root->children->item(0)->tag);
        $this->assertEquals('body', $root->children->item(1)->tag);
        $this->assertEquals($html, $root->toHtml());
    }

    public function testParseAndExport()
    {
        $html = '<div id="main"><p>Hello <b>World</b></p></div>';
        $root = DOMinator::read($html);
        $exported = '';
        foreach ($root->children as $child) {
            $exported .= $child->toHtml();
        }
        $this->assertStringContainsString('<div id="main">', $exported);
        $this->assertStringContainsString('<p>Hello <b>World</b></p>', $exported);
    }

    public function testQuerySelectorAllByClass()
    {
        $html = '<div><span class="foo">A</span><span class="bar">B</span></div>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('.foo');
        $this->assertCount(1, $nodes);
        $this->assertEquals('span', $nodes->item(0)->tag);
        $this->assertEquals('A', $nodes->item(0)->innerText);
    }

    public function testSetInnerText()
    {
        $html = '<div><span class="foo">A</span></div>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('.foo');
        $nodes->item(0)->innerText = 'B';
        $this->assertEquals('B', $nodes->item(0)->innerText);
    }

    public function testSetAndRemoveAttribute()
    {
        $html = '<div><span class="foo">A</span></div>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('span');
        $nodes->item(0)->setAttribute('id', 'test');
        $this->assertEquals('test', $nodes->item(0)->getAttribute('id'));
        $nodes->item(0)->removeAttribute('id');
        $this->assertFalse($nodes->item(0)->hasAttribute('id'));;
    }

    public function testRemoveNode()
    {
        $html = '<div><span class="foo">A</span><span>B</span></div>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('.foo');
        $nodes->item(0)->remove();
        $spans = $root->querySelectorAll('span');
        $this->assertCount(1, $spans);
        $this->assertEquals('B', $spans->item(0)->innerText);
    }

    public function testSelfClosingAndVoidElements()
    {
        $html = '<div>foo<br/><img src="a.png"><hr></div>';
        $root = DOMinator::read($html);
        $this->assertEquals('div', $root->children->item(0)->tag);
        $this->assertEquals('br', $root->children->item(0)->children->item(1)->tag);
        $this->assertEquals('img', $root->children->item(0)->children->item(2)->tag);
        $this->assertEquals('hr', $root->children->item(0)->children->item(3)->tag);
    }

    public function testHtmlComments()
    {
        $html = '<div><!-- comment here --><span>ok</span></div>';
        $root = DOMinator::read($html);
        $comment = $root->children->item(0)->children->item(0);
        $this->assertTrue($comment->isComment);
        $this->assertEquals(' comment here ', $comment->innerText);
        $this->assertEquals('<!-- comment here -->', $comment->toHtml());
    }

    public function testScriptAndStyleRawContent()
    {
        $html = '<script>if (a < b) { alert("x"); }</script><style>body { color: red; }</style>';
        $root = DOMinator::read($html);
        $this->assertEquals('script', $root->children->item(0)->tag);
        $this->assertEquals('if (a < b) { alert("x"); }', $root->children->item(0)->children->item(0)->innerText);
        $this->assertEquals('style', $root->children->item(1)->tag);
        $this->assertEquals('body { color: red; }', $root->children->item(1)->children->item(0)->innerText);
    }

    public function testEntityDecoding()
    {
        $html = '<div title="&amp; &lt; &gt;">&amp; &lt; &gt;</div>';
        $root = DOMinator::read($html);
        $div = $root->children->item(0);
        $this->assertEquals('& < >', $div->attributes['title']);
        $this->assertEquals('& < >', $div->children->item(0)->innerText);
    }

    public function testCdataSection()
    {
        $html = '<![CDATA[Some <b>unparsed</b> data]]>';
        $root = DOMinator::read($html);
        $cdata = $root->children->item(0);
        $this->assertTrue($cdata->isCdata);
        $this->assertEquals('Some <b>unparsed</b> data', $cdata->innerText);
        $this->assertEquals('<![CDATA[Some <b>unparsed</b> data]]>', $cdata->toHtml());
    }

    public function testAttributeQuoting()
    {
        $html = "<div a='1' b=2 c=\"3\">x</div>";
        $root = DOMinator::read($html);
        $div = $root->children->item(0);
        $this->assertEquals('1', $div->attributes['a']);
        $this->assertEquals('2', $div->attributes['b']);
        $this->assertEquals('3', $div->attributes['c']);
    }

    public function testDoctypeVariants()
    {
        $html = "<!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'><svg></svg>";
        $root = DOMinator::read($html);
        $this->assertStringContainsString('DOCTYPE svg', $root->doctype);
    }

    public function testNamespaceSupport()
    {
        $html = '<svg:rect width="100" height="100"/>';
        $root = DOMinator::read($html);
        $svg = $root->children->item(0);
        $this->assertEquals('svg', $svg->namespace);
        $this->assertEquals('rect', $svg->tag);
    }

    public function testWhitespaceNormalization()
    {
        // Use real newline and tab characters
        $html = "<div>   a   b\n\t   c </div>";
        $html = str_replace(['\\n', '\\t'], ["\n", "\t"], $html);
        $root = DOMinator::read($html, true);
        $this->assertEquals(' a b c ', $root->children->item(0)->children->item(0)->innerText);
    }

    public function testMalformedHtmlRecovery()
    {
        $html = '<div><span>foo';
        $root = DOMinator::read($html);
        $this->assertEquals('div', $root->children->item(0)->tag);
        $this->assertEquals('span', $root->children->item(0)->children->item(0)->tag);
        $this->assertEquals('foo', $root->children->item(0)->children->item(0)->children->item(0)->innerText);
    }

    public function testMultipleNestedElements()
    {
        $html = '<div><section><article><p>Text</p></article></section></div>';
        $root = DOMinator::read($html);
        $this->assertEquals('div', $root->children->item(0)->tag);
        $this->assertEquals('section', $root->children->item(0)->children->item(0)->tag);
        $this->assertEquals('article', $root->children->item(0)->children->item(0)->children->item(0)->tag);
        $this->assertEquals('p', $root->children->item(0)->children->item(0)->children->item(0)->children->item(0)->tag);
        $this->assertEquals('Text', $root->children->item(0)->children->item(0)->children->item(0)->children->item(0)->children->item(0)->innerText);
    }

    public function testUnclosedTags()
    {
        $html = '<ul><li>One<li>Two<li>Three</ul>';
        $root = DOMinator::read($html);
        $ul = $root->children->item(0);
        $this->assertEquals('ul', $ul->tag);
        $this->assertCount(3, $ul->children);
        $this->assertEquals('One', $ul->children->item(0)->children->item(0)->innerText);
        $this->assertEquals('Two', $ul->children->item(1)->children->item(0)->innerText);
        $this->assertEquals('Three', $ul->children->item(2)->children->item(0)->innerText);
    }

    public function testAttributesWithoutValues()
    {
        $html = '<input type="checkbox" checked>';
        $root = DOMinator::read($html);
        $input = $root->children->item(0);
        $this->assertEquals('input', $input->tag);
        $this->assertEquals('checkbox', $input->attributes['type']);
        $this->assertArrayHasKey('checked', $input->attributes);
        $this->assertEquals('', $input->attributes['checked']);
    }

    public function testMultipleClasses()
    {
        $html = '<div class="foo bar baz">x</div>';
        $root = DOMinator::read($html);
        $div = $root->children->item(0);
        $this->assertEquals('foo bar baz', $div->attributes['class']);
    }

    public function testDeeplyNested()
    {
        $html = str_repeat('<div>', 50) . 'end' . str_repeat('</div>', 50);
        $root = DOMinator::read($html);
        $node = $root->children->item(0);
        for ($i = 0; $i < 49; $i++) {
            $this->assertEquals('div', $node->tag);
            $node = $node->children->item(0);
        }
        $this->assertEquals('div', $node->tag);
        $this->assertEquals('end', $node->children->item(0)->innerText);
    }

    public function testHtmlWithCommentsAndCdata()
    {
        $html = '<div><!--comment--><![CDATA[raw]]></div>';
        $root = DOMinator::read($html);
        $div = $root->children->item(0);
        $this->assertTrue($div->children->item(0)->isComment);
        $this->assertTrue($div->children->item(1)->isCdata);
        $this->assertEquals('comment', trim($div->children->item(0)->innerText));
        $this->assertEquals('raw', $div->children->item(1)->innerText);
    }

    public function testScriptWithTagsInside()
    {
        $html = '<script>if (x < 1) { document.write("<b>bold</b>"); }</script>';
        $root = DOMinator::read($html);
        $script = $root->children->item(0);
        $this->assertEquals('script', $script->tag);
        $this->assertStringContainsString('<b>bold</b>', $script->children->item(0)->innerText);
    }

    public function testHtmlEntitiesInAttributes()
    {
        $html = '<a href="test.php?x=1&amp;y=2">link</a>';
        $root = DOMinator::read($html);
        $a = $root->children->item(0);
        $this->assertEquals('test.php?x=1&y=2', $a->attributes['href']);
    }

    public function testNamespaceAndAttributes()
    {
        $html = '<svg:circle cx="50" cy="50" r="40"/>';
        $root = DOMinator::read($html);
        $svg = $root->children->item(0);
        $this->assertEquals('svg', $svg->namespace);
        $this->assertEquals('circle', $svg->tag);
        $this->assertEquals('50', $svg->attributes['cx']);
        $this->assertEquals('50', $svg->attributes['cy']);
        $this->assertEquals('40', $svg->attributes['r']);
    }

    public function testNestedVoidElements()
    {
        $html = '<div><img src="a.png"><br><hr><input type="text"></div>';
        $root = DOMinator::read($html);
        $div = $root->children->item(0);
        $this->assertEquals('img', $div->children->item(0)->tag);
        $this->assertEquals('br', $div->children->item(1)->tag);
        $this->assertEquals('hr', $div->children->item(2)->tag);
        $this->assertEquals('input', $div->children->item(3)->tag);
        $this->assertEquals('a.png', $div->children->item(0)->attributes['src']);
        $this->assertEquals('text', $div->children->item(3)->attributes['type']);
    }

    public function testMixedContent()
    {
        $html = '<p>Hello <b>World</b>! <i>How</i> are <span>you</span>?</p>';
        $root = DOMinator::read($html);
        $p = $root->children->item(0);
        $this->assertEquals('p', $p->tag);
        $this->assertEquals('Hello ', $p->children->item(0)->innerText);
        $this->assertEquals('b', $p->children->item(1)->tag);
        $this->assertEquals('World', $p->children->item(1)->children->item(0)->innerText);
        $this->assertEquals('! ', $p->children->item(2)->innerText);
        $this->assertEquals('i', $p->children->item(3)->tag);
        $this->assertEquals('How', $p->children->item(3)->children->item(0)->innerText);
        $this->assertEquals(' are ', $p->children->item(4)->innerText);
        $this->assertEquals('span', $p->children->item(5)->tag);
        $this->assertEquals('you', $p->children->item(5)->children->item(0)->innerText);
        $this->assertEquals('?', $p->children->item(6)->innerText);
    }

    public function testDeeplyBrokenHtml()
    {
        $html = '<div><ul><li>One<li>Two<li>Three</ul><p>Para';
        $root = DOMinator::read($html);
        $div = $root->children->item(0);
        $this->assertEquals('ul', $div->children->item(0)->tag);
        $this->assertEquals('li', $div->children->item(0)->children->item(0)->tag);
        $this->assertEquals('Three', $div->children->item(0)->children->item(2)->children->item(0)->innerText);
        $this->assertEquals('p', $div->children->item(1)->tag);
        $this->assertEquals('Para', $div->children->item(1)->children->item(0)->innerText);
    }

    public function testScriptWithCdataAndComment()
    {
        $html = '<script><![CDATA[var x = 1;]]><!-- comment --></script>';
        $root = DOMinator::read($html);
        $script = $root->children->item(0);
        $this->assertEquals('script', $script->tag);
        $this->assertStringContainsString('CDATA', $script->children->item(0)->toHtml());
        $this->assertStringContainsString('comment', $script->children->item(0)->toHtml());
    }

    public function testAttributeWithSpecialCharacters()
    {
        $html = '<div data-json="{&quot;foo&quot;:1, &quot;bar&quot;:2}"></div>';
        $root = DOMinator::read($html);
        $div = $root->children->item(0);
        $this->assertEquals('{"foo":1, "bar":2}', $div->attributes['data-json']);
    }

    public function testEmptyElements()
    {
        $html = '<div></div><span></span><p></p>';
        $root = DOMinator::read($html);
        $this->assertEquals('div', $root->children->item(0)->tag);
        $this->assertEquals('span', $root->children->item(1)->tag);
        $this->assertEquals('p', $root->children->item(2)->tag);
        $this->assertEmpty($root->children->item(0)->children);
        $this->assertEmpty($root->children->item(1)->children);
        $this->assertEmpty($root->children->item(2)->children);
    }

    public function testMultipleRootElements()
    {
        $html = '<header>Header</header><main>Main</main><footer>Footer</footer>';
        $root = DOMinator::read($html);
        $this->assertEquals('header', $root->children->item(0)->tag);
        $this->assertEquals('main', $root->children->item(1)->tag);
        $this->assertEquals('footer', $root->children->item(2)->tag);
    }

    public function testCaseInsensitiveTags()
    {
        $html = '<DIV><SpAn>Test</SpAn></DIV>';
        $root = DOMinator::read($html);
        $this->assertEquals('div', $root->children->item(0)->tag);
        $this->assertEquals('span', $root->children->item(0)->children->item(0)->tag);
        $this->assertEquals('Test', $root->children->item(0)->children->item(0)->children->item(0)->innerText);
    }

    public function testSimpleHtml()
    {
        $html = '<div>a</div>';
        $root = DOMinator::read($html);
        $this->assertEquals('<div>a</div>', $root->toHtml());

        $html = '<div><p>p</p>a</div>';
        $root = DOMinator::read($html);
        $this->assertEquals('<div><p>p</p>a</div>', $root->toHtml());
    }

    public function testNamespaceWithHyphen()
    {
        $html = '<xlink:href>value</xlink:href>';
        $root = DOMinator::read($html);
        $xlink = $root->children->item(0);
        $this->assertEquals('xlink', $xlink->namespace);
        $this->assertEquals('href', $xlink->tag);
        $this->assertEquals('value', $xlink->children->item(0)->innerText);
    }

    public function testPerformanceLargeFlatHtml()
    {
        $html = '<ul>' . str_repeat('<li>Item</li>', 10000) . '</ul>';
        $start = microtime(true);
        $root = DOMinator::read($html);
        $duration = microtime(true) - $start;
        $ul = $root->children->item(0);
        $this->assertEquals('ul', $ul->tag);
        $this->assertCount(10000, $ul->children);
        $this->assertLessThan(2, $duration, 'Parsing 10,000 flat elements should be fast');
    }

    public function testPerformanceLargeNestedHtml()
    {
        $html = '';
        for ($i = 0; $i < 2000; $i++) {
            $html .= '<div>';
        }
        $html .= 'end';
        for ($i = 0; $i < 2000; $i++) {
            $html .= '</div>';
        }
        $start = microtime(true);
        $root = DOMinator::read($html);
        $duration = microtime(true) - $start;
        $node = $root->children->item(0);
        for ($i = 0; $i < 1999; $i++) {
            $this->assertEquals('div', $node->tag);
            $node = $node->children->item(0);
        }
        $this->assertEquals('div', $node->tag);
        $this->assertEquals('end', $node->children->item(0)->innerText);
        $this->assertLessThan(2, $duration, 'Parsing 2000 nested elements should be fast');
    }

    public function testPerformanceManyAttributes()
    {
        $attrs = [];
        for ($i = 0; $i < 100; $i++) {
            $attrs[] = 'data-x' . $i . '="' . $i . '"';
        }
        $html = '<div ' . implode(' ', $attrs) . '>content</div>';
        $start = microtime(true);
        $root = DOMinator::read($html);
        $duration = microtime(true) - $start;
        $div = $root->children->item(0);
        $this->assertEquals('div', $div->tag);
        for ($i = 0; $i < 100; $i++) {
            $this->assertEquals((string)$i, $div->attributes['data-x' . $i]);
        }
        $this->assertLessThan(1, $duration, 'Parsing 100 attributes should be fast');
    }

    public function testGetAllTextNodesAndModify()
    {
        $html = '<div>Hello <b>World</b> and <i>Universe</i></div>';
        $root = DOMinator::read($html); // Assuming Node::parse exists, or use HtmlParser if needed
        $textNodes = $root->getAllTextNodes();
        $this->assertCount(4, $textNodes);
        $this->assertEquals('Hello ', $textNodes->first()->innerText);
        $this->assertEquals('World', $textNodes->item(1)->innerText);
        $this->assertEquals(' and ', $textNodes->item(2)->innerText);
        $this->assertEquals('Universe', $textNodes->item(3)->innerText);

        // Modify text nodes
        $textNodes->first()->innerText = 'Hi ';
        $textNodes->item(1)->innerText = 'Earth';
        $textNodes->item(3)->innerText = 'Galaxy';
        $htmlOut = $root->toHtml();
        $this->assertStringContainsString('Hi <b>Earth</b> and <i>Galaxy</i>', $htmlOut);
        $this->assertEquals('<div>Hi <b>Earth</b> and <i>Galaxy</i></div>', $htmlOut);

        $this->assertEquals('div', $textNodes->first()->parent->tag);
        $this->assertEquals('b', $textNodes->item(1)->parent->tag);
        $this->assertEquals('i', $textNodes->item(3)->parent->tag);
    }

    public function testGetAllCommentNodesAndRemove()
    {
        $html = '<div><!-- comment1 --><span>ok</span><!-- comment2 --></div>';
        $root = DOMinator::read($html);
        $comments = $root->getAllCommentNodes();
        $this->assertCount(2, $comments);
        $this->assertEquals(' comment1 ', $comments->first()->innerText);
        $this->assertEquals(' comment2 ', $comments->item(1)->innerText);
        // Remove all comments
        foreach ($comments as $comment) {
            $comment->remove();
        }
        $htmlOut = $root->toHtml();
        $this->assertStringNotContainsString('<!-- comment1 -->', $htmlOut);
        $this->assertStringNotContainsString('<!-- comment2 -->', $htmlOut);
        $this->assertStringContainsString('<span>ok</span>', $htmlOut);
    }

    public function testGetAllCommentNodesAndRemoveWithContent()
    {
        $html = '<div>foo <!-- comment1 -->bar<span>ok</span><!-- comment2 -->baz</div>';
        $root = DOMinator::read($html);
        $comments = $root->getAllCommentNodes();
        $this->assertCount(2, $comments);
        // Remove all comments and their content from adjacent text nodes
        foreach ($comments as $comment) {
            $comment->remove(true);
        }
        $htmlOut = $root->toHtml();
        $this->assertStringNotContainsString('<!-- comment1 -->', $htmlOut);
        $this->assertStringNotContainsString('<!-- comment2 -->', $htmlOut);
        $this->assertStringContainsString('<span>ok</span>', $htmlOut);
        $this->assertStringContainsString('foo bar', $htmlOut); // 'bar' should remain if not part of comment
        $this->assertStringContainsString('baz', $htmlOut);
        // The text nodes adjacent to comments should have the comment content removed
        $this->assertStringNotContainsString('comment1', $htmlOut);
        $this->assertStringNotContainsString('comment2', $htmlOut);
    }

    public function testGetAllCommentNodesAndRemoveWithContentSecond()
    {
        $html = '<!--[if mso]><div style="mso-hide: all"><![endif]--><a href="#" class="call-to-action-button" style="text-decoration:none;">test</a>';
        $root = DOMinator::read($html);
        $comments = $root->getAllCommentNodes();
        $this->assertCount(1, $comments);
        // Remove all comments and their content from adjacent text nodes
        foreach ($comments as $comment) {
            $comment->remove();
        }
        $htmlOut = $root->toHtml();
        $this->assertStringNotContainsString('<!--[if mso]>', $htmlOut);
        $this->assertStringNotContainsString('<![endif]-->', $htmlOut);
        $this->assertStringContainsString('<a href="#" class="call-to-action-button" style="text-decoration:none;">test</a>', $htmlOut); // 'bar' should remain if not part of comment
        $this->assertStringNotContainsString('<div style="mso-hide: all">', $htmlOut);
    }


    public function testQuerySelectorAllAndBulkEdit()
    {
        $html = '<div><p>One</p><p>Two</p><p>Three</p></div>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('p');
        $texts = [];
        foreach ($nodes as $node) {
            $texts[] = $node->innerText;
            $node->innerText = strtoupper($node->innerText);
        }
        $this->assertEquals(['One', 'Two', 'Three'], $texts);
        foreach ($nodes as $node) {
            $this->assertTrue(strtoupper($node->innerText) === $node->innerText);
        }
        $htmlOut = $root->toHtml();
        $this->assertStringContainsString('<p>ONE</p>', $htmlOut);
        $this->assertStringContainsString('<p>TWO</p>', $htmlOut);
        $this->assertStringContainsString('<p>THREE</p>', $htmlOut);
    }

    public function testQuerySelectorAllNestedBulkEdit()
    {
        $html = '<section><div><p>First</p></div><div><p>Second</p></div></section>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('p');
        foreach ($nodes as $i => $node) {
            $node->innerText = "Para $i";
        }
        $htmlOut = $root->toHtml();
        $this->assertStringContainsString('<p>Para 0</p>', $htmlOut);
        $this->assertStringContainsString('<p>Para 1</p>', $htmlOut);
    }

    public function testAttributeSelectorExactMatch()
    {
        $html = '<div data-role="admin">A</div><div data-role="user">B</div>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('[data-role="admin"]');
        $this->assertCount(1, $nodes);
        $this->assertEquals('A', $nodes->item(0)->innerText);
    }

    public function testAttributeSelectorSpaceSeparatedWord()
    {
        $html = '<div data-role="admin">A</div><div data-role="super admin">B</div><div data-role="administrator">C</div>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('[data-role~="admin"]');
        $this->assertCount(2, $nodes);
        $this->assertEquals('A', $nodes->item(0)->innerText);
        $this->assertEquals('B', $nodes->item(1)->innerText);
    }

    public function testAttributeSelectorSubstring()
    {
        $html = '<div data-role="admin">A</div><div data-role="super-admin">B</div><div data-role="administrator">C</div>';
        $root = DOMinator::read($html);
        $nodes = $root->querySelectorAll('[data-role*="admin"]');
        $this->assertCount(3, $nodes);
        $this->assertEquals('A', $nodes->item(0)->innerText);
        $this->assertEquals('B', $nodes->item(1)->innerText);
        $this->assertEquals('C', $nodes->item(2)->innerText);
    }

    public function testXmlDeclarationPreserved()
    {
        $xmls = [
            '<?xml version="1.0"?>',
            '<?xml version="1.0" encoding="utf-8"?>',
            '<?xml encoding="utf-8"?>',
            '<?xml version="1.1" encoding="ISO-8859-1" standalone="yes"?>',
        ];
        foreach ($xmls as $xmlDecl) {
            $html = $xmlDecl . '<root><child>Text</child></root>';
            $root = DOMinator::read($html);
            $this->assertEquals($xmlDecl, $root->xmlDeclaration);
            $exported = $root->toHtml();
            $this->assertStringStartsWith($xmlDecl, $exported);
            $this->assertStringContainsString('<child>Text</child>', $exported);
        }
    }

    public function testXmlDeclarationWithDoctype()
    {
        $xmlDecl = '<?xml version="1.0" encoding="utf-8"?>';
        $doctype = '<!DOCTYPE html>';
        $html = $xmlDecl . $doctype . '<html><body>Test</body></html>';
        $root = DOMinator::read($html);
        $this->assertEquals($xmlDecl, $root->xmlDeclaration);
        $this->assertEquals($doctype, $root->doctype);
        $exported = $root->toHtml();
        $this->assertStringStartsWith($xmlDecl, $exported);
        $this->assertStringContainsString($doctype, $exported);
        $this->assertStringContainsString('<body>Test</body>', $exported);
    }

    public function testToHtmlPrettyPrint()
    {
        $html = '<div><span>A</span><span>B</span></div>';
        $root = DOMinator::read($html);
        $pretty = $root->toHtml(false);
        $expected = "<div><span>A</span><span>B</span></div>";
        $this->assertEquals($expected, trim($pretty));
        // Minified output should not have newlines or indentation
        $minified = $root->toHtml(true);
        $this->assertStringNotContainsString("\n", $minified);
        $this->assertStringNotContainsString("    ", $minified);
    }

    public function testToHtmlPrettyPrintWithXmlDeclarationAndDoctype()
    {
        $xmlDecl = '<?xml version="1.0" encoding="utf-8"?>';
        $doctype = '<!DOCTYPE html>';
        $html = $xmlDecl . $doctype . '<html><body><div><span>A</span></div></body></html>';
        $root = DOMinator::read($html);
        $pretty = $root->toHtml(false);
        $expected = $xmlDecl . "\n" . $doctype . "\n<html>\n    <body>\n        <div><span>A</span></div>\n    </body>\n</html>";
        $this->assertEquals($expected, trim($pretty));
    }

    public function testToHtmlPrettyPrintSecond()
    {
        $html = '<button >
    <div>
        <svg viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" alt="">
            <title >
 %%ab066b3292d8ab61ef3b5c77169cdd19%%
 </title>
            <path></path>
            <path></path>
        </svg>
    </div>
</button>';
        $root = DOMinator::read($html);
        $pretty = $root->toInlinedHtml(false);
        $expected = '<button>
    <div>
        <svg viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" alt="">
            <title>%%ab066b3292d8ab61ef3b5c77169cdd19%%</title>
            <path></path>
            <path></path>
        </svg>
    </div>
</button>';
        $this->assertEquals($expected, $pretty);
    }

    public function testToInlinedHtmlSimpleSelectors()
    {
        $html = '<style>div { color: red; } .foo { font-weight: bold; } #bar { text-align: center; }</style>'
            . '<div class="foo" id="bar">A</div><div>B</div>';
        $root = DOMinator::read($html);
        $inlined = $root->toInlinedHtml();
        $this->assertStringNotContainsString('<style>', $inlined);
        $this->assertStringContainsString('<div class="foo" id="bar" style="color: red;font-weight: bold;text-align: center;">A</div>', $inlined);
        $this->assertStringContainsString('<div style="color: red;">B</div>', $inlined);
    }

    public function testToInlinedHtmlPrettyPrint()
    {
        $html = '<style>.x { color: blue; }</style><span class="x">T</span>';
        $root = DOMinator::read($html);
        $pretty = $root->toInlinedHtml(false);
        $expected = "<span class=\"x\" style=\"color: blue;\">T</span>";
        $this->assertEquals($expected, trim($pretty));
        $this->assertStringNotContainsString('<style>', $pretty);
    }

    public function testToInlinedHtmlKeepsUnmatchedAndGlobalRules()
    {
        $html = '<style>div { color: red; } .foo { font-weight: bold; } @media (max-width:600px) { body { background: #fff; } } .unused { color: green; }</style>'
            . '<div class="foo">A</div>';
        $root = DOMinator::read($html);
        $inlined = $root->toInlinedHtml();
        // Inlined rules should be removed
        $this->assertStringNotContainsString('div { color: red; }', $inlined);
        $this->assertStringNotContainsString('.foo { font-weight: bold; }', $inlined);
        // Unmatched and global rules should be kept
        $this->assertStringContainsString('@media (max-width:600px)', $inlined);
        $this->assertStringContainsString('.unused { color: green; }', $inlined);
        $this->assertStringContainsString('<style>', $inlined);
        // The inlined style should be present on the element
        $this->assertStringContainsString('<div class="foo" style="color: red;font-weight: bold;">A</div>', $inlined);
    }

    public function testToInlinedHtmlRemovesStyleTagIfAllRulesInlined()
    {
        $html = '<style>div { color: red; } .foo { font-weight: bold; }</style>'
            . '<div class="foo">A</div><div>B</div>';
        $root = DOMinator::read($html);
        $inlined = $root->toInlinedHtml();
        // All rules are inlined, so <style> should be removed
        $this->assertStringNotContainsString('<style>', $inlined);
        $this->assertStringContainsString('<div class="foo" style="color: red;font-weight: bold;">A</div>', $inlined);
        $this->assertStringContainsString('<div style="color: red;">B</div>', $inlined);
    }

    public function testToInlinedHtmlKeepsStyleTagWithOnlyGlobalRules()
    {
        $html = '<style>@media (max-width:600px) { body { background: #fff; } } @font-face { font-family: test; src: url(test.woff); }</style>'
            . '<div>A</div>';
        $root = DOMinator::read($html);
        $inlined = $root->toInlinedHtml();
        // No rules are inlined, so <style> should be kept as-is
        $this->assertStringContainsString('<style>', $inlined);
        $this->assertStringContainsString('@media (max-width:600px)', $inlined);
        $this->assertStringContainsString('@font-face', $inlined);
        $this->assertStringContainsString('<div>A</div>', $inlined);
    }

    public function testToInlinedHtmlKeepsStyleTagWithUnusedSelector()
    {
        $html = '<style>.unused { color: green; }</style><div>B</div>';
        $root = DOMinator::read($html);
        $inlined = $root->toInlinedHtml();
        // .unused is not matched, so <style> should be kept
        $this->assertStringContainsString('<style>', $inlined);
        $this->assertStringContainsString('.unused { color: green; }', $inlined);
        $this->assertStringContainsString('<div>B</div>', $inlined);
    }

    public function testHTMLCharacterEntities()
    {
        $input = "<p>Special characters: &nbsp; &amp; &lt; &gt; &quot; &apos;</p>";
        $root = DOMinator::read($input);
        $this->assertEquals($input, $root->toHtml());
        $input = "<p>&nbsp;</p>";
        $root = DOMinator::read($input);
        $this->assertEquals($input, $root->toHtml());
    }

    public function testAccentsCharacters()
    {
        $input = "<p>Accents: é è ê ë à â ä î ï ô ö û ü</p>";
        $root = DOMinator::read($input);
        $this->assertEquals($input, $root->toHtml());

        $romanianInput = "<p>Romanian: ă â î ș ț</p>";
        $root = DOMinator::read($romanianInput);
        $this->assertEquals($romanianInput, $root->toHtml());

        $cyrillicInput = "<p>Cyrillic: Привет мир</p>";
        $root = DOMinator::read($cyrillicInput);
        $this->assertEquals($cyrillicInput, $root->toHtml());

        $arabicInput = "<p>Arabic: مرحبا بالعالم</p>";
        $root = DOMinator::read($arabicInput);
        $this->assertEquals($arabicInput, $root->toHtml());

        $chineseInput = "<p>Chinese: 你好，世界</p>";
        $root = DOMinator::read($chineseInput);
        $this->assertEquals($chineseInput, $root->toHtml());

        $japaneseInput = "<p>Japanese: こんにちは世界</p>";
        $root = DOMinator::read($japaneseInput);
        $this->assertEquals($japaneseInput, $root->toHtml());
    }

    public function testComplexAttributes()
    {
        $source = '<div ngcontent-afp-c35="" class="dropdown" @click.prevent="dropdownVisible = !dropdownVisible"></div>';
        $root = DOMinator::read($source);
        $this->assertEquals($source, $root->toHtml());
    }


    public function testTextNodeIsAttributeError()
    {
        $source = '<template x-if="state.currentQuestionIndex >= 0 && state.currentQuestionIndex < questions.length">
        <div class="fixed top-0 z-50 w-screen bg-gray-800/15 dark:bg-zinc-200/10 h-1 rounded-full overflow-hidden">
            <div class="h-full bg-blue-500 transition-all duration-300 ease-out" :style="`width: ${percentage * 100}%;`"
                 :aria-valuenow="percentage * 100" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </template>';
        $textNodes = DOMinator::read($source)->getAllTextNodes();
        $this->assertNotEquals('= 0 && state.currentQuestionIndex ', $textNodes->first()->innerText);
    }

    public function testPreprocessCallbackIsCalled()
    {
        $html = '<div>foo</div>';
        $called = false;
        $cb = function ($input) use (&$called) {
            $called = true;
            return $input;
        };
        DOMinator::read($html, false, $cb);
        $this->assertTrue($called, 'Preprocess callback should be called');
    }

    public function testPreprocessCallbackCanModifyHtml()
    {
        $html = '<div>foo</div>';
        $cb = function ($input) {
            return str_replace('foo', 'bar', $input);
        };
        $root = DOMinator::read($html, false, $cb);
        $this->assertEquals('bar', $root->children->item(0)->children->item(0)->innerText);
    }

    public function testPreprocessCallbackCanReturnEmpty()
    {
        $html = '<div>foo</div>';
        $cb = function ($input) {
            return '';
        };
        $root = DOMinator::read($html, false, $cb);
        $this->assertEmpty($root->children);
    }

    public function testCustomHtml()
    {
        $html = '<p style="font-family: verdana; font-size: 14px; color: #555555; line-height: 21px;font-size: 14px; line-height: 21px;">
                    <b>Compatible Devices:</b>
                    Please refer to this
                    <a href="{{ats_url}}" style="color: #1565C0; text-decoration: none;" target="_blank">information about compatible devices</a>.
                </p>';
        $root = DOMinator::read($html);
        $this->assertEquals($html, $root->toHtml());
    }

    public function testNewLine()
    {
        $html = "<br>";
        $root = DOMinator::read($html);
        $this->assertEquals($html, $root->toHtml());
    }

    public function testQuotes()
    {
        $html = "<div :class=\"'test'\"></div>";
        $root = DOMinator::read($html);
        $this->assertEquals($html, $root->toHtml());
    }

    public function testAdvancedQuotes()
    {
        $html = "<h1 class=\"text-4xl font-bold text-center tracking-wider bg-clip-text text-transparent bg-linear-to-r\" :class=\"{ 'from-green-500 to-teal-800': goodScore, 'from-amber-500 to-yellow-800': averageScore, 'from-rose-500 to-red-800': badScore}\" x-html=\"outro.title\"></h1>";
        $root = DOMinator::read($html);
        $this->assertEquals($html, $root->toHtml());
    }
}
