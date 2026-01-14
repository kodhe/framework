<?php namespace Kodhe\Framework\Routing;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Support\Facades\Facade;
use Kodhe\Framework\Config\Config;
use Kodhe\Framework\Exceptions\Http\{
    NotFoundException,
    BadRequestException,
    ForbiddenException
};

/**
 * Simplified Routing Manager dengan UnifiedRouter
 */
class RoutingManager
{
    /**
     * @var UnifiedRouter Router instance
     */
    protected $router;
    
    /**
     * @var array Router configuration
     */
    protected $configuration = [];
    
    /**
     * @var array Route cache
     */
    protected $routeCache = [];
    
    /**
     * @var array Last resolved routing
     */
    protected $lastRouting = [];
    
    /**
     * Constructor
     */
    public function __construct(?UnifiedRouter $router = null)
    {
        $this->router = $router ?? new UnifiedRouter();
        $this->initializeConfig();
    }
    
    /**
     * Initialize router configuration
     */
    protected function initializeConfig(): void
    {
        $this->configuration = [
            'enable_query_strings' => defined('REQ') && REQ === 'CP',
            'controller_suffix' => '',
            'default_controller' => 'Welcome',
            'default_method' => 'index',
            'modules' => [],
            'auto_detect_namespace' => true,
            'allow_namespace_in_routes' => true,
            'enable_modern_routing' => true,
            'enable_legacy_routing' => true,
            'prefer_modern' => true,
            'cache_routes' => ENVIRONMENT === 'production',
            'namespaces' => [
                'app' => 'App\\Controllers\\',
                'kodhe' => 'Kodhe\\Controllers\\',
                'modules' => 'App\\Modules\\'
            ],
            'router_type' => 'hybrid',
            '404_override' => '',
            'show_404_on_missing' => true,
            'default_404_controller' => 'FileNotFound',
            'default_404_namespace' => 'Kodhe\\Controllers\\Error\\',
            'enable_auto_route' => true,
            'auto_detect_module' => true
        ];
        
        // Apply config ke router
        $this->router->setConfig($this->configuration);
    }
    
    /**
     * Set routing config for CP
     */
    public function overrideRoutingConfig(): void
    {
        $routing_config = [
            'directory_trigger' => 'D',
            'controller_trigger' => 'C',
            'function_trigger' => 'M',
            'enable_query_strings' => defined('REQ') && REQ == 'CP'
        ];

        $this->overrideConfig($routing_config);
    }

    /**
     * Override config
     */
    public function overrideConfig(array $config): void
    {
        if (isset($GLOBALS['CFG']) && method_exists($GLOBALS['CFG'], '_assign_to_config')) {
            $GLOBALS['CFG']->_assign_to_config($config);
        }
    }

    /**
     * Resolve routing dengan UnifiedRouter
     */
    public function resolve(Request $request): array
    {
        $uri = $this->getRequestUri($request);
        $cacheKey = md5($uri . '_unified');
        
        // Check cache
        if (isset($this->routeCache[$cacheKey]) && $this->configuration['cache_routes']) {
            $cachedRouting = $this->routeCache[$cacheKey];
            $this->lastRouting = $cachedRouting;
            return $cachedRouting;
        }
        
        // Resolve menggunakan UnifiedRouter
        $routing = $this->router->resolve($request);
        
        // Jika tidak valid, coba 404 override
        if (!$this->isValidRouting($routing)) {
            $override404 = $this->get404Override();
            if ($override404) {
                $routing = $this->parse404Override($override404);
            } else {
                $routing = $this->router->getErrorRouting();
            }
        }
        
        // Validasi controller exists
        if (!$this->controllerExists($routing)) {
            $routing = $this->router->getErrorRouting();
        }
        
        // Simpan sebagai last routing
        $this->lastRouting = $routing;
        
        // Cache result
        if ($this->configuration['cache_routes']) {
            $this->routeCache[$cacheKey] = $routing;
        }
        
        return $routing;
    }
    
    /**
     * Get request URI
     */
    protected function getRequestUri(Request $request): string
    {
        $uri = $request->getUri();
        
        if ($uri instanceof \Kodhe\Framework\Http\Uri) {
            $path = $uri->getPath();
        } elseif (is_string($uri)) {
            $parsed = parse_url($uri);
            $path = $parsed['path'] ?? '/';
        } else {
            $path = '/';
        }
        
        // Normalize path
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }
    
    /**
     * Get module dari current routing
     */
    public function getModule(): string
    {
        // Coba dari last routing
        if (!empty($this->lastRouting['module'])) {
            return $this->lastRouting['module'];
        }
        
        // Coba dari router
        return $this->router->fetch_module();
    }
    
    public function getModuleInfo(): array
    {
        $module = $this->getModule();
        if (empty($module)) {
            return [];
        }
        
        $modulePath = \Kodhe\Framework\Support\Modules::path($module);
        
        return [
            'name' => $module,
            'path' => $modulePath,
            'exists' => !empty($modulePath),
            'config' => \Kodhe\Framework\Support\Modules::config($module, true)
        ];
    }
    
    /**
     * Check if routing is valid
     */
    protected function isValidRouting(array $routing): bool
    {
        return !empty($routing['class']) && !empty($routing['method']);
    }
    
    /**
     * Get 404 override dari konfigurasi
     */
    protected function get404Override(): ?string
    {
        // Coba dari konfigurasi routing
        if (!empty($this->configuration['404_override'])) {
            return $this->configuration['404_override'];
        }
        
        // Coba dari konfigurasi global CI jika ada
        if (isset($GLOBALS['CFG'])) {
            $config = $GLOBALS['CFG'];
            if (isset($config->config['404_override'])) {
                return $config->config['404_override'];
            }
        }
        
        return null;
    }
    
    /**
     * Parse 404 override string ke routing array
     */
    protected function parse404Override(string $override): array
    {
        $parts = explode('/', $override);
        
        $controller = ucfirst($parts[0]);
        $method = $parts[1] ?? 'index';
        
        $routing = [
            'class' => $controller,
            'method' => $method,
            'segments' => [],
            'source' => '404_override',
            'uri' => '404',
            'type' => 'legacy',
            'is_404' => true
        ];
        
        // Cari controller file
        $routing['file'] = $this->locateLegacyControllerFile($routing);
        
        return $routing;
    }
    
    /**
     * Locate legacy controller file
     */
    protected function locateLegacyControllerFile(array $routing): ?string
    {
        // Jika ada module, coba cari via Modules
        if (!empty($routing['module'])) {
            $controllerFile = \Kodhe\Framework\Support\Modules::file_path($routing['module'], 'controllers', $routing['class']);
            if ($controllerFile) {
                return $controllerFile;
            }
        }
        
        $basePath = resolve_path(APPPATH, 'controllers/');
        
        // Without suffix
        $filePath = $basePath . 
            ($routing['directory'] ?? '') . 
            ucfirst($routing['class']) . '.php';
        
        if (file_exists($filePath)) {
            return $filePath;
        }
        
        // With suffix
        $suffix = $this->configuration['controller_suffix'] ?? '';
        if ($suffix) {
            $filePathWithSuffix = $basePath . 
                ($routing['directory'] ?? '') . 
                ucfirst($routing['class']) . $suffix . '.php';
            
            if (file_exists($filePathWithSuffix)) {
                return $filePathWithSuffix;
            }
        }

        return null;
    }
    
    /**
     * Cek apakah controller exists
     */
    protected function controllerExists(array $routing): bool
    {
        if ($routing['type'] === 'modern') {
            return isset($routing['fqcn']) && class_exists($routing['fqcn']);
        }

        if ($routing['type'] === 'legacy' && isset($routing['file'])) {
            return file_exists($routing['file']);
        }

        $file = $this->locateLegacyControllerFile($routing);
        return $file !== null && file_exists($file);
    }
    
    /**
     * Clear route cache
     */
    public function clearCache(): void
    {
        $this->routeCache = [];
        $this->router->clearCache();
    }

    /**
     * Update configuration
     */
    public function setConfig(array $config): void
    {
        $this->configuration = array_merge($this->configuration, $config);
        $this->router->setConfig($config);
        $this->clearCache();
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->configuration;
    }
    
    /**
     * Get current router instance
     */
    public function getRouter(): UnifiedRouter
    {
        return $this->router;
    }
    
    /**
     * Override routing
     */
    public function overrideRouting(array $routing): void
    {
        if (method_exists($this->router, '_set_overrides')) {
            $this->router->_set_overrides($routing);
        }
    }
    
    /**
     * Check if using modern routing
     */
    public function isModernRouting(): bool
    {
        return $this->configuration['enable_modern_routing'];
    }
    
    /**
     * Check if modern routing is enabled
     */
    public function isModernRoutingEnabled(): bool
    {
        return $this->configuration['enable_modern_routing'] ?? false;
    }
}