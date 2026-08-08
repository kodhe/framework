<?php

declare(strict_types=0);

namespace Kodhe\Framework\Config\Loaders;

/**
 * Array Loader
 * 
 * Loads configuration from PHP arrays.
 * Useful for testing and runtime configuration.
 * 
 * @package Kodhe\Framework\Config\Loaders
 * @since   1.0.0
 */
class ArrayLoader implements LoaderInterface
{
    /**
     * Configuration data storage
     * 
     * @var array
     */
    protected array $config = [];

    /**
     * List of loaded configurations
     * 
     * @var array
     */
    protected array $loaded = [];

    /**
     * Load configuration from array
     * 
     * @param string $name Configuration name
     * @param array $data Configuration data
     * @param bool $merge Whether to merge with existing config
     * @return bool True on success
     */
    public function load(string $name, array $data, bool $merge = true): bool
    {
        if (in_array($name, $this->loaded, true)) {
            return true;
        }

        if ($merge && isset($this->config[$name])) {
            $this->config[$name] = array_merge($this->config[$name], $data);
        } else {
            $this->config[$name] = $data;
        }

        $this->loaded[] = $name;
        return true;
    }

    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $result = $this->config;

        foreach ($keys as $k) {
            if (!isset($result[$k])) {
                return $default;
            }
            $result = $result[$k];
        }

        return $result;
    }

    /**
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

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
     * Check if configuration exists
     * 
     * @param string $key Configuration key
     * @return bool True if exists
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $result = $this->config;

        foreach ($keys as $k) {
            if (!isset($result[$k])) {
                return false;
            }
            $result = $result[$k];
        }

        return true;
    }

    /**
     * Get all configuration
     * 
     * @return array All configuration data
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Clear configuration
     * 
     * @param string|null $name Specific config to clear, or null for all
     * @return void
     */
    public function clear(?string $name = null): void
    {
        if ($name === null) {
            $this->config = [];
            $this->loaded = [];
        } else {
            unset($this->config[$name]);
            $key = array_search($name, $this->loaded);
            if ($key !== false) {
                unset($this->loaded[$key]);
            }
        }
    }
}