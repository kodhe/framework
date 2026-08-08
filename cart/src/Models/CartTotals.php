<?php

declare(strict_types=0);

namespace Kodhe\Framework\Cart\Models;

/**
 * Class CartTotals
 * 
 * Value object for detailed cart totals breakdown.
 * Extends CartSummary with per-item subtotals and line items.
 * 
 * @package Kodhe\Cart\Models
 */
class CartTotals extends CartSummary
{
    /**
     * @var array Line items with their subtotals
     */
    private array $lineItems;

    /**
     * @var float Total savings from discounts
     */
    private float $savings;

    /**
     * Constructor
     *
     * @param array $lineItems
     * @param int $totalItems
     * @param float $subtotal
     * @param float $tax
     * @param float $taxRate
     * @param float $discount
     * @param float $shipping
     * @param float $total
     */
    public function __construct(
        array $lineItems = [],
        int $totalItems = 0,
        float $subtotal = 0.0,
        float $tax = 0.0,
        private float $taxRate = 0.0,
        float $discount = 0.0,
        float $shipping = 0.0,
        float $total = 0.0,
        float $savings = 0.0
    ) {
        parent::__construct($totalItems, $subtotal, $tax, $discount, $shipping, $total);
        $this->lineItems = $lineItems;
        $this->savings = $savings;
    }

    /**
     * Get line items
     *
     * @return array
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    /**
     * Set line items
     *
     * @param array $lineItems
     * @return self
     */
    public function setLineItems(array $lineItems): self
    {
        $this->lineItems = $lineItems;
        return $this;
    }

    /**
     * Add a line item
     *
     * @param string $rowId
     * @param float $subtotal
     * @return self
     */
    public function addLineItem(string $rowId, float $subtotal): self
    {
        $this->lineItems[$rowId] = $subtotal;
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
     * Set tax rate
     *
     * @param float $taxRate
     * @return self
     */
    public function setTaxRate(float $taxRate): self
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    /**
     * Get total savings
     *
     * @return float
     */
    public function getSavings(): float
    {
        return $this->savings;
    }

    /**
     * Set savings
     *
     * @param float $savings
     * @return self
     */
    public function setSavings(float $savings): self
    {
        $this->savings = $savings;
        return $this;
    }

    /**
     * Calculate tax from subtotal and tax rate
     *
     * @return self
     */
    public function calculateTax(): self
    {
        $this->tax = $this->getSubtotal() * ($this->taxRate / 100);
        return $this;
    }

    /**
     * Recalculate all totals from line items
     *
     * @return self
     */
    public function recalculateFromLineItems(): self
    {
        $this->subtotal = array_sum($this->lineItems);
        $this->calculateTax();
        $this->recalculateTotal();
        return $this;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'line_items' => $this->lineItems,
            'tax_rate' => $this->taxRate,
            'savings' => $this->savings,
        ]);
    }
}
