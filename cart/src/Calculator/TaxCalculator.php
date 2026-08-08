<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cart\Calculator;

/**
 * Class TaxCalculator
 * 
 * Strategy pattern implementation for tax calculations.
 * Supports flat rate, percentage, and custom tax rules.
 * 
 * @package Kodhe\Cart\Calculator
 */
class TaxCalculator
{
    /**
     * @var float Default tax rate (percentage)
     */
    private float $taxRate = 0.0;

    /**
     * @var bool Whether tax is included in prices
     */
    private bool $taxIncluded = false;

    /**
     * @var array Tax exemptions by product ID or category
     */
    private array $exemptions = [];

    /**
     * @var callable|null Custom tax calculator callback
     */
    private $customCalculator = null;

    /**
     * Constructor
     *
     * @param float $taxRate Tax rate as percentage (e.g., 10.0 for 10%)
     * @param bool $taxIncluded Whether prices include tax
     */
    public function __construct(float $taxRate = 0.0, bool $taxIncluded = false)
    {
        $this->taxRate = $taxRate;
        $this->taxIncluded = $taxIncluded;
    }

    /**
     * Calculate tax amount for cart
     *
     * @param array $cartItems Cart items
     * @param float $subtotal Cart subtotal
     * @return float Tax amount
     */
    public function calculate(array $cartItems, float $subtotal): float
    {
        if ($this->customCalculator !== null) {
            return call_user_func($this->customCalculator, $cartItems, $subtotal, $this->taxRate);
        }

        // If no taxable items, return 0
        if (!$this->hasTaxableItems($cartItems)) {
            return 0.0;
        }

        if ($this->taxIncluded) {
            // Extract tax from total (tax-inclusive pricing)
            return $subtotal - ($subtotal / (1 + ($this->taxRate / 100)));
        }

        // Calculate tax on subtotal
        return $subtotal * ($this->taxRate / 100);
    }

    /**
     * Calculate tax for a specific item
     *
     * @param array $item Item data
     * @return float Tax amount for this item
     */
    public function calculateItemTax(array $item): float
    {
        if ($this->isExempt($item)) {
            return 0.0;
        }

        $itemSubtotal = ($item['price'] ?? 0) * ($item['qty'] ?? 0);

        if ($this->taxIncluded) {
            return $itemSubtotal - ($itemSubtotal / (1 + ($this->taxRate / 100)));
        }

        return $itemSubtotal * ($this->taxRate / 100);
    }

    /**
     * Check if cart has taxable items
     *
     * @param array $cartItems
     * @return bool
     */
    public function hasTaxableItems(array $cartItems): bool
    {
        foreach ($cartItems as $rowId => $item) {
            if (!is_array($item) || !isset($item['price'], $item['qty'])) {
                continue;
            }

            if (!$this->isExempt($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an item is tax-exempt
     *
     * @param array $item
     * @return bool
     */
    public function isExempt(array $item): bool
    {
        $productId = $item['id'] ?? '';
        $category = $item['category'] ?? null;

        if (in_array($productId, $this->exemptions, true)) {
            return true;
        }

        if ($category !== null && in_array($category, $this->exemptions, true)) {
            return true;
        }

        return false;
    }

    /**
     * Set tax rate
     *
     * @param float $rate
     * @return self
     */
    public function setTaxRate(float $rate): self
    {
        $this->taxRate = $rate;
        return $this;
    }

    /**
     * Get tax rate
     *
     * @return float
     */
    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    /**
     * Set whether prices include tax
     *
     * @param bool $included
     * @return self
     */
    public function setTaxIncluded(bool $included): self
    {
        $this->taxIncluded = $included;
        return $this;
    }

    /**
     * Check if prices include tax
     *
     * @return bool
     */
    public function isTaxIncluded(): bool
    {
        return $this->taxIncluded;
    }

    /**
     * Add tax exemption (product ID or category)
     *
     * @param string $identifier
     * @return self
     */
    public function addExemption(string $identifier): self
    {
        if (!in_array($identifier, $this->exemptions, true)) {
            $this->exemptions[] = $identifier;
        }
        return $this;
    }

    /**
     * Remove tax exemption
     *
     * @param string $identifier
     * @return self
     */
    public function removeExemption(string $identifier): self
    {
        $key = array_search($identifier, $this->exemptions, true);
        if ($key !== false) {
            unset($this->exemptions[$key]);
        }
        return $this;
    }

    /**
     * Set tax exemptions
     *
     * @param array $exemptions
     * @return self
     */
    public function setExemptions(array $exemptions): self
    {
        $this->exemptions = $exemptions;
        return $this;
    }

    /**
     * Set custom tax calculator callback
     * Callback signature: function(array $items, float $subtotal, float $taxRate): float
     *
     * @param callable|null $calculator
     * @return self
     */
    public function setCustomCalculator(?callable $calculator): self
    {
        $this->customCalculator = $calculator;
        return $this;
    }

    /**
     * Get the calculated tax-inclusive price
     *
     * @param float $price Price without tax
     * @return float Price with tax included
     */
    public function getPriceWithTax(float $price): float
    {
        if ($this->taxIncluded) {
            return $price;
        }
        return $price * (1 + ($this->taxRate / 100));
    }

    /**
     * Extract price without tax from tax-inclusive price
     *
     * @param float $price Price with tax included
     * @return float Price without tax
     */
    public function getPriceWithoutTax(float $price): float
    {
        if (!$this->taxIncluded) {
            return $price;
        }
        return $price / (1 + ($this->taxRate / 100));
    }
}
