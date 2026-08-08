<?php

declare(strict_types=1);

namespace Kodhe\Validation\Support;

/**
 * Compiled Rules Cache
 * 
 * Caches parsed and prepared rules for performance
 */
class RuleCache
{
    /**
     * Cache storage
     */
    protected static array $cache = [];
    
    /**
     * Cache hits counter
     */
    protected static int $hits = 0;
    
    /**
     * Cache misses counter
     */
    protected static int $misses = 0;
    
    /**
     * Get a cached item
     * 
     * @param string $key
     * @return mixed|null
     */
    public static function get(string $key): mixed
    {
        if (isset(self::$cache[$key])) {
            self::$hits++;
            return self::$cache[$key];
        }
        
        self::$misses++;
        return null;
    }
    
    /**
     * Set a cache item
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::$cache[$key] = $value;
    }
    
    /**
     * Check if key exists in cache
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }
    
    /**
     * Clear the cache
     * 
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = [];
        self::$hits = 0;
        self::$misses = 0;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public static function getStats(): array
    {
        return [
            'items' => count(self::$cache),
            'hits' => self::$hits,
            'misses' => self::$misses,
            'hit_rate' => self::$hits + self::$misses > 0 
                ? round(self::$hits / (self::$hits + self::$misses) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * Generate cache key from rules
     * 
     * @param array $rules
     * @return string
     */
    public static function generateKey(array $rules): string
    {
        return md5(serialize($rules));
    }
}
