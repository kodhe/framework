<?php

declare(strict_types=1);

namespace Kodhe\Framework\Config\Loaders;

/**
 * Loader Interface
 * 
 * Defines the contract for configuration loaders.
 * 
 * @package Kodhe\Framework\Config\Loaders
 * @since   1.0.0
 */
interface LoaderInterface
{
    /**
     * Load configuration from source
     * 
     * @param string $name Configuration name
     * @param mixed $data Configuration data
     * @param bool $merge Whether to merge with existing config
     * @return bool True on success
     */
    public function load(string $name, mixed $data, bool $merge = true): bool;

    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if configuration exists
     * 
     * @param string $key Configuration key
     * @return bool True if exists
     */
    public function has(string $key): bool;

    /**
     * Get all configuration
     * 
     * @return array All configuration data
     */
    public function all(): array;

    /**
     * Clear configuration
     * 
     * @param string|null $name Specific config to clear, or null for all
     * @return void
     */
    public function clear(?string $name = null): void;
}
