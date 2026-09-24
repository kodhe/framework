<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cache\Contracts;

/**
 * Main Cache Interface for CodeIgniter 3
 * 
 * Defines the public API for the cache library
 * 
 * @package     Kodhe\Framework\Cache\Contracts
 * @author      EllisLab Dev Team (refactored by Kodhe)
 * @version     2.0.0
 * @license     MIT
 */
interface CacheInterface
{
    /**
     * Get a cache item
     *
     * @param string $id Cache ID
     * @return mixed Value matching $id or FALSE on failure
     */
    public function get(string $id);

    /**
     * Save a cache item
     *
     * @param string $id Cache ID
     * @param mixed $data Data to store
     * @param int $ttl Cache TTL (in seconds)
     * @param bool $raw Whether to store the raw value
     * @return bool TRUE on success, FALSE on failure
     */
    public function save(string $id, $data, int $ttl = 60, bool $raw = false): bool;

    /**
     * Delete a cache item
     *
     * @param string $id Cache ID
     * @return bool TRUE on success, FALSE on failure
     */
    public function delete(string $id): bool;

    /**
     * Increment a cache item
     *
     * @param string $id Cache ID
     * @param int $offset Step/value to add
     * @return mixed New value on success or FALSE on failure
     */
    public function increment(string $id, int $offset = 1);

    /**
     * Decrement a cache item
     *
     * @param string $id Cache ID
     * @param int $offset Step/value to reduce by
     * @return mixed New value on success or FALSE on failure
     */
    public function decrement(string $id, int $offset = 1);

    /**
     * Clean the cache
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function clean(): bool;

    /**
     * Get cache info
     *
     * @param string|null $type Cache type
     * @return mixed Cache info or FALSE on failure
     */
    public function cacheInfo(?string $type = null);

    /**
     * Get cache metadata
     *
     * @param string $id Cache ID
     * @return mixed Cache item metadata
     */
    public function getMetadata(string $id);

    /**
     * Check if a driver is supported
     *
     * @param string $driver Driver name
     * @return bool TRUE if supported, FALSE otherwise
     */
    public function isSupported(string $driver): bool;
}
