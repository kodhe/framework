<?php

declare(strict_types=1);

namespace Kodhe\Typography\Support;

/**
 * Regex Cache
 * 
 * Caches compiled regular expressions for performance optimization.
 */
class RegexCache
{
    /**
     * @var array Cached regex patterns
     */
    private static $cache = [];

    /**
     * Get a cached pattern or compile and cache it.
     *
     * @param string $name
     * @param string $pattern
     * @return string
     */
    public static function get(string $name, string $pattern): string
    {
        if (!isset(self::$cache[$name])) {
            // Validate the pattern before caching
            if (@preg_match($pattern, '') === false) {
                throw new \InvalidArgumentException("Invalid regex pattern: {$pattern}");
            }
            self::$cache[$name] = $pattern;
        }

        return self::$cache[$name];
    }

    /**
     * Check if a pattern exists in cache.
     *
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset(self::$cache[$name]);
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = [];
    }

    /**
     * Get all cached patterns.
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$cache;
    }
}
