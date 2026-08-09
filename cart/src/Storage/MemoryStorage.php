<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cart\Storage;

use Kodhe\Framework\Cart\Contracts\CartStorageInterface;

/**
 * Class MemoryStorage
 * 
 * Stores cart data in memory (PHP array).
 * Useful for testing, CLI applications, or short-lived carts.
 * 
 * @package Kodhe\Cart\Storage
 */
class MemoryStorage implements CartStorageInterface
{
    /**
     * In-memory storage container
     */
    private static array $storage = [];

    /**
     * Storage key for this instance
     */
    private string $key;

    /**
     * Cached cart data
     */
    protected ?array $cache = null;

    /**
     * Constructor
     *
     * @param string $key Unique identifier for this cart
     */
    public function __construct(string $key = 'default')
    {
        $this->key = $key;
        
        // Load initial data into cache
        $this->load();
    }

    /**
     * Load cart data from memory
     *
     * @return array|null
     */
    public function load(): ?array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = self::$storage[$this->key] ?? null;
        return $this->cache;
    }

    /**
     * Save cart data to memory
     *
     * @param array $data
     * @return bool
     */
    public function save(array $data): bool
    {
        $this->cache = $data;

        // If cart is empty, delete from storage
        if (count($data) <= 2) {
            return $this->delete();
        }

        self::$storage[$this->key] = $data;
        return true;
    }

    /**
     * Delete cart data from memory
     *
     * @return bool
     */
    public function delete(): bool
    {
        $this->cache = null;
        unset(self::$storage[$this->key]);
        return true;
    }

    /**
     * Check if cart data exists in memory
     *
     * @return bool
     */
    public function exists(): bool
    {
        if ($this->cache !== null) {
            return count($this->cache) > 2;
        }

        return isset(self::$storage[$this->key]) && count(self::$storage[$this->key]) > 2;
    }

    /**
     * Clear all storage (useful for testing cleanup)
     *
     * @return void
     */
    public static function clearAll(): void
    {
        self::$storage = [];
    }

    /**
     * Get all stored carts (for debugging/testing)
     *
     * @return array
     */
    public static function getAll(): array
    {
        return self::$storage;
    }

    /**
     * Clear the internal cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = null;
    }

    /**
     * Get storage key
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
