<?php declare(strict_types=1);

namespace Kodhe\Framework\Routing\Modern;

use Kodhe\Framework\Routing\RouteCollection;
use Kodhe\Framework\Routing\RouteItem;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Exceptions\Http\NotFoundException;

/**
 * Modern PSR-compliant Router for Kodhe Framework
 * 
 * Handles modern routing with namespace support, middleware,
 * and RESTful resource routing. Completely separated from legacy CI3 logic.
 */
class ModernRouter
{
    protected RouteCollection $collection;
    protected array $config;
    protected string $module = '';
    
    protected const DEFAULT_CONFIG = [
        'cache_routes' => false,
        'auto_detect_namespace' => true,
        'allow_namespace_in_routes' => true,
        'controller_suffix' => '',
        'default_404_controller' => 'FileNotFound',
        'default_404_namespace' => 'Kodhe\\Controllers\\Error\\'
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge(self::DEFAULT_CONFIG, $config);
        $this->collection = new RouteCollection();
        
        // Set global route collection reference
        \Kodhe\Framework\Routing\Route::setCollection($this->collection);
        
        $this->loadRoutes();
    }

    /**
     * Load routes from modern route files
     */
    protected function loadRoutes(): void
    {
        // Try load from cache first
        if ($this->config['cache_routes'] && $this->collection->loadFromCache()) {
            log_message('debug', 'Modern routes loaded from cache');
            return;
        }

        $routeFiles = $this->collectRouteFiles();

        foreach ($routeFiles as $file) {
            if (file_exists($file)) {
                require $file;
            }
        }

        // Cache routes if enabled
        if ($this->config['cache_routes']) {
            $this->collection->cache();
        }

        log_message('debug', 'Modern routes loaded from files');
    }

    /**
     * Collect all route file paths
     */
    protected function collectRouteFiles(): array
    {
        $routeFiles = [];
        
        // Scan modules for routes
        $moduleLocations = \Kodhe\Framework\Support\Modules::folders();
        foreach ($moduleLocations as $location) {
            $modules = $this->scanModulesInLocation($location);
            
            foreach ($modules as $module) {
                $this->module = $module;
                
                $moduleWebRoutes = $location . $module . '/routes/web.php';
                if (file_exists($moduleWebRoutes)) {
                    $routeFiles[] = $moduleWebRoutes;
                }
                
                $moduleApiRoutes = $location . $module . '/routes/api.php';
                if (file_exists($moduleApiRoutes)) {
                    $routeFiles[] = $moduleApiRoutes;
                }
            }
        }

        // Add base application routes
        $routeFiles[] = APPPATH . 'routes/api.php';
        $routeFiles[] = APPPATH . 'routes/console.php';
        $routeFiles[] = APPPATH . 'routes/web.php';

        return $routeFiles;
    }

    /**
     * Scan modules in a location
     */
    protected function scanModulesInLocation(string $location): array
    {
        $modules = [];
        
        if (!is_dir($location)) {
            return $modules;
        }
        
        $items = scandir($location);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $modulePath = $location . $item;
            if (is_dir($modulePath)) {
                $modules[] = $item;
            }
        }
        
        return $modules;
    }

    /**
     * Match request to modern route
     */
    public function matchRequest(Request $request): ?array
    {
        $route = $this->collection->match($request);

        if (!$route) {
            return null;
        }

        return $this->extractRoutingInfo($route);
    }

    /**
     * Extract routing information from matched route
     */
    protected function extractRoutingInfo(RouteItem $route): array
    {
        $action = $route->getAction();
        $parameters = $route->getParameters();

        if ($action instanceof \Closure) {
            return [
                'class' => 'Closure',
                'method' => '__invoke',
                'segments' => array_values($parameters),
                'type' => 'modern',
                'source' => 'modern_router',
                'route' => $route,
                'parameters' => $parameters,
                'middleware' => $route->getMiddleware(),
                'namespace' => $route->getNamespace()
            ];
        }

        if (is_string($action)) {
            $method = 'index';
            if (strpos($action, '@') !== false) {
                [$class, $method] = explode('@', $action, 2);
            } else {
                $class = $action;
            }

            return [
                'class' => $class,
                'method' => $method,
                'segments' => array_values($parameters),
                'type' => 'modern',
                'source' => 'modern_router',
                'route' => $route,
                'parameters' => $parameters,
                'middleware' => $route->getMiddleware(),
                'namespace' => $route->getNamespace()
            ];
        }

        if (is_array($action)) {
            [$class, $method] = $action;
            return [
                'class' => $class,
                'method' => $method,
                'segments' => array_values($parameters),
                'type' => 'modern',
                'source' => 'modern_router',
                'route' => $route,
                'parameters' => $parameters,
                'middleware' => $route->getMiddleware(),
                'namespace' => $route->getNamespace()
            ];
        }

        return null;
    }

    /**
     * Execute modern route
     */
    public function execute(array $routing, Request $request, Response $response): mixed
    {
        if (!isset($routing['route']) || !$routing['route'] instanceof RouteItem) {
            throw new \InvalidArgumentException('ModernRouter: Invalid route for execution');
        }

        $route = $routing['route'];
        $result = $route->run($request, $response);
        
        if (is_array($result) && isset($result['type'])) {
            return $result;
        }
        
        if ($result instanceof Response) {
            return $result;
        }
        
        $response->setBody((string)$result);
        return $response;
    }

    /**
     * Clear route cache
     */
    public function clearCache(): void
    {
        $this->collection->clearCache();
    }

    /**
     * Get all routes for debugging
     */
    public function getRoutes(): array
    {
        return $this->collection->getRoutes();
    }

    /**
     * Get route collection
     */
    public function getCollection(): RouteCollection
    {
        return $this->collection;
    }
}
