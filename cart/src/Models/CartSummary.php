<?php

declare(strict_types=0);

namespace Kodhe\Framework\Cart\Models;

/**
 * Class CartSummary
 * 
 * Value object containing cart summary information.
 * 
 * @package Kodhe\Cart\Models
 */
class CartSummary
{
    /**
     * @var int Total number of items
     */
    private int $totalItems;

    /**
     * @var float Subtotal before tax, discounts, and shipping
     */
    private float $subtotal;

    /**
     * @var float Tax amount
     */
    private float $tax;

    /**
     * @var float Discount amount
     */
    private float $discount;

    /**
     * @var float Shipping cost
     */
    private float $shipping;

    /**
     * @var float Grand total
     */
    private float $total;

    /**
     * Constructor
     *
     * @param int $totalItems
     * @param float $subtotal
     * @param float $tax
     * @param float $discount
     * @param float $shipping
     * @param float $total
     */
    public function __construct(
        int $totalItems = 0,
        float $subtotal = 0.0,
        float $tax = 0.0,
        float $discount = 0.0,
        float $shipping = 0.0,
        float $total = 0.0
    ) {
        $this->totalItems = $totalItems;
        $this->subtotal = $subtotal;
        $this->tax = $tax;
        $this->discount = $discount;
        $this->shipping = $shipping;
        $this->total = $total;
    }

    /**
     * Get total items count
     *
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Get subtotal
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    /**
     * Get tax amount
     *
     * @return float
     */
    public function getTax(): float
    {
        return $this->tax;
    }

    /**
     * Get discount amount
     *
     * @return float
     */
    public function getDiscount(): float
    {
        return $this->discount;
    }

    /**
     * Get shipping cost
     *
     * @return float
     */
    public function getShipping(): float
    {
        return $this->shipping;
    }

    /**
     * Get grand total
     *
     * @return float
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * Set total items
     *
     * @param int $totalItems
     * @return self
     */
    public function setTotalItems(int $totalItems): self
    {
        $this->totalItems = $totalItems;
        return $this;
    }

    /**
     * Set subtotal
     *
     * @param float $subtotal
     * @return self
     */
    public function setSubtotal(float $subtotal): self
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    /**
     * Set tax
     *
     * @param float $tax
     * @return self
     */
    public function setTax(float $tax): self
    {
        $this->tax = $tax;
        return $this;
    }

    /**
     * Set discount
     *
     * @param float $discount
     * @return self
     */
    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    /**
     * Set shipping
     *
     * @param float $shipping
     * @return self
     */
    public function setShipping(float $shipping): self
    {
        $this->shipping = $shipping;
        return $this;
    }

    /**
     * Set total
     *
     * @param float $total
     * @return self
     */
    public function setTotal(float $total): self
    {
        $this->total = $total;
        return $this;
    }

    /**
     * Recalculate total from components
     *
     * @return self
     */
    public function recalculateTotal(): self
    {
        $this->total = $this->subtotal + $this->tax + $this->shipping - $this->discount;
        return $this;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'total_items' => $this->totalItems,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'total' => $this->total,
        ];
    }
}
