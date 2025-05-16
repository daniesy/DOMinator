# DOMinator

A robust, fast, and fully-featured HTML5 parser and query engine for PHP. Parse, traverse, manipulate, and query HTML documents with ease, supporting all modern HTML5 features, error recovery, namespaces, and more.

## Features

- **Full HTML5 parsing**: Handles all standard HTML5 elements, void/self-closing tags, comments, CDATA, script/style content, and multiple doctype variants.
- **Error recovery**: Gracefully parses malformed or broken HTML, just like browsers do.
- **Entity decoding**: Decodes HTML entities in text and attributes.
- **Whitespace normalization**: Optionally normalizes whitespace in text nodes.
- **Namespaces**: Supports XML/HTML namespaces (e.g., `svg:rect`).
- **Attribute handling**: Handles quoted/unquoted and boolean attributes.
- **Query engine**: Powerful CSS-like selectors for finding nodes (call `querySelectorAll`, `querySelector`, or `getElementsByTagName` directly on any `HtmlNode`).
- **Node manipulation**: Add, remove, set attributes, change text, and more (`HtmlNode`).
- **Performance**: Optimized for large and deeply nested documents.
- **Comprehensive tests**: Includes extensive tests for all features and edge cases.

## Installation

Install via Composer:

```
composer require daniesy/dominator
```

Or include the `src/` files directly in your project.

## Usage

### Basic Parsing

```php
use Daniesy\DOMinator\HtmlParser;

$html = '<div id="main"><p>Hello <b>World</b></p></div>';
$root = HtmlParser::parse($html);
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
foreach ($nodes as $node) {
    echo $node->getInnerText();
}

// Find the first <span> element
$span = $root->querySelector('span');
if ($span) {
    echo $span->getInnerText();
}

// Find all <div> elements (case-insensitive)
$divs = $root->getElementsByTagName('div');
foreach ($divs as $div) {
    echo $div->getInnerText();
}
```

### Manipulating Nodes

```php
$node = $nodes[0];
$node->setAttribute('id', 'new-id');
$node->setInnerText('Updated text');
$node->remove(); // Remove from parent
```

### Exporting Back to HTML

```php
$html = $root->toHtml();
```

### Handling Namespaces

```php
$html = '<svg:rect width="100" height="100"/>';
$root = HtmlParser::parse($html);
$svg = $root->children[0];
echo $svg->namespace; // 'svg'
echo $svg->tag;       // 'rect'
```

### Parsing Options

- `HtmlParser::parse($html, $normalizeWhitespace = false)`
  - Set `$normalizeWhitespace` to `true` to collapse whitespace in text nodes.

## API Reference

### `HtmlParser`

- `HtmlParser::parse(string $html, bool $normalizeWhitespace = false): HtmlNode`
  - Parses HTML and returns the root node.

### `HtmlNode`

- Properties:
  - `tag`: Tag name (e.g., 'div')
  - `namespace`: Namespace prefix (e.g., 'svg')
  - `attributes`: Associative array of attributes
  - `children`: Array of child nodes
  - `innerText`: Text content (for text, comment, or CDATA nodes)
  - `isComment`, `isCdata`: Node type flags
  - `parent`: Parent node
- Methods:
  - `getInnerText()`: Get all text content
  - `setInnerText($text)`: Set text content
  - `setAttribute($name, $value)`: Set or update attribute
  - `removeAttribute($name)`: Remove attribute
  - `remove()`: Remove node from parent
  - `toHtml()`: Export node and children as HTML
  - `querySelectorAll($selector)`: Returns array of matching nodes (CSS selector)
  - `querySelector($selector)`: Returns the first matching node or null
  - `getElementsByTagName($tag)`: Returns array of nodes with the given tag name (case-insensitive)

## Testing

Run all tests with PHPUnit:

```
vendor/bin/phpunit tests
```

## Examples

See `tests/HtmlParserTest.php`, `tests/HtmlNodeTest.php`, and `tests/HtmlQueryTest.php` for comprehensive usage and edge cases.

## License

MIT License. See LICENSE file.

---

**Author:** Daniesy

Contributions and issues welcome!
