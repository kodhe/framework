<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Middleware;

use Kodhe\Framework\Http\Contracts\MiddlewareInterface;
use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;
use Closure;

/**
 * Base Middleware class
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
abstract class Middleware implements MiddlewareInterface
{
    /**
     * The application instance
     *
     * @var mixed
     */
    protected $app;

    /**
     * Create a new middleware instance
     *
     * @param mixed $app
     */
    public function __construct($app = null)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request
     *
     * @param RequestInterface $request
     * @param Closure $next
     * @return ResponseInterface|mixed
     */
    abstract public function handle(RequestInterface $request, Closure $next);

    /**
     * Set the application instance
     *
     * @param mixed $app
     * @return $this
     */
    public function setApp($app): self
    {
        $this->app = $app;
        return $this;
    }

    /**
     * Get the application instance
     *
     * @return mixed
     */
    public function getApp()
    {
        return $this->app;
    }
}
