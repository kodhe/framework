<?php namespace Kodhe\Framework\Routing;

use Kodhe\Framework\Support\Modules;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Exceptions\Http\{
    NotFoundException,
    BadRequestException,
    ForbiddenException
};

class Router extends LegacyRouter
{
    protected $collection;

    public $module = '';
    protected $located = 0;
    
    protected const CONTROLLER_SEPARATORS = ['::', '@', '/'];
    protected const DEFAULT_CONTROLLER_NAMES = ['Home', 'Index', 'Main', 'Welcome'];
    protected const URI_DASH_REPLACEMENT_RANGE = [0, 1, 2];

    public function __construct() 
    {
        parent::__construct();
        Modules::init(); // Initialize modules system
    }

    protected function _set_request(array $segments = []): void
    {
        $segments = $this->normalizeSegments($segments);
        
        if ($this->shouldHandleDefaultController($segments)) {
            $this->handleDefaultController();
            return;
        }
        
        $this->resetRequestState();
        
        if ($this->tryParseNamespaceSegments($segments)) {
            return;
        }
        
        $this->processRegularRouting($segments);
    }

    protected function handleDefaultController(): void
    {
        $this->_set_default_controller();
    }

    protected function resetRequestState(): void
    {
        $this->located = 0;
        $this->class = '';
        $this->method = '';
        $this->module = '';
    }

    protected function tryParseNamespaceSegments(array &$segments): bool
    {
        $parsedSegments = $this->parseNamespaceSegments($segments);
        
        if ($this->located > 0 && !empty($this->class)) {
            // Set module dari namespace jika ada
            $this->extractModuleFromNamespace();
            return true;
        }
        
        if ($parsedSegments !== $segments && !empty($parsedSegments)) {
            $segments = $parsedSegments;
        }
        
        return false;
    }

    protected function processRegularRouting(array $segments): void
    {
        // Apply module routes terlebih dahulu
        $segments = $this->applyModuleRoutes($segments);
        
        $locatedSegments = $this->locate($segments);
        
        if ($this->shouldShow404()) {
            $this->handle404();
            return;
        }
        
        $this->processLocatedSegments($locatedSegments, $segments);
        
        // Extract module setelah routing ditemukan
        $this->extractModuleFromRouting();
    }

    protected function handle404(): void
    {
        if (!empty($this->routes['404_override'])) {
            $this->handle404Override();
        } 
    }

    protected function processLocatedSegments(array $locatedSegments, array $originalSegments): void
    {
        if (!empty($locatedSegments)) {
            $this->processSegments($locatedSegments);
        } else {
            $this->processSegments($originalSegments);
        }
    }

    protected function normalizeSegments(array $segments): array
    {
        if ($this->translate_uri_dashes !== true) {
            return $segments;
        }
        
        foreach (self::URI_DASH_REPLACEMENT_RANGE as $index) {
            if (isset($segments[$index])) {
                $segments[$index] = str_replace('-', '_', $segments[$index]);
            }
        }
        
        return $segments;
    }

    protected function shouldHandleDefaultController(array $segments): bool
    {
        return empty($segments) || (count($segments) === 1 && empty($segments[0]));
    }

    protected function shouldShow404(): bool
    {
        return $this->located == -1;
    }

    protected function processSegments(array $segments): void
    {
        if (!empty($this->class)) {
            return;
        }
        
        if (count($segments) === 1 && $this->located === 1) {
            $segments[1] = 'index';
        }
        
        $this->set_class($segments[0]);
        $this->set_method($segments[1] ?? 'index');
        
        $this->uri->rsegments = $this->prepareRsegments($segments);
    }

    protected function prepareRsegments(array $segments): array
    {
        array_unshift($segments, null);
        unset($segments[0]);
        return $segments;
    }

    protected function _set_default_controller(): void
    {
        if ($this->trySetNamespaceDefaultController()) {
            return;
        }
        
        if (empty($this->directory)) {
            if (!empty($this->default_controller)) {
                $this->_set_module_path($this->default_controller);
            }
        }
        
        parent::_set_default_controller();
        
        if (empty($this->class)) {
            $this->handle404Override();
        }
        
        // Extract module dari default controller
        $this->extractModuleFromRouting();
    }

    protected function trySetNamespaceDefaultController(): bool
    {
        if (empty($this->default_controller) || strpos($this->default_controller, '\\') === false) {
            return false;
        }
        
        $segments = $this->parseNamespaceSegments([$this->default_controller]);
        
        if ($this->located > 0 && !empty($this->class)) {
            $this->extractModuleFromNamespace();
            return true;
        }
        
        return false;
    }

    protected function handle404Override(): void
    {
        $override = $this->routes['404_override'] ?? '';
        
        $this->resetRequestState();
        
        if ($this->tryNamespace404Override($override)) {
            return;
        }
        
        $this->processRegular404Override($override);
    }

    protected function tryNamespace404Override(string $override): bool
    {
        if (empty($override) || strpos($override, '\\') === false) {
            return false;
        }
        
        $segments = $this->parseNamespaceSegments([$override]);
        
        if ($this->located > 0 && !empty($this->class)) {
            $this->extractModuleFromNamespace();
            return true;
        }
        
        return false;
    }

    protected function processRegular404Override(string $override): void
    {
        if (empty($override)) {
            throw NotFoundException::endpoint();
        }
        
        $routeSegments = explode('/', trim($override, '/'));
        
        if (empty($routeSegments)) {
            return;
        }
        
        $this->located = 0;
        $located = $this->locate($routeSegments);
        
        if ($this->located > 0) {
            $this->processSegments($located);
            $this->extractModuleFromRouting();
        } else {
            $this->processSegments($routeSegments);
            $this->extractModuleFromRouting();
        }
    }

    protected function locate(array $segments): array
    {
        $this->located = 0;
        $this->module = ''; // Reset module
        
        // Apply module routes
        $segments = $this->applyModuleRoutes($segments);
        
        if ($this->shouldReturnEmptySegments($segments)) {
            return $this->handleNoSegments();
        }
        
        $parsed = $this->parseSegments($segments);
        
        $result = $this->locateInModules($parsed);
        if ($result !== null) {
            // Module ditemukan, set module property
            if (!empty($this->module)) {
                // Module sudah diset di locateInModules
            } elseif (!empty($parsed['module']) && Modules::moduleExists($parsed['module'])) {
                $this->module = $parsed['module'];
            }
            return $result;
        }
        
        $result = $this->locateInControllers($parsed);
        if ($result !== null) {
            $this->extractModuleFromDirectory();
            return $result;
        }
        
        return $this->handleNotFound($segments);
    }

    protected function shouldReturnEmptySegments(array $segments): bool
    {
        return empty($segments) || empty($segments[0]);
    }

    /**
     * Apply module routes dengan Modules class
     */
    protected function applyModuleRoutes(array $segments): array
    {
        if (!isset($segments[0])) {
            return $segments;
        }
        
        $route = implode('/', $segments);
        $firstSegment = $segments[0];
        
        // Gunakan Modules::parse_routes untuk module routing
        $parsed = Modules::parse_routes($firstSegment, $route);
        
        if ($parsed && is_array($parsed)) {
            log_message('debug', "Module route matched: {$route} -> " . implode('/', $parsed));
            
            // Set module dari parsed route
            if (!empty($parsed[0])) {
                $this->module = $parsed[0];
            }
            
            return $parsed;
        }
        
        return $segments;
    }

    protected function parseNamespaceSegments(array $segments): array
    {
        if (empty($segments[0]) || strpos($segments[0], '\\') === false) {
            return $segments;
        }
        
        $fullPath = $segments[0];
        $method = 'index';
        $controllerClass = $fullPath;
        
        foreach (self::CONTROLLER_SEPARATORS as $separator) {
            $pos = strrpos($fullPath, $separator);
            if ($pos !== false) {
                $controllerClass = substr($fullPath, 0, $pos);
                $method = substr($fullPath, $pos + strlen($separator));
                break;
            }
        }
        
        if (class_exists($controllerClass)) {
            $this->setNamespaceController($controllerClass, $method, $segments);
            return [$controllerClass, $method];
        }
        
        return $segments;
    }

    protected function setNamespaceController(string $controllerClass, string $method, array $segments): void
    {
        $this->set_class($controllerClass);
        $this->set_method($method);
        
        $this->uri->rsegments = [
            1 => $controllerClass,
            2 => $method
        ];
        
        $params = array_slice($segments, 1);
        if (!empty($params)) {
            $index = 3;
            foreach ($params as $param) {
                $this->uri->rsegments[$index++] = $param;
            }
        }
        
        $this->located = 1;
    }

    protected function parseSegments(array $segments): array
    {
        $params = [];
        if (count($segments) > 3) {
            $params = array_slice($segments, 3);
        }
        
        return [
            'module' => $segments[0] ?? null,
            'controller' => $segments[1] ?? null,
            'method' => $segments[2] ?? null,
            'params' => $params
        ];
    }

    protected function handleNoSegments(): array
    {
        $this->located = -1;
        return [];
    }

    protected function locateInModules(array $parsed): ?array
    {
        foreach (Modules::$locations as $location => $offset) {
            $result = $this->checkModuleLocation($location, $offset, $parsed);
            if ($result !== null) {
                return $result;
            }
        }
        
        return null;
    }

    protected function checkModuleLocation(string $location, string $offset, array $parsed): ?array
    {
        $source = $location . $parsed['module'] . '/controllers/';
        
        if (!is_dir($source)) {
            return null;
        }
        
        // Set module property
        $this->module = $parsed['module'];
        $this->directory = $offset . $parsed['module'] . '/controllers/';
        
        if (empty($parsed['controller'])) {
            return $this->handleModuleOnly($source, $parsed);
        }
        
        return $this->handleModuleWithController($source, $parsed);
    }

    protected function handleModuleOnly(string $source, array $parsed): ?array
    {
        $moduleController = $source . ucfirst($parsed['module']) . '.php';
        if (is_file($moduleController)) {
            $this->located = 1;
            return [$parsed['module'], 'index'];
        }
        
        $homeController = $source . 'Home' . '.php';
        if (is_file($homeController)) {
            $this->located = 1;
            return ['Home', 'index'];
        }
        
        return null;
    }

    protected function handleModuleWithController(string $source, array $parsed): ?array
    {
        $controllerFile = $source . ucfirst($parsed['controller']) . '.php';
    
        if (is_file($controllerFile)) {
            $this->located = 2;
            
            return $this->buildReturnArray(
                $parsed['controller'], 
                $parsed['method'], 
                $parsed['params']
            );
        }
        
        $moduleController = $source . ucfirst($parsed['module']) . '.php';
        if (is_file($moduleController) && $this->isValidMethod($parsed['module'], $parsed['controller'])) {
            $this->located = 1;
            return $this->buildReturnArray($parsed['module'], $parsed['controller'], $parsed['params'], $parsed['method']);
        }
        
        return $this->checkModuleSubfolders($source, $parsed);
    }

    protected function checkModuleSubfolders(string $source, array $parsed): ?array
    {
        $subfolderController = $source . $parsed['controller'] . '/' . ucfirst($parsed['controller']) . '.php';
        if (is_file($subfolderController)) {
            $this->directory .= $parsed['controller'] . '/';
            $this->located = 2;
            return $this->buildReturnArray($parsed['controller'], $parsed['method'], $parsed['params']);
        }
        
        $subfolders = glob($source . '*/', GLOB_ONLYDIR);
        foreach ($subfolders as $subfolder) {
            $subfolderName = basename($subfolder);
            $subController = $subfolder . ucfirst($parsed['controller']) . '.php';
            
            if (is_file($subController)) {
                $this->directory .= $subfolderName . '/';
                $this->located = 2;
                return $this->buildReturnArray($parsed['controller'], $parsed['method'], $parsed['params']);
            }
        }
        
        return null;
    }

    protected function locateInControllers(array $parsed): ?array
    {
        $controllersPath = resolve_path(APPPATH, 'controllers');
        
        return $this->checkRootController($controllersPath, $parsed)
            ?? $this->checkControllerSubfolder($controllersPath, $parsed)
            ?? $this->checkDirectoryControllerPattern($controllersPath, $parsed);
    }

    protected function checkRootController(string $controllersPath, array $parsed): ?array
    {
        $rootController = $controllersPath . ucfirst($parsed['module']) . '.php';
        
        if (!is_file($rootController)) {
            return null;
        }
        
        $this->located = 1;
        
        if (!empty($parsed['controller'])) {
            if ($this->isValidMethod($parsed['module'], $parsed['controller'])) {
                return $this->buildReturnArray($parsed['module'], $parsed['controller'], $parsed['params'], $parsed['method']);
            }
        }
        
        return $this->buildReturnArray($parsed['module'], 'index', $parsed['params'], $parsed['controller']);
    }

    protected function checkControllerSubfolder(string $controllersPath, array $parsed): ?array
    {
        $subfolderPath = $controllersPath . $parsed['module'] . '/';
        
        if (!is_dir($subfolderPath)) {
            return null;
        }
        
        $this->directory = $parsed['module'] . '/';
        
        if (empty($parsed['controller'])) {
            return $this->findDefaultInSubfolder($subfolderPath, $parsed);
        }
        
        return $this->findControllerInSubfolder($subfolderPath, $parsed);
    }

    protected function findDefaultInSubfolder(string $subfolderPath, array $parsed): ?array
    {
        foreach (self::DEFAULT_CONTROLLER_NAMES as $default) {
            $defaultController = $subfolderPath . $default . '.php';
            if (is_file($defaultController)) {
                $this->located = 1;
                return $this->buildReturnArray($default, 'index', $parsed['params']);
            }
        }
        
        return null;
    }

    protected function findControllerInSubfolder(string $subfolderPath, array $parsed): ?array
    {
        $controllerFile = $subfolderPath . ucfirst($parsed['controller']) . '.php';
        if (is_file($controllerFile)) {
            $this->located = 2;
            return $this->buildReturnArray($parsed['controller'], $parsed['method'], $parsed['params']);
        }
        
        $lowerController = $subfolderPath . strtolower($parsed['controller']) . '.php';
        if (is_file($lowerController)) {
            $this->located = 2;
            return $this->buildReturnArray($parsed['controller'], $parsed['method'], $parsed['params']);
        }
        
        return $this->checkNestedSubfolder($subfolderPath, $parsed);
    }

    protected function checkNestedSubfolder(string $subfolderPath, array $parsed): ?array
    {
        $nestedPath = $subfolderPath . $parsed['controller'] . '/';
        
        if (!is_dir($nestedPath)) {
            return null;
        }
        
        $searchNames = array_merge([ucfirst($parsed['controller'])], self::DEFAULT_CONTROLLER_NAMES);
        
        foreach ($searchNames as $name) {
            $nestedController = $nestedPath . $name . '.php';
            if (is_file($nestedController)) {
                $this->directory .= $parsed['controller'] . '/';
                $this->located = 3;
                return $this->buildReturnArray($name, $parsed['method'], $parsed['params']);
            }
        }
        
        return null;
    }

    protected function checkDirectoryControllerPattern(string $controllersPath, array $parsed): ?array
    {
        if (empty($parsed['controller']) || !empty($this->directory)) {
            return null;
        }
        
        $dirs = glob($controllersPath . '*/', GLOB_ONLYDIR);
        
        foreach ($dirs as $dir) {
            $dirName = basename($dir);
            $controllerFile = $dir . ucfirst($parsed['controller']) . '.php';
            
            if (is_file($controllerFile)) {
                $this->directory = $dirName . '/';
                $this->located = 2;
                return $this->buildReturnArray($parsed['controller'], $parsed['method'], $parsed['params']);
            }
        }
        
        return null;
    }

    protected function handleNotFound(array $segments): array
    {
        $this->located = -1;
        return $segments;
    }

    protected function buildReturnArray(string $controller, ?string $method, array $params = [], ?string $extraMethod = null): array
    {
        $returnArray = [$controller, $method ?: 'index'];
        
        if ($extraMethod !== null && $extraMethod !== 'index') {
            $returnArray[] = $extraMethod;
        }
        
        if (!empty($params)) {
            $returnArray = array_merge($returnArray, $params);
        }
        
        return $returnArray;
    }

    protected function isValidMethod(string $class, string $method): bool
    {
        return true;
    }

    /**
     * Extract module dari namespace controller
     */
    protected function extractModuleFromNamespace(): void
    {
        if (empty($this->class)) {
            return;
        }
        
        // Coba extract dari class name jika namespace
        if (strpos($this->class, '\\') !== false) {
            if (preg_match('#(?:App\\\|Kodhe\\\)Modules\\\([^\\\]+)#i', $this->class, $matches)) {
                $module = strtolower($matches[1]);
                if (Modules::moduleExists($module)) {
                    $this->module = $module;
                    return;
                }
            }
        }
    }
    
    /**
     * Extract module dari directory path
     */
    protected function extractModuleFromDirectory(): void
    {
        if (empty($this->directory)) {
            return;
        }
        
        // Pattern: modules/{module}/controllers/
        if (preg_match('#modules/([^/]+)/controllers/#', $this->directory, $matches)) {
            $module = $matches[1];
            if (Modules::moduleExists($module)) {
                $this->module = $module;
                return;
            }
        }
        
        // Pattern: ../modules/{module}/controllers/
        if (preg_match('#\.\./modules/([^/]+)/controllers/#', $this->directory, $matches)) {
            $module = $matches[1];
            if (Modules::moduleExists($module)) {
                $this->module = $module;
                return;
            }
        }
        
        // Pattern: {module}/ (subfolder di controllers/)
        if (preg_match('#^([^/]+)/#', $this->directory, $matches)) {
            $module = $matches[1];
            if (Modules::moduleExists($module)) {
                $this->module = $module;
                return;
            }
        }
    }
    
    /**
     * Extract module dari routing
     */
    protected function extractModuleFromRouting(): void
    {
        // Priority 1: Coba dari class namespace
        $this->extractModuleFromNamespace();
        
        // Priority 2: Coba dari directory
        if (empty($this->module)) {
            $this->extractModuleFromDirectory();
        }
        
        // Priority 3: Coba dari URI segments
        if (empty($this->module) && !empty($this->uri->segments[0])) {
            $firstSegment = $this->uri->segments[0];
            if (Modules::moduleExists($firstSegment)) {
                $this->module = $firstSegment;
            }
        }
        
        // Set module di Modules registry jika ada method
        if (!empty($this->module) && method_exists('Modules', 'setCurrentModule')) {
            Modules::setCurrentModule($this->module);
        }
    }

    public function fetch_module(): string
    {
        return $this->module ?? '';
    }
    
    /**
     * Set module secara manual
     */
    public function setModule(string $module): void
    {
        $this->module = $module;
        
        // Update Modules registry
        if (method_exists('Modules', 'setCurrentModule')) {
            Modules::setCurrentModule($module);
        }
    }

    public function set_class($class): void
    {
        parent::set_class($class);
    }

    /**
     * Set default controller
     */
    public function setDefaultController(?string $controller = null): void
    {
        if ($controller !== null) {
            $this->default_controller = $controller;
        }

        $this->_set_default_controller();
    }

    /**
     * Set translate URI dashes
     */
    public function setTranslateUriDashes(bool $value): void
    {
        $this->translate_uri_dashes = $value;
    }

    protected function isValidController(array $segments): bool
    {
        $originalLocated = $this->located;
        
        $testSegments = $segments;
        $this->locate($testSegments);
        
        $found = !empty($testSegments) && $this->located > 0;
        $this->located = $originalLocated;
        
        return $found;
    }

    protected function set404Override(): void
    {
        $this->handle404Override();
    }

    public function _set_module_path(string &$_route = ''): void
    {
        if (empty($_route)) {
            return;
        }
        
        $_route = (string)$_route;
        
        $parsed = sscanf($_route, '%[^/]/%[^/]/%[^/]/%s', $module, $directory, $class, $method);
        
        if ($this->locate([$module, $directory, $class])) {
            $_route = $this->rebuildRoute($parsed, $module, $directory, $class, $method);
        }
    }

    protected function rebuildRoute(int $parsedCount, ?string $module, ?string $directory, ?string $class, ?string $method): string
    {
        switch ($parsedCount) {
            case 1: 
                return $module . '/index';
            case 2: 
                return ($this->located < 2) ? $module . '/' . $directory : $directory . '/index';
            case 3: 
                return ($this->located == 2) ? $directory . '/' . $class : $class . '/index';
            case 4: 
                return ($this->located == 3) ? $class . '/' . $method : $method . '/index';
            default:
                return '';
        }
    }
    
    /**
     * Implement RouterInterface
     */
    public function getRouting(): ?array
    {
        $directory = $this->fetch_directory();
        $class = $this->fetch_class();
        $method = $this->fetch_method();
        
        if (empty($class)) {
            return null;
        }
        
        $segments = [];
        if (!empty($this->uri->rsegments)) {
            $source = $this->uri->rsegments;
            $startIndex = (count($source) > 2) ? 2 : 1;
            $segments = array_slice($source, $startIndex);
            
            if (isset($segments[0]) && $segments[0] === $method) {
                array_shift($segments);
            }
        }
        
        return [
            'directory' => $directory,
            'class' => $class,
            'method' => $method,
            'segments' => $segments,
            'module' => $this->fetch_module(),
            'type' => 'legacy',
            'source' => 'legacy_router'
        ];
    }
    
    /**
     * Match request to route
     */
    public function matchRequest(Request $request): ?array
    {
        return parent::matchRequest($request);
    }
    
    /**
     * Generate URL for named route (implementation for RouterInterface)
     * Note: Legacy router doesn't support named routes, so this is a basic implementation
     */
    public function url(string $name, array $parameters = []): string
    {
        // Cek jika ada named routes di config
        $namedRoutes = config_item('named_routes') ?? [];
        
        if (isset($namedRoutes[$name])) {
            $uri = $namedRoutes[$name];
            
            foreach ($parameters as $key => $value) {
                $uri = str_replace('{' . $key . '}', $value, $uri);
            }
            
            $uri = preg_replace('/\{[^}]+\}/', '', $uri);
            $uri = rtrim($uri, '/');
            
            return site_url($uri);
        }
        
        // Jika name mengandung titik, konversi ke path
        if (strpos($name, '.') !== false) {
            $path = str_replace('.', '/', $name);
            return site_url($path);
        }
        
        return site_url($name);
    }

    /**
     * Execute route
     */
    public function execute(array $routing, Request $request, Response $response): mixed
    {
        return parent::execute($routing, $request, $response);
    }
}