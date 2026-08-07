# Kodhe Parser

Modular CodeIgniter 3 Parser library with PSR-4 autoloading, maintaining 100% backward compatibility.

## Features

- **100% CI3 Compatible**: Drop-in replacement for CodeIgniter 3 Parser
- **Modular Architecture**: Separated concerns using modern design patterns
- **PSR-4 Autoloading**: Modern PHP standard autoloading
- **Dependency Injection**: Inject custom lexer, compiler, and cache implementations
- **Template Caching**: Built-in caching with lazy compilation
- **Token Reuse**: Optimized token pooling for better performance

## Installation

```bash
composer require kodhe/parser
```

## Quick Start

```php
use Kodhe\Parser\Parser;

$parser = new Parser();

// Simple variable replacement
$template = "Hello, {name}!";
echo $parser->parse_string($template, ['name' => 'World']);
// Output: Hello, World!

// Tag pair (loop)
$template = "<ul>{items}<li>{item}</li>{/items}</ul>";
echo $parser->parse_string($template, [
    'items' => [
        ['item' => 'First'],
        ['item' => 'Second'],
        ['item' => 'Third']
    ]
]);
// Output: <ul><li>First</li><li>Second</li><li>Third</li></ul>
```

## API

### Methods

| Method | Description |
|--------|-------------|
| `parse($template, $data, $return = false)` | Parse a template file |
| `parse_string($template, $data, $return = false)` | Parse a string template |
| `set_delimiters($l = '{', $r = '}')` | Set custom delimiters |

### Additional Methods (Modern Features)

| Method | Description |
|--------|-------------|
| `enableCache()` | Enable template caching |
| `disableCache()` | Disable template caching |
| `clearCache()` | Clear compiled cache |

## Design Patterns Used

- **Interpreter**: Token interpretation for template compilation
- **Strategy**: Swappable lexer and compiler implementations
- **Factory**: ParserFactory for building parser instances
- **Builder**: Fluent interface in ParserFactory
- **Dependency Injection**: Constructor injection for components

## Architecture

```
Parser/
├── Parser.php              # Main class (CI3 compatible)
├── Contracts/              # Interfaces
│   ├── ParserInterface.php
│   ├── LexerInterface.php
│   ├── CompilerInterface.php
│   ├── CacheInterface.php
│   └── TokenInterface.php
├── Lexer/                  # Tokenization
│   └── TemplateLexer.php
├── Compiler/               # Compilation
│   └── TemplateCompiler.php
├── Context/                # Parse context
│   └── ParseContext.php
├── Factory/                # Object creation
│   └── ParserFactory.php
├── Cache/                  # Caching
│   └── TemplateCache.php
├── Support/                # Utilities
│   └── TemplateHelper.php
└── ValueObjects/           # Data structures
    └── Token.php
```

## Advanced Usage

### Dependency Injection

```php
use Kodhe\Parser\Parser;
use Kodhe\Parser\Lexer\TemplateLexer;
use Kodhe\Parser\Compiler\TemplateCompiler;
use Kodhe\Parser\Cache\TemplateCache;

$lexer = new TemplateLexer('{{', '}}');
$compiler = new TemplateCompiler();
$cache = new TemplateCache(true);

$parser = new Parser($lexer, $compiler, $cache);
```

### Using Factory

```php
use Kodhe\Parser\Factory\ParserFactory;

// Default parser
$parser = ParserFactory::create();

// Custom delimiters
$parser = ParserFactory::createWithDelimiters('[', ']');

// Builder pattern
$parser = (new ParserFactory())
    ->setCacheEnabled(true)
    ->setDelimiters('{{', '}}')
    ->build();
```

### Custom Delimiters

```php
$parser = new Parser();
$parser->set_delimiters('{{', '}}');

$template = "Hello, {{name}}!";
echo $parser->parse_string($template, ['name' => 'World']);
```

## Testing

```bash
cd parser
composer install
vendor/bin/phpunit
```

## Performance Features

1. **Lazy Compilation**: Components are initialized only when needed
2. **Token Caching**: Tokens are cached for repeated templates
3. **Compiled Cache**: Final output is cached for identical inputs
4. **Token Pooling**: Common tokens are reused to reduce allocations

## Requirements

- PHP 8.1+
- CodeIgniter 3 (for full integration)

## License

MIT License
