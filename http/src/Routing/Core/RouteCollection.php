<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Routing\Core;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Routing\Contracts\{
    RouteCollectionInterface,
    RouteInterface
};

/**
 * RouteCollection - Collection of routes implementation
 * 
 * PSR-12 compliant route collection implementing RouteCollectionInterface.
 */
class RouteCollection implements RouteCollectionInterface
{
    /**
     * @var array All routes
     */
    protected array $routes = [];

    /**
     * @var array Named routes
     */
    protected array $namedRoutes = [];

    /**
     * @var string Cache file path
     */
    protected string $cacheFile;

    /**
     * Constructor
     */
    public function __construct()
    {
        $path = app()->config->item('cache_path') ?? '';
        $cachePath = ($path === '') ? STORAGEPATH . 'cache/' : $path;
        $this->cacheFile = $cachePath . 'routes.cache.php';
    }

    /**
     * {@inheritdoc}
     */
    public function add(RouteInterface $route): void
    {
        // Check for duplicates
        $routeKey = $route->getMethod() . ':' . $route->getUri();

        foreach ($this->routes as $existingRoute) {
            if ($existingRoute->getMethod() === $route->getMethod() &&
                $existingRoute->getUri() === $route->getUri()) {
                return; // Skip duplicate
            }
        }

        $this->routes[] = $route;

        if ($name = $route->getName()) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function getByName(string $name): ?RouteInterface
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function match(Request $request): ?RouteInterface
    {
        $method = $request->method();
        $uri = $request->getUri()->getPath();
        $uri = $this->normalizeUri($uri);

        foreach ($this->routes as $route) {
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

    /**
     * Normalize URI
     */
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

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function cache(): bool
    {
        // Implementation for caching routes
        // Can be extended based on existing RouteCollection logic
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromCache(): bool
    {
        // Implementation for loading from cache
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): bool
    {
        if (file_exists($this->cacheFile)) {
            return unlink($this->cacheFile);
        }
        return true;
    }
}
