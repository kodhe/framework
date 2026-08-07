<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Middleware;

use Kodhe\Framework\Http\Contracts\MiddlewareRegistryInterface;
use Kodhe\Framework\Http\Contracts\MiddlewareInterface;
use InvalidArgumentException;

/**
 * Middleware Registry - Register and resolve middleware by name
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class MiddlewareRegistry implements MiddlewareRegistryInterface
{
    /**
     * The application instance
     *
     * @var mixed
     */
    protected $app;

    /**
     * Registered middleware
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Middleware groups
     *
     * @var array
     */
    protected $groups = [];

    /**
     * Create a new middleware registry instance
     *
     * @param mixed $app
     */
    public function __construct($app = null)
    {
        $this->app = $app;
    }

    /**
     * Register a middleware
     *
     * @param string $name
     * @param string|MiddlewareInterface|callable $middleware
     * @return $this
     */
    public function register(string $name, $middleware): self
    {
        $this->middleware[$name] = $middleware;
        return $this;
    }

    /**
     * Register multiple middleware at once
     *
     * @param array $middleware
     * @return $this
     */
    public function registerMany(array $middleware): self
    {
        foreach ($middleware as $name => $mw) {
            $this->register($name, $mw);
        }

        return $this;
    }

    /**
     * Check if a middleware is registered
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->middleware[$name]) || isset($this->groups[$name]);
    }

    /**
     * Get a middleware by name
     *
     * @param string $name
     * @return MiddlewareInterface|string|callable|null
     */
    public function get(string $name)
    {
        if (isset($this->middleware[$name])) {
            return $this->middleware[$name];
        }

        if (isset($this->groups[$name])) {
            return $this->groups[$name];
        }

        return null;
    }

    /**
     * Resolve a middleware by name
     *
     * @param string $name
     * @return MiddlewareInterface
     * @throws InvalidArgumentException
     */
    public function resolve(string $name): MiddlewareInterface
    {
        $middleware = $this->get($name);

        if ($middleware === null) {
            throw new InvalidArgumentException("Middleware [{$name}] not registered.");
        }

        // If it's already an instance
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        // If it's a callable
        if (is_callable($middleware)) {
            return new CallableMiddleware($middleware);
        }

        // If it's a class name
        if (is_string($middleware)) {
            if ($this->app && method_exists($this->app, 'make')) {
                $instance = $this->app->make($middleware);
            } else {
                $instance = new $middleware();
            }

            if (!$instance instanceof MiddlewareInterface) {
                throw new InvalidArgumentException(
                    "Middleware [{$name}] must implement MiddlewareInterface."
                );
            }

            return $instance;
        }

        throw new InvalidArgumentException(
            "Middleware [{$name}] is not a valid middleware type."
        );
    }

    /**
     * Register a middleware group
     *
     * @param string $name
     * @param array $middleware
     * @return $this
     */
    public function group(string $name, array $middleware): self
    {
        $this->groups[$name] = new MiddlewareGroup($middleware);
        return $this;
    }

    /**
     * Get a middleware group
     *
     * @param string $name
     * @return MiddlewareGroup|null
     */
    public function getGroup(string $name): ?MiddlewareGroup
    {
        return $this->groups[$name] ?? null;
    }

    /**
     * Get all registered middleware
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->middleware, $this->groups);
    }

    /**
     * Get all middleware groups
     *
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Remove a middleware
     *
     * @param string $name
     * @return $this
     */
    public function remove(string $name): self
    {
        unset($this->middleware[$name], $this->groups[$name]);
        return $this;
    }

    /**
     * Clear all registered middleware
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->middleware = [];
        $this->groups = [];
        return $this;
    }
}
