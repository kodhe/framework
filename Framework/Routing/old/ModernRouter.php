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
     * Load routes from files
     */
    protected function loadRoutes(): void
    {
        // Coba load dari cache dulu
        if ($this->collection->loadFromCache()) {
            return;
        }

        // Initialize modules untuk mendapatkan lokasi
        Modules::init();
        
        // Base route files
        $routeFiles = [
            //APPPATH . 'routes/web.php',
            //APPPATH . 'routes/api.php',
            //APPPATH . 'routes/console.php'
        ];

        // Tambahkan route files dari semua lokasi module
        $moduleLocations = Modules::folders();
        foreach ($moduleLocations as $location) {

            // Cari file routes di dalam setiap module
            $modules = $this->scanModulesInLocation($location);
            
            foreach ($modules as $module) {
   
                // Juga tambahkan file routes di subfolder routes jika ada
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


        $routeFiles[] = APPPATH . 'routes/api.php';
        $routeFiles[] = APPPATH . 'routes/console.php';
        $routeFiles[] = APPPATH . 'routes/web.php';
 
        

        // Load semua file route
        foreach ($routeFiles as $file) {
            if (file_exists($file)) {
                require $file;
            }
        }

        // Pastikan semua route dari Route class terdaftar di collection
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
     * Match request to route
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

        return $this->routing;
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

}