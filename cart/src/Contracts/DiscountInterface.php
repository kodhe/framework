<?php

declare(strict_types=1);

namespace Kodhe\Cart\Contracts;

/**
 * Interface DiscountInterface
 * 
 * Defines the contract for discount calculators.
 * Implementations can handle percentage discounts, fixed amount discounts,
 * buy-X-get-Y deals, coupon codes, etc.
 * 
 * @package Kodhe\Cart\Contracts
 */
interface DiscountInterface
{
    /**
     * Calculate discount amount
     *
     * @param array $cartItems Cart items
     * @param float $subtotal Cart subtotal
     * @return float Discount amount (positive value)
     */
    public function calculate(array $cartItems, float $subtotal): float;

    /**
     * Get discount description/label
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Check if discount is applicable
     *
     * @param array $cartItems Cart items
     * @return bool
     */
    public function isApplicable(array $cartItems): bool;
}
