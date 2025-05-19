# DOMinator

A robust, fast, and fully-featured HTML5 parser and query engine for PHP. Parse, traverse, manipulate, and query HTML documents with ease, supporting all modern HTML5 features, error recovery, namespaces, and more.

## Features

- **Full HTML5 parsing**: Handles all standard HTML5 elements, void/self-closing tags, comments, CDATA, script/style content, and multiple doctype variants.
- **XML declaration support**: Preserves and exports XML declarations (e.g., `<?xml version="1.0" encoding="utf-8"?>`) at the start of the document.
- **Error recovery**: Gracefully parses malformed or broken HTML, just like browsers do.
- **Entity decoding**: Decodes HTML entities in text and attributes.
- **Whitespace normalization**: Optionally normalizes whitespace in text nodes.
- **Namespaces**: Supports XML/HTML namespaces (e.g., `svg:rect`).
- **Attribute handling**: Handles quoted/unquoted and boolean attributes.
- **Query engine**: Powerful CSS-like selectors for finding nodes (call `querySelectorAll`, `querySelector`, or `getElementsByTagName` directly on any `Node`).
- **Node manipulation**: Add, remove, set attributes, change text, and more (`Node`).
- **Performance**: Optimized for large and deeply nested documents.
- **Comprehensive tests**: Includes extensive tests for all features and edge cases.
- **Robust CSS parser**: Parses CSS rules and at-rules (including nested and simple at-rules like `@media` and `@font-face`).
- **CSS inlining**: Optionally inlines CSS styles as `style` attributes when exporting HTML (`Node::toInlinedHtml`).
- **Pretty-print and minify HTML**: Export HTML as minified (default) or pretty-printed (indented, human-readable) with `Node::toHtml(false)`.

## Installation

Install via Composer:

```
composer require daniesy/dominator
```

Or include the `src/` files directly in your project.

## Usage

### Basic Parsing

```php
use Daniesy\DOMinator\DOMinator;

$html = '<?xml version="1.0" encoding="utf-8"?>\n<div id="main"><p>Hello <b>World</b></p></div>';
$root = DOMinator::read($html);
```

### Traversing the DOM

```php
foreach ($root->children as $child) {
    echo $child->tag; // e.g., 'div'
}
```

### Querying with CSS Selectors (DOM-like)

```php
// Find all elements with class="foo"
$nodes = $root->querySelectorAll('.foo');
// Attribute selectors:
// Exact match
$adminNodes = $root->querySelectorAll('[data-role="admin"]');
// Space-separated word match
$adminWordNodes = $root->querySelectorAll('[data-role~="admin"]');
// Substring match
$adminSubstringNodes = $root->querySelectorAll('[data-role*="admin"]');
// Attribute presence
$withPlaceholder = $root->querySelectorAll('[placeholder]');
// Comma-separated (OR) selectors
$iconLinks = $root->querySelectorAll('link[rel="shortcut icon"], link[rel="icon"]');

// Access by index using item() method
echo $nodes->item(0)->innerText;
// Get the number of nodes
echo $nodes->length;
// Iterate over nodes
foreach ($nodes as $node) {
    echo $node->innerText;
}

// Find the first <span> element
$span = $root->querySelector('span');
if ($span) {
    echo $span->innerText;
}

// Find all <div> elements (case-insensitive)
$divs = $root->getElementsByTagName('div');
// Access by index
echo $divs->item(0)->innerText;
// Iterate over nodes
foreach ($divs as $div) {
    echo $div->innerText;
}
```

### Manipulating Nodes

```php
$node = $nodes->item(0);
$node->setAttribute('id', 'new-id');
$node->innerText = 'Updated text';
$node->remove(); // Remove from parent
```

### CSS Parsing and Selector Matching

```php
use Daniesy\DOMinator\CssParser;

$css = '@media (max-width:600px) { body { background: #fff; } }\n@font-face { font-family: test; src: url(test.woff); }\ndiv.foo#bar { color: green; }';
$rules = CssParser::parse($css);
// $rules[0]['type'] === 'at' for @media, $rules[1]['type'] === 'at' for @font-face, $rules[2]['type'] === 'rule' for div.foo#bar

// Selector matching:
use Daniesy\DOMinator\Nodes\Node;
$node = new Node('div', ['class' => 'foo bar', 'id' => 'bar']);
CssParser::matches('div.foo#bar', $node); // true
CssParser::matches('.baz', $node); // false
```

- `CssParser::parse($css)` parses a CSS string into an array of rules and at-rules (including nested and simple at-rules).
- `CssParser::matches($selector, $node)` checks if a node matches a CSS selector (supports tag, class, id, compound, and descendant selectors).

### Exporting Back to HTML

```php
// Minified (default)
$html = $root->toHtml();
// Pretty-printed (indented, human-readable)
$prettyHtml = $root->toHtml(false);
// Inline CSS styles (simple selectors only)
$inlinedHtml = $root->toInlinedHtml();
$prettyInlinedHtml = $root->toInlinedHtml(false);
```

### Handling Namespaces

```php
$html = '<svg:rect width="100" height="100"/>';
$root = DOMinator::read($html);
$svg = $root->children->item(0); // Direct access to children array is still available
echo $svg->namespace; // 'svg'
echo $svg->tag;       // 'rect'

// Alternatively, you can use querySelector
$svg = $root->querySelector('svg\\:rect');
echo $svg->namespace; // 'svg'
echo $svg->tag;       // 'rect'
```

### Parsing Options

- `DOMinator::read($html, $normalizeWhitespace = false)`
  - Supports input with an XML declaration (e.g., `<?xml ...?>`).
- `Node::toHtml($minify = true)`
  - If `$minify` is `false`, outputs pretty-printed HTML with indentation and newlines.
  - If `$minify` is `true` (default), outputs minified HTML.
- `Node::toInlinedHtml($minify = true)`
  - Inlines simple CSS rules from <style> tags as inline `style` attributes.
  - Supports only tag, class, and id selectors (no combinators or advanced selectors).
  - Removes <style> tags from the output.
  - Use `$minify = false` for pretty-printed output.

## API Reference

### `DOMinator`

- `DOMinator::read(string $html, bool $normalizeWhitespace = false): Node`
  - Parses HTML and returns the root node.

### `Node`

- Properties:
  - `tag`: Tag name (e.g., 'div')
  - `namespace`: Namespace prefix (e.g., 'svg')
  - `attributes`: Associative array of attributes
  - `children`: Array of child nodes
  - `innerText`: Text content (for text, comment, or CDATA nodes)
  - `isComment`, `isCdata`: Node type flags
  - `parent`: Parent node
  - `xmlDeclaration`: XML declaration string if present (e.g., `<?xml version="1.0"?>`)
- Methods:
  - `setAttribute($name, $value)`: Set or update attribute
  - `removeAttribute($name)`: Remove attribute
  - `remove()`: Remove node from parent
  - `toHtml()`: Export node and children as HTML
  - `toInlinedHtml($minify = true)`: Export HTML with inlined CSS styles
  - `querySelectorAll($selector)`: Returns a NodeList of matching nodes (CSS selector)
  - `querySelector($selector)`: Returns the first matching node or null
  - `getElementsByTagName($tag)`: Returns a NodeList of nodes with the given tag name (case-insensitive)

### `NodeList`

- Properties:
  - `length`: The number of nodes in the list
- Methods:
  - `item($index)`: Returns the node at the specified index, or null if the index is out of range
  - `count()`: Returns the number of nodes in the list (implements Countable)
  - `getIterator()`: Returns an iterator for the nodes in the list (implements IteratorAggregate)

## Testing

Run all tests with PHPUnit:

```
vendor/bin/phpunit tests
```

## Examples

See `tests/DOMinatorTest.php`, `tests/NodeTest.php`, and `tests/QueryTest.php` for comprehensive usage and edge cases.

## License

MIT License. See LICENSE file.

---

**Author:** Daniesy

Contributions and issues welcome!
