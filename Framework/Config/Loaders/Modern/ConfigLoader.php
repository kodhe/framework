<?php declare(strict_types=1);

namespace Kodhe\Framework\Config\Loaders\Modern;

/**
 * Modern PSR-compliant Configuration Loader for Kodhe Framework
 * 
 * Provides a clean, modern approach to loading configuration files
 * with strict typing and proper error handling.
 * Separated from legacy CI3 loader logic.
 */
class ConfigLoader
{
    protected array $config = [];
    protected string $environment;
    protected bool $cacheEnabled;
    protected string $cachePath;

    public function __construct(
        string $environment = 'development',
        bool $cacheEnabled = false,
        string $cachePath = ''
    ) {
        $this->environment = $environment;
        $this->cacheEnabled = $cacheEnabled;
        $this->cachePath = $cachePath ?: WRITEPATH . 'cache/config_';
    }

    /**
     * Load configuration file
     * 
     * @param string $file Configuration file name (without extension)
     * @param string|null $group Optional group name for namespaced config
     * @return array Loaded configuration
     * @throws \RuntimeException If file not found
     */
    public function load(string $file, ?string $group = null): array
    {
        $cacheKey = $group ? "{$group}.{$file}" : $file;
        
        // Try cache first
        if ($this->cacheEnabled) {
            $cached = $this->loadFromCache($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $config = $this->loadConfigFile($file, $group);

        // Cache if enabled
        if ($this->cacheEnabled) {
            $this->saveToCache($cacheKey, $config);
        }

        return $config;
    }

    /**
     * Load configuration from file
     */
    protected function loadConfigFile(string $file, ?string $group = null): array
    {
        $paths = $this->getConfigPaths($group);

        foreach ($paths as $path) {
            $filePath = $path . $file . '.php';
            
            if (file_exists($filePath)) {
                return $this->parseConfigFile($filePath);
            }
        }

        // Check environment-specific config
        $envPaths = $this->getEnvironmentConfigPaths($group);
        foreach ($envPaths as $path) {
            $filePath = $path . $this->environment . '/' . $file . '.php';
            
            if (file_exists($filePath)) {
                return $this->parseConfigFile($filePath);
            }
        }

        throw new \RuntimeException("Configuration file '{$file}' not found");
    }

    /**
     * Parse configuration file
     */
    protected function parseConfigFile(string $filePath): array
    {
        $config = [];
        
        $loadConfig = function (string $file) use (&$config): void {
            $loaded = include $file;
            if (is_array($loaded)) {
                $config = array_merge($config, $loaded);
            }
        };

        $loadConfig($filePath);

        return $config;
    }

    /**
     * Get base configuration paths
     */
    protected function getConfigPaths(?string $group = null): array
    {
        $paths = [APPPATH . 'config/'];
        
        if ($group) {
            $paths[] = APPPATH . 'config/' . $group . '/';
        }

        return $paths;
    }

    /**
     * Get environment-specific configuration paths
     */
    protected function getEnvironmentConfigPaths(?string $group = null): array
    {
        $paths = [APPPATH . 'config/'];
        
        if ($group) {
            $paths[] = APPPATH . 'config/' . $group . '/';
        }

        return $paths;
    }

    /**
     * Load from cache
     */
    protected function loadFromCache(string $key): ?array
    {
        $cacheFile = $this->cachePath . md5($key) . '.cache';
        
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = @unserialize($content);
            
            if ($data !== false && is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Save to cache
     */
    protected function saveToCache(string $key, array $config): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }

        $cacheFile = $this->cachePath . md5($key) . '.cache';
        file_put_contents($cacheFile, serialize($config));
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(): void
    {
        if (is_dir($this->cachePath)) {
            $files = glob($this->cachePath . '*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Set configuration value
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config[array_shift($keys)] = $value;
    }

    /**
     * Get configuration value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $config = $this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config)) {
                return $default;
            }
            $config = $config[$k];
        }

        return $config;
    }

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $config = $this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config)) {
                return false;
            }
            $config = $config[$k];
        }

        return true;
    }

    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }
}
