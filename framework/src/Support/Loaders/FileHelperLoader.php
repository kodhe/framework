<?php

declare(strict_types=0);

namespace Kodhe\Framework\Support\Loaders;

/**
 * Helper Loader untuk file helper tradisional CodeIgniter 3
 * 
 * Mencari dan load file helper dengan berbagai variasi path dan naming:
 * - {path}/{helper}_helper.php
 * - {path}/helpers/{helper}_helper.php
 * - {path}/{Helper}Helper.php
 */
class FileHelperLoader implements HelperLoaderInterface
{
    /**
     * @var array Daftar path yang akan dicari
     */
    protected $searchPaths = [];
    
    /**
     * @var array Cache untuk file yang sudah ditemukan
     */
    protected $fileCache = [];
    
    /**
     * Constructor
     * 
     * @param array $paths Daftar path untuk mencari helper files
     */
    public function __construct(array $paths = [])
    {
        $this->searchPaths = $paths;
    }
    
    /**
     * {@inheritDoc}
     */
    public function canLoad(string $helper): bool
    {
        return $this->findHelperFile($helper) !== null;
    }
    
    /**
     * {@inheritDoc}
     */
    public function load(string $helper): void
    {
        $file = $this->findHelperFile($helper);
        
        if ($file === null) {
            throw new \Exception("Helper file not found: {$helper}");
        }
        
        // Check if already loaded
        if (isset($this->fileCache[$helper])) {
            return;
        }
        
        require_once $file;
        $this->fileCache[$helper] = $file;
    }
    
    /**
     * Cari file helper dengan berbagai variasi
     * 
     * @param string $helper Nama helper
     * @return string|null Full path ke file helper atau null jika tidak ditemukan
     */
    protected function findHelperFile(string $helper): ?string
    {
        $helperLower = strtolower($helper);
        $helperPascal = ucfirst($helper);
        
        // Pola pencarian file
        $patterns = [
            '{helper}_helper.php',
            '{helper}.php',
            '{Helper}Helper.php',
            'helpers/{helper}_helper.php',
            'Helpers/{Helper}Helper.php',
        ];
        
        foreach ($this->searchPaths as $basePath) {
            $basePath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR;
            
            foreach ($patterns as $pattern) {
                // Replace placeholders
                $file = str_replace(
                    ['{helper}', '{Helper}'],
                    [$helperLower, $helperPascal],
                    $pattern
                );
                
                $fullPath = $basePath . $file;
                
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
                
                // Try with resolve_path for case-insensitive directories
                if (function_exists('resolve_path')) {
                    $dirName = dirname($file);
                    $fileName = basename($file);
                    
                    if ($dirName !== '.' && $dirName !== '') {
                        $resolvedDir = resolve_path($basePath, $dirName);
                        $resolvedPath = $resolvedDir . $fileName;
                        
                        if (file_exists($resolvedPath)) {
                            return $resolvedPath;
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Add search path
     * 
     * @param string $path Path to add
     * @param int $priority Priority (higher = searched first)
     * @return self
     */
    public function addSearchPath(string $path, int $priority = 0): self
    {
        $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
        
        if (!isset($this->searchPaths[$priority])) {
            $this->searchPaths[$priority] = [];
        }
        
        $this->searchPaths[$priority][] = $path;
        ksort($this->searchPaths, SORT_DESC);
        
        // Flatten array
        $this->searchPaths = array_merge(...$this->searchPaths);
        
        return $this;
    }
    
    /**
     * Set all search paths
     * 
     * @param array $paths Array of paths
     * @return self
     */
    public function setSearchPaths(array $paths): self
    {
        $this->searchPaths = array_map(function($path) {
            return rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
        }, $paths);
        
        return $this;
    }
    
    /**
     * Get loaded helpers
     * 
     * @return array Map of helper => file path
     */
    public function getLoaded(): array
    {
        return $this->fileCache;
    }
    
    /**
     * Clear cache
     * 
     * @return self
     */
    public function clearCache(): self
    {
        $this->fileCache = [];
        return $this;
    }
}
