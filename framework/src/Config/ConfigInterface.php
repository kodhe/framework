<?php

declare(strict_types=0);

namespace Kodhe\Framework\Config;

/**
 * Config Interface
 * 
 * Defines the contract for configuration management.
 * Compatible with CodeIgniter 3 CI_Config class.
 * 
 * @package Kodhe\Framework\Config
 * @since   1.0.0
 */
interface ConfigInterface
{
    /**
     * Load a configuration file
     *
     * @param string $file Filename without extension
     * @param bool $use_sections Whether to use sections
     * @param bool $fail_gracefully Whether to fail gracefully on error
     * @param string $_module Module name
     * @return mixed|null Configuration data or null
     */
    public function load(string $file = '', bool $use_sections = false, bool $fail_gracefully = false, string $_module = ''): mixed;

    /**
     * Fetch a config item
     *
     * @param string $item Config item name
     * @param string $index Index name for nested items
     * @return mixed|null The configuration item or NULL
     */
    public function item(string $item, string $index = ''): mixed;

    /**
     * Fetch a config item with slash appended
     *
     * @param string $item Config item name
     * @return string|null The configuration item with trailing slash or NULL
     */
    public function slash_item(string $item): ?string;

    /**
     * Generate site URL
     *
     * @param string|array $uri URI string or segments array
     * @param string|null $protocol Protocol override
     * @return string Full site URL
     */
    public function site_url(string|array $uri = '', ?string $protocol = null): string;

    /**
     * Generate base URL
     *
     * @param string|array $uri URI string or segments array
     * @param string|null $protocol Protocol override
     * @return string Full base URL
     */
    public function base_url(string|array $uri = '', ?string $protocol = null): string;

    /**
     * Set a config item
     *
     * @param string $item Config item key
     * @param mixed $value Config item value
     * @return void
     */
    public function set_item(string $item, mixed $value): void;

    /**
     * Check if a config item exists
     *
     * @param string $item Config item name
     * @return bool True if exists
     */
    public function has_item(string $item): bool;

    /**
     * Remove a config item
     *
     * @param string $item Config item name
     * @return void
     */
    public function remove_item(string $item): void;

    /**
     * Get all config items
     *
     * @return array All configuration items
     */
    public function get_all(): array;

    /**
     * Assign config items from external source
     *
     * @param array $items Items to assign
     * @return void
     */
    public function _assign_to_config(array $items = []): void;
}