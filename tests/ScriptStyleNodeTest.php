<?php
use PHPUnit\Framework\TestCase;
use Daniesy\DOMinator\DOMinator;
use Daniesy\DOMinator\Nodes\ScriptNode;
use Daniesy\DOMinator\Nodes\StyleNode;

class ScriptStyleNodeTest extends TestCase {
    public function testScriptNodeCreation() {
        $script = new ScriptNode(['type' => 'text/javascript'], 'console.log("hi");');
        $this->assertEquals('script', $script->tag);
        $this->assertEquals('text/javascript', $script->attributes['type']);
        $this->assertEquals('console.log("hi");', $script->children->item(0)->innerText);
        $this->assertStringContainsString('<script type="text/javascript">console.log("hi");</script>', $script->toHtml());
    }

    public function testStyleNodeCreation() {
        $style = new StyleNode(['media' => 'screen'], 'body { color: red; }');
        $this->assertEquals('style', $style->tag);
        $this->assertEquals('screen', $style->attributes['media']);
        $this->assertEquals('body { color: red; }', $style->children->item(0)->innerText);
        $this->assertStringContainsString('<style media="screen">body { color: red; }</style>', $style->toHtml());
    }

    public function testParseScriptAndStyle() {
        $html = '<div><script type="text/javascript">alert(1);</script><style>h1{font-size:2em;}</style></div>';
        $root = DOMinator::read($html);
        $script = $root->querySelector('script');
        $style = $root->querySelector('style');
        $this->assertInstanceOf(ScriptNode::class, $script);
        $this->assertEquals('alert(1);', $script->innerText);
        $this->assertInstanceOf(StyleNode::class, $style);
        $this->assertEquals('h1{font-size:2em;}', $style->innerText);
    }
}
