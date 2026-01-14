<?php namespace Kodhe\Framework\Routing;

use Kodhe\Framework\Support\Facades\Facade;
use Kodhe\Framework\Container\Container;
use Kodhe\Framework\Foundation\Application;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Support\Modules;
use Kodhe\Framework\Exceptions\Http\{
    NotFoundException,
    BadRequestException,
    UnauthorizedException,
    ForbiddenException,
    ValidationException,
    MethodNotAllowedException
};

/**
 * Controller Executor dengan Hybrid Support dan Module Integration
 */
class ControllerExecutor
{
    /**
     * @var Facade Facade instance
     */
    protected $facade;

    /**
     * @var RoutingManager Routing manager instance
     */
    protected $routingManager;

    /**
     * @var Container Container instance
     */
    protected $container;

    /**
     * @var Application Application instance
     */
    protected $application;

    /**
     * @var ModernRouter Modern router instance
     */
    protected $modernRouter;
    
    /**
     * @var string Current module
     */
    protected $currentModule = '';

    /**
     * Constructor
     */
    public function __construct(Facade $facade, RoutingManager $routingManager)
    {
        $this->facade = $facade;
        $this->routingManager = $routingManager;
        
        // Get container dari facade jika ada
        if ($facade->has('di')) {
            $this->container = $facade->get('di');
        } else {
            $this->container = new Container();
        }
        
        // Try to get application dari global atau container
        $this->application = $this->resolveApplication();
        
        // Initialize Modules system
        Modules::init();
    }

    /**
     * Set modern router instance
     */
    public function setModernRouter(ModernRouter $modernRouter): void
    {
        $this->modernRouter = $modernRouter;
    }

    /**
     * Set container instance
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Resolve application instance
     */
    protected function resolveApplication(): ?Application
    {
        // Coba dari container
        try {
            if ($this->container->has('Kernel')) {
                $kernel = $this->container->make('Kernel');
                // Buat application wrapper jika kernel ada
                $app = new Application($this->container);
                $appReflection = new \ReflectionProperty($app, 'kernel');
                $appReflection->setAccessible(true);
                $appReflection->setValue($app, $kernel);
                return $app;
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        return null;
    }

    /**
     * Execute controller based on routing dengan module context
     */
    public function execute(array $routing): void
    {
        // Set module context
        $this->setModuleContext($routing);
        
        // Cek apakah ini 404 routing
        if (isset($routing['is_404']) && $routing['is_404']) {
            $this->handle404($routing);
            return;
        }
        
        switch ($routing['type'] ?? 'legacy') {
            case 'modern':
                $this->executeModernController($routing);
                break;
                
            case 'legacy':
                $this->executeLegacyController($routing);
                break;
                
            case 'error':
                $this->executeErrorController($routing);
                break;
                
            default:
                $this->handle404();
                break;
        }
    }
    
    /**
     * Set module context dari routing
     */
    protected function setModuleContext(array $routing): void
    {
        $this->currentModule = $routing['module'] ?? '';
        
        // Set di container untuk diakses
        if ($this->container) {
            $this->container->register('current.module', $this->currentModule);
        }
        
        // Set di facade jika ada
        if ($this->facade && !$this->facade->has('current.module')) {
            $this->facade->set('current.module', $this->currentModule);
        }
        
        // Set di Modules registry
        if (!empty($this->currentModule) && method_exists('Modules', 'setCurrentModule')) {
            Modules::setCurrentModule($this->currentModule);
        }
        
        // Untuk legacy compatibility, set di CI instance
        if (function_exists('get_instance') && !empty($this->currentModule)) {
            $CI = get_instance();
            if (property_exists($CI, 'module')) {
                $CI->module = $this->currentModule;
            }
        }
    }
    
    /**
     * Handle 404 dengan konsisten
     */
    protected function handle404(array $routing = []): void
    {
        // Set HTTP status code ke 404
        http_response_code(404);
        
        // Priority 1: Gunakan custom 404 controller dari routing jika ada
        if (!empty($routing['fqcn']) && class_exists($routing['fqcn'])) {
            $this->executeModernController($routing);
            return;
        }
        
        // Priority 2: Gunakan error routing dari RoutingManager
        $errorRouting = $this->routingManager->getErrorRouting();
        if ($this->controllerExists($errorRouting)) {
            $this->executeErrorController($errorRouting);
            return;
        }
        
        // Fallback ke 404 default
        throw NotFoundException::endpoint();
    }
    
    protected function executeModernController(array $routing): void
    {
        // Priority: Jika ada modern router, gunakan itu
        if ($this->modernRouter && isset($routing['route'])) {
            $this->executeWithModernRouter($routing);
            return;
        }
        
        // Fallback: Cek jika routing berasal dari route item
        if (isset($routing['_route_item']) && $routing['_route_item'] instanceof RouteItem) {
            $this->executeFromRouteItem($routing);
            return;
        }
        
        // Fallback ke eksekusi controller langsung
        $this->executeModernControllerDirect($routing);
    }

    protected function executeWithModernRouter(array $routing): void
    {
        try {
            $request = $this->getRequest();
            $response = $this->getResponse();
            
            $result = $this->modernRouter->execute($routing, $request, $response);
            
            if ($result instanceof Response) {
                $this->handleResponse($result);
            } elseif (is_array($result)) {
                // If it's routing info, execute as controller
                if (isset($result['type'])) {
                    $this->executeModernControllerDirect($result);
                } else {
                    // Return as JSON
                    $this->handleResponseFromResult($result);
                }
            } else {
                $this->handleResponseFromResult($result);
            }
        } catch (\Exception $e) {
            // Fallback to direct execution
            try {
                $this->executeModernControllerDirect($routing);
            } catch (\Exception $e2) {
                $this->handleException($e2, $routing);
            }
        }
    }

    /**
     * Execute controller from route item information
     */
    protected function executeFromRouteItem(array $routing): void
    {
        /** @var RouteItem $routeItem */
        $routeItem = $routing['_route_item'];
        
        // Get routing info dari route item
        $routeInfo = $routeItem->getRoutingInfo();
        
        // Merge dengan routing yang ada
        $routing = array_merge($routeInfo, $routing);
        
        // Tambahkan module info jika belum ada
        if (empty($routing['module']) && !empty($this->currentModule)) {
            $routing['module'] = $this->currentModule;
        }
        
        // Sekarang eksekusi seperti biasa
        $this->executeModernControllerDirect($routing);
    }

    /**
     * Execute modern controller directly (without middleware)
     */
    protected function executeModernControllerDirect(array $routing): void
    {
        // Jika belum ada namespace, coba tentukan dari module
        if (!isset($routing['namespace']) || empty($routing['namespace'])) {
            $module = $routing['module'] ?? $this->currentModule;
            if (!empty($module)) {
                $routing['namespace'] = 'App\\Modules\\' . ucfirst($module) . '\\Controllers\\';
            } else if (isset($routing['_route_item'])) {
                $routeItem = $routing['_route_item'];
                $routing['namespace'] = $routeItem->getNamespace();
            } else {
                $routing['namespace'] = $this->routingManager->determineNamespace($routing);
            }
        }
        
        // If fqcn is not set, try to get it from class
        if (!isset($routing['fqcn']) && isset($routing['class'])) {
            if (strpos($routing['class'], '\\') !== false) {
                $routing['fqcn'] = $routing['class'];
            } else {
                $namespace = $routing['namespace'] ?? '';
                $className = ucfirst($routing['class']);
                
                // Build FQCN dengan berbagai format
                $possibleFqcns = [];
                
                // Format 1: Namespace\ClassName
                if (!empty($namespace)) {
                    $possibleFqcns[] = rtrim($namespace, '\\') . '\\' . $className;
                }
                
                // Format 2: App\Controllers\ClassName
                $possibleFqcns[] = 'App\\Controllers\\' . $className;
                
                // Format 3: Kodhe\Controllers\ClassName
                $possibleFqcns[] = 'Kodhe\\Controllers\\' . $className;
                
                // Format 4: Module namespace jika ada module
                $module = $routing['module'] ?? $this->currentModule;
                if (!empty($module)) {
                    $possibleFqcns[] = 'App\\Modules\\' . ucfirst($module) . '\\Controllers\\' . $className;
                }
                
                // Format 5: Nama class saja (untuk autoloading)
                $possibleFqcns[] = $className;
                
                // Coba setiap kemungkinan
                foreach ($possibleFqcns as $fqcn) {
                    if (class_exists($fqcn)) {
                        $routing['fqcn'] = $fqcn;
                        break;
                    }
                }
                
                // Jika masih belum ditemukan, coba dengan suffix
                if (!isset($routing['fqcn'])) {
                    $config = $this->routingManager->getConfig();
                    $suffix = $config['controller_suffix'] ?? 'Controller';
                    
                    foreach ($possibleFqcns as $fqcn) {
                        $fqcnWithSuffix = $fqcn . $suffix;
                        if (class_exists($fqcnWithSuffix)) {
                            $routing['fqcn'] = $fqcnWithSuffix;
                            break;
                        }
                    }
                }
                
                // Jika masih gagal, buat berdasarkan namespace + class
                if (!isset($routing['fqcn'])) {
                    $routing['fqcn'] = (!empty($namespace) ? rtrim($namespace, '\\') . '\\' : '') . $className;
                }
            }
        }
        
        $class = $routing['fqcn'] ?? '';
        $method = $routing['method'] ?? 'index';
        $params = $routing['segments'] ?? [];

        if (empty($class)) {
            throw new BadRequestException('Controller class is empty');
        }

        if (!class_exists($class)) {
            throw NotFoundException::resource('controller', $class);
        }

        // Create controller dengan DI support
        $controller = $this->createControllerWithDI($class);
        
        // Inject module ke controller jika ada property
        if (!empty($this->currentModule) && property_exists($controller, 'module')) {
            $controller->module = $this->currentModule;
        }
        
        // Handle _remap
        if (method_exists($controller, '_remap')) {
            array_unshift($params, $method);
            $method = '_remap';
        }

        // Validate method
        if (!method_exists($controller, $method) && !method_exists($controller, '__call')) {
            throw NotFoundException::resource('method', $method . ' in ' . $class);
        }

        try {
            $result = $this->callControllerMethod($controller, $method, $params);
            
            if (isset($result)) {
                $this->handleResponseFromResult($result);
            }
        } catch (\Exception $ex) {
            $this->handleException($ex, $routing);
        }
    }

    /**
     * Get request instance
     */
    protected function getRequest(): Request
    {
        if ($this->facade->has('request')) {
            return $this->facade->get('request');
        }
        
        // Buat request baru dari globals
        return Request::fromGlobals();
    }

    /**
     * Get response instance
     */
    protected function getResponse(): Response
    {
        if ($this->facade->has('response')) {
            return $this->facade->get('response');
        }
        
        return new Response();
    }

    /**
     * Handle response from route execution
     */
    protected function handleResponse(Response $response): void
    {
        // Update facade response jika ada
        if ($this->facade->has('response')) {
            $facadeResponse = $this->facade->get('response');
            
            // Copy body
            $facadeResponse->setBody($response->getBody());
            
            // Copy headers
            foreach ($response->getHeaders() as $name => $value) {
                $facadeResponse->setHeader($name, $value);
            }
            
            // Copy status
            $facadeResponse->setStatus($response->getStatus());
        }
        
        // Send response jika perlu
        if (!headers_sent()) {
            $response->send();
        }
    }

    /**
     * Handle response from controller result
     */
    protected function handleResponseFromResult($result): void
    {
        if (is_string($result) || is_numeric($result) || (is_object($result) && method_exists($result, '__toString'))) {
            // Legacy output handling
            if (isset($GLOBALS['OUT'])) {
                $GLOBALS['OUT']->set_output((string)$result);
            } else {
                $response = $this->facade->has('response') ? $this->facade->get('response') : new Response();
                $response->setBody((string)$result);
                $this->handleResponse($response);
            }
        } elseif (is_array($result) || is_object($result)) {
            // JSON response
            $response = $this->facade->has('response') ? $this->facade->get('response') : new Response();
            $response->setJson($result);
            $this->handleResponse($response);
        } elseif ($result instanceof Response) {
            // Response object
            $this->handleResponse($result);
        } elseif ($result === null) {
            // Controller returned null, no response handling needed
        } else {
            throw new BadRequestException('Unknown result type from controller');
        }
    }

    protected function createControllerWithDI(string $class)
    {
        try {
            // Coba resolve dari container jika ada
            if ($this->container->has($class)) {
                return $this->container->make($class);
            }
            
            // Coba dengan alias atau short name
            $shortName = $this->getShortClassName($class);
            if ($this->container->has($shortName)) {
                return $this->container->make($shortName);
            }
            
            // Reflection untuk constructor injection
            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            
            if ($constructor === null) {
                $controller = new $class();
                // Inject module jika ada property
                if (!empty($this->currentModule) && property_exists($controller, 'module')) {
                    $controller->module = $this->currentModule;
                }
                return $controller;
            }
            
            $params = $constructor->getParameters();
            $args = [];
            
            foreach ($params as $param) {
                $paramName = $param->getName();
                
                // Priority: 1. Type-hinted dependencies 2. Default values 3. Null
                if ($param->hasType() && !$param->getType()->isBuiltin()) {
                    $type = $param->getType()->getName();
                    
                    // Special cases
                    if ($type === Application::class || $type === 'Kodhe\\Core\\Foundation\\Application') {
                        $args[] = $this->application ?? Application::create($this->container);
                    }
                    elseif ($type === Facade::class || $type === 'Kodhe\\Core\\Support\\Facades\\Facade') {
                        $args[] = $this->facade;
                    }
                    elseif ($type === Container::class || $type === 'Kodhe\\Core\\Container\\Container') {
                        $args[] = $this->container;
                    }
                    elseif ($type === Request::class || $type === 'Kodhe\\Core\\Http\\Request') {
                        $args[] = $this->getRequest();
                    }
                    elseif ($type === Response::class || $type === 'Kodhe\\Core\\Http\\Response') {
                        $args[] = $this->getResponse();
                    }
                    // Try container or create instance
                    elseif ($this->container->has($type)) {
                        try {
                            $args[] = $this->container->make($type);
                        } catch (\Exception $e) {
                            $args[] = $this->createInstanceWithFallback($param, $type);
                        }
                    }
                    else {
                        $args[] = $this->createInstanceWithFallback($param, $type);
                    }
                }
                elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                }
                elseif ($param->allowsNull()) {
                    $args[] = null;
                }
                else {
                    // Untuk parameter non-typehinted (legacy), beri null
                    $args[] = null;
                }
            }
            
            $controller = $reflection->newInstanceArgs($args);
            
            // Inject module jika ada property
            if (!empty($this->currentModule) && property_exists($controller, 'module')) {
                $controller->module = $this->currentModule;
            }
            
            return $controller;
            
        } catch (\Exception $e) {
            // Fallback langsung ke instantiation sederhana
            try {
                $controller = new $class();
                // Inject module jika ada property
                if (!empty($this->currentModule) && property_exists($controller, 'module')) {
                    $controller->module = $this->currentModule;
                }
                return $controller;
            } catch (\Exception $e2) {
                throw new BadRequestException("Cannot create controller {$class}: " . $e2->getMessage());
            }
        }
    }
    
    protected function createInstanceWithFallback(\ReflectionParameter $param, string $type)
    {
        try {
            return new $type();
        } catch (\Exception $e) {
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                return null;
            } else {
                throw new BadRequestException(
                    "Cannot resolve parameter \${$param->getName()} of type {$type}"
                );
            }
        }
    }
    
    protected function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }

    /**
     * Execute legacy controller dengan fallback ke 404 dan module support
     */
    protected function executeLegacyController(array $routing): void
    {
        $className = ucfirst($routing['class']);
        $method = $routing['method'];
        $params = $routing['segments'];

        // Jika ada module, coba cari controller file via Modules
        $module = $routing['module'] ?? $this->currentModule;
        if (!empty($module) && empty($routing['file'])) {
            $controllerFile = Modules::file_path($module, 'controllers', $routing['class']);
            if ($controllerFile) {
                $routing['file'] = $controllerFile;
            }
        }

        // Load controller file if not already loaded
        if (!class_exists($className, false) && isset($routing['file']) && $routing['file']) {
            if (!file_exists($routing['file'])) {
                throw NotFoundException::file($routing['file']);
            }
            require_once $routing['file'];
        }

        // Check for controller suffix
        $config = $this->routingManager->getConfig();
        if (isset($config['controller_suffix'])) {
            $suffix = $config['controller_suffix'];
            $suffixedName = $className . $suffix;
            
            if (class_exists($suffixedName)) {
                $className = $suffixedName;
            }
        }

        if (!class_exists($className)) {
            throw NotFoundException::resource('controller', $className);
        }

        // Untuk legacy controller, kita gunakan cara lama
        // tapi coba inject Application jika constructor mendukung
        $controller = $this->createLegacyController($className);
        
        // Inject module ke controller jika ada property
        if (!empty($this->currentModule) && property_exists($controller, 'module')) {
            $controller->module = $this->currentModule;
        }
        
        // Handle _remap
        if (method_exists($controller, '_remap')) {
            array_unshift($params, $method);
            $method = '_remap';
        }

        // Validate method
        if (!method_exists($controller, $method) && !method_exists($controller, '__call')) {
            throw NotFoundException::resource('method', $method . ' in ' . $className);
        }

        try {
            $result = call_user_func_array([$controller, $method], $params);
            
            if (isset($result)) {
                $this->handleResponseFromResult($result);
            }
        } catch (\Exception $ex) {
            $this->handleException($ex, $routing);
        }
    }

    /**
     * Create legacy controller dengan dukungan Application dan Module
     */
    protected function createLegacyController(string $className)
    {
        // Coba buat dengan Application jika controller extends base controller baru
        $reflection = new \ReflectionClass($className);
        
        if ($reflection->hasMethod('__construct')) {
            $constructor = $reflection->getConstructor();
            $params = $constructor->getParameters();
            
            // Check if expects Application
            foreach ($params as $param) {
                if ($param->hasType()) {
                    $type = $param->getType()->getName();
                    if ($type === Application::class || $type === 'Kodhe\\Core\\Foundation\\Application') {
                        if ($this->application) {
                            $controller = new $className($this->application);
                            // Inject module
                            if (!empty($this->currentModule) && property_exists($controller, 'module')) {
                                $controller->module = $this->currentModule;
                            }
                            return $controller;
                        }
                    }
                }
            }
        }
        
        // Fallback ke constructor lama
        $controller = new $className();
        
        // Set facade jika ada method setFacade
        if (method_exists($controller, 'setFacade')) {
            $controller->setFacade($this->facade);
        }
        
        // Inject module
        if (!empty($this->currentModule) && property_exists($controller, 'module')) {
            $controller->module = $this->currentModule;
        }
        
        return $controller;
    }

    /**
     * Call controller method dengan DI untuk parameter
     */
    protected function callControllerMethod($controller, string $method, array $params = [])
    {
        if (!method_exists($controller, $method)) {
            return $controller->__call($method, $params);
        }
        
        $reflection = new \ReflectionMethod($controller, $method);
        $resolvedParams = $this->resolveMethodParameters($reflection, $params);
        
        return $reflection->invokeArgs($controller, $resolvedParams);
    }

    /**
     * Resolve method parameters dengan DI
     */
    protected function resolveMethodParameters(\ReflectionMethod $method, array $params): array
    {
        $parameters = $method->getParameters();
        $resolvedParams = [];
        
        foreach ($parameters as $index => $parameter) {
            if (isset($params[$index])) {
                $resolvedParams[] = $params[$index];
            } elseif ($parameter->hasType() && !$parameter->getType()->isBuiltin()) {
                // Coba resolve dari container
                $type = $parameter->getType()->getName();
                try {
                    if ($this->container->has($type)) {
                        $resolvedParams[] = $this->container->make($type);
                    } else {
                        $resolvedParams[] = new $type;
                    }
                } catch (\Exception $e) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $resolvedParams[] = $parameter->getDefaultValue();
                    } elseif ($parameter->allowsNull()) {
                        $resolvedParams[] = null;
                    } else {
                        throw new BadRequestException(
                            "Cannot resolve parameter \${$parameter->getName()} of type {$type}"
                        );
                    }
                }
            } elseif ($parameter->isDefaultValueAvailable()) {
                $resolvedParams[] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                $resolvedParams[] = null;
            } else {
                throw new BadRequestException(
                    "Missing required parameter \${$parameter->getName()}"
                );
            }
        }
        
        return $resolvedParams;
    }

    /**
     * Execute error controller
     */
    protected function executeErrorController(array $routing): void
    {
        $this->executeModernController($routing);
    }

    /**
     * Handle exceptions dengan HTTP exceptions yang sesuai
     */
    protected function handleException(\Exception $ex, array $originalRouting): void
    {
        // Jika exception sudah adalah HttpException, lempar kembali
        if ($ex instanceof \Kodhe\Framework\Exceptions\Http\HttpException) {
            throw $ex;
        }
        
        // Handle exception berdasarkan tipe
        if ($ex instanceof \InvalidArgumentException) {
            throw new BadRequestException($ex->getMessage());
        } elseif ($ex instanceof \RuntimeException) {
            throw new BadRequestException($ex->getMessage());
        } elseif ($ex instanceof \LogicException) {
            throw new BadRequestException($ex->getMessage());
        }
        
        // Untuk exception lainnya, gunakan 404 atau internal server error
        // Coba gunakan error routing jika ada
        $errorRouting = $this->routingManager->getErrorRouting();
        
        if ($this->controllerExists($errorRouting)) {
            $this->executeErrorController($errorRouting);
        } else {
            // Default: throw internal server error
            throw new \Kodhe\Framework\Exceptions\Http\HttpException(
                'Internal Server Error: ' . $ex->getMessage(),
                500
            );
        }
    }
    
    /**
     * Check if controller exists
     */
    public function controllerExists(array $routing): bool
    {
        if ($routing['type'] === 'modern') {
            return class_exists($routing['fqcn']);
        }

        if ($routing['type'] === 'legacy') {
            return !empty($routing['file']) && file_exists($routing['file']);
        }

        return false;
    }

    /**
     * Check if method exists
     */
    public function methodExists(array $routing): bool
    {
        return $routing['method_valid'] ?? false;
    }

    /**
     * Get container instance
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get application instance
     */
    public function getApplication(): ?Application
    {
        return $this->application;
    }

    /**
     * Get modern router instance
     */
    public function getModernRouter(): ?ModernRouter
    {
        return $this->modernRouter;
    }
    
    /**
     * Get current module
     */
    public function getCurrentModule(): string
    {
        return $this->currentModule;
    }
    
    /**
     * Set current module
     */
    public function setCurrentModule(string $module): void
    {
        $this->currentModule = $module;
        
        // Update Modules registry
        if (method_exists('Modules', 'setCurrentModule')) {
            Modules::setCurrentModule($module);
        }
        
        // Update container
        if ($this->container) {
            $this->container->register('current.module', $module);
        }
        
        // Update facade
        if ($this->facade) {
            $this->facade->set('current.module', $module);
        }
    }
}