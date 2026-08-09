<?php

declare(strict_types=0);

namespace Kodhe\Framework\Cart;

use Kodhe\Framework\Cart\Contracts\CartInterface;
use Kodhe\Framework\Cart\Contracts\CartStorageInterface;
use Kodhe\Framework\Cart\Storage\SessionStorage;
use Kodhe\Framework\Cart\Calculator\TaxCalculator;
use Kodhe\Framework\Cart\Calculator\DiscountCalculator;
use Kodhe\Framework\Cart\Calculator\ShippingCalculator;
use Kodhe\Framework\Cart\Models\CartItem;
use Kodhe\Framework\Cart\Models\CartSummary;

/**
 * Shopping Cart Class
 *
 * A flexible shopping cart library that supports:
 * - Multiple items with quantities
 * - Product options (size, color, etc.)
 * - Automatic cart total calculation
 * - Item subtotal calculation
 * - Cart persistence via session
 * - Tax calculations
 * - Discount calculations
 * - Shipping calculations
 * - Multiple storage backends (Session, Database, Memory)
 *
 * @package     Kodhe\Cart
 * @category    Shopping Cart
 * @author      EllisLab Dev Team, Kodhe Team
 * @link        https://codeigniter.com/user_guide/libraries/cart.html
 *
 * @example
 * ```php
 * $cart = new Cart();
 * 
 * // Insert item
 * $cart->insert([
 *     'id' => 'sku_123',
 *     'qty' => 2,
 *     'price' => 29.99,
 *     'name' => 'T-Shirt',
 *     'options' => ['Size' => 'L', 'Color' => 'Blue']
 * ]);
 * 
 * // Get cart total
 * echo $cart->total();
 * 
 * // Display cart contents
 * print_r($cart->contents());
 * 
 * // With tax and discounts
 * $cart->getTaxCalculator()->setTaxRate(10);
 * $cart->getDiscountCalculator()->applyPercentageDiscount('SAVE10', 10);
 * ```
 */
class Cart implements CartInterface
{
    /**
     * Regular expression rules for validating product ID
     * alpha-numeric, dashes, underscores, or periods
     *
     * @var string
     */
    public $product_id_rules = '\.a-z0-9_-';

    /**
     * Regular expression rules for validating product name
     * alpha-numeric, dashes, underscores, colons or periods
     *
     * @var string
     */
    public $product_name_rules = '\w \-\.\:';

    /**
     * Only allow safe product names
     *
     * @var bool
     */
    public $product_name_safe = true;

    /**
     * Reference to CodeIgniter instance
     *
     * @var object
     */
    protected $CI;

    /**
     * Contents of the cart
     *
     * @var array
     */
    protected $_cart_contents = [];

    /**
     * Cart storage handler
     *
     * @var CartStorageInterface
     */
    protected $storage;

    /**
     * Tax calculator
     *
     * @var TaxCalculator
     */
    protected $taxCalculator;

    /**
     * Discount calculator
     *
     * @var DiscountCalculator
     */
    protected $discountCalculator;

    /**
     * Shipping calculator
     *
     * @var ShippingCalculator
     */
    protected $shippingCalculator;

    /**
     * Cache for calculated totals
     *
     * @var CartSummary|null
     */
    protected $totalsCache = null;

    /**
     * Whether totals cache is dirty
     *
     * @var bool
     */
    protected $totalsDirty = true;

    /**
     * Shopping Class Constructor
     *
     * @param array $params Configuration parameters
     * @return void
     */
    public function __construct($params = [])
    {
        $this->CI = kodhe();

        $config = is_array($params) ? $params : [];

        // Initialize storage (default to session for backward compatibility)
        $this->storage = new SessionStorage($this->CI);

        // Load cart data from storage
        $this->_cart_contents = $this->storage->load();
        
        if ($this->_cart_contents === null) {
            // No cart exists so we'll set some base values
            $this->_cart_contents = ['cart_total' => 0, 'total_items' => 0];
        }

        // Initialize calculators
        $this->taxCalculator = new TaxCalculator(
            $config['tax_rate'] ?? 0.0,
            $config['tax_included'] ?? false
        );

        $this->discountCalculator = new DiscountCalculator(
            $config['discounts_stackable'] ?? false
        );

        $this->shippingCalculator = new ShippingCalculator(
            $config['shipping_method'] ?? 'flat',
            $config['shipping_rate'] ?? 0.0
        );

        log_message('info', 'Cart Class Initialized');
    }

    /**
     * Set custom storage handler
     *
     * @param CartStorageInterface $storage
     * @return self
     */
    public function setStorage(CartStorageInterface $storage): self
    {
        $this->storage = $storage;
        $this->_cart_contents = $storage->load() ?? ['cart_total' => 0, 'total_items' => 0];
        $this->markTotalsDirty();
        return $this;
    }

    /**
     * Get current storage handler
     *
     * @return CartStorageInterface
     */
    public function getStorage(): CartStorageInterface
    {
        return $this->storage;
    }

    /**
     * Get tax calculator
     *
     * @return TaxCalculator
     */
    public function getTaxCalculator(): TaxCalculator
    {
        return $this->taxCalculator;
    }

    /**
     * Get discount calculator
     *
     * @return DiscountCalculator
     */
    public function getDiscountCalculator(): DiscountCalculator
    {
        return $this->discountCalculator;
    }

    /**
     * Get shipping calculator
     *
     * @return ShippingCalculator
     */
    public function getShippingCalculator(): ShippingCalculator
    {
        return $this->shippingCalculator;
    }

    /**
     * Insert items into the cart
     *
     * @param array $items Item(s) to insert
     * @return string|bool Row ID on success, FALSE on failure
     */
    public function insert($items = [])
    {
        if (!is_array($items) || count($items) === 0) {
            log_message('error', 'The insert method must be passed an array containing data.');
            return false;
        }

        $save_cart = false;
        
        if (isset($items['id'])) {
            // Single item
            if (($rowid = $this->_insert($items))) {
                $save_cart = true;
            }
        } else {
            // Multiple items
            foreach ($items as $val) {
                if (is_array($val) && isset($val['id'])) {
                    if ($this->_insert($val)) {
                        $save_cart = true;
                    }
                }
            }
        }

        if ($save_cart === true) {
            $this->_save_cart();
            return isset($rowid) ? $rowid : true;
        }

        return false;
    }

    /**
     * Insert a single item (internal)
     *
     * @param array $items Item data
     * @return string|bool Row ID on success, FALSE on failure
     */
    protected function _insert($items = [])
    {
        if (!is_array($items) || count($items) === 0) {
            log_message('error', 'The insert method must be passed an array containing data.');
            return false;
        }

        // Required fields
        if (!isset($items['id'], $items['qty'], $items['price'], $items['name'])) {
            log_message('error', 'The cart array must contain a product ID, quantity, price, and name.');
            return false;
        }

        // Prep quantity
        $items['qty'] = (float) $items['qty'];

        if ($items['qty'] == 0) {
            return false;
        }

        // Validate product ID
        if (!preg_match('/^[' . $this->product_id_rules . ']+$/i', $items['id'])) {
            log_message('error', 'Invalid product ID. The product ID can only contain alpha-numeric characters, dashes, and underscores');
            return false;
        }

        // Validate product name
        if ($this->product_name_safe 
            && !preg_match('/^[' . $this->product_name_rules . ']+$/i' . (UTF8_ENABLED ? 'u' : ''), $items['name'])) {
            log_message('error', 'An invalid name was submitted as the product name: ' . $items['name'] . ' The name can only contain alpha-numeric characters, dashes, underscores, colons, and spaces');
            return false;
        }

        // Prep price
        $items['price'] = (float) $items['price'];

        // Create unique row ID
        if (isset($items['options']) && count($items['options']) > 0) {
            $rowid = md5($items['id'] . serialize($items['options']));
        } else {
            $rowid = md5($items['id']);
        }

        // Merge with existing quantity if item already in cart
        $old_quantity = isset($this->_cart_contents[$rowid]['qty']) 
            ? (int) $this->_cart_contents[$rowid]['qty'] 
            : 0;

        $items['rowid'] = $rowid;
        $items['qty'] += $old_quantity;
        $this->_cart_contents[$rowid] = $items;

        $this->markTotalsDirty();

        return $rowid;
    }

    /**
     * Update the cart
     *
     * @param array $items Item(s) to update
     * @return bool TRUE on success, FALSE on failure
     */
    public function update($items = [])
    {
        if (!is_array($items) || count($items) === 0) {
            return false;
        }

        $save_cart = false;
        
        if (isset($items['rowid'])) {
            // Single item
            if ($this->_update($items) === true) {
                $save_cart = true;
            }
        } else {
            // Multiple items
            foreach ($items as $val) {
                if (is_array($val) && isset($val['rowid'])) {
                    if ($this->_update($val) === true) {
                        $save_cart = true;
                    }
                }
            }
        }

        if ($save_cart === true) {
            $this->_save_cart();
            return true;
        }

        return false;
    }

    /**
     * Update a single item (internal)
     *
     * @param array $items Item data with rowid
     * @return bool TRUE on success, FALSE on failure
     */
    protected function _update($items = [])
    {
        if (!isset($items['rowid'], $this->_cart_contents[$items['rowid']])) {
            return false;
        }

        // Prep quantity
        if (isset($items['qty'])) {
            $items['qty'] = (float) $items['qty'];
            
            if ($items['qty'] == 0) {
                unset($this->_cart_contents[$items['rowid']]);
                $this->markTotalsDirty();
                return true;
            }
        }

        // Find updatable keys
        $keys = array_intersect(
            array_keys($this->_cart_contents[$items['rowid']]), 
            array_keys($items)
        );

        // Validate price if passed
        if (isset($items['price'])) {
            $items['price'] = (float) $items['price'];
        }

        // Product ID & name shouldn't be changed
        foreach (array_diff($keys, ['id', 'name']) as $key) {
            $this->_cart_contents[$items['rowid']][$key] = $items[$key];
        }

        $this->markTotalsDirty();

        return true;
    }

    /**
     * Save the cart array to the storage
     *
     * @return bool TRUE on success, FALSE if cart is empty
     */
    protected function _save_cart()
    {
        // Calculate totals
        $this->_cart_contents['total_items'] = 0;
        $this->_cart_contents['cart_total'] = 0;

        foreach ($this->_cart_contents as $key => $val) {
            if (!is_array($val) || !isset($val['price'], $val['qty'])) {
                continue;
            }

            $this->_cart_contents['cart_total'] += ($val['price'] * $val['qty']);
            $this->_cart_contents['total_items'] += $val['qty'];
            $this->_cart_contents[$key]['subtotal'] = ($val['price'] * $val['qty']);
        }

        // If cart is empty, delete from storage
        if (count($this->_cart_contents) <= 2) {
            $this->storage->delete();
            return false;
        }

        // Save to storage
        $result = $this->storage->save($this->_cart_contents);
        
        // Clear totals cache after save
        $this->recalculateTotals();
        
        return $result;
    }

    /**
     * Get Cart Total (base total without tax, shipping, discounts)
     * For detailed totals, use getTotals()
     *
     * @return float
     */
    public function total()
    {
        return $this->_cart_contents['cart_total'];
    }

    /**
     * Get detailed cart totals including tax, discounts, and shipping
     *
     * @return CartSummary
     */
    public function getTotals(): CartSummary
    {
        if ($this->totalsDirty) {
            $this->recalculateTotals();
        }
        return $this->totalsCache;
    }

    /**
     * Recalculate all cart totals
     *
     * @return void
     */
    protected function recalculateTotals(): void
    {
        $items = $this->contents();
        $subtotal = $this->total();

        // Calculate tax
        $tax = $this->taxCalculator->calculate($items, $subtotal);

        // Calculate discounts
        $discount = $this->discountCalculator->calculate($items, $subtotal);

        // Calculate shipping
        $shipping = $this->shippingCalculator->calculate($items, $subtotal);

        // Calculate grand total
        $total = $subtotal + $tax + $shipping - $discount;

        $this->totalsCache = new CartSummary(
            $this->total_items(),
            $subtotal,
            $tax,
            $discount,
            $shipping,
            $total
        );

        $this->totalsDirty = false;
    }

    /**
     * Mark totals as dirty (needs recalculation)
     *
     * @return void
     */
    protected function markTotalsDirty(): void
    {
        $this->totalsDirty = true;
        $this->totalsCache = null;
    }

    /**
     * Remove Item from cart
     *
     * @param string $rowid Row ID of item to remove
     * @return bool TRUE
     */
    public function remove($rowid)
    {
        unset($this->_cart_contents[$rowid]);
        $this->markTotalsDirty();
        $this->_save_cart();
        return true;
    }

    /**
     * Total Items count
     *
     * @return int
     */
    public function total_items()
    {
        return $this->_cart_contents['total_items'];
    }

    /**
     * Cart Contents
     *
     * @param bool $newest_first Sort order
     * @return array Cart contents without totals
     */
    public function contents($newest_first = false)
    {
        $cart = ($newest_first) 
            ? array_reverse($this->_cart_contents) 
            : $this->_cart_contents;

        // Remove totals so they don't interfere with display
        unset($cart['total_items']);
        unset($cart['cart_total']);

        return $cart;
    }

    /**
     * Get specific cart item
     *
     * @param string $row_id Row ID
     * @return array|bool Item array or FALSE if not found
     */
    public function get_item($row_id)
    {
        if (in_array($row_id, ['total_items', 'cart_total'], true) 
            || !isset($this->_cart_contents[$row_id])) {
            return false;
        }

        return $this->_cart_contents[$row_id];
    }

    /**
     * Check if item has options
     *
     * @param string $row_id Row ID
     * @return bool
     */
    public function has_options($row_id = '')
    {
        return (isset($this->_cart_contents[$row_id]['options']) 
            && count($this->_cart_contents[$row_id]['options']) !== 0);
    }

    /**
     * Get product options
     *
     * @param string $row_id Row ID
     * @return array
     */
    public function product_options($row_id = '')
    {
        return isset($this->_cart_contents[$row_id]['options']) 
            ? $this->_cart_contents[$row_id]['options'] 
            : [];
    }

    /**
     * Format Number
     *
     * @param float $n Number to format
     * @return string Formatted number
     */
    public function format_number($n = '')
    {
        return ($n === '') ? '' : number_format((float) $n, 2, '.', ',');
    }

    /**
     * Destroy the cart
     *
     * @return void
     */
    public function destroy()
    {
        $this->_cart_contents = ['cart_total' => 0, 'total_items' => 0];
        $this->storage->delete();
        $this->markTotalsDirty();
    }

    /**
     * Get cart subtotal (alias for total())
     *
     * @return float
     */
    public function subtotal()
    {
        return $this->total();
    }

    /**
     * Get cart tax amount
     *
     * @return float
     */
    public function getTax(): float
    {
        return $this->getTotals()->getTax();
    }

    /**
     * Get cart discount amount
     *
     * @return float
     */
    public function getDiscount(): float
    {
        return $this->getTotals()->getDiscount();
    }

    /**
     * Get shipping cost
     *
     * @return float
     */
    public function getShipping(): float
    {
        return $this->getTotals()->getShipping();
    }

    /**
     * Get grand total (including tax and shipping, minus discounts)
     *
     * @return float
     */
    public function grandTotal(): float
    {
        return $this->getTotals()->getTotal();
    }

    /**
     * Convert cart to CartItem objects
     *
     * @return CartItem[]
     */
    public function getItems(): array
    {
        $items = [];
        foreach ($this->contents() as $rowId => $itemData) {
            if (is_array($itemData)) {
                $items[] = CartItem::fromArray($itemData);
            }
        }
        return $items;
    }

    /**
     * Get item count (number of distinct items, not total quantity)
     *
     * @return int
     */
    public function itemCount(): int
    {
        return count($this->contents());
    }

    /**
     * Check if cart is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->total_items() == 0;
    }

    /**
     * Clear cart totals cache (useful for testing)
     *
     * @return void
     */
    public function clearTotalsCache(): void
    {
        $this->markTotalsDirty();
    }
}
