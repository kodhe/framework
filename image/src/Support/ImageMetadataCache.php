<?php

declare(strict_types=0);

namespace Kodhe\Framework\Image\Support;

/**
 * Class ImageMetadataCache
 *
 * Caches image metadata to avoid repeated getimagesize() calls.
 * Implements performance optimization through lazy loading and caching.
 *
 * @package     Kodhe\Image
 * @author      CodeIgniter Refactored
 * @version     2.0.0
 * @license     MIT
 */
class ImageMetadataCache
{
    /**
     * @var array
     */
    private static $cache = [];

    /**
     * Get image metadata from cache or load it
     *
     * @param string $path
     * @return array|null
     */
    public static function get(string $path): ?array
    {
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        $data = self::load($path);
        if ($data !== null) {
            self::$cache[$path] = $data;
        }

        return $data;
    }

    /**
     * Load image metadata from file
     *
     * @param string $path
     * @return array|null
     */
    private static function load(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $data = @getimagesize($path);
        if ($data === false || !is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Manually set cache for a path
     *
     * @param string $path
     * @param array  $data
     * @return void
     */
    public static function set(string $path, array $data): void
    {
        self::$cache[$path] = $data;
    }

    /**
     * Clear cache for a specific path or all paths
     *
     * @param string|null $path
     * @return void
     */
    public static function clear(?string $path = null): void
    {
        if ($path === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$path]);
        }
    }

    /**
     * Check if path is cached
     *
     * @param string $path
     * @return bool
     */
    public static function has(string $path): bool
    {
        return isset(self::$cache[$path]);
    }
}
