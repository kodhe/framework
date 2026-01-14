<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace Kodhe\Framework\Support;

/**
 * ExpressionEngine Autoloader
 *
 * Really basic autoloader using the PSR-4 autoloading rules.
 * Modified to support lowercase folder names like CodeIgniter 3.
 */
class Autoloader
{
    protected $prefixes = array();
    protected $spaces = array();

    protected static $instance;

    /**
     * Use as a singleton
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Register the autoloader with PHP
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
        return $this;
    }

    /**
     * Remove the autoloader
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
        return $this;
    }

    /**
     * Map a namespace prefix to a SINGLE path
     */
    public function addPrefix($namespace, $path)
    {
        $this->prefixes[$namespace] = rtrim($path, '/') . '/';
        return $this;
    }

    /**
     * Map a namespace prefix to MULTIPLE paths
     */
    public function addSpace($namespace, $path)
    {
        if (!isset($this->spaces[$namespace])) {
            $this->spaces[$namespace] = array();
        }
        
        $normalizedPath = rtrim($path, '/') . '/';
        if (!in_array($normalizedPath, $this->spaces[$namespace])) {
            $this->spaces[$namespace][] = $normalizedPath;
        }
        
        return $this;
    }

    /**
     * Get all registered prefixes
     */
    public function getPrefixes()
    {
        return $this->prefixes;
    }

    /**
     * Get all registered spaces
     */
    public function getSpaces()
    {
        return $this->spaces;
    }

    /**
     * Handle the autoload call.
     */
    public function loadClass($class)
    {
        // Check spaces first (multiple paths per namespace)
        foreach ($this->spaces as $prefix => $paths) {
            if (empty($prefix)) {
                continue;
            }

            if (strpos($class, $prefix) === 0) {
                foreach ($paths as $path) {
                    if ($this->tryLoadClass($class, $prefix, $path)) {
                        return;
                    }
                }
            }
        }

        // Check prefixes (single path per namespace)
        foreach ($this->prefixes as $prefix => $path) {
            if (empty($prefix)) {
                continue;
            }

            if (strpos($class, $prefix) === 0) {
                if ($this->tryLoadClass($class, $prefix, $path)) {
                    return;
                }
            }
        }
    }

    /**
     * Try to load class from a specific path
     */
    protected function tryLoadClass($class, $prefix, $path)
    {
        $relativeClass = substr($class, strlen($prefix));
        $className = basename(str_replace('\\', '/', $relativeClass));
        
        // Define search patterns
        $patterns = array();
        
        // 1. Original PSR-4 path
        $patterns[] = $path . str_replace('\\', '/', $relativeClass) . '.php';
        
        // 2. CI3 style (all lowercase)
        $patterns[] = $this->buildPath($relativeClass, $path, true);
        
        // 3. Lowercase folders only
        $patterns[] = $this->buildPath($relativeClass, $path, false);
        
        // 4. CI3 underscore style
        $patterns[] = $this->buildUnderscorePath($relativeClass, $path);
        
        // 5. Controller variations if applicable
        if (substr($className, -10) === 'Controller') {
            $patterns = array_merge($patterns, $this->getControllerPatterns($relativeClass, $path));
        }
        
        // Try each pattern
        foreach ($patterns as $pattern) {
            if (file_exists($pattern)) {
                require_once $pattern;
                return true;
            }
        }
        
        // 6. Case-insensitive search as last resort
        return $this->tryCaseInsensitiveSearch($class, $prefix, $path);
    }

    /**
     * Build path with optional lowercase conversion
     */
    protected function buildPath($relativeClass, $basePath, $lowercaseAll = false)
    {
        $parts = explode('\\', trim($relativeClass, '\\'));
        
        if (empty($parts)) {
            return $basePath . '.php';
        }
        
        $className = array_pop($parts);
        
        // Process folders
        foreach ($parts as &$part) {
            $part = strtolower($part);
        }
        
        // Process class name
        if ($lowercaseAll) {
            $className = strtolower($className);
        }
        
        // Rebuild path
        $path = implode('/', array_filter($parts));
        if (!empty($path)) {
            $path .= '/';
        }
        
        return $basePath . $path . $className . '.php';
    }

    /**
     * Build CI3 underscore style path
     */
    protected function buildUnderscorePath($relativeClass, $basePath)
    {
        $parts = explode('\\', trim($relativeClass, '\\'));
        
        foreach ($parts as &$part) {
            if (strpos($part, '_') !== false) {
                $part = strtolower($part);
            } else {
                $part = strtolower($this->pascalToUnderscore($part));
            }
        }
        
        return $basePath . implode('/', array_filter($parts)) . '.php';
    }

    /**
     * Get controller-specific search patterns
     */
    protected function getControllerPatterns($relativeClass, $basePath)
    {
        $patterns = array();
        $parts = explode('\\', trim($relativeClass, '\\'));
        
        if (empty($parts)) {
            return $patterns;
        }
        
        $className = array_pop($parts);
        $simpleName = substr($className, 0, -10);
        $underscoreName = $this->pascalToUnderscore($className);
        
        // Process folders to lowercase
        foreach ($parts as &$part) {
            $part = strtolower($part);
        }
        
        $folderPath = implode('/', array_filter($parts));
        if (!empty($folderPath)) {
            $folderPath .= '/';
        }
        
        // Controller patterns
        $patterns[] = $basePath . $folderPath . $underscoreName . '.php';
        $patterns[] = $basePath . $folderPath . $simpleName . '.php';
        $patterns[] = $basePath . $folderPath . strtolower($simpleName) . '.php';
        
        return $patterns;
    }

    /**
     * Try case-insensitive search
     */
    protected function tryCaseInsensitiveSearch($class, $prefix, $basePath)
    {
        $relativeClass = substr($class, strlen($prefix));
        $parts = explode('\\', trim($relativeClass, '\\'));
        $currentPath = rtrim($basePath, '/');
        
        foreach ($parts as $index => $part) {
            $isLast = ($index === count($parts) - 1);
            
            if ($isLast) {
                // Try different filename variations
                $variations = array(
                    $part . '.php',
                    strtolower($part) . '.php',
                    $this->pascalToUnderscore($part) . '.php'
                );
                
                if (substr($part, -10) === 'Controller') {
                    $simple = substr($part, 0, -10);
                    $variations[] = $simple . '.php';
                    $variations[] = strtolower($simple) . '.php';
                }
                
                foreach ($variations as $filename) {
                    $filePath = $currentPath . '/' . $filename;
                    if (file_exists($filePath)) {
                        require_once $filePath;
                        return true;
                    }
                }
                
                return false;
            }
            
            // Find matching directory
            $found = false;
            if (is_dir($currentPath)) {
                $items = scandir($currentPath);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    
                    if (strtolower($item) === strtolower($part) && is_dir($currentPath . '/' . $item)) {
                        $currentPath .= '/' . $item;
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found) {
                return false;
            }
        }
        
        return false;
    }

    /**
     * Convert PascalCase to underscore
     */
    protected function pascalToUnderscore($className)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * Merge another autoloader
     */
    public function merge(Autoloader $other)
    {
        $this->prefixes = array_merge($this->prefixes, $other->getPrefixes());
        
        foreach ($other->getSpaces() as $namespace => $paths) {
            foreach ($paths as $path) {
                $this->addSpace($namespace, $path);
            }
        }
        
        return $this;
    }

    /**
     * Add multiple namespaces at once
     */
    public function addNamespaces(array $namespaces)
    {
        foreach ($namespaces as $namespace => $paths) {
            if (is_array($paths)) {
                foreach ($paths as $path) {
                    $this->addNamespaces($namespace, $path);
                }
            } else {
                $this->addNamespaces($namespace, $paths);
            }
        }
        
        return $this;
    }
}