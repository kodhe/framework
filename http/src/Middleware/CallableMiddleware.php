<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Middleware;

use Kodhe\Framework\Http\Contracts\MiddlewareInterface;
use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;
use Closure;

/**
 * Callable Middleware - Wrap a callable as middleware
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class CallableMiddleware implements MiddlewareInterface
{
    /**
     * The callable to execute
     *
     * @var callable
     */
    protected $callable;

    /**
     * Create a new callable middleware instance
     *
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Handle an incoming request
     *
     * @param RequestInterface $request
     * @param Closure $next
     * @return ResponseInterface|mixed
     */
    public function handle(RequestInterface $request, Closure $next)
    {
        return call_user_func($this->callable, $request, $next);
    }
}
