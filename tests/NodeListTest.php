<?php
use PHPUnit\Framework\TestCase;
use Daniesy\DOMinator\DOMinator;
use Daniesy\DOMinator\NodeList;

class NodeListTest extends TestCase {
    private $html;
    private $root;

    protected function setUp(): void {
        $this->html = '<div><span>A</span><span>B</span><p>C</p></div>';
        $this->root = DOMinator::parse($this->html);
    }

    public function testNodeListLength() {
        $spans = $this->root->querySelectorAll('span');
        $this->assertInstanceOf(NodeList::class, $spans);
        $this->assertEquals(2, $spans->length);
    }

    public function testNodeListItem() {
        $spans = $this->root->querySelectorAll('span');
        $this->assertEquals('A', $spans->item(0)->getInnerText());
        $this->assertEquals('B', $spans->item(1)->getInnerText());
        $this->assertNull($spans->item(2)); // Out of bounds
    }

    public function testNodeListCount() {
        $spans = $this->root->querySelectorAll('span');
        $this->assertEquals(2, count($spans));
    }

    public function testNodeListIteration() {
        $spans = $this->root->querySelectorAll('span');
        $texts = [];
        foreach ($spans as $span) {
            $texts[] = $span->getInnerText();
        }
        $this->assertEquals(['A', 'B'], $texts);
    }

    public function testEmptyNodeList() {
        $nonExistent = $this->root->querySelectorAll('.non-existent');
        $this->assertInstanceOf(NodeList::class, $nonExistent);
        $this->assertEquals(0, $nonExistent->length);
        $this->assertNull($nonExistent->item(0));
    }

    public function testGetElementsByTagNameReturnsNodeList() {
        $spans = $this->root->getElementsByTagName('span');
        $this->assertInstanceOf(NodeList::class, $spans);
        $this->assertEquals(2, $spans->length);
        $this->assertEquals('A', $spans->item(0)->getInnerText());
        $this->assertEquals('B', $spans->item(1)->getInnerText());
    }
}