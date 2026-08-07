<?php

namespace Kodhe\Framework\View\Path;

/**
 * Class ThemePathResolver
 *
 * @package Kodhe\Framework\View\Path
 */
class ThemePathResolver
{
    /**
     * @var string[]
     */
    protected $themePaths = [];

    /**
     * Create a new ThemePathResolver instance
     *
     * @param array $themePaths
     */
    public function __construct(array $themePaths = [])
    {
        $this->themePaths = $themePaths;
    }

    /**
     * Resolve theme path
     *
     * @param string $theme
     * @return string|null
     */
    public function resolve(string $theme): ?string
    {
        foreach ($this->themePaths as $basePath) {
            $path = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $theme;

            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Check if theme exists
     *
     * @param string $theme
     * @return bool
     */
    public function exists(string $theme): bool
    {
        return $this->resolve($theme) !== null;
    }

    /**
     * Add theme path
     *
     * @param string $path
     * @return self
     */
    public function addThemePath(string $path): self
    {
        $this->themePaths[] = rtrim($path, DIRECTORY_SEPARATOR);
        return $this;
    }

    /**
     * Set theme paths
     *
     * @param array $paths
     * @return self
     */
    public function setThemePaths(array $paths): self
    {
        $this->themePaths = array_map(function ($path) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }, $paths);
        return $this;
    }

    /**
     * Get all theme paths
     *
     * @return array
     */
    public function getThemePaths(): array
    {
        return $this->themePaths;
    }
}
