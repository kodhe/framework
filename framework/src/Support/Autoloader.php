<?php

declare(strict_types=1);

namespace Kodhe\Framework\Support;

/**
 * Autoloader - PSR-4 Compatible Autoloader with CI3 Compatibility
 * 
 * Modernized version with typed properties, strict types, and improved performance.
 * Maintains backward compatibility with legacy CodeIgniter 3 autoloading.
 * 
 * @package Kodhe\Framework\Support
 * @since 2.0.0
 */
class Autoloader
{
    /**
     * @var array<string, string[]> Namespace prefixes to directory paths
     */
    private static array $prefixes = [];

    /**
     * @var array<string, string> Class aliases
     */
    private static array $aliases = [];

    /**
     * @var bool Whether autoloader is registered
     */
    private static bool $registered = false;

    /**
     * @var self|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize autoloader with default namespaces
     */
    public function initialize(): void
    {
        // Register framework namespace
        $this->addNamespace('Kodhe\Framework', ROOTPATH . 'framework/src');
        
        // Register application namespaces
        $this->addNamespace('App', APPPATH);
        $this->addNamespace('Modules', APPPATH . 'modules');
        
        // Register the autoloader with SPL
        $this->register();
    }

    /**
     * Add a namespace prefix with its base directory
     * 
     * @param string $prefix Namespace prefix (e.g., 'App\Controllers')
     * @param string $baseDir Base directory path
     * @param bool $prepend Whether to prepend instead of append
     * @return self
     */
    public function addNamespace(string $prefix, string $baseDir, bool $prepend = false): self
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!isset(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }

        if ($prepend) {
            array_unshift(self::$prefixes[$prefix], $baseDir);
        } else {
            self::$prefixes[$prefix][] = $baseDir;
        }

        return $this;
    }

    /**
     * Add a class alias (legacy compatibility)
     * 
     * @param string $space Alias name
     * @param string $path Original class path
     * @return self
     */
    public function addSpace(string $space, string $path): self
    {
        self::$aliases[$space] = $path;
        return $this;
    }

    /**
     * Register the autoloader with SPL
     * 
     * @return self
     */
    public function register(): self
    {
        if (!self::$registered) {
            spl_autoload_register([$this, 'autoload'], true, true);
            self::$registered = true;
        }
        return $this;
    }

    /**
     * Unregister the autoloader
     * 
     * @return self
     */
    public function unregister(): self
    {
        if (self::$registered) {
            spl_autoload_unregister([$this, 'autoload']);
            self::$registered = false;
        }
        return $this;
    }

    /**
     * Autoload a class
     * 
     * @param string $class Fully qualified class name
     * @return void
     */
    public function autoload(string $class): void
    {
        $this->loadClass($class);
    }

    /**
     * Load a class (legacy method name for compatibility)
     * 
     * @param string $class Class name
     */
    public function loadClass(string $class): void
    {
        // Check for alias first
        $class = ltrim($class, '\\');
        
        foreach (self::$aliases as $alias => $original) {
            if (strpos($class, $alias) === 0) {
                $class = $original . substr($class, strlen($alias));
                break;
            }
        }

        // Try PSR-4 namespaces
        foreach (self::$prefixes as $prefix => $baseDirs) {
            if (strpos($class, $prefix) === 0) {
                $relativeClass = substr($class, strlen($prefix));
                
                foreach ($baseDirs as $baseDir) {
                    $filePath = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
                    
                    if (file_exists($filePath)) {
                        require_once $filePath;
                        return;
                    }
                }
            }
        }

        // Fallback to legacy loading
        $this->loadLegacy($class);
    }

    /**
     * Legacy class loading fallback
     * 
     * @param string $class Class name
     * @return void
     */
    private function loadLegacy(string $class): void
    {
        // Try loading from legacy paths
        $legacyPaths = [
            APPPATH . 'libraries/',
            APPPATH . 'models/',
            APPPATH . 'controllers/',
        ];

        $fileName = $class . '.php';

        foreach ($legacyPaths as $path) {
            $filePath = $path . $fileName;
            if (file_exists($filePath)) {
                require_once $filePath;
                return;
            }
        }
    }

    /**
     * Get all registered namespaces
     * 
     * @return array<string, string[]>
     */
    public function getNamespaces(): array
    {
        return self::$prefixes;
    }

    /**
     * Get all registered aliases/spaces
     * 
     * @return array<string, string>
     */
    public function getSpaces(): array
    {
        return self::$aliases;
    }

    /**
     * Check if autoloader is registered
     * 
     * @return bool
     */
    public function isRegistered(): bool
    {
        return self::$registered;
    }

    /**
     * Reset autoloader state (useful for testing)
     */
    public function reset(): void
    {
        $this->unregister();
        self::$prefixes = [];
        self::$aliases = [];
        self::$instance = null;
    }
}
