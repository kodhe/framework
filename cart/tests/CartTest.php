<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cart\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Cart\Cart;
use Kodhe\Framework\Cart\Storage\MemoryStorage;
use Kodhe\Framework\Cart\Factory\CartFactory;

/**
 * Cart Test Suite
 * 
 * Tests for the refactored Cart library ensuring:
 * - Backward compatibility with CodeIgniter 3 API
 * - All storage implementations work correctly
 * - Tax, discount, and shipping calculations
 * - Product options handling
 */
class CartTest extends TestCase
{
    /**
     * @var Cart
     */
    private $cart;

    protected function setUp(): void
    {
        // Create cart with memory storage for test isolation
        $this->cart = CartFactory::createForTesting('test_' . uniqid());
    }

    protected function tearDown(): void
    {
        // Clean up memory storage
        \Kodhe\Framework\Cart\Storage\MemoryStorage::clearAll();
    }

    public function testInsertSingleItem(): void
    {
        $item = [
            'id' => 'sku_123',
            'qty' => 2,
            'price' => 29.99,
            'name' => 'T-Shirt'
        ];

        $rowId = $this->cart->insert($item);

        $this->assertNotFalse($rowId);
        $this->assertEquals(1, $this->cart->total_items());
        $this->assertEquals(59.98, $this->cart->total());
    }

    public function testInsertMultipleItems(): void
    {
        $items = [
            [
                'id' => 'sku_123',
                'qty' => 2,
                'price' => 29.99,
                'name' => 'T-Shirt'
            ],
            [
                'id' => 'sku_456',
                'qty' => 1,
                'price' => 49.99,
                'name' => 'Jeans'
            ]
        ];

        $result = $this->cart->insert($items);

        $this->assertTrue($result);
        $this->assertEquals(3, $this->cart->total_items());
        $this->assertEquals(109.97, $this->cart->total());
    }

    public function testUpdateQuantity(): void
    {
        $item = [
            'id' => 'sku_123',
            'qty' => 2,
            'price' => 10.00,
            'name' => 'Product'
        ];

        $rowId = $this->cart->insert($item);
        $this->assertNotFalse($rowId);

        // Update quantity
        $updateResult = $this->cart->update([
            'rowid' => $rowId,
            'qty' => 5
        ]);

        $this->assertTrue($updateResult);
        
        $item = $this->cart->get_item($rowId);
        $this->assertEquals(5, $item['qty']);
        $this->assertEquals(50.00, $this->cart->total());
    }

    public function testRemoveItem(): void
    {
        $item = [
            'id' => 'sku_123',
            'qty' => 1,
            'price' => 10.00,
            'name' => 'Product'
        ];

        $this->cart->insert($item);
        $contents = $this->cart->contents();
        $rowId = key($contents);

        $result = $this->cart->remove($rowId);

        $this->assertTrue($result);
        $this->assertEquals(0, $this->cart->total_items());
    }

    public function testDestroyCart(): void
    {
        $this->cart->insert([
            'id' => 'sku_123',
            'qty' => 2,
            'price' => 10.00,
            'name' => 'Product'
        ]);

        $this->cart->destroy();

        $this->assertEquals(0, $this->cart->total_items());
        $this->assertEquals(0, $this->cart->total());
        $this->assertTrue($this->cart->isEmpty());
    }

    public function testCartTotal(): void
    {
        $this->cart->insert([
            ['id' => 'sku_1', 'qty' => 2, 'price' => 10.00, 'name' => 'A'],
            ['id' => 'sku_2', 'qty' => 3, 'price' => 20.00, 'name' => 'B']
        ]);

        // 2*10 + 3*20 = 20 + 60 = 80
        $this->assertEquals(80.00, $this->cart->total());
    }

    public function testTaxCalculation(): void
    {
        $this->cart->insert([
            'id' => 'sku_123',
            'qty' => 1,
            'price' => 100.00,
            'name' => 'Product'
        ]);

        $this->cart->getTaxCalculator()->setTaxRate(10);

        $totals = $this->cart->getTotals();
        
        $this->assertEquals(100.00, $totals->getSubtotal());
        $this->assertEquals(10.00, $totals->getTax());
    }

    public function testDiscountCalculation(): void
    {
        $this->cart->insert([
            'id' => 'sku_123',
            'qty' => 1,
            'price' => 100.00,
            'name' => 'Product'
        ]);

        $this->cart->getDiscountCalculator()
            ->applyPercentageDiscount('SAVE10', 10);

        $totals = $this->cart->getTotals();
        
        $this->assertEquals(100.00, $totals->getSubtotal());
        $this->assertEquals(10.00, $totals->getDiscount());
    }

    public function testProductOptions(): void
    {
        $item = [
            'id' => 'sku_123',
            'qty' => 1,
            'price' => 29.99,
            'name' => 'T-Shirt',
            'options' => ['Size' => 'L', 'Color' => 'Blue']
        ];

        $rowId = $this->cart->insert($item);

        $this->assertTrue($this->cart->has_options($rowId));
        
        $options = $this->cart->product_options($rowId);
        $this->assertEquals('L', $options['Size']);
        $this->assertEquals('Blue', $options['Color']);
    }

    public function testSessionStorage(): void
    {
        // Note: This test requires a working CodeIgniter session
        // In real CI3 environment, this would use actual sessions
        $this->markTestSkipped('Requires CodeIgniter session setup');
    }

    public function testDatabaseStorage(): void
    {
        // Note: This test requires database connection
        $this->markTestSkipped('Requires database setup');
    }

    public function testFormatNumber(): void
    {
        $this->assertEquals('1,234.56', $this->cart->format_number(1234.56));
        $this->assertEquals('0.00', $this->cart->format_number(0));
        $this->assertEquals('', $this->cart->format_number(''));
    }

    public function testContentsReturnsArrayWithoutTotals(): void
    {
        $this->cart->insert([
            'id' => 'sku_123',
            'qty' => 1,
            'price' => 10.00,
            'name' => 'Product'
        ]);

        $contents = $this->cart->contents();

        $this->assertIsArray($contents);
        $this->assertArrayNotHasKey('total_items', $contents);
        $this->assertArrayNotHasKey('cart_total', $contents);
    }

    public function testGetItemReturnsFalseForNonExistentRowId(): void
    {
        $result = $this->cart->get_item('nonexistent');
        $this->assertFalse($result);
    }

    public function testGrandTotalWithTaxAndShipping(): void
    {
        $this->cart->insert([
            'id' => 'sku_123',
            'qty' => 1,
            'price' => 100.00,
            'name' => 'Product'
        ]);

        $this->cart->getTaxCalculator()->setTaxRate(10);
        $this->cart->getShippingCalculator()->setFlatRate(5.00);

        $grandTotal = $this->cart->grandTotal();
        
        // 100 + 10 (tax) + 5 (shipping) = 115
        $this->assertEquals(115.00, $grandTotal);
    }

    public function testEmptyCart(): void
    {
        $this->assertTrue($this->cart->isEmpty());
        $this->assertEquals(0, $this->cart->total_items());
        $this->assertEquals([], $this->cart->contents());
    }

    public function testItemCountVsTotalItems(): void
    {
        $this->cart->insert([
            ['id' => 'sku_1', 'qty' => 2, 'price' => 10.00, 'name' => 'A'],
            ['id' => 'sku_2', 'qty' => 3, 'price' => 20.00, 'name' => 'B']
        ]);

        // itemCount returns number of distinct line items
        $this->assertEquals(2, $this->cart->itemCount());
        
        // total_items returns sum of quantities
        $this->assertEquals(5, $this->cart->total_items());
    }
}
