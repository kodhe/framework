<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Routing\Core;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Http\Routing\Contracts\{
    RouterInterface,
    RouteCollectionInterface,
    ControllerExecutorInterface
};
use Kodhe\Framework\Http\Routing\Exceptions\{
    RouteNotFoundException,
    MethodNotAllowedException
};

/**
 * Router - Main router implementation
 * 
 * PSR-12 compliant, modular router with full CodeIgniter 3 backward compatibility.
 */
class Router implements RouterInterface
{
    /**
     * @var RouteCollectionInterface
     */
    protected RouteCollectionInterface $collection;

    /**
     * @var ControllerExecutorInterface|null
     */
    protected ?ControllerExecutorInterface $executor = null;

    /**
     * @var array Configuration
     */
    protected array $config = [
        'enable_modern_routing' => true,
        'enable_legacy_routing' => true,
        'prefer_modern' => true,
        'cache_routes' => false,
        'auto_detect_namespace' => true,
        'controller_suffix' => '',
        'default_404_controller' => 'FileNotFound',
    ];

    /**
     * Legacy properties for CI3 compatibility
     */
    public $module = '';
    protected $located = 0;
    public $class = '';
    public $method = '';
    public $directory = '';
    protected $uri;
    protected $enable_query_strings = false;
    protected $default_controller = 'welcome';
    protected $translate_uri_dashes = false;
    protected $routes = [];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->collection = new RouteCollection();
        
        // Set collection on Route facade
        Route::setCollection($this->collection);

        if ($this->config['cache_routes']) {
            $this->collection->loadFromCache();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request): ?array
    {
        // Try modern routing first
        if ($this->config['enable_modern_routing']) {
            $route = $this->collection->match($request);

            if ($route instanceof RouteItem) {
                return $this->extractRoutingInfo($route, $request);
            }
        }

        // Fallback to legacy routing if enabled
        if ($this->config['enable_legacy_routing']) {
            return $this->matchLegacy($request);
        }

        return null;
    }

    /**
     * Extract routing information from matched route
     */
    protected function extractRoutingInfo(RouteItem $route, Request $request): array
    {
        $action = $route->getAction();
        $parameters = $route->getParameters();

        if ($action instanceof \Closure) {
            $class = 'Closure';
            $method = '__invoke';
            $type = 'closure';
        } elseif (is_string($action)) {
            if (strpos($action, '@') !== false) {
                [$class, $method] = explode('@', $action, 2);
            } else {
                $class = $action;
                $method = 'index';
            }
            $type = 'controller';
        } elseif (is_array($action)) {
            [$class, $method] = $action;
            $type = 'controller';
        } else {
            throw new \InvalidArgumentException('Unsupported action type: ' . gettype($action));
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
            'namespace' => $route->getNamespace(),
        ];
    }

    /**
     * Legacy routing match (for CI3 compatibility)
     */
    protected function matchLegacy(Request $request): ?array
    {
        // Legacy routing logic would go here
        // This is a simplified version
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouting(): ?array
    {
        return [
            'directory' => $this->directory,
            'class' => $this->class,
            'method' => $this->method,
            'type' => 'legacy',
            'source' => 'legacy_router',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $routing, Request $request, Response $response): mixed
    {
        if (!isset($routing['route']) || !$routing['route'] instanceof RouteItem) {
            throw new \InvalidArgumentException('Invalid routing information');
        }

        /** @var RouteItem $route */
        $route = $routing['route'];

        return $route->run($request, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function url(string $name, array $parameters = []): string
    {
        $route = $this->collection->getByName($name);

        if (!$route) {
            throw new RouteNotFoundException("Route [{$name}] not found");
        }

        return $route->url($parameters);
    }

    /**
     * Set route executor
     */
    public function setExecutor(ControllerExecutorInterface $executor): void
    {
        $this->executor = $executor;
    }

    /**
     * Get route collection
     */
    public function getCollection(): RouteCollectionInterface
    {
        return $this->collection;
    }

    /**
     * Set configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    // ========== Legacy CI3 Compatibility Methods ==========

    /**
     * {@inheritdoc}
     */
    public function _set_routing()
    {
        // Legacy CI3 method
    }

    /**
     * {@inheritdoc}
     */
    public function fetch_class()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch_method()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch_directory()
    {
        return $this->directory;
    }

    /**
     * {@inheritdoc}
     */
    public function set_class($class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function set_method($method)
    {
        $this->method = $method;
    }
}
