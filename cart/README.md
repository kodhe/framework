# Kodhe Cart Library

A modular, maintainable, and testable shopping cart library for CodeIgniter 3 with PSR-4 and PSR-12 compliance.

## Features

- **100% Backward Compatible** - All original CodeIgniter 3 Cart API methods preserved
- **PSR-4 Autoloading** - Proper namespace-based autoloading
- **PSR-12 Coding Standards** - Clean, modern PHP code
- **Multiple Storage Backends** - Session, Database, or Memory storage
- **Strategy Pattern** - Extensible tax, discount, and shipping calculators
- **Factory Pattern** - Easy cart instantiation with different configurations
- **Value Objects** - Type-safe CartItem, CartSummary, and CartTotals models
- **Dependency Injection** - Easy to mock and test
- **Performance Optimized** - Lazy calculation caching, optimized session writes

## Installation

```bash
composer require kodhe/cart
```

## Quick Start

```php
use Kodhe\Cart\Cart;

// Create cart (uses session storage by default)
$cart = new Cart();

// Insert item
$cart->insert([
    'id' => 'sku_123',
    'qty' => 2,
    'price' => 29.99,
    'name' => 'T-Shirt',
    'options' => ['Size' => 'L', 'Color' => 'Blue']
]);

// Get cart total
echo $cart->total();

// Display cart contents
print_r($cart->contents());
```

## Advanced Usage

### Tax Calculation

```php
$cart->getTaxCalculator()->setTaxRate(10); // 10% tax
$tax = $cart->getTax();
```

### Discount Calculation

```php
$cart->getDiscountCalculator()
    ->applyPercentageDiscount('SAVE10', 10); // 10% off
    
$discount = $cart->getDiscount();
```

### Shipping Calculation

```php
$cart->getShippingCalculator()
    ->setFlatRate(5.99);
    
$shipping = $cart->getShipping();
```

### Grand Total (with tax, shipping, discounts)

```php
$grandTotal = $cart->grandTotal();
```

### Using Different Storage

```php
use Kodhe\Cart\Factory\CartFactory;

// Memory storage (for testing)
$cart = CartFactory::createForTesting('my_test_cart');

// Database storage (for logged-in users)
$cart = CartFactory::createForUser('user_123');

// Custom storage
use Kodhe\Cart\Storage\SessionStorage;
$storage = new SessionStorage();
$cart = new Cart();
$cart->setStorage($storage);
```

## API Reference

All original CodeIgniter 3 Cart methods are available:

| Method | Description |
|--------|-------------|
| `insert($items)` | Insert single or multiple items |
| `update($items)` | Update item quantity or other properties |
| `remove($rowid)` | Remove an item from cart |
| `destroy()` | Empty the entire cart |
| `total()` | Get cart subtotal |
| `total_items()` | Get total quantity of items |
| `contents($newest_first)` | Get all cart items |
| `product_options($row_id)` | Get options for specific item |
| `has_options($row_id)` | Check if item has options |
| `format_number($n)` | Format number as currency |
| `get_item($row_id)` | Get specific item by row ID |

### New Methods

| Method | Description |
|--------|-------------|
| `getTotals()` | Get CartSummary with detailed breakdown |
| `getTax()` | Get calculated tax amount |
| `getDiscount()` | Get calculated discount amount |
| `getShipping()` | Get calculated shipping cost |
| `grandTotal()` | Get total including tax/shipping minus discounts |
| `getItems()` | Get array of CartItem objects |
| `itemCount()` | Get number of distinct line items |
| `isEmpty()` | Check if cart is empty |
| `getTaxCalculator()` | Access tax calculator |
| `getDiscountCalculator()` | Access discount calculator |
| `getShippingCalculator()` | Access shipping calculator |
| `getStorage()` | Get current storage handler |
| `setStorage($storage)` | Set custom storage handler |

## Project Structure

```
cart/
├── Cart.php                    # Main cart class (backward compatible)
├── Contracts/
│   ├── CartInterface.php       # Cart public API contract
│   ├── CartStorageInterface.php # Storage backend contract
│   └── DiscountInterface.php   # Discount strategy contract
├── Storage/
│   ├── SessionStorage.php      # Session-based storage
│   ├── DatabaseStorage.php     # Database persistence
│   └── MemoryStorage.php       # In-memory storage (testing)
├── Models/
│   ├── CartItem.php            # Single cart item value object
│   ├── CartSummary.php         # Cart totals summary
│   └── CartTotals.php          # Extended totals with line items
├── Calculator/
│   ├── TaxCalculator.php       # Tax calculation strategies
│   ├── DiscountCalculator.php  # Discount calculation strategies
│   └── ShippingCalculator.php  # Shipping calculation strategies
├── Factory/
│   └── CartFactory.php         # Cart instance factory
├── Support/                    # Helper classes
├── ValueObjects/               # Additional value objects
├── Exceptions/                 # Custom exceptions
└── tests/
    └── CartTest.php            # PHPUnit test suite
```

## Design Patterns Used

- **Strategy Pattern** - Tax, discount, and shipping calculators
- **Factory Pattern** - CartFactory for creating configured instances
- **Repository Pattern** - Storage abstraction for cart persistence
- **Value Object** - CartItem, CartSummary, CartTotals
- **Dependency Injection** - Inject storage and calculators

## Running Tests

```bash
cd cart
composer install
vendor/bin/phpunit tests/
```

## License

MIT License
