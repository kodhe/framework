<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface KernelInterface
 * 
 * HTTP Kernel for handling requests
 */
interface KernelInterface
{
    /**
     * Handle an incoming request
     */
    public function handle(RequestInterface $request): ResponseInterface;

    /**
     * Bootstrap the kernel
     */
    public function bootstrap(): self;

    /**
     * Terminate the kernel
     */
    public function terminate(RequestInterface $request, ResponseInterface $response): void;

    /**
     * Get the application instance
     */
    public function getApplication();

    /**
     * Set the application instance
     */
    public function setApplication($app): self;

    /**
     * Get the request
     */
    public function getRequest(): RequestInterface;

    /**
     * Set the request
     */
    public function setRequest(RequestInterface $request): self;

    /**
     * Get the response
     */
    public function getResponse(): ResponseInterface;

    /**
     * Set the response
     */
    public function setResponse(ResponseInterface $response): self;

    /**
     * Register middleware
     */
    public function registerMiddleware(array $middleware): self;

    /**
     * Get global middleware
     */
    public function getGlobalMiddleware(): array;

    /**
     * Get route middleware groups
     */
    public function getRouteMiddlewareGroups(): array;

    /**
     * Check if kernel is bootstrapped
     */
    public function isBootstrapped(): bool;

    /**
     * Load configuration
     */
    public function loadConfiguration(array $config): self;

    /**
     * Get configuration value
     */
    public function getConfig(string $key, $default = null);
}
