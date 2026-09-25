<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Routing;

use Kodhe\Framework\Exceptions\Http\BadRequestException;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Support\CacheFileWriter;

class RouteCollection
{
    /**
     * @var array All routes
     */
    protected $routes = [];

    /**
     * @var array Named routes
     */
    protected $namedRoutes = [];

    /**
     * @var string Cache file path
     */
    protected $cacheFile;

    /**
     * Constructor
     */
    public function __construct()
    {
        $path = app()->config->item('cache_path');
		$cache_path = ($path === '') ? STORAGEPATH.'cache/' : $path;
        $this->cacheFile = rtrim($cache_path, '/\\') . DIRECTORY_SEPARATOR . 'routes.cache.json';
    }

    /**
     * Add route to collection
     */
    public function add(RouteItem $route): void
    {
        // Check for duplicates by unique key
        $routeKey = $route->getMethod() . ':' . $route->getUri();
        
        foreach ($this->routes as $existingRoute) {
            if ($existingRoute->getMethod() === $route->getMethod() && 
                $existingRoute->getUri() === $route->getUri()) {
                return;
            }
        }

        $this->routes[] = $route;

        if ($name = $route->getName()) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * Get all routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get route by name
     */
    public function getByName(string $name): ?RouteItem
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function match(Request $request): ?RouteItem
    {
        $method = $request->method();
        $uri = $request->getUri()->getPath();
        
        // Normalize URI
        $uri = $this->normalizeUri($uri);
        
        foreach ($this->routes as $index => $route) {
            // Check method
            if ($route->getMethod() !== 'ANY' && $route->getMethod() !== $method) {
                continue;
            }
            
            // Check if route matches
            if ($route->matches($uri)) {
                return $route;
            }
        }
    
        return null;
    }
    
    protected function normalizeUri(string $uri): string
    {
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Normalize slashes
        $uri = '/' . trim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }
        
        // Remove base path from URI jika ada dalam konfigurasi
        $basePath = $this->getBasePath();
        if (!empty($basePath) && $basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
            if ($uri === '') {
                $uri = '/';
            }
        }
        
        // Ensure it starts with slash
        if ($uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        
        return $uri;
    }
    
    protected function getBasePath(): string
    {
        static $basePath = null;
        
        if ($basePath === null) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $basePath = dirname($scriptName);
            
            // Normalize base path
            if ($basePath === '.') {
                $basePath = '';
            } elseif ($basePath !== '/' && $basePath !== '') {
                $basePath = rtrim($basePath, '/');
            }
        }
        
        return $basePath;
    }
    
    /**
     * Cache routes using JSON encoding
     */
    public function cache(): bool
    {
        // Prepare cache data
        $cacheData = $this->prepareCacheData();
        
        if (empty($cacheData['routes'])) {
            return false;
        }

        try {
            // Pure-JSON payload, written atomically (temp+rename) so
            // concurrent requests never read a half-written file. The
            // file is no longer executable PHP.
            CacheFileWriter::write($this->cacheFile, $cacheData);

            // Remove the legacy .cache.php sibling if it still exists.
            $legacy = CacheFileWriter::legacyPath($this->cacheFile);
            if (is_file($legacy)) {
                @unlink($legacy);
            }

            return true;
        } catch (\RuntimeException $e) {
            throw new BadRequestException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Prepare cache data for JSON encoding
     */
    protected function prepareCacheData(): array
    {
        $routesData = [];
        
        foreach ($this->routes as $route) {
            $routeData = [
                'method' => $route->getMethod(),
                'uri' => $route->getUri(),
                'middleware' => $route->getMiddleware(),
                'name' => $route->getName(),
                'namespace' => $route->getNamespace(),
            ];

            // Handle action based on type
            $action = $route->getAction();
            if ($action instanceof \Closure) {
                // Cannot serialize closures
                $routeData['action_type'] = 'closure';
                $routeData['action'] = null;
            } elseif (is_string($action)) {
                $routeData['action_type'] = 'controller';
                $routeData['action'] = $action;
            } elseif (is_array($action)) {
                $routeData['action_type'] = 'array';
                $routeData['action'] = $action;
            } else {
                $routeData['action_type'] = 'unknown';
                $routeData['action'] = null;
            }

            $routesData[] = $routeData;
        }

        // Named routes
        $namedRoutesData = [];
        foreach ($this->namedRoutes as $name => $route) {
            $namedRoutesData[$name] = [
                'method' => $route->getMethod(),
                'uri' => $route->getUri(),
            ];
        }

        return [
            'routes' => $routesData,
            'named_routes' => $namedRoutesData,
            'timestamp' => time(),
            'count' => count($this->routes)
        ];
    }

    /**
     * Load routes from cache
     */
    public function loadFromCache(): bool
    {
        // Debug mode: disable cache untuk development
        if (ENVIRONMENT !== 'production') {
            return false;
        }
        
        // New JSON cache first; fall back to a legacy .cache.php heredoc
        // file (if any) and migrate it transparently to the new format.
        $cacheData = CacheFileWriter::read($this->cacheFile, 'routes');

        if ($cacheData === null) {
            $legacy = CacheFileWriter::legacyPath($this->cacheFile);
            $cacheData = CacheFileWriter::readLegacy($legacy, 'routes');

            if ($cacheData === null) {
                // Missing or corrupt - drop both and rebuild from scratch.
                $this->clearCache();
                return false;
            }

            @unlink($legacy);
            try {
                CacheFileWriter::write($this->cacheFile, $cacheData);
            } catch (\RuntimeException $e) {
                // Migration write failed; still usable in-memory this request.
            }
        }

        // Clear current routes
        $this->routes = [];
        $this->namedRoutes = [];

        // Rebuild routes from cache
        foreach ($cacheData['routes'] as $routeData) {
            $action = $this->restoreActionFromCache($routeData);

            if ($action === null) {
                continue;
            }

            $route = new RouteItem(
                $routeData['method'],
                $routeData['uri'],
                $action,
                $routeData['middleware'] ?? [],
                $routeData['namespace'] ?? ''
            );

            if (!empty($routeData['name'])) {
                $route->name($routeData['name']);
            }

            $this->add($route);
        }

        return true;
    }

    /**
     * Restore action from cache data
     */
    protected function restoreActionFromCache(array $routeData)
    {
        $actionType = $routeData['action_type'] ?? 'unknown';
        
        switch ($actionType) {
            case 'controller':
                return $routeData['action'] ?? null;
            case 'array':
                return $routeData['action'] ?? null;
            case 'closure':
                // Closures cannot be restored from cache
                return null;
            default:
                return $routeData['action'] ?? null;
        }
    }

    /**
     * Clear route cache
     */
    public function clearCache(): bool
    {
        $result = true;

        foreach (array($this->cacheFile, CacheFileWriter::legacyPath($this->cacheFile)) as $file) {
            if (is_file($file) && !@unlink($file)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Is cache fresh?
     */
    public function isCacheFresh(int $maxAge = 3600): bool
    {
        if (!file_exists($this->cacheFile)) {
            return false;
        }

        $cacheTime = filemtime($this->cacheFile);
        return (time() - $cacheTime) < $maxAge;
    }
}
