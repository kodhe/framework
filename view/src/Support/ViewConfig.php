<?php

namespace Kodhe\Framework\View\Support;

/**
 * Class ViewConfig
 *
 * @package Kodhe\Framework\View\Support
 */
class ViewConfig
{
    /**
     * @var array
     */
    protected static $config = [];

    /**
     * Load configuration from file
     *
     * @param string $path
     * @return void
     */
    public static function load(string $path): void
    {
        if (file_exists($path)) {
            self::$config = require $path;
        }
    }

    /**
     * Get a config value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set a config value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$config;

        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config[array_shift($keys)] = $value;
    }

    /**
     * Get all config
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$config;
    }
}
