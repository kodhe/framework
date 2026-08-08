<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cart\Calculator;

use Kodhe\Framework\Cart\Contracts\DiscountInterface;

/**
 * Class DiscountCalculator
 * 
 * Strategy pattern implementation for discount calculations.
 * Manages multiple discount strategies and applies them to cart totals.
 * 
 * @package Kodhe\Cart\Calculator
 */
class DiscountCalculator
{
    /**
     * @var array Registered discount strategies
     */
    private array $discounts = [];

    /**
     * @var bool Whether to apply all discounts or just the best one
     */
    private bool $stackable = false;

    /**
     * @var float Maximum total discount amount
     */
    private float $maxDiscount = PHP_FLOAT_MAX;

    /**
     * @var string|null Discount code/coupon applied
     */
    private ?string $appliedCode = null;

    /**
     * Constructor
     *
     * @param bool $stackable Whether discounts can be stacked
     */
    public function __construct(bool $stackable = false)
    {
        $this->stackable = $stackable;
    }

    /**
     * Register a discount strategy
     *
     * @param string $key Unique identifier for this discount
     * @param DiscountInterface $discount
     * @return self
     */
    public function addDiscount(string $key, DiscountInterface $discount): self
    {
        $this->discounts[$key] = $discount;
        return $this;
    }

    /**
     * Remove a discount strategy
     *
     * @param string $key
     * @return self
     */
    public function removeDiscount(string $key): self
    {
        unset($this->discounts[$key]);
        return $this;
    }

    /**
     * Get a specific discount strategy
     *
     * @param string $key
     * @return DiscountInterface|null
     */
    public function getDiscount(string $key): ?DiscountInterface
    {
        return $this->discounts[$key] ?? null;
    }

    /**
     * Calculate total discount for cart
     *
     * @param array $cartItems Cart items
     * @param float $subtotal Cart subtotal
     * @return float Total discount amount
     */
    public function calculate(array $cartItems, float $subtotal): float
    {
        if (empty($this->discounts)) {
            return 0.0;
        }

        $totalDiscount = 0.0;
        $this->appliedCode = null;

        foreach ($this->discounts as $key => $discount) {
            if (!$discount->isApplicable($cartItems)) {
                continue;
            }

            $discountAmount = $discount->calculate($cartItems, $subtotal);
            
            if ($this->stackable) {
                $totalDiscount += $discountAmount;
            } else {
                // Use the better discount
                if ($discountAmount > $totalDiscount) {
                    $totalDiscount = $discountAmount;
                    $this->appliedCode = $key;
                }
            }
        }

        // Apply maximum discount cap
        return min($totalDiscount, $this->maxDiscount);
    }

    /**
     * Apply a percentage discount
     *
     * @param string $code Discount code
     * @param float $percentage Percentage (e.g., 10 for 10%)
     * @param array $conditions Optional conditions (min_amount, applicable_products, etc.)
     * @return self
     */
    public function applyPercentageDiscount(
        string $code,
        float $percentage,
        array $conditions = []
    ): self {
        $this->addDiscount($code, new PercentageDiscount($percentage, $conditions));
        return $this;
    }

    /**
     * Apply a fixed amount discount
     *
     * @param string $code Discount code
     * @param float $amount Fixed discount amount
     * @param array $conditions Optional conditions
     * @return self
     */
    public function applyFixedDiscount(
        string $code,
        float $amount,
        array $conditions = []
    ): self {
        $this->addDiscount($code, new FixedDiscount($amount, $conditions));
        return $this;
    }

    /**
     * Clear all discounts
     *
     * @return self
     */
    public function clearDiscounts(): self
    {
        $this->discounts = [];
        $this->appliedCode = null;
        return $this;
    }

    /**
     * Set whether discounts are stackable
     *
     * @param bool $stackable
     * @return self
     */
    public function setStackable(bool $stackable): self
    {
        $this->stackable = $stackable;
        return $this;
    }

    /**
     * Check if discounts are stackable
     *
     * @return bool
     */
    public function isStackable(): bool
    {
        return $this->stackable;
    }

    /**
     * Set maximum discount amount
     *
     * @param float $max
     * @return self
     */
    public function setMaxDiscount(float $max): self
    {
        $this->maxDiscount = $max;
        return $this;
    }

    /**
     * Get maximum discount amount
     *
     * @return float
     */
    public function getMaxDiscount(): float
    {
        return $this->maxDiscount;
    }

    /**
     * Get the applied discount code (for non-stackable mode)
     *
     * @return string|null
     */
    public function getAppliedCode(): ?string
    {
        return $this->appliedCode;
    }

    /**
     * Get all registered discount codes
     *
     * @return array
     */
    public function getDiscountCodes(): array
    {
        return array_keys($this->discounts);
    }

    /**
     * Check if a discount code exists
     *
     * @param string $code
     * @return bool
     */
    public function hasDiscount(string $code): bool
    {
        return isset($this->discounts[$code]);
    }
}

/**
 * Class PercentageDiscount
 * 
 * Simple percentage-based discount implementation.
 */
class PercentageDiscount implements DiscountInterface
{
    private float $percentage;
    private array $conditions;

    public function __construct(float $percentage, array $conditions = [])
    {
        $this->percentage = $percentage;
        $this->conditions = $conditions;
    }

    public function calculate(array $cartItems, float $subtotal): float
    {
        return $subtotal * ($this->percentage / 100);
    }

    public function getDescription(): string
    {
        return "{$this->percentage}% Discount";
    }

    public function isApplicable(array $cartItems): bool
    {
        // Check minimum amount
        if (isset($this->conditions['min_amount'])) {
            $subtotal = $this->calculateSubtotal($cartItems);
            if ($subtotal < $this->conditions['min_amount']) {
                return false;
            }
        }

        // Check applicable products
        if (isset($this->conditions['products']) && is_array($this->conditions['products'])) {
            foreach ($cartItems as $item) {
                if (in_array($item['id'] ?? '', $this->conditions['products'], true)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    private function calculateSubtotal(array $cartItems): float
    {
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            if (is_array($item) && isset($item['price'], $item['qty'])) {
                $subtotal += $item['price'] * $item['qty'];
            }
        }
        return $subtotal;
    }
}

/**
 * Class FixedDiscount
 * 
 * Fixed amount discount implementation.
 */
class FixedDiscount implements DiscountInterface
{
    private float $amount;
    private array $conditions;

    public function __construct(float $amount, array $conditions = [])
    {
        $this->amount = $amount;
        $this->conditions = $conditions;
    }

    public function calculate(array $cartItems, float $subtotal): float
    {
        // Don't discount more than the subtotal
        return min($this->amount, $subtotal);
    }

    public function getDescription(): string
    {
        return '$' . number_format($this->amount, 2) . ' Off';
    }

    public function isApplicable(array $cartItems): bool
    {
        // Check minimum amount
        if (isset($this->conditions['min_amount'])) {
            $subtotal = 0.0;
            foreach ($cartItems as $item) {
                if (is_array($item) && isset($item['price'], $item['qty'])) {
                    $subtotal += $item['price'] * $item['qty'];
                }
            }
            if ($subtotal < $this->conditions['min_amount']) {
                return false;
            }
        }

        return true;
    }
}
