<?php

declare(strict_types=0);

namespace Kodhe\Framework\Cart\Calculator;

/**
 * Class ShippingCalculator
 * 
 * Strategy pattern implementation for shipping cost calculations.
 * Supports flat rate, weight-based, price-based, and custom shipping rules.
 * 
 * @package Kodhe\Cart\Calculator
 */
class ShippingCalculator
{
    /**
     * @var string Current shipping method
     */
    private string $method = 'flat';

    /**
     * @var float Flat rate shipping cost
     */
    private float $flatRate = 0.0;

    /**
     * @var array Weight-based shipping rates
     */
    private array $weightRates = [];

    /**
     * @var array Price-based shipping rates
     */
    private array $priceRates = [];

    /**
     * @var array Free shipping thresholds
     */
    private array $freeShippingThresholds = [];

    /**
     * @var callable|null Custom shipping calculator
     */
    private $customCalculator = null;

    /**
     * @var bool Whether free shipping is available
     */
    private bool $freeShippingAvailable = false;

    /**
     * Constructor
     *
     * @param string $method Default shipping method
     * @param float $flatRate Default flat rate
     */
    public function __construct(string $method = 'flat', float $flatRate = 0.0)
    {
        $this->method = $method;
        $this->flatRate = $flatRate;
    }

    /**
     * Calculate shipping cost for cart
     *
     * @param array $cartItems Cart items
     * @param float $subtotal Cart subtotal
     * @param array $shippingAddress Optional shipping address
     * @return float Shipping cost
     */
    public function calculate(array $cartItems, float $subtotal, array $shippingAddress = []): float
    {
        // Check for free shipping first
        if ($this->isFreeShipping($cartItems, $subtotal)) {
            return 0.0;
        }

        if ($this->customCalculator !== null) {
            return call_user_func($this->customCalculator, $cartItems, $subtotal, $shippingAddress);
        }

        switch ($this->method) {
            case 'weight':
                return $this->calculateWeightBased($cartItems);
            
            case 'price':
                return $this->calculatePriceBased($subtotal);
            
            case 'free':
                return 0.0;
            
            case 'flat':
            default:
                return $this->flatRate;
        }
    }

    /**
     * Calculate weight-based shipping
     *
     * @param array $cartItems
     * @return float
     */
    private function calculateWeightBased(array $cartItems): float
    {
        $totalWeight = 0.0;

        foreach ($cartItems as $item) {
            if (!is_array($item) || !isset($item['qty'])) {
                continue;
            }

            $weight = $item['weight'] ?? 0;
            $qty = $item['qty'] ?? 0;
            $totalWeight += $weight * $qty;
        }

        // Find applicable rate
        foreach ($this->weightRates as $rate) {
            if ($totalWeight <= $rate['max_weight']) {
                return $rate['cost'];
            }
        }

        // Return highest rate if no match
        $lastRate = end($this->weightRates);
        return $lastRate['cost'] ?? 0.0;
    }

    /**
     * Calculate price-based shipping
     *
     * @param float $subtotal
     * @return float
     */
    private function calculatePriceBased(float $subtotal): float
    {
        foreach ($this->priceRates as $rate) {
            if ($subtotal <= $rate['max_price']) {
                return $rate['cost'];
            }
        }

        // Free shipping for orders above all thresholds
        $lastRate = end($this->priceRates);
        return ($subtotal > $lastRate['max_price']) ? 0.0 : ($lastRate['cost'] ?? 0.0);
    }

    /**
     * Check if order qualifies for free shipping
     *
     * @param array $cartItems
     * @param float $subtotal
     * @return bool
     */
    private function isFreeShipping(array $cartItems, float $subtotal): bool
    {
        if (!$this->freeShippingAvailable) {
            return false;
        }

        foreach ($this->freeShippingThresholds as $threshold) {
            switch ($threshold['type'] ?? 'subtotal') {
                case 'subtotal':
                    if ($subtotal >= $threshold['amount']) {
                        return true;
                    }
                    break;
                
                case 'items':
                    $totalQty = 0;
                    foreach ($cartItems as $item) {
                        if (is_array($item) && isset($item['qty'])) {
                            $totalQty += $item['qty'];
                        }
                    }
                    if ($totalQty >= $threshold['amount']) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    /**
     * Set shipping method
     *
     * @param string $method
     * @return self
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Get current shipping method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set flat rate
     *
     * @param float $rate
     * @return self
     */
    public function setFlatRate(float $rate): self
    {
        $this->flatRate = $rate;
        return $this;
    }

    /**
     * Get flat rate
     *
     * @return float
     */
    public function getFlatRate(): float
    {
        return $this->flatRate;
    }

    /**
     * Set weight-based shipping rates
     * Format: [['max_weight' => 10, 'cost' => 5.00], ['max_weight' => 20, 'cost' => 10.00], ...]
     *
     * @param array $rates
     * @return self
     */
    public function setWeightRates(array $rates): self
    {
        // Sort by max_weight ascending
        usort($rates, fn($a, $b) => $a['max_weight'] <=> $b['max_weight']);
        $this->weightRates = $rates;
        return $this;
    }

    /**
     * Add a weight rate
     *
     * @param float $maxWeight
     * @param float $cost
     * @return self
     */
    public function addWeightRate(float $maxWeight, float $cost): self
    {
        $this->weightRates[] = [
            'max_weight' => $maxWeight,
            'cost' => $cost,
        ];
        // Re-sort
        usort($this->weightRates, fn($a, $b) => $a['max_weight'] <=> $b['max_weight']);
        return $this;
    }

    /**
     * Set price-based shipping rates
     * Format: [['max_price' => 50, 'cost' => 10.00], ['max_price' => 100, 'cost' => 5.00], ...]
     *
     * @param array $rates
     * @return self
     */
    public function setPriceRates(array $rates): self
    {
        // Sort by max_price ascending
        usort($rates, fn($a, $b) => $a['max_price'] <=> $b['max_price']);
        $this->priceRates = $rates;
        return $this;
    }

    /**
     * Add a price rate
     *
     * @param float $maxPrice
     * @param float $cost
     * @return self
     */
    public function addPriceRate(float $maxPrice, float $cost): self
    {
        $this->priceRates[] = [
            'max_price' => $maxPrice,
            'cost' => $cost,
        ];
        // Re-sort
        usort($this->priceRates, fn($a, $b) => $a['max_price'] <=> $b['max_price']);
        return $this;
    }

    /**
     * Enable free shipping with thresholds
     *
     * @param array $thresholds Format: [['type' => 'subtotal', 'amount' => 100], ...]
     * @return self
     */
    public function enableFreeShipping(array $thresholds = []): self
    {
        $this->freeShippingAvailable = true;
        $this->freeShippingThresholds = $thresholds;
        return $this;
    }

    /**
     * Disable free shipping
     *
     * @return self
     */
    public function disableFreeShipping(): self
    {
        $this->freeShippingAvailable = false;
        $this->freeShippingThresholds = [];
        return $this;
    }

    /**
     * Set custom shipping calculator
     * Callback signature: function(array $items, float $subtotal, array $address): float
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
     * Get available shipping methods
     *
     * @return array
     */
    public function getAvailableMethods(): array
    {
        $methods = ['flat', 'weight', 'price', 'free'];
        
        if ($this->customCalculator !== null) {
            $methods[] = 'custom';
        }

        return $methods;
    }
}
