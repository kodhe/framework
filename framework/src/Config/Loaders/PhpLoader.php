<?php

declare(strict_types=0);

namespace Kodhe\Framework\Config\Loaders;

/**
 * PHP Loader
 * 
 * Loads configuration from PHP files with return statements.
 * Modern approach: config files return arrays instead of using $config variable.
 * 
 * @package Kodhe\Framework\Config\Loaders
 * @since   1.0.0
 */
class PhpLoader implements LoaderInterface
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
     * Base path for configuration files
     * 
     * @var string
     */
    protected string $basePath;

    /**
     * Constructor
     * 
     * @param string $basePath Base path for config files
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath ?: APPPATH;
    }

    /**
     * Load configuration from PHP file
     * 
     * @param string $name Configuration file name (without .php)
     * @param string|null $path Optional custom path
     * @param bool $merge Whether to merge with existing config
     * @return bool True on success
     * @throws \RuntimeException If file not found or invalid
     */
    public function load(string $name, mixed $path = null, bool $merge = true): bool
    {
        if (in_array($name, $this->loaded, true)) {
            return true;
        }

        $file = is_string($path) ? $path : $this->resolveFilePath($name);

        if (!file_exists($file)) {
            throw new \RuntimeException("Configuration file not found: {$file}");
        }

        // Security: Prevent directory traversal
        $realPath = realpath($file);
        $realBase = realpath($this->basePath);
        
        if ($realPath === false || strpos($realPath, $realBase) !== 0) {
            throw new \RuntimeException("Invalid configuration file path: Directory traversal detected");
        }

        try {
            $data = include $file;

            // Handle both return-style and variable-style config files
            if ($data === 1 && isset($config) && is_array($config)) {
                $data = $config;
            }

            if (!is_array($data)) {
                $data = [];
            }

            if ($merge && isset($this->config[$name])) {
                $this->config[$name] = array_merge($this->config[$name], $data);
            } else {
                $this->config[$name] = $data;
            }

            $this->loaded[] = $name;
            return true;
        } catch (\ParseError $e) {
            throw new \RuntimeException("Syntax error in configuration file {$file}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve file path for configuration
     * 
     * @param string $name Configuration name
     * @return string Full file path
     */
    protected function resolveFilePath(string $name): string
    {
        $name = str_replace('.php', '', $name);
        
        // Check environment-specific config first
        $envConfig = defined('ENVIRONMENT') 
            ? $this->basePath . 'config/' . ENVIRONMENT . '/' . $name . '.php'
            : null;

        if ($envConfig && file_exists($envConfig)) {
            return $envConfig;
        }

        return $this->basePath . 'config/' . $name . '.php';
    }

    /**
     * Get configuration value
     * 
     * @param string $key Configuration key (dot notation supported)
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
     * @param string $key Configuration key (dot notation supported)
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

    /**
     * Check if configuration is already loaded
     * 
     * @param string $name Configuration name
     * @return bool True if loaded
     */
    public function isLoaded(string $name): bool
    {
        return in_array($name, $this->loaded, true);
    }
}