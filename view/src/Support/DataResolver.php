<?php

namespace Kodhe\Framework\View\Support;

/**
 * Class DataResolver
 *
 * @package Kodhe\Framework\View\Support
 */
class DataResolver
{
    /**
     * Resolve data from various sources
     *
     * @param mixed $data
     * @return array
     */
    public static function resolve($data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            if ($data instanceof \ArrayObject) {
                return (array) $data->getArrayCopy();
            }
            return get_object_vars($data);
        }

        if ($data instanceof \Traversable) {
            return iterator_to_array($data);
        }

        return [];
    }

    /**
     * Merge multiple data sources
     *
     * @param array ...$sources
     * @return array
     */
    public static function merge(array ...$sources): array
    {
        $result = [];
        
        foreach ($sources as $source) {
            $result = array_merge($result, self::resolve($source));
        }
        
        return $result;
    }

    /**
     * Extract specific keys from data
     *
     * @param array $data
     * @param array $keys
     * @return array
     */
    public static function extract(array $data, array $keys): array
    {
        return array_intersect_key($data, array_flip($keys));
    }

    /**
     * Share data across views
     *
     * @var array
     */
    protected static $shared = [];

    /**
     * Set shared data
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    public static function share($key, $value = null): void
    {
        if (is_array($key)) {
            self::$shared = array_merge(self::$shared, $key);
        } else {
            self::$shared[$key] = $value;
        }
    }

    /**
     * Get shared data
     *
     * @return array
     */
    public static function getShared(): array
    {
        return self::$shared;
    }

    /**
     * Clear shared data
     *
     * @return void
     */
    public static function clearShared(): void
    {
        self::$shared = [];
    }
}
