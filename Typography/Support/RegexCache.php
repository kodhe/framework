<?php

namespace Kodhe\Typography\Support;

/**
 * Helper untuk caching pola regex.
 */
class RegexCache
{
    /**
     * @var array Cache pola regex
     */
    private static array $cache = [];

    /**
     * Dapatkan pola regex dari cache atau buat baru.
     *
     * @param string $key Kunci cache
     * @param callable $generator Generator pola
     * @return string Pola regex
     */
    public static function get(string $key, callable $generator): string
    {
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $generator();
        }

        return self::$cache[$key];
    }

    /**
     * Clear cache.
     */
    public static function clear(): void
    {
        self::$cache = [];
    }
}
