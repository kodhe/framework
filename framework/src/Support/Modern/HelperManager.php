<?php

declare(strict_types=0);

namespace Kodhe\Framework\Support\Modern;

use Kodhe\Framework\Support\Autoloader;

/**
 * HelperManager - Modern helper loading system
 * 
 * Provides lazy-loading, auto-discovery, and PSR-4 compatible helper management
 * while maintaining backward compatibility with legacy load_helper() function.
 * 
 * @package Kodhe\Framework\Support\Modern
 * @since 2.0.0
 */
class HelperManager
{
    /**
     * @var array<string, string> Map of helper name to file path
     */
    private static array $helpers = [];

    /**
     * @var array<string, bool> Track loaded helpers
     */
    private static array $loaded = [];

    /**
     * @var string[] Directories to search for helpers
     */
    private static array $paths = [];

    /**
     * @var self|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - initialize default paths
     */
    public function __construct()
    {
        // Add default framework helper paths
        self::addPath(ROOTPATH . 'framework/src/Support/Helpers');
        self::addPath(APPPATH . 'helpers');
        
        // Auto-discover helpers in registered paths
        $this->discoverHelpers();
    }

    /**
     * Add a directory path to search for helpers
     * 
     * @param string $path Directory path
     * @return self
     */
    public function addPath(string $path): self
    {
        $normalized = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
        if (!in_array($normalized, self::$paths, true)) {
            self::$paths[] = $normalized;
            $this->discoverHelpersInPath($normalized);
        }
        
        return $this;
    }

    /**
     * Get all registered paths
     * 
     * @return string[]
     */
    public function getPaths(): array
    {
        return self::$paths;
    }

    /**
     * Discover helpers in all registered paths
     */
    private function discoverHelpers(): void
    {
        foreach (self::$paths as $path) {
            $this->discoverHelpersInPath($path);
        }
    }

    /**
     * Discover helpers in a specific path
     * 
     * @param string $path Directory path
     */
    private function discoverHelpersInPath(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . '*_helper.php');
        
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $filename = basename($file, '_helper.php');
            
            if (!isset(self::$helpers[$filename])) {
                self::$helpers[$filename] = $file;
            }
        }
    }

    /**
     * Load a helper by name
     * 
     * @param string|string[] $helpers Helper name or array of names
     * @return bool True if all helpers loaded successfully
     * @throws \RuntimeException If helper not found
     */
    public function load(string|array $helpers): bool
    {
        $helpers = is_string($helpers) ? [$helpers] : $helpers;

        foreach ($helpers as $helper) {
            $helper = strtolower(trim($helper));
            
            if ($this->isLoaded($helper)) {
                continue;
            }

            $filePath = $this->findHelper($helper);
            
            if ($filePath === null) {
                throw new \RuntimeException(
                    "Unable to locate the helper file: {$helper}_helper.php"
                );
            }

            require_once $filePath;
            self::$loaded[$helper] = true;
            
            // Trigger hook if exists
            if (function_exists('log_message')) {
                log_message('debug', "Helper loaded: {$helper}");
            }
        }

        return true;
    }

    /**
     * Check if a helper is loaded
     * 
     * @param string $helper Helper name
     * @return bool
     */
    public function isLoaded(string $helper): bool
    {
        return isset(self::$loaded[strtolower($helper)]);
    }

    /**
     * Find helper file path
     * 
     * @param string $name Helper name
     * @return string|null File path or null if not found
     */
    private function findHelper(string $name): ?string
    {
        // Check if already mapped
        if (isset(self::$helpers[$name])) {
            return self::$helpers[$name];
        }

        // Search in all paths
        $fileName = $name . '_helper.php';
        
        foreach (self::$paths as $path) {
            $filePath = $path . $fileName;
            
            if (file_exists($filePath)) {
                self::$helpers[$name] = $filePath;
                return $filePath;
            }
        }

        return null;
    }

    /**
     * Register a helper manually
     * 
     * @param string $name Helper name
     * @param string $filePath Full file path
     * @return self
     */
    public function register(string $name, string $filePath): self
    {
        self::$helpers[strtolower($name)] = $filePath;
        return $this;
    }

    /**
     * Get list of all available helpers
     * 
     * @return array<string, string> Map of helper name to file path
     */
    public function getAvailableHelpers(): array
    {
        $this->discoverHelpers();
        return self::$helpers;
    }

    /**
     * Get list of loaded helpers
     * 
     * @return string[]
     */
    public function getLoadedHelpers(): array
    {
        return array_keys(self::$loaded);
    }

    /**
     * Reset manager state (useful for testing)
     */
    public function reset(): void
    {
        self::$helpers = [];
        self::$loaded = [];
        self::$paths = [];
        self::$instance = null;
    }
}
