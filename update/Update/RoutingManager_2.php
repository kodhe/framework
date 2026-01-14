<?php namespace Kodhe\Framework\Routing;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Support\Facades\Facade;
use Kodhe\Framework\Config\Config;
use Kodhe\Framework\Support\Modules;
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
        $this->initializeModules();
    }
    
    /**
     * Initialize router configuration
     */
    protected function initializeConfig(): void
    {
        // Default configuration
        $this->configuration = [
            'enable_query_strings' => defined('REQ') && REQ === 'CP',
            'controller_suffix' => config_item('controller_suffix') ?? '',
            'default_controller' => config_item('default_controller') ?? 'Welcome',
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
            '404_override' => config_item('404_override') ?? '',
            'show_404_on_missing' => true,
            'default_404_controller' => 'FileNotFound',
            'default_404_namespace' => 'Kodhe\\Controllers\\Error\\',
            'enable_auto_route' => true,
            'auto_detect_module' => true,
            'translate_uri_dashes' => config_item('translate_uri_dashes') ?? false
        ];
        
        // Apply config ke router
        $this->router->setConfig($this->configuration);
    }
    
    /**
     * Initialize modules system
     */
    protected function initializeModules(): void
    {
        Modules::init();
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
        
        // Update internal configuration
        $this->configuration = array_merge($this->configuration, $config);
        $this->router->setConfig($this->configuration);
    }

    /**
     * Resolve routing dengan UnifiedRouter
     */
    public function resolve(Request $request): array
    {
        $uri = $this->getRequestUri($request);
        $cacheKey = md5($uri . '_unified_v2');
        
        // Check cache
        if (isset($this->routeCache[$cacheKey]) && $this->configuration['cache_routes']) {
            $cachedRouting = $this->routeCache[$cacheKey];
            $this->lastRouting = $cachedRouting;
            log_message('debug', 'Routing resolved from cache: ' . $uri);
            return $cachedRouting;
        }
        
        log_message('debug', 'Resolving routing for URI: ' . $uri);
        
        // Priority 1: Try modern routing first if enabled and preferred
        $routing = [];
        
        if ($this->configuration['enable_modern_routing'] && $this->configuration['prefer_modern']) {
            $routing = $this->resolveModern($request);
            if ($this->isValidRouting($routing)) {
                log_message('debug', 'Modern routing found for URI: ' . $uri);
                $routing = $this->enrichRouting($routing);
                $this->cacheRouting($cacheKey, $routing);
                return $routing;
            }
        }
        
        // Priority 2: Try legacy routing
        if ($this->configuration['enable_legacy_routing']) {
            $routing = $this->resolveLegacy($request);
            if ($this->isValidRouting($routing)) {
                log_message('debug', 'Legacy routing found for URI: ' . $uri);
                $routing = $this->enrichRouting($routing);
                $this->cacheRouting($cacheKey, $routing);
                return $routing;
            }
        }
        
        // Priority 3: Try modern routing as fallback
        if ($this->configuration['enable_modern_routing'] && !$this->configuration['prefer_modern']) {
            $routing = $this->resolveModern($request);
            if ($this->isValidRouting($routing)) {
                log_message('debug', 'Modern routing (fallback) found for URI: ' . $uri);
                $routing = $this->enrichRouting($routing);
                $this->cacheRouting($cacheKey, $routing);
                return $routing;
            }
        }
        
        // Priority 4: Check for 404 override
        $override404 = $this->get404Override();
        if ($override404) {
            log_message('debug', 'Using 404 override for URI: ' . $uri);
            $routing = $this->parse404Override($override404);
            $routing['is_404'] = true;
            $routing = $this->enrichRouting($routing);
            $this->cacheRouting($cacheKey, $routing);
            return $routing;
        }
        
        // Priority 5: Default 404 routing
        log_message('debug', 'No routing found, using default 404 for URI: ' . $uri);
        $routing = $this->getErrorRouting();
        $routing['is_404'] = true;
        $routing = $this->enrichRouting($routing);
        $this->cacheRouting($cacheKey, $routing);
        return $routing;
    }
    
    /**
     * Resolve modern routing
     */
    protected function resolveModern(Request $request): array
    {
        try {
            // Use UnifiedRouter's modern routing capability
            $routing = $this->router->resolve($request);
            
            if ($this->isValidRouting($routing) && $routing['type'] === 'modern') {
                return $routing;
            }
        } catch (\Exception $e) {
            log_message('error', 'Modern routing error: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Resolve legacy routing
     */
    protected function resolveLegacy(Request $request): array
    {
        try {
            // Setup URI untuk legacy routing
            $uri = $this->getRequestUri($request);
            $this->router->uri->uri_string = trim($uri, '/');
            $this->router->uri->segments = explode('/', trim($uri, '/'));
            
            // Execute legacy routing
            $this->router->_set_routing();
            
            // Get routing info
            $routing = $this->router->getRouting();
            
            if ($this->isValidRouting($routing)) {
                $routing['type'] = 'legacy';
                $routing['router_type'] = 'legacy';
                return $routing;
            }
        } catch (\Exception $e) {
            log_message('error', 'Legacy routing error: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Enrich routing dengan informasi tambahan
     */
    protected function enrichRouting(array $routing): array
    {
        // Ensure required keys
        $defaults = [
            'type' => $routing['type'] ?? 'legacy',
            'router_type' => $routing['type'] ?? 'legacy',
            'source' => $routing['source'] ?? 'routing_manager',
            'segments' => $routing['segments'] ?? [],
            'parameters' => $routing['parameters'] ?? [],
            'method_valid' => true,
            'is_404' => false
        ];
        
        $routing = array_merge($defaults, $routing);
        
        // Extract dan set module info
        $routing = $this->extractModuleInfo($routing);
        
        // Untuk modern routing, tambahkan FQCN jika belum ada
        if ($routing['type'] === 'modern' && empty($routing['fqcn']) && !empty($routing['class'])) {
            $routing['fqcn'] = $this->determineFullyQualifiedClassName($routing);
        }
        
        // Untuk legacy routing, tambahkan file path jika belum ada
        if ($routing['type'] === 'legacy' && empty($routing['file']) && !empty($routing['class'])) {
            $routing['file'] = $this->locateLegacyControllerFile($routing);
        }
        
        // Validate method existence
        $routing['method_valid'] = $this->validateMethod($routing);
        
        // Set last routing
        $this->lastRouting = $routing;
        
        // Set module di Modules registry
        if (!empty($routing['module']) && method_exists('Modules', 'setCurrentModule')) {
            Modules::setCurrentModule($routing['module']);
        }
        
        return $routing;
    }
    
    /**
     * Extract module information dari routing
     */
    protected function extractModuleInfo(array $routing): array
    {
        // Jika sudah ada module info, return
        if (!empty($routing['module'])) {
            return $routing;
        }
        
        // Priority 1: Dari router
        $module = $this->router->fetch_module();
        if (!empty($module)) {
            $routing['module'] = $module;
            return $routing;
        }
        
        // Priority 2: Extract dari directory
        if (!empty($routing['directory'])) {
            $module = $this->extractModuleFromDirectory($routing['directory']);
            if ($module) {
                $routing['module'] = $module;
                return $routing;
            }
        }
        
        // Priority 3: Extract dari namespace
        if (!empty($routing['namespace'])) {
            $module = $this->extractModuleFromNamespace($routing['namespace']);
            if ($module) {
                $routing['module'] = $module;
                return $routing;
            }
        }
        
        // Priority 4: Extract dari class name
        if (!empty($routing['class'])) {
            $module = $this->extractModuleFromClassName($routing['class']);
            if ($module) {
                $routing['module'] = $module;
                return $routing;
            }
        }
        
        // Priority 5: Extract dari URI
        if (!empty($routing['uri'])) {
            $segments = explode('/', trim($routing['uri'], '/'));
            if (!empty($segments[0]) && Modules::moduleExists($segments[0])) {
                $routing['module'] = $segments[0];
                return $routing;
            }
        }
        
        return $routing;
    }
    
    /**
     * Extract module dari directory path
     */
    protected function extractModuleFromDirectory(string $directory): ?string
    {
        // Pattern: modules/{module}/controllers/
        if (preg_match('#modules/([^/]+)/controllers/#', $directory, $matches)) {
            $module = $matches[1];
            if (Modules::moduleExists($module)) {
                return $module;
            }
        }
        
        // Pattern: ../modules/{module}/controllers/
        if (preg_match('#\.\./modules/([^/]+)/controllers/#', $directory, $matches)) {
            $module = $matches[1];
            if (Modules::moduleExists($module)) {
                return $module;
            }
        }
        
        return null;
    }
    
    /**
     * Extract module dari namespace
     */
    protected function extractModuleFromNamespace(string $namespace): ?string
    {
        // Pattern: App\Modules\{Module}\Controllers
        if (preg_match('#(?:App\\\|Kodhe\\\)Modules\\\([^\\\]+)#i', $namespace, $matches)) {
            $module = strtolower($matches[1]);
            if (Modules::moduleExists($module)) {
                return $module;
            }
        }
        
        return null;
    }
    
    /**
     * Extract module dari class name
     */
    protected function extractModuleFromClassName(string $className): ?string
    {
        // Jika class mengandung backslash, coba extract dari namespace
        if (strpos($className, '\\') !== false) {
            return $this->extractModuleFromNamespace($className);
        }
        
        return null;
    }
    
    /**
     * Determine fully qualified class name untuk modern routing
     */
    protected function determineFullyQualifiedClassName(array $routing): string
    {
        // Already fully qualified
        if (isset($routing['class']) && strpos($routing['class'], '\\') !== false) {
            return $routing['class'];
        }
    
        // Dari modern routing dengan action string
        if (isset($routing['action']) && is_string($routing['action'])) {
            if (strpos($routing['action'], '@') !== false) {
                list($controller, $method) = explode('@', $routing['action'], 2);
                if (strpos($controller, '\\') !== false) {
                    return $controller;
                }
            } else {
                $controller = $routing['action'];
                if (strpos($controller, '\\') !== false) {
                    return $controller;
                }
            }
        }
    
        // Coba build dari namespace dan class
        $namespace = $routing['namespace'] ?? $this->determineNamespace($routing);
        $className = isset($routing['class']) ? ucfirst($routing['class']) : '';
        
        $fqcn = $namespace . $className;
        
        // Try tanpa suffix dulu
        if (class_exists($fqcn)) {
            return $fqcn;
        }
        
        // Try dengan suffix
        $suffix = $this->configuration['controller_suffix'] ?? '';
        if ($suffix) {
            $fqcnWithSuffix = $fqcn . $suffix;
            if (class_exists($fqcnWithSuffix)) {
                return $fqcnWithSuffix;
            }
        }
    
        return $fqcn;
    }
    
    /**
     * Determine namespace untuk controller
     */
    protected function determineNamespace(array $routing): string
    {
        // Priority 1: Namespace dari route item (jika ada)
        if (isset($routing['namespace']) && !empty($routing['namespace'])) {
            return $this->normalizeNamespace($routing['namespace']);
        }

        // Priority 2: Jika class sudah fully qualified
        if (isset($routing['class']) && str_contains($routing['class'], '\\')) {
            $namespace = substr($routing['class'], 0, strrpos($routing['class'], '\\'));
            return $namespace;
        }

        // Priority 3: Dari action string (modern routes)
        if (isset($routing['action']) && is_string($routing['action'])) {
            if (str_contains($routing['action'], '@')) {
                [$controller, $method] = explode('@', $routing['action'], 2);
                if (str_contains($controller, '\\')) {
                    $namespace = substr($controller, 0, strrpos($controller, '\\'));
                    return $namespace;
                }
            }
        }

        // Priority 4: Default namespace dengan module jika ada
        $module = $routing['module'] ?? '';
        if (!empty($module)) {
            return 'App\\Modules\\' . ucfirst($module) . '\\Controllers\\';
        }

        // Priority 5: Default app namespace
        return $this->configuration['namespaces']['app'];
    }
    
    /**
     * Normalize namespace
     */
    protected function normalizeNamespace(string $namespace): string
    {
        $namespace = trim($namespace, '\\');
        return $namespace === '' ? '' : $namespace . '\\';
    }
    
    /**
     * Locate legacy controller file
     */
    protected function locateLegacyControllerFile(array $routing): ?string
    {
        // Jika ada module, coba cari via Modules
        if (!empty($routing['module'])) {
            $controllerFile = Modules::file_path($routing['module'], 'controllers', $routing['class']);
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
     * Validate method existence
     */
    protected function validateMethod(array $routing): bool
    {
        if ($routing['type'] === 'modern' && isset($routing['fqcn'])) {
            if (!class_exists($routing['fqcn'])) {
                return false;
            }
            
            $method = strtolower($routing['method'] ?? 'index');
            $methods = get_class_methods($routing['fqcn']);
            
            return in_array('_remap', $methods) || 
                   in_array($method, array_map('strtolower', $methods)) ||
                   method_exists($routing['fqcn'], '__call');
        }

        if ($routing['type'] === 'legacy') {
            $className = ucfirst($routing['class']);
            
            // Check for controller suffix
            $suffix = $this->configuration['controller_suffix'] ?? '';
            if ($suffix) {
                $suffixedName = $className . $suffix;
                if (class_exists($suffixedName)) {
                    $className = $suffixedName;
                }
            }
            
            if (!class_exists($className)) {
                // Load controller file jika ada
                if (isset($routing['file']) && file_exists($routing['file'])) {
                    require_once $routing['file'];
                    
                    if (!class_exists($className)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            
            $method = strtolower($routing['method']);
            $methods = get_class_methods($className);
            
            return in_array('_remap', $methods) || 
                   in_array($method, array_map('strtolower', $methods)) ||
                   method_exists($className, '__call');
        }

        return false;
    }
    
    /**
     * Cache routing result
     */
    protected function cacheRouting(string $cacheKey, array $routing): void
    {
        if ($this->configuration['cache_routes']) {
            $this->routeCache[$cacheKey] = $routing;
        }
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
    
    /**
     * Get module info
     */
    public function getModuleInfo(): array
    {
        $module = $this->getModule();
        if (empty($module)) {
            return [];
        }
        
        $modulePath = Modules::path($module);
        
        return [
            'name' => $module,
            'path' => $modulePath,
            'exists' => !empty($modulePath),
            'config' => Modules::config($module, true)
        ];
    }
    
    /**
     * Check if routing is valid
     */
    protected function isValidRouting(array $routing): bool
    {
        if (empty($routing['class']) || empty($routing['method'])) {
            return false;
        }
        
        // Check if it's a 404 routing
        if (isset($routing['is_404']) && $routing['is_404']) {
            return true;
        }
        
        return true;
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
     * Get error routing for 404
     */
    public function getErrorRouting(): array
    {
        $uri = '';
        
        if (isset($GLOBALS['URI'])) {
            $uri = $GLOBALS['URI']->uri_string ?? '';
        }
        
        $get = $_GET;
        unset($get['D'], $get['C'], $get['M'], $get['S']);
        
        $qs = '';
        if (!empty($get)) {
            $qs = '?' . http_build_query($get);
        }
        
        // Gunakan default 404 controller dari konfigurasi
        $controller = $this->configuration['default_404_controller'];
        $namespace = $this->configuration['default_404_namespace'];
        
        return [
            'class' => $controller,
            'method' => 'index',
            'segments' => [$uri . $qs],
            'type' => 'modern',
            'namespace' => $namespace,
            'fqcn' => $namespace . $controller,
            'source' => 'error',
            'uri' => $uri,
            'is_404' => true,
            'method_valid' => true
        ];
    }
    
    /**
     * Get simplified 404 routing untuk legacy compatibility
     */
    public function getLegacy404Routing(): array
    {
        return [
            'directory' => '',
            'class' => 'FileNotFound',
            'method' => 'index',
            'segments' => [],
            'type' => 'legacy',
            'source' => 'legacy_404',
            'file' => null,
            'is_404' => true
        ];
    }
    
    /**
     * Clear route cache
     */
    public function clearCache(): void
    {
        $this->routeCache = [];
        $this->router->clearCache();
        Modules::clearCache();
        
        log_message('debug', 'Routing cache cleared');
    }

    /**
     * Update configuration
     */
    public function setConfig(array $config): void
    {
        $this->configuration = array_merge($this->configuration, $config);
        $this->router->setConfig($config);
        $this->clearCache();
        
        log_message('debug', 'Routing configuration updated');
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
            log_message('debug', 'Routing overridden: ' . print_r($routing, true));
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
    
    /**
     * Check if legacy routing is enabled
     */
    public function isLegacyRoutingEnabled(): bool
    {
        return $this->configuration['enable_legacy_routing'] ?? false;
    }
    
    /**
     * Check if controller exists
     */
    public function controllerExists(array $routing): bool
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
     * Get all registered routes (for debugging)
     */
    public function getRoutes(): array
    {
        return $this->router->getRoutes();
    }
    
    /**
     * Generate URL for named route
     */
    public function url(string $name, array $parameters = []): string
    {
        return $this->router->url($name, $parameters);
    }
    
    /**
     * Register a route (delegated to router)
     */
    public function get(string $uri, $action): RouteItem
    {
        return $this->router->get($uri, $action);
    }
    
    public function post(string $uri, $action): RouteItem
    {
        return $this->router->post($uri, $action);
    }
    
    public function put(string $uri, $action): RouteItem
    {
        return $this->router->put($uri, $action);
    }
    
    public function patch(string $uri, $action): RouteItem
    {
        return $this->router->patch($uri, $action);
    }
    
    public function delete(string $uri, $action): RouteItem
    {
        return $this->router->delete($uri, $action);
    }
    
    public function any(string $uri, $action): RouteItem
    {
        return $this->router->any($uri, $action);
    }
    
    public function match(array $methods, string $uri, $action): RouteItem
    {
        return $this->router->match($methods, $uri, $action);
    }
    
    public function group(array $attributes, callable $callback): void
    {
        $this->router->group($attributes, $callback);
    }
    
    public function module(string $module, callable $callback, array $attributes = []): void
    {
        $this->router->module($module, $callback, $attributes);
    }
    
    /**
     * Get last resolved routing
     */
    public function getLastRouting(): array
    {
        return $this->lastRouting;
    }
    
    /**
     * Refresh modules cache
     */
    public function refreshModulesCache(): bool
    {
        return Modules::refreshCache();
    }
    
    /**
     * Debug: Show routing statistics
     */
    public function getStats(): array
    {
        $routes = $this->getRoutes();
        
        return [
            'total_routes' => count($routes),
            'cached_routes' => count($this->routeCache),
            'modern_enabled' => $this->configuration['enable_modern_routing'],
            'legacy_enabled' => $this->configuration['enable_legacy_routing'],
            'prefer_modern' => $this->configuration['prefer_modern'],
            'cache_enabled' => $this->configuration['cache_routes'],
            'last_routing' => $this->lastRouting ? [
                'type' => $this->lastRouting['type'] ?? 'none',
                'class' => $this->lastRouting['class'] ?? 'none',
                'method' => $this->lastRouting['method'] ?? 'none',
                'module' => $this->lastRouting['module'] ?? 'none'
            ] : 'none',
            'current_module' => $this->getModule()
        ];
    }
}