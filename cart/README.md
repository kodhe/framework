
## File Test `test_cart.php`

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kodhe\Library\Cart\Cart;

echo "=== Kodhe Cart Tests ===\n\n";

// Test 1: Basic insert
echo "Test 1: Insert Single Item\n";
$cart = new Cart();
$cart->destroy(); // Start fresh

$rowid = $cart->insert([
    'id' => 'sku_123',
    'qty' => 2,
    'price' => 29.99,
    'name' => 'T-Shirt'
]);
echo "Row ID: {$rowid}\n";
echo "Total items: " . $cart->total_items() . "\n";
echo "Cart total: $" . $cart->format_number($cart->total()) . "\n";
assert($cart->total_items() === 2, 'Item count should be 2');
assert($cart->total() === 59.98, 'Total should be 59.98');
echo "✓ Passed\n\n";

// Test 2: Insert item with options
echo "Test 2: Insert Item with Options\n";
$rowid2 = $cart->insert([
    'id' => 'sku_456',
    'qty' => 1,
    'price' => 49.99,
    'name' => 'Hoodie',
    'options' => ['Size' => 'XL', 'Color' => 'Black']
]);
echo "Row ID: {$rowid2}\n";
echo "Has options: " . ($cart->has_options($rowid2) ? 'Yes' : 'No') . "\n";
$options = $cart->product_options($rowid2);
echo "Options: " . json_encode($options) . "\n";
assert($cart->has_options($rowid2) === true, 'Should have options');
echo "✓ Passed\n\n";

// Test 3: Insert multiple items
echo "Test 3: Insert Multiple Items\n";
$cart3 = new Cart();
$cart3->destroy();

$cart3->insert([
    ['id' => 'a1', 'qty' => 2, 'price' => 10.00, 'name' => 'Item A'],
    ['id' => 'b2', 'qty' => 3, 'price' => 20.00, 'name' => 'Item B'],
    ['id' => 'c3', 'qty' => 1, 'price' => 30.00, 'name' => 'Item C']
]);
echo "Total items: " . $cart3->total_items() . "\n";
echo "Cart total: $" . $cart3->format_number($cart3->total()) . "\n";
assert($cart3->total_items() === 6, 'Should have 6 items');
assert($cart3->total() === 110.00, 'Total should be 110.00');
echo "✓ Passed\n\n";

// Test 4: Update quantity
echo "Test 4: Update Quantity\n";
$items = $cart3->contents();
$rowid = key($items); // Get first item's rowid
$cart3->update(['rowid' => $rowid, 'qty' => 5]);
$updated_item = $cart3->get_item($rowid);
echo "Updated qty: " . $updated_item['qty'] . "\n";
echo "Updated subtotal: $" . $cart3->format_number($updated_item['subtotal']) . "\n";
assert($updated_item['qty'] === 5.0, 'Quantity should be 5');
echo "✓ Passed\n\n";

// Test 5: Remove item
echo "Test 5: Remove Item\n";
$before_count = $cart3->total_items();
$cart3->remove($rowid);
$after_count = $cart3->total_items();
$removed = $cart3->get_item($rowid);
echo "Before: {$before_count}, After: {$after_count}\n";
echo "Removed item exists: " . ($removed ? 'Yes' : 'No') . "\n";
assert($removed === false, 'Removed item should not exist');
assert($after_count < $before_count, 'Count should decrease');
echo "✓ Passed\n\n";

// Test 6: Destroy cart
echo "Test 6: Destroy Cart\n";
$cart3->destroy();
echo "Items after destroy: " . $cart3->total_items() . "\n";
echo "Total after destroy: $" . $cart3->format_number($cart3->total()) . "\n";
assert($cart3->total_items() === 0, 'Cart should be empty');
assert($cart3->total() === 0, 'Total should be 0');
echo "✓ Passed\n\n";

// Test 7: Validation
echo "Test 7: Input Validation\n";
$result = $cart->insert(['invalid' => 'data']);
echo "Invalid insert result: " . var_export($result, true) . "\n";
assert($result === false, 'Should reject invalid data');
echo "✓ Passed\n\n";

echo "=== All Tests Passed ===\n";