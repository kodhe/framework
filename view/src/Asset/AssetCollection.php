<?php

namespace Kodhe\Framework\View\Asset;

/**
 * Class AssetCollection
 *
 * @package Kodhe\Framework\View\Asset
 */
class AssetCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var Asset[]
     */
    protected $assets = [];

    /**
     * Add an asset
     *
     * @param Asset $asset
     * @return self
     */
    public function add(Asset $asset): self
    {
        $this->assets[] = $asset;
        return $this;
    }

    /**
     * Remove an asset by path
     *
     * @param string $path
     * @return self
     */
    public function remove(string $path): self
    {
        $this->assets = array_filter($this->assets, function ($asset) use ($path) {
            return $asset->getPath() !== $path;
        });

        return $this;
    }

    /**
     * Get all assets
     *
     * @return Asset[]
     */
    public function all(): array
    {
        return $this->assets;
    }

    /**
     * Get assets by type
     *
     * @param string $type
     * @return Asset[]
     */
    public function getByType(string $type): array
    {
        return array_filter($this->assets, function ($asset) use ($type) {
            return $asset->getType() === $type;
        });
    }

    /**
     * Clear all assets
     *
     * @return self
     */
    public function clear(): self
    {
        $this->assets = [];
        return $this;
    }

    /**
     * Render all assets
     *
     * @param string|null $type
     * @return string
     */
    public function render(?string $type = null): string
    {
        $output = '';
        $assets = $type ? $this->getByType($type) : $this->assets;

        foreach ($assets as $asset) {
            $output .= $asset->render();
        }

        return $output;
    }

    /**
     * Get iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->assets);
    }

    /**
     * Count assets
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->assets);
    }
}
