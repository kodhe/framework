<?php
/**
 * Cache Interface
 *
 * @package CodeIgniter\Parser\Contracts
 */

namespace CodeIgniter\Parser\Contracts;

interface CacheInterface
{
    /**
     * Get cached compiled template
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string;

    /**
     * Store compiled template in cache
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function set(string $key, string $value): bool;

    /**
     * Check if cache exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
}
