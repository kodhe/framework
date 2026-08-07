<?php

namespace Kodhe\Framework\View\Path;

/**
 * Class ViewPathResolver
 *
 * @package Kodhe\Framework\View\Path
 */
class ViewPathResolver
{
    /**
     * @var string[]
     */
    protected $viewPaths = [];

    /**
     * @var string
     */
    protected $extension = '.php';

    /**
     * Create a new ViewPathResolver instance
     *
     * @param array $viewPaths
     * @param string $extension
     */
    public function __construct(array $viewPaths = [], string $extension = '.php')
    {
        $this->viewPaths = $viewPaths;
        $this->extension = $extension;
    }

    /**
     * Resolve view path
     *
     * @param string $view
     * @return string|null
     */
    public function resolve(string $view): ?string
    {
        // Normalize view name
        $view = $this->normalizeViewName($view);

        foreach ($this->viewPaths as $basePath) {
            $path = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $view;

            if (file_exists($path)) {
                return $path;
            }

            // Try with extension
            $pathWithExt = $path . $this->extension;
            if (file_exists($pathWithExt)) {
                return $pathWithExt;
            }
        }

        return null;
    }

    /**
     * Check if view exists
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool
    {
        return $this->resolve($view) !== null;
    }

    /**
     * Normalize view name
     *
     * @param string $view
     * @return string
     */
    protected function normalizeViewName(string $view): string
    {
        // Remove leading/trailing slashes
        $view = trim($view, '/');
        
        // Replace dots with directory separators
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);
        
        // Remove extension if present
        if (pathinfo($view, PATHINFO_EXTENSION) === ltrim($this->extension, '.')) {
            $view = pathinfo($view, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($view, PATHINFO_FILENAME);
            $view = trim($view, DIRECTORY_SEPARATOR);
        }

        return $view;
    }

    /**
     * Add view path
     *
     * @param string $path
     * @return self
     */
    public function addViewPath(string $path): self
    {
        $this->viewPaths[] = rtrim($path, DIRECTORY_SEPARATOR);
        return $this;
    }

    /**
     * Set view paths
     *
     * @param array $paths
     * @return self
     */
    public function setViewPaths(array $paths): self
    {
        $this->viewPaths = array_map(function ($path) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }, $paths);
        return $this;
    }

    /**
     * Get all view paths
     *
     * @return array
     */
    public function getViewPaths(): array
    {
        return $this->viewPaths;
    }

    /**
     * Set file extension
     *
     * @param string $extension
     * @return self
     */
    public function setExtension(string $extension): self
    {
        $this->extension = $extension;
        return $this;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }
}
