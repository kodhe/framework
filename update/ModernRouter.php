<?php namespace Kodhe\Framework\Routing;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Support\Modules;
use Kodhe\Framework\Exceptions\Http\{
    NotFoundException,
    BadRequestException
};

class ModernRouter 
{
    /**
     * @var RouteCollection
     */
    protected $collection;

    /**
     * @var array Current routing
     */
    protected $routing = [];

    /**
     * @var string Current class
     */
    public $class = '';

    /**
     * @var string Current method
     */
    public $method = 'index';

    /**
     * @var string Current directory
     */
    public $directory = '';

    /**
     * @var array Legacy compatibility properties
     */
    public $module = '';
    protected $located = 0;
    public $default_controller = 'Welcome';
    public $translate_uri_dashes = false;
    public $routes = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->collection = new RouteCollection();
        
        // Set collection to Route class
        Route::setCollection($this->collection);
        
        $this->loadRoutes();
    }

    /**
     * Load routes from files dengan module context
     */
    protected function loadRoutes(): void
    {
        // Coba load dari cache dulu
        if ($this->collection->loadFromCache()) {
            return;
        }

        // Initialize modules
        Modules::init();
        
        $routeFiles = [];

        // Tambahkan route files dari semua lokasi module
        $moduleLocations = Modules::folders();
        foreach ($moduleLocations as $location) {
            $modules = $this->scanModulesInLocation($location);
            
            foreach ($modules as $module) {
                // Set module context
                $this->module = $module;
                
                // Load module routes
                $moduleWebRoutes = $location . $module . '/routes/web.php';
                if (file_exists($moduleWebRoutes)) {
                    $routeFiles[] = $moduleWebRoutes;
                }
                
                $moduleApiRoutes = $location . $module . '/routes/api.php';
                if (file_exists($moduleApiRoutes)) {
                    $routeFiles[] = $moduleApiRoutes;
                }
                
                // Reset module context
                $this->module = '';
            }
        }

        // Add base routes
        $routeFiles[] = APPPATH . 'routes/api.php';
        $routeFiles[] = APPPATH . 'routes/console.php';
        $routeFiles[] = APPPATH . 'routes/web.php';
 
        // Load semua file route
        foreach ($routeFiles as $file) {
            if (file_exists($file)) {
                require $file;
            }
        }

        // Pastikan semua route terdaftar
        Route::registerToCollection($this->collection);

        // Cache routes
        $this->collection->cache();
    }

    /**
     * Scan modules dalam sebuah lokasi
     */
    protected function scanModulesInLocation(string $location): array
    {
        $modules = [];
        
        if (!is_dir($location)) {
            return $modules;
        }
        
        // Scan directory untuk module
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
     * Match request to route dengan module extraction
     */
    public function matchRequest(Request $request): ?array
    {
        $route = $this->collection->match($request);

        if (!$route) {
            return null;
        }

        // Extract routing information
        $action = $route->getAction();
        $parameters = $route->getParameters();

        // Determine controller and method
        if ($action instanceof \Closure) {
            $class = 'Closure';
            $method = '__invoke';
            $type = 'closure';
        } elseif (is_string($action)) {
            if (strpos($action, '@') !== false) {
                list($class, $method) = explode('@', $action, 2);
            } else {
                $class = $action;
                $method = 'index';
            }
            $type = 'controller';
        } elseif (is_array($action)) {
            @list($class, $method) = $action;
            $type = 'controller';
        } else {
            return null;
        }

        $this->routing = [
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

        // Extract module dari route
        $this->routing['module'] = $this->extractModuleFromRoute($route, $this->routing);
        
        // Set module property
        $this->module = $this->routing['module'];

        return $this->routing;
    }

    /**
     * Extract module dari RouteItem
     */
    protected function extractModuleFromRoute(RouteItem $route, array $routing): string
    {
        // Priority 1: Dari namespace route
        $namespace = $route->getNamespace();
        if (!empty($namespace)) {
            if (preg_match('#Modules\\\([^\\\]+)#i', $namespace, $matches)) {
                $module = strtolower($matches[1]);
                if (Modules::moduleExists($module)) {
                    return $module;
                }
            }
        }
        
        // Priority 2: Dari controller class
        $class = $routing['class'] ?? '';
        if (!empty($class)) {
            if (preg_match('#Modules\\\([^\\\]+)#i', $class, $matches)) {
                $module = strtolower($matches[1]);
                if (Modules::moduleExists($module)) {
                    return $module;
                }
            }
        }
        
        // Priority 3: Dari URI pattern (untuk dynamic routes)
        $uri = $route->getUri();
        if (preg_match('#^/([^/]+)/#', $uri, $matches)) {
            $module = $matches[1];
            if (Modules::moduleExists($module)) {
                return $module;
            }
        }
        
        // Priority 4: Dari route group attributes (jika ada)
        $groupAttributes = Route::getGroupAttributes();
        if (!empty($groupAttributes['module'])) {
            return $groupAttributes['module'];
        }
        
        return '';
    }

    /**
     * Execute route
     */
    public function execute(array $routing, Request $request, Response $response): mixed
    {
        if (!isset($routing['route']) || !$routing['route'] instanceof RouteItem) {
            throw new BadRequestException('ModernRouter: Invalid route for execution');
        }

        /** @var RouteItem $route */
        $route = $routing['route'];

        // Execute the route
        $result = $route->run($request, $response);
        
        // If result is routing info (for controllers), return it
        if (is_array($result) && isset($result['type'])) {
            return $result;
        }
        
        // If result is already a response, return it
        if ($result instanceof Response) {
            return $result;
        }
        
        // Otherwise, set result as response body
        $response->setBody((string)$result);
        return $response;
    }

    /**
     * Get routing information
     */
    public function getRouting(): ?array
    {
        return $this->routing;
    }

    /**
     * Generate URL for named route
     */
    public function url(string $name, array $parameters = []): string
    {
        $route = $this->collection->getByName($name);
        
        if (!$route) {
            throw NotFoundException::resource('route', $name);
        }
        
        return $route->url($parameters);
    }

    /**
     * Clear route cache
     */
    public function clearCache(): void
    {
        $this->collection->clearCache();
    }

    /**
     * Get all routes (for debugging)
     */
    public function getRoutes(): array
    {
        return $this->collection->getRoutes();
    }

    /**
     * Set module
     */
    public function setModule(string $module): void
    {
        $this->module = $module;
        
        // Update Modules registry
        if (method_exists('Modules', 'setCurrentModule')) {
            Modules::setCurrentModule($module);
        }
    }

    /**
     * Get module
     */
    public function getModule(): string
    {
        return $this->module;
    }

}