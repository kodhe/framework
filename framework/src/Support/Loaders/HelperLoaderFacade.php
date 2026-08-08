<?php

declare(strict_types=0);

namespace Kodhe\Framework\Support\Loaders;

/**
 * Facade untuk Helper Loading dengan Strategy Pattern
 * 
 * Mengkoordinir multiple loader strategies dengan priority-based execution.
 * Memungkinkan lazy loading dan caching untuk performance.
 */
class HelperLoaderFacade
{
    /**
     * @var array Loader instances dengan priority grouping
     */
    protected $loaders = [];
    
    /**
     * @var array Helpers yang sudah di-load
     */
    protected $loaded = [];
    
    /**
     * @var bool Apakah sudah initialized dengan default loaders
     */
    protected $initialized = false;
    
    /**
     * Singleton instance
     * 
     * @var self|null
     */
    protected static ?self $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Constructor - private untuk enforce singleton
     */
    private function __construct()
    {
    }
    
    /**
     * Initialize dengan default loaders
     * 
     * @return self
     */
    public function initialize(): self
    {
        if ($this->initialized) {
            return $this;
        }
        
        // Register default loaders dengan priority
        // Priority lebih tinggi = dicoba lebih dulu
        
        // 100: Namespace helpers (modern approach)
        $this->addLoader(new NamespaceHelperLoader(), 100);
        
        // 50: File helpers dari framework
        $frameworkPath = defined('BASEPATH') ? BASEPATH : '';
        if ($frameworkPath) {
            $this->addLoader(new FileHelperLoader([
                $frameworkPath . 'Core/Support/Helpers/',
                $frameworkPath . 'Support/Helpers/',
            ]), 50);
        }
        
        // 25: File helpers dari application
        $appPath = defined('APPPATH') ? APPPATH : '';
        if ($appPath) {
            $this->addLoader(new FileHelperLoader([
                $appPath . 'helpers/',
            ]), 25);
        }
        
        $this->initialized = true;
        
        return $this;
    }
    
    /**
     * Add loader dengan priority
     * 
     * @param HelperLoaderInterface $loader Loader instance
     * @param int $priority Priority (higher = tried first)
     * @return self
     */
    public function addLoader(HelperLoaderInterface $loader, int $priority = 0): self
    {
        if (!isset($this->loaders[$priority])) {
            $this->loaders[$priority] = [];
        }
        
        $this->loaders[$priority][] = $loader;
        
        // Sort by priority descending
        ksort($this->loaders, SORT_DESC);
        
        return $this;
    }
    
    /**
     * Load helper atau array of helpers
     * 
     * @param string|array $helpers Helper name(s)
     * @return self
     * @throws \Exception Jika helper tidak ditemukan
     */
    public function load($helpers): self
    {
        foreach ((array)$helpers as $helper) {
            $this->loadSingle($helper);
        }
        
        return $this;
    }
    
    /**
     * Load single helper
     * 
     * @param string $helper Helper name
     * @return void
     * @throws \Exception Jika helper tidak ditemukan
     */
    protected function loadSingle(string $helper): void
    {
        // Normalize helper name
        $helper = $this->normalizeHelperName($helper);
        
        // Check if already loaded
        if (isset($this->loaded[$helper])) {
            return;
        }
        
        // Initialize if needed
        $this->initialize();
        
        // Try each loader by priority
        foreach ($this->loaders as $loaderGroup) {
            foreach ($loaderGroup as $loader) {
                if ($loader->canLoad($helper)) {
                    $loader->load($helper);
                    $this->loaded[$helper] = true;
                    return;
                }
            }
        }
        
        // Helper not found
        throw new \Exception("Helper '{$helper}' not found in any registered loader");
    }
    
    /**
     * Normalize helper name (remove _helper suffix jika ada)
     * 
     * @param string $helper Helper name
     * @return string Normalized name
     */
    protected function normalizeHelperName(string $helper): string
    {
        $helper = trim($helper);
        
        // Remove _helper suffix
        if (strtolower(substr($helper, -7)) === '_helper') {
            $helper = substr($helper, 0, -7);
        }
        
        return $helper;
    }
    
    /**
     * Check if helper is loaded
     * 
     * @param string $helper Helper name
     * @return bool
     */
    public function isLoaded(string $helper): bool
    {
        $helper = $this->normalizeHelperName($helper);
        return isset($this->loaded[$helper]);
    }
    
    /**
     * Get list of loaded helpers
     * 
     * @return array
     */
    public function getLoaded(): array
    {
        return array_keys($this->loaded);
    }
    
    /**
     * Get all registered loaders
     * 
     * @return array [priority => [loaders]]
     */
    public function getLoaders(): array
    {
        return $this->loaders;
    }
    
    /**
     * Clear loaded cache
     * 
     * @param string|null $helper Specific helper to clear or null for all
     * @return self
     */
    public function clearCache(?string $helper = null): self
    {
        if ($helper === null) {
            $this->loaded = [];
        } else {
            $helper = $this->normalizeHelperName($helper);
            unset($this->loaded[$helper]);
        }
        
        return $this;
    }
    
    /**
     * Reset instance (untuk testing)
     * 
     * @return void
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
