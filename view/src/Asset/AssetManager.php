<?php

namespace Kodhe\Framework\View\Asset;

use Kodhe\Framework\View\Contracts\AssetManagerInterface;

/**
 * Class AssetManager
 *
 * @package Kodhe\Framework\View\Asset
 */
class AssetManager implements AssetManagerInterface
{
    /**
     * @var AssetCollection
     */
    protected $collection;

    /**
     * @var AssetResolver
     */
    protected $resolver;

    /**
     * Create a new AssetManager instance
     */
    public function __construct()
    {
        $this->collection = new AssetCollection();
        $this->resolver = new AssetResolver();
    }

    /**
     * Add an asset
     *
     * @param string $type
     * @param string $path
     * @param array $attributes
     * @return self
     */
    public function add(string $type, string $path, array $attributes = []): self
    {
        $resolvedPath = $this->resolver->resolve($path);
        $asset = new Asset($type, $resolvedPath, $attributes);
        $this->collection->add($asset);

        return $this;
    }

    /**
     * Add a CSS asset
     *
     * @param string $path
     * @param array $attributes
     * @return self
     */
    public function css(string $path, array $attributes = []): self
    {
        return $this->add('css', $path, $attributes);
    }

    /**
     * Add a JS asset
     *
     * @param string $path
     * @param array $attributes
     * @return self
     */
    public function js(string $path, array $attributes = []): self
    {
        return $this->add('js', $path, $attributes);
    }

    /**
     * Get all assets
     *
     * @param string|null $type
     * @return array
     */
    public function getAssets(?string $type = null): array
    {
        if ($type) {
            return $this->collection->getByType($type);
        }

        return $this->collection->all();
    }

    /**
     * Render assets
     *
     * @param string|null $type
     * @return string
     */
    public function render(?string $type = null): string
    {
        return $this->collection->render($type);
    }

    /**
     * Clear all assets
     *
     * @return self
     */
    public function clear(): self
    {
        $this->collection->clear();
        return $this;
    }

    /**
     * Get the asset collection
     *
     * @return AssetCollection
     */
    public function getCollection(): AssetCollection
    {
        return $this->collection;
    }

    /**
     * Set the resolver
     *
     * @param AssetResolver $resolver
     * @return self
     */
    public function setResolver(AssetResolver $resolver): self
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * Enable cache busting
     *
     * @return self
     */
    public function enableCacheBusting(): self
    {
        $this->resolver->enableCacheBusting();
        return $this;
    }

    /**
     * Disable cache busting
     *
     * @return self
     */
    public function disableCacheBusting(): self
    {
        $this->resolver->disableCacheBusting();
        return $this;
    }
}
