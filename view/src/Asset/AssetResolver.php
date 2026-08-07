<?php

namespace Kodhe\Framework\View\Asset;

/**
 * Class AssetResolver
 *
 * @package Kodhe\Framework\View\Asset
 */
class AssetResolver
{
    /**
     * @var string
     */
    protected $baseUrl = '';

    /**
     * @var bool
     */
    protected $useCacheBusting = false;

    /**
     * @var string
     */
    protected $cacheBustParameter = 'v';

    /**
     * Create a new AssetResolver instance
     *
     * @param string $baseUrl
     */
    public function __construct(string $baseUrl = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Resolve asset path
     *
     * @param string $path
     * @return string
     */
    public function resolve(string $path): string
    {
        // Check if already absolute URL
        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        // Remove leading slash
        $path = ltrim($path, '/');

        // Prepend base URL
        $resolved = $this->baseUrl ? "{$this->baseUrl}/{$path}" : "/{$path}";

        // Add cache busting if enabled
        if ($this->useCacheBusting) {
            $resolved = $this->addCacheBusting($resolved, $path);
        }

        return $resolved;
    }

    /**
     * Check if path is absolute URL
     *
     * @param string $path
     * @return bool
     */
    protected function isAbsoluteUrl(string $path): bool
    {
        return preg_match('#^(https?:)?//|^data:#i', $path);
    }

    /**
     * Add cache busting parameter
     *
     * @param string $url
     * @param string $path
     * @return string
     */
    protected function addCacheBusting(string $url, string $path): string
    {
        $file = $this->getFilePath($path);
        
        if ($file && file_exists($file)) {
            $version = filemtime($file);
            $separator = strpos($url, '?') === false ? '?' : '&';
            $url .= "{$separator}{$this->cacheBustParameter}={$version}";
        }

        return $url;
    }

    /**
     * Get physical file path
     *
     * @param string $path
     * @return string|null
     */
    protected function getFilePath(string $path): ?string
    {
        // This would need to be configured based on your setup
        $baseDir = $_SERVER['DOCUMENT_ROOT'] ?? '';
        
        if (empty($baseDir)) {
            return null;
        }

        $path = ltrim($path, '/');
        $file = rtrim($baseDir, '/') . DIRECTORY_SEPARATOR . $path;

        return file_exists($file) ? $file : null;
    }

    /**
     * Enable cache busting
     *
     * @param string $parameter
     * @return self
     */
    public function enableCacheBusting(string $parameter = 'v'): self
    {
        $this->useCacheBusting = true;
        $this->cacheBustParameter = $parameter;
        return $this;
    }

    /**
     * Disable cache busting
     *
     * @return self
     */
    public function disableCacheBusting(): self
    {
        $this->useCacheBusting = false;
        return $this;
    }

    /**
     * Set base URL
     *
     * @param string $baseUrl
     * @return self
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }
}
