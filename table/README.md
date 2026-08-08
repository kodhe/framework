# Kodhe Table Library

A modular, maintainable, and testable table generation library following PSR-4/PSR-12 standards.

## Features

- **Modular Architecture**: Separated concerns using Builder, Strategy, Factory, and Value Object patterns
- **Backward Compatible**: 100% compatible with CodeIgniter 3 Table library API
- **Extensible Renderers**: Support for HTML and plain text rendering, easily extendable
- **Dependency Injection**: Clean separation of concerns with injectable dependencies

## Installation

```bash
composer require kodhe/table
```

## Usage

### Basic Usage

```php
use Kodhe\Table\Table;

$table = new Table();

$table->set_heading('Name', 'Email', 'Role');
$table->add_row('John Doe', 'john@example.com', 'Admin');
$table->add_row('Jane Smith', 'jane@example.com', 'User');

echo $table->generate();
```

### With Array Data

```php
$data = [
    ['Name' => 'John Doe', 'Email' => 'john@example.com'],
    ['Name' => 'Jane Smith', 'Email' => 'jane@example.com']
];

echo $table->generate($data);
```

### Custom Template

```php
$template = [
    'table_open' => '<table class="my-table">',
    'heading_cell_start' => '<th class="header">',
    'cell_start' => '<td class="cell">'
];

$table->set_template($template);
```

### Custom Renderer

```php
use Kodhe\Table\Renderers\PlainTextRenderer;

$renderer = new PlainTextRenderer();
$table->setRenderer($renderer);

echo $table->generate();
```

## Structure

```
table/
├── Table.php                 # Main facade class
├── Contracts/
│   ├── TableInterface.php    # Table contract
│   ├── RendererInterface.php # Renderer contract
│   └── TemplateInterface.php # Template contract
├── Builder/
│   ├── HeaderBuilder.php     # Header building logic
│   └── RowBuilder.php        # Row building logic
├── Renderers/
│   ├── HtmlRenderer.php      # HTML output renderer
│   └── PlainTextRenderer.php # Plain text renderer
├── Templates/
│   ├── DefaultTemplate.php   # Default template implementation
│   └── TemplateAdapter.php   # Template adapter for BC
├── Factory/
│   └── RendererFactory.php   # Renderer factory
├── Support/
│   ├── ColumnNormalizer.php  # Column data normalization
│   ├── TableValidator.php    # Table data validation
│   └── TemplateResolver.php  # Template resolution
└── ValueObjects/
    ├── TableCell.php         # Table cell value object
    ├── TableRow.php          # Table row value object
    └── TableDefinition.php   # Table definition value object
```

## Design Patterns Used

- **Builder Pattern**: For constructing complex table structures
- **Strategy Pattern**: For interchangeable rendering strategies
- **Factory Pattern**: For creating renderer instances
- **Value Object Pattern**: For immutable table data representation
- **Dependency Injection**: For clean separation of concerns

## API Methods

All original CodeIgniter 3 Table methods are preserved:

- `set_heading()` - Set table heading
- `set_columns()` - Set table columns
- `add_row()` - Add a table row
- `make_columns()` - Create multi-dimensional array from flat array
- `set_template()` - Set custom template
- `set_empty()` - Set empty cell content
- `set_caption()` - Set table caption
- `clear()` - Clear table data
- `generate()` - Generate table HTML

## License

MIT
