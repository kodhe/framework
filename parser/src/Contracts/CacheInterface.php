<?php

declare(strict_types=1);

namespace Kodhe\Parser\Contracts;

/**
 * Cache Interface
 *
 * Defines the contract for template caching.
 */
interface CacheInterface
{
    /**
     * Get cached compiled template
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    public function get(string $key): mixed;

    /**
     * Store compiled template in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return bool Success status
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * Check if cache exists
     *
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool;

    /**
     * Remove cached item
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function remove(string $key): bool;
}
