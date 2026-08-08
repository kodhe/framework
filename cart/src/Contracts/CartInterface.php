<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cart\Contracts;

/**
 * Interface CartInterface
 * 
 * Main cart interface defining the public API
 * that must remain backward compatible with CodeIgniter 3
 * 
 * @package Kodhe\Cart\Contracts
 */
interface CartInterface
{
    /**
     * Insert items into the cart
     *
     * @param array $items Item(s) to insert
     * @return string|bool Row ID on success, FALSE on failure
     */
    public function insert($items = []);

    /**
     * Update the cart
     *
     * @param array $items Item(s) to update
     * @return bool TRUE on success, FALSE on failure
     */
    public function update($items = []);

    /**
     * Remove Item from cart
     *
     * @param string $rowid Row ID of item to remove
     * @return bool TRUE
     */
    public function remove($rowid);

    /**
     * Destroy the cart
     *
     * @return void
     */
    public function destroy();

    /**
     * Get Cart Total
     *
     * @return float
     */
    public function total();

    /**
     * Total Items count
     *
     * @return int
     */
    public function total_items();

    /**
     * Cart Contents
     *
     * @param bool $newest_first Sort order
     * @return array Cart contents without totals
     */
    public function contents($newest_first = false);

    /**
     * Get product options
     *
     * @param string $row_id Row ID
     * @return array
     */
    public function product_options($row_id = '');

    /**
     * Check if item has options
     *
     * @param string $row_id Row ID
     * @return bool
     */
    public function has_options($row_id = '');

    /**
     * Format Number
     *
     * @param float $n Number to format
     * @return string Formatted number
     */
    public function format_number($n = '');
}
