<?php

namespace Kodhe\Framework\View\Contracts;

/**
 * Interface ThemeManagerInterface
 *
 * @package Kodhe\Framework\View\Contracts
 */
interface ThemeManagerInterface
{
    /**
     * Set active theme
     *
     * @param string $theme
     * @return self
     */
    public function setTheme(string $theme): self;

    /**
     * Get active theme
     *
     * @return string
     */
    public function getTheme(): string;

    /**
     * Get all available themes
     *
     * @return array
     */
    public function getThemes(): array;

    /**
     * Check if theme exists
     *
     * @param string $theme
     * @return bool
     */
    public function hasTheme(string $theme): bool;

    /**
     * Get theme path
     *
     * @param string|null $theme
     * @return string
     */
    public function getThemePath(?string $theme = null): string;
}
