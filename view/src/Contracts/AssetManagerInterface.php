<?php

namespace Kodhe\Framework\View\Contracts;

/**
 * Interface AssetManagerInterface
 *
 * @package Kodhe\Framework\View\Contracts
 */
interface AssetManagerInterface
{
    /**
     * Add an asset
     *
     * @param string $type
     * @param string $path
     * @param array $attributes
     * @return self
     */
    public function add(string $type, string $path, array $attributes = []): self;

    /**
     * Add a CSS asset
     *
     * @param string $path
     * @param array $attributes
     * @return self
     */
    public function css(string $path, array $attributes = []): self;

    /**
     * Add a JS asset
     *
     * @param string $path
     * @param array $attributes
     * @return self
     */
    public function js(string $path, array $attributes = []): self;

    /**
     * Get all assets
     *
     * @param string|null $type
     * @return array
     */
    public function getAssets(?string $type = null): array;

    /**
     * Render assets
     *
     * @param string|null $type
     * @return string
     */
    public function render(?string $type = null): string;

    /**
     * Clear all assets
     *
     * @return self
     */
    public function clear(): self;
}
