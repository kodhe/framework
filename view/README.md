
## File Test `test_parser.php`

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kodhe\Library\Parser\Parser;

echo "=== Kodhe Parser Tests ===\n\n";

$parser = new Parser();

// Test 1: Simple variable replacement
echo "Test 1: Simple Variable Replacement\n";
$template = "Hello, {name}! Welcome to {site}.";
$data = ['name' => 'John', 'site' => 'MyApp'];
$result = $parser->parse_string($template, $data, true);
echo "Template: {$template}\n";
echo "Result: {$result}\n\n";
assert($result === 'Hello, John! Welcome to MyApp.', 'Test 1 failed');
echo "✓ Passed\n\n";

// Test 2: Tag pair (loop)
echo "Test 2: Tag Pair Loop\n";
$template = "<ul>{items}<li>{item}</li>{/items}</ul>";
$data = [
    'items' => [
        ['item' => 'First'],
        ['item' => 'Second'],
        ['item' => 'Third']
    ]
];
$result = $parser->parse_string($template, $data, true);
echo "Template: {$template}\n";
echo "Result: {$result}\n\n";
assert($result === '<ul><li>First</li><li>Second</li><li>Third</li></ul>', 'Test 2 failed');
echo "✓ Passed\n\n";

// Test 3: Nested tag pairs
echo "Test 3: Nested Tag Pairs\n";
$template = "{menu}{items}<li>{item}</li>{/items}{/menu}";
$data = [
    'menu' => [
        [
            'items' => [
                ['item' => 'Home'],
                ['item' => 'About']
            ]
        ],
        [
            'items' => [
                ['item' => 'Contact'],
                ['item' => 'Help']
            ]
        ]
    ]
];
$result = $parser->parse_string($template, $data, true);
echo "Template: {$template}\n";
echo "Result: {$result}\n\n";
assert(str_contains($result, '<li>Home</li>'), 'Test 3 failed');
assert(str_contains($result, '<li>Contact</li>'), 'Test 3 failed');
echo "✓ Passed\n\n";

// Test 4: Custom delimiters
echo "Test 4: Custom Delimiters\n";
$parser->set_delimiters('{{', '}}');
$template = "Hello, {{name}}!";
$result = $parser->parse_string($template, ['name' => 'World'], true);
echo "Template: {$template}\n";
echo "Result: {$result}\n\n";
assert($result === 'Hello, World!', 'Test 4 failed');
echo "✓ Passed\n\n";

// Test 5: Empty template
echo "Test 5: Empty Template\n";
$result = $parser->parse_string('', [], true);
echo "Result: " . var_export($result, true) . "\n\n";
assert($result === false, 'Test 5 failed');
echo "✓ Passed\n\n";

// Test 6: Multiple variables
echo "Test 6: Multiple Variables\n";
$parser->set_delimiters('{', '}'); // Reset to default
$template = "{greeting}, {name}! You have {count} new messages.";
$data = [
    'greeting' => 'Good morning',
    'name' => 'Alice',
    'count' => 5
];
$result = $parser->parse_string($template, $data, true);
echo "Template: {$template}\n";
echo "Result: {$result}\n\n";
assert($result === 'Good morning, Alice! You have 5 new messages.', 'Test 6 failed');
echo "✓ Passed\n\n";

echo "=== All Tests Passed ===\n";