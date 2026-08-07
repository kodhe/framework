<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Middleware;

use Kodhe\Framework\Http\Contracts\MiddlewareInterface;
use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;
use Closure;

/**
 * Middleware Stack - Execute middleware in order
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class MiddlewareStack
{
    /**
     * The middleware stack
     *
     * @var array
     */
    protected $stack = [];

    /**
     * Push middleware onto the stack
     *
     * @param MiddlewareInterface|string|callable $middleware
     * @return $this
     */
    public function push($middleware): self
    {
        $this->stack[] = $middleware;
        return $this;
    }

    /**
     * Prepend middleware to the stack
     *
     * @param MiddlewareInterface|string|callable $middleware
     * @return $this
     */
    public function prepend($middleware): self
    {
        array_unshift($this->stack, $middleware);
        return $this;
    }

    /**
     * Handle the request through the middleware stack
     *
     * @param RequestInterface $request
     * @param Closure $next
     * @return ResponseInterface|mixed
     */
    public function handle(RequestInterface $request, Closure $next)
    {
        if (empty($this->stack)) {
            return $next($request);
        }

        // Create a pipeline from the stack
        $pipeline = $this->createPipeline($request, $next);

        return $pipeline($request);
    }

    /**
     * Create the pipeline closure
     *
     * @param RequestInterface $request
     * @param Closure $next
     * @return Closure
     */
    protected function createPipeline(RequestInterface $request, Closure $next): Closure
    {
        // Start with the final destination
        $seed = $next;

        // Wrap each middleware around the seed
        foreach (array_reverse($this->stack) as $middleware) {
            $resolved = $this->resolveMiddleware($middleware);
            
            $seed = function ($request) use ($resolved, $seed) {
                if ($resolved instanceof MiddlewareInterface) {
                    return $resolved->handle($request, $seed);
                }

                if (is_callable($resolved)) {
                    return $resolved($request, $seed);
                }

                return $seed($request);
            };
        }

        return $seed;
    }

    /**
     * Resolve a middleware instance
     *
     * @param mixed $middleware
     * @return MiddlewareInterface|callable|null
     */
    protected function resolveMiddleware($middleware)
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (is_callable($middleware)) {
            return $middleware;
        }

        if (is_string($middleware)) {
            // Try to instantiate the class
            if (class_exists($middleware)) {
                return new $middleware();
            }
        }

        return $middleware;
    }

    /**
     * Get the middleware stack
     *
     * @return array
     */
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * Set the middleware stack
     *
     * @param array $stack
     * @return $this
     */
    public function setStack(array $stack): self
    {
        $this->stack = $stack;
        return $this;
    }

    /**
     * Clear the middleware stack
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->stack = [];
        return $this;
    }

    /**
     * Count the middleware in the stack
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->stack);
    }
}
