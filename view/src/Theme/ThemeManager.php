<?php

namespace Kodhe\Framework\View\Theme;

use Kodhe\Framework\View\Contracts\ThemeManagerInterface;
use Kodhe\Framework\View\Exceptions\ThemeNotFoundException;

/**
 * Class ThemeManager
 *
 * @package Kodhe\Framework\View\Theme
 */
class ThemeManager implements ThemeManagerInterface
{
    /**
     * @var ThemeResolver
     */
    protected $resolver;

    /**
     * @var string
     */
    protected $activeTheme;

    /**
     * @var array
     */
    protected $themePaths = [];

    /**
     * Create a new ThemeManager instance
     *
     * @param ThemeResolver|null $resolver
     */
    public function __construct(?ThemeResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new ThemeResolver();
    }

    /**
     * Set active theme
     *
     * @param string $theme
     * @return self
     */
    public function setTheme(string $theme): self
    {
        if (!$this->hasTheme($theme)) {
            throw ThemeNotFoundException::make($theme);
        }

        $this->activeTheme = $theme;
        return $this;
    }

    /**
     * Get active theme
     *
     * @return string
     */
    public function getTheme(): string
    {
        return $this->activeTheme ?? 'default';
    }

    /**
     * Get all available themes
     *
     * @return array
     */
    public function getThemes(): array
    {
        $themes = [];
        
        foreach ($this->themePaths as $path) {
            if (is_dir($path)) {
                $dirs = scandir($path);
                foreach ($dirs as $dir) {
                    if ($dir[0] !== '.' && is_dir($path . DIRECTORY_SEPARATOR . $dir)) {
                        $themes[$dir] = $dir;
                    }
                }
            }
        }

        return array_values(array_unique($themes));
    }

    /**
     * Check if theme exists
     *
     * @param string $theme
     * @return bool
     */
    public function hasTheme(string $theme): bool
    {
        return $this->resolver->resolve($theme) !== null;
    }

    /**
     * Get theme path
     *
     * @param string|null $theme
     * @return string
     */
    public function getThemePath(?string $theme = null): string
    {
        $theme = $theme ?? $this->getTheme();
        $resolved = $this->resolver->resolve($theme);

        if ($resolved) {
            return $resolved->getPath();
        }

        throw ThemeNotFoundException::make($theme);
    }

    /**
     * Add theme search path
     *
     * @param string $path
     * @return self
     */
    public function addThemePath(string $path): self
    {
        $this->themePaths[] = rtrim($path, DIRECTORY_SEPARATOR);
        $this->resolver->addSearchPath(rtrim($path, DIRECTORY_SEPARATOR));
        return $this;
    }

    /**
     * Set theme search paths
     *
     * @param array $paths
     * @return self
     */
    public function setThemePaths(array $paths): self
    {
        $this->themePaths = array_map(function ($path) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }, $paths);
        
        $this->resolver->setSearchPaths($this->themePaths);
        return $this;
    }

    /**
     * Get the resolved theme object
     *
     * @param string|null $name
     * @return Theme|null
     */
    public function getThemeObject(?string $name = null): ?Theme
    {
        $name = $name ?? $this->getTheme();
        return $this->resolver->resolve($name);
    }
}
