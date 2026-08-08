<?php

declare(strict_types=1);

namespace Kodhe\Cache\Contracts;

/**
 * Cache Driver Interface (PSR-16 inspired)
 * 
 * Defines the contract for cache drivers in CodeIgniter 3
 * 
 * @package     Kodhe\Framework\Cache\Contracts
 * @author      EllisLab Dev Team (refactored by Kodhe)
 * @version     2.0.0
 * @license     MIT
 */
interface CacheDriverInterface
{
    /**
     * Check if the driver is supported on this system
     *
     * @return bool TRUE if supported, FALSE otherwise
     */
    public function isSupported(): bool;

    /**
     * Fetch from cache
     *
     * @param string $id Cache ID
     * @return mixed Data on success, FALSE on failure
     */
    public function get(string $id);

    /**
     * Save into cache
     *
     * @param string $id Cache ID
     * @param mixed $data Data to store
     * @param int $ttl Time to live in seconds
     * @param bool $raw Whether to store the raw value
     * @return bool TRUE on success, FALSE on failure
     */
    public function save(string $id, $data, int $ttl = 60, bool $raw = false): bool;

    /**
     * Delete from Cache
     *
     * @param string $id Cache ID
     * @return bool TRUE on success, FALSE on failure
     */
    public function delete(string $id): bool;

    /**
     * Increment a raw value
     *
     * @param string $id Cache ID
     * @param int $offset Step/value to add
     * @return mixed New value on success, FALSE on failure
     */
    public function increment(string $id, int $offset = 1);

    /**
     * Decrement a raw value
     *
     * @param string $id Cache ID
     * @param int $offset Step/value to reduce by
     * @return mixed New value on success, FALSE on failure
     */
    public function decrement(string $id, int $offset = 1);

    /**
     * Clean the Cache
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function clean(): bool;

    /**
     * Get cache info
     *
     * @param string|null $type Cache type (user/filehits)
     * @return mixed Cache info or FALSE on failure
     */
    public function cacheInfo(?string $type = null);

    /**
     * Get cache metadata
     *
     * @param string $id Cache ID
     * @return mixed Cache item metadata or FALSE on failure
     */
    public function getMetadata(string $id);
}
