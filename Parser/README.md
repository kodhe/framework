# CodeIgniter 3 Parser - Modular Refactoring

Template Parser library untuk CodeIgniter 3 yang di-refactor menjadi modular dengan tetap mempertahankan API asli.

## Struktur

```
Parser/
├── Parser.php                    # Main class (API CI3 compatible)
├── Contracts/
│   ├── ParserInterface.php       # Interface utama parser
│   ├── LexerInterface.php        # Interface tokenizer
│   ├── CompilerInterface.php     # Interface compiler
│   ├── CacheInterface.php        # Interface cache
│   └── TokenInterface.php        # Interface token
├── Lexer/
│   └── TemplateLexer.php         # Tokenizer template
├── Compiler/
│   └── TemplateCompiler.php      # Interpreter pattern compiler
├── Context/
│   └── ParseContext.php          # Context management
├── Factory/
│   └── ParserFactory.php         # Factory + Builder pattern
├── Cache/
│   └── TemplateCache.php         # Lazy compilation cache
├── Support/
│   └── TemplateHelper.php        # Utility functions
├── ValueObjects/
│   └── Token.php                 # Immutable token VO
├── tests/
│   └── ParserTest.php            # PHPUnit test suite
├── composer.json                 # PSR-4 autoloading
└── phpunit.xml                   # PHPUnit configuration
```

## API (100% CI3 Compatible)

```php
// Basic usage
$parser = new \CodeIgniter\Parser\Parser();

// Parse template dengan variable
$template = 'Hello, {name}!';
$data = ['name' => 'World'];
echo $parser->parse($template, $data);

// Parse string (alias)
echo $parser->parse_string($template, $data);

// Custom delimiters
$parser->set_delimiters('[', ']');
```

## Features

### Patterns
- **Interpreter**: TemplateCompiler menginterpretasi tokens
- **Strategy**: Lexer, Compiler, Cache dapat diganti
- **Factory**: ParserFactory untuk membuat instance
- **Builder**: Fluent interface di ParserFactory
- **Dependency Injection**: Constructor injection di Parser

### Performance
- **Cache Template Compile**: Hasil compile di-cache
- **Lazy Compile**: Cache dicek sebelum compile
- **Token Reuse**: Token dapat dibuat ulang dengan efisien

### Testing
- Variable substitution
- Loop constructs
- Nested loops
- Conditional blocks
- Include directives
- Cache behavior
- Custom delimiters

## Installation

```bash
composer install
```

## Running Tests

```bash
vendor/bin/phpunit
```

## Usage Examples

### Variables
```php
$template = '{title} - {content}';
$data = ['title' => 'Hello', 'content' => 'World'];
$parser->parse($template, $data);
// Output: Hello - World
```

### Loops
```php
$template = '{loop items}<li>{items}</li>{/loop}';
$data = ['items' => ['A', 'B', 'C']];
$parser->parse($template, $data);
// Output: <li>A</li><li>B</li><li>C</li>
```

### Nested Loops
```php
$template = '{loop categories}{category}: {loop products}{products}, {/loop}{/loop}';
$data = [
    'categories' => [
        ['category' => 'Fruits', 'products' => ['Apple', 'Banana']],
        ['category' => 'Vegetables', 'products' => ['Carrot']]
    ]
];
```

### Conditionals
```php
$template = '{if show}Visible{/if}';
$data = ['show' => 'yes'];
$parser->parse($template, $data);

// Negation
$template = '{if !hidden}Visible{/if}';
$data = ['hidden' => ''];
```

### Includes
```php
$parser->setViewPaths(['/path/to/views']);
$template = '{include "header.html"}Content{include "footer.html"}';
```

### Custom Delimiters
```php
$parser->set_delimiters('{{', '}}');
$template = '{{greeting}}, {{name}}!';
```

### Factory Pattern
```php
use CodeIgniter\Parser\Factory\ParserFactory;

// Default
$parser = ParserFactory::make();

// With config
$parser = ParserFactory::makeWithConfig([
    'left_delimiter' => '{{',
    'right_delimiter' => '}}',
    'cache_enabled' => false,
    'view_paths' => ['/path/to/views']
]);

// With custom components
$parser = ParserFactory::makeWithComponents(
    new CustomLexer(),
    new CustomCompiler(),
    new CustomCache()
);
```

### Cache Control
```php
$parser->setCacheEnabled(false);
$parser->clearCache();
```

### Dot Notation
```php
$template = '{user.name} - {user.email}';
$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
// Output: John - john@example.com
```

## PSR-4 Autoloading

```json
{
    "autoload": {
        "psr-4": {
            "CodeIgniter\\Parser\\": ""
        }
    }
}
```

## Requirements

- PHP >= 7.2
- PHPUnit >= 8.0 (untuk testing)

## License

MIT
