
## File Test `test_pagination.php`

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kodhe\Library\Pagination\Pagination;

echo "=== Kodhe Pagination Tests ===\n\n";

// Test 1: Basic pagination
echo "Test 1: Basic Pagination\n";
$pagination = new Pagination();
$pagination->initialize([
    'base_url' => 'http://example.com/products/page/',
    'total_rows' => 200,
    'per_page' => 20,
    'cur_page' => 3
]);
$links = $pagination->create_links();
echo $links . "\n";
assert(strpos($links, '<a') !== false, 'Test 1 failed');
echo "✓ Passed\n\n";

// Test 2: Single page (no pagination needed)
echo "Test 2: Single Page (should return empty)\n";
$pagination2 = new Pagination([
    'base_url' => 'http://example.com/',
    'total_rows' => 5,
    'per_page' => 10
]);
$links2 = $pagination2->create_links();
echo "Result: '" . $links2 . "'\n";
assert($links2 === '', 'Test 2 failed');
echo "✓ Passed\n\n";

// Test 3: Custom HTML wrappers
echo "Test 3: Custom HTML Wrappers\n";
$pagination3 = new Pagination([
    'base_url' => 'http://example.com/items/',
    'total_rows' => 100,
    'per_page' => 10,
    'full_tag_open' => '<div class="pagination">',
    'full_tag_close' => '</div>',
    'cur_tag_open' => '<span class="active">',
    'cur_tag_close' => '</span>',
    'num_tag_open' => '<span>',
    'num_tag_close' => '</span>'
]);
$links3 = $pagination3->create_links();
echo $links3 . "\n";
assert(strpos($links3, '<div class="pagination">') !== false, 'Test 3 failed');
echo "✓ Passed\n\n";

// Test 4: Use page numbers
echo "Test 4: Use Page Numbers\n";
$pagination4 = new Pagination([
    'base_url' => 'http://example.com/news/',
    'total_rows' => 50,
    'per_page' => 10,
    'use_page_numbers' => true,
    'cur_page' => 2
]);
$links4 = $pagination4->create_links();
echo $links4 . "\n";
assert(strpos($links4, '<strong>2</strong>') !== false, 'Test 4 failed');
echo "✓ Passed\n\n";

// Test 5: With attributes
echo "Test 5: With HTML Attributes\n";
$pagination5 = new Pagination([
    'base_url' => 'http://example.com/blog/',
    'total_rows' => 150,
    'per_page' => 25,
    'attributes' => [
        'class' => 'page-link',
        'rel' => true
    ]
]);
$links5 = $pagination5->create_links();
echo $links5 . "\n";
assert(strpos($links5, 'page-link') !== false, 'Test 5 failed');
assert(strpos($links5, 'rel=') !== false, 'Test 5 failed');
echo "✓ Passed\n\n";

// Test 6: First/Last links disabled
echo "Test 6: First/Last Links Disabled\n";
$pagination6 = new Pagination([
    'base_url' => 'http://example.com/',
    'total_rows' => 100,
    'per_page' => 10,
    'first_link' => false,
    'last_link' => false
]);
$links6 = $pagination6->create_links();
echo $links6 . "\n";
assert(strpos($links6, 'First') === false, 'Test 6 failed');
assert(strpos($links6, 'Last') === false, 'Test 6 failed');
echo "✓ Passed\n\n";

echo "=== All Tests Passed ===\n";