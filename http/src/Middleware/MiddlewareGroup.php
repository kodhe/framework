<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Middleware;

use Kodhe\Framework\Http\Contracts\MiddlewareInterface;
use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;
use Closure;
use Exception;

/**
 * Middleware Group - Execute a group of middleware as one
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class MiddlewareGroup implements MiddlewareInterface
{
    /**
     * The middleware in this group
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Create a new middleware group instance
     *
     * @param array $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    /**
     * Add middleware to the group
     *
     * @param MiddlewareInterface|string|callable $middleware
     * @return $this
     */
    public function add($middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Set the middleware array
     *
     * @param array $middleware
     * @return $this
     */
    public function setMiddleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Get the middleware array
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Handle an incoming request
     *
     * @param RequestInterface $request
     * @param Closure $next
     * @return ResponseInterface|mixed
     * @throws Exception
     */
    public function handle(RequestInterface $request, Closure $next)
    {
        if (empty($this->middleware)) {
            return $next($request);
        }

        // Create a pipeline for this group
        $pipeline = new MiddlewareStack();
        
        foreach ($this->middleware as $middleware) {
            $pipeline->push($middleware);
        }

        return $pipeline->handle($request, $next);
    }
}
