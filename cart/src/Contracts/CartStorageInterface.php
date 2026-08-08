<?php

declare(strict_types=0);

namespace Kodhe\Framework\Cart\Contracts;

/**
 * Interface CartStorageInterface
 * 
 * Defines the contract for cart storage mechanisms.
 * Implementations can use Session, Database, Memory, or any other storage.
 * 
 * @package Kodhe\Cart\Contracts
 */
interface CartStorageInterface
{
    /**
     * Load cart data from storage
     *
     * @return array|null Cart data or null if not found
     */
    public function load(): ?array;

    /**
     * Save cart data to storage
     *
     * @param array $data Cart data to save
     * @return bool TRUE on success, FALSE on failure
     */
    public function save(array $data): bool;

    /**
     * Delete cart data from storage
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function delete(): bool;

    /**
     * Check if cart data exists in storage
     *
     * @return bool
     */
    public function exists(): bool;
}
