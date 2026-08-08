<?php

declare(strict_types=0);

namespace Kodhe\Framework\Support\Loaders;

/**
 * Helper Loader menggunakan Strategy Pattern untuk namespace modern
 * 
 * Load helper functions dari class-based helpers dengan static registration.
 * Contoh: Kodhe\Framework\Support\Helpers\UrlHelper::register()
 */
class NamespaceHelperLoader implements HelperLoaderInterface
{
    /**
     * @var string Namespace prefix untuk helper classes
     */
    protected $namespacePrefix;
    
    /**
     * @var array Mapping helper name ke class name
     */
    protected $mapping = [];
    
    /**
     * Constructor
     * 
     * @param string $namespacePrefix Default namespace prefix (default: Kodhe\Framework\Support\Helpers\)
     * @param array $mapping Optional mapping untuk override default naming
     */
    public function __construct(
        string $namespacePrefix = 'Kodhe\\Framework\\Support\\Helpers\\',
        array $mapping = []
    ) {
        $this->namespacePrefix = rtrim($namespacePrefix, '\\') . '\\';
        $this->mapping = $mapping;
    }
    
    /**
     * {@inheritDoc}
     */
    public function canLoad(string $helper): bool
    {
        $className = $this->getClassName($helper);
        return class_exists($className, false) || class_exists($className);
    }
    
    /**
     * {@inheritDoc}
     */
    public function load(string $helper): void
    {
        $className = $this->getClassName($helper);
        
        if (!class_exists($className)) {
            throw new \Exception("Helper class not found: {$className}");
        }
        
        // Call static register method jika ada
        if (method_exists($className, 'register')) {
            $className::register();
        }
    }
    
    /**
     * Get class name untuk helper tertentu
     * 
     * @param string $helper Nama helper
     * @return string Fully qualified class name
     */
    protected function getClassName(string $helper): string
    {
        // Check mapping dulu
        if (isset($this->mapping[$helper])) {
            return $this->mapping[$helper];
        }
        
        // Default: PascalCase + Helper suffix
        return $this->namespacePrefix . ucfirst($helper) . 'Helper';
    }
    
    /**
     * Add custom mapping
     * 
     * @param string $helper Helper name
     * @param string $className Full class name
     * @return self
     */
    public function addMapping(string $helper, string $className): self
    {
        $this->mapping[$helper] = $className;
        return $this;
    }
    
    /**
     * Set namespace prefix
     * 
     * @param string $prefix Namespace prefix
     * @return self
     */
    public function setNamespacePrefix(string $prefix): self
    {
        $this->namespacePrefix = rtrim($prefix, '\\') . '\\';
        return $this;
    }
}
