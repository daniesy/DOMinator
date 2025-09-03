# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

DOMinator is a PHP HTML5 parser and DOM manipulation library. It provides a robust, fast HTML parser with CSS selector support, node manipulation capabilities, and CSS inlining features.

## Development Commands

### Testing
```bash
# Run all tests
vendor/bin/phpunit tests

# Alternative using composer script
composer test
```

### Dependencies
```bash
# Install dependencies
composer install

# Validate composer configuration
composer validate --strict
```

## Architecture

### Core Components

- **DOMinator** (`src/DOMinator.php`): Main parser class with static `read()` method
- **Node Classes** (`src/Nodes/`):
  - `Node.php`: Base DOM node with manipulation and querying capabilities
  - `TextNode.php`: Text content nodes
  - `CommentNode.php`: HTML comment nodes  
  - `ScriptNode.php`: Script tag nodes with raw content preservation
  - `StyleNode.php`: Style tag nodes with CSS content
- **CssParser** (`src/CssParser.php`): CSS parsing and selector matching
- **NodeList** (`src/NodeList.php`): Collection of nodes with DOM-like interface

### Traits Structure

- **HandlesAttributes** (`src/Traits/`): Attribute manipulation methods
- **ModifiesNode**: Node modification and manipulation functionality
- **QueriesNodes**: CSS selector querying capabilities (querySelector, querySelectorAll)

### Key Features

- HTML5 parsing with error recovery
- CSS selector support (querySelector/querySelectorAll)
- XML namespace support
- CSS inlining capabilities
- Pretty-printing and minification
- Script/style content preservation
- Entity decoding

### Parser Design

The parser uses a stack-based approach to handle nested elements and supports:
- Void elements (self-closing tags)
- Auto-closing tags (li, p, td, etc.)
- Raw content handling for script/style tags
- XML declarations and doctypes
- Malformed HTML recovery

### Testing Structure

Tests are organized by component:
- `DOMinatorTest.php`: Main parser functionality
- `NodeTest.php`: Node manipulation and querying
- `QueryTest.php`: CSS selector functionality
- `CssParserTest.php`: CSS parsing features
- `NodeListTest.php`: NodeList collection behavior
- `ScriptStyleNodeTest.php`: Special node types

## PHP Requirements

- PHP 8.4+ required
- PSR-4 autoloading with namespace `Daniesy\DOMinator\`
- PHPUnit 10 for testing