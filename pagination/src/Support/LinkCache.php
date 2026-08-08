<?php

declare(strict_types=1);

namespace Kodhe\Framework\Pagination\Support;

/**
 * Link Cache for Performance Optimization
 * 
 * Caches rendered pagination HTML to avoid re-rendering
 */
class LinkCache
{
    private static array $cache = [];
    private static int $maxSize = 100;
    
    /**
     * Get cached links
     * 
     * @param string $key Cache key
     * @return string|null Cached HTML or null if not found
     */
    public static function get(string $key): ?string
    {
        return self::$cache[$key] ?? null;
    }
    
    /**
     * Set cache entry
     * 
     * @param string $key Cache key
     * @param string $value HTML to cache
     * @return void
     */
    public static function set(string $key, string $value): void
    {
        // Implement simple LRU-like eviction
        if (count(self::$cache) >= self::$maxSize) {
            array_shift(self::$cache);
        }
        
        self::$cache[$key] = $value;
    }
    
    /**
     * Check if cache has entry
     * 
     * @param string $key Cache key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }
    
    /**
     * Clear cache
     * 
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = [];
    }
    
    /**
     * Set maximum cache size
     * 
     * @param int $size Maximum entries
     * @return void
     */
    public static function setMaxSize(int $size): void
    {
        self::$maxSize = max(1, $size);
    }
    
    /**
     * Generate cache key from config
     * 
     * @param array $config Configuration array
     * @return string Cache key
     */
    public static function generateKey(array $config): string
    {
        return md5(json_encode($config));
    }
}
