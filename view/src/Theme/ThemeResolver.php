<?php

namespace Kodhe\Framework\View\Theme;

/**
 * Class ThemeResolver
 *
 * @package Kodhe\Framework\View\Theme
 */
class ThemeResolver
{
    /**
     * @var string[]
     */
    protected $searchPaths = [];

    /**
     * @var Theme[]
     */
    protected $themes = [];

    /**
     * Create a new ThemeResolver instance
     *
     * @param array $searchPaths
     */
    public function __construct(array $searchPaths = [])
    {
        $this->searchPaths = $searchPaths;
    }

    /**
     * Resolve theme by name
     *
     * @param string $name
     * @return Theme|null
     */
    public function resolve(string $name): ?Theme
    {
        if (isset($this->themes[$name])) {
            return $this->themes[$name];
        }

        $path = $this->findThemePath($name);

        if ($path) {
            $config = $this->loadThemeConfig($path);
            $theme = new Theme($name, $path, $config);
            $this->themes[$name] = $theme;
            return $theme;
        }

        return null;
    }

    /**
     * Find theme path
     *
     * @param string $name
     * @return string|null
     */
    protected function findThemePath(string $name): ?string
    {
        foreach ($this->searchPaths as $basePath) {
            $path = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
            
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Load theme config
     *
     * @param string $path
     * @return array
     */
    protected function loadThemeConfig(string $path): array
    {
        $configFile = $path . DIRECTORY_SEPARATOR . 'theme.json';
        
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : [];
        }

        $configFile = $path . DIRECTORY_SEPARATOR . 'config.php';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            return is_array($config) ? $config : [];
        }

        return [];
    }

    /**
     * Add search path
     *
     * @param string $path
     * @return self
     */
    public function addSearchPath(string $path): self
    {
        $this->searchPaths[] = $path;
        return $this;
    }

    /**
     * Set search paths
     *
     * @param array $paths
     * @return self
     */
    public function setSearchPaths(array $paths): self
    {
        $this->searchPaths = $paths;
        return $this;
    }

    /**
     * Get all search paths
     *
     * @return array
     */
    public function getSearchPaths(): array
    {
        return $this->searchPaths;
    }
}
