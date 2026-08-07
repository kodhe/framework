<?php

namespace Kodhe\Framework\View\Support;

/**
 * Class ViewNameResolver
 *
 * @package Kodhe\Framework\View\Support
 */
class ViewNameResolver
{
    /**
     * Resolve view name to file path
     *
     * @param string $view
     * @param string $extension
     * @return string
     */
    public static function resolve(string $view, string $extension = '.php'): string
    {
        // Remove leading/trailing slashes
        $view = trim($view, '/');
        
        // Replace dots with directory separators
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);
        
        // Add extension if not present
        if (pathinfo($view, PATHINFO_EXTENSION) === '') {
            $view .= $extension;
        }
        
        return $view;
    }

    /**
     * Check if view name is valid
     *
     * @param string $view
     * @return bool
     */
    public static function isValid(string $view): bool
    {
        return !empty(trim($view)) && !preg_match('/[<>:"|?*]/', $view);
    }

    /**
     * Normalize view name
     *
     * @param string $view
     * @return string
     */
    public static function normalize(string $view): string
    {
        return str_replace(['/', '\\'], '.', trim($view, '/\\'));
    }
}
