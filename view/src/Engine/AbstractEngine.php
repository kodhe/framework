<?php

namespace Kodhe\Framework\View\Engine;

/**
 * Class AbstractEngine
 *
 * @package Kodhe\Framework\View\Engine
 */
abstract class AbstractEngine implements EngineInterface
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
     * Create a new AbstractEngine instance
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
     * Get engine name
     *
     * @return string
     */
    public function getName(): string
    {
        // Extract short class name without "Engine" suffix
        $name = (new \ReflectionClass($this))->getShortName();
        return str_replace('Engine', '', strtolower($name));
    }

    /**
     * Find view file path
     *
     * @param string $view
     * @return string|null
     */
    protected function findViewFile(string $view): ?string
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
     * Normalize view name
     *
     * @param string $view
     * @return string
     */
    protected function normalizeViewName(string $view): string
    {
        $view = trim($view, '/');
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);

        // Remove extension if present
        $ext = ltrim($this->extension, '.');
        if (pathinfo($view, PATHINFO_EXTENSION) === $ext) {
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
     * Get view paths
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
}
