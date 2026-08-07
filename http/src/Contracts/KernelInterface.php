<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Kernel Interface
 */
interface KernelInterface
{
    /**
     * Handle an incoming HTTP request
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Bootstrap the kernel
     */
    public function bootstrap(): void;

    /**
     * Terminate the kernel after response is sent
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void;

    /**
     * Get the application instance
     */
    public function getApplication(): object;

    /**
     * Set the application instance
     */
    public function setApplication(object $app): self;

    /**
     * Register a service provider
     */
    public function registerServiceProvider(string $provider): self;

    /**
     * Get registered middleware groups
     */
    public function getMiddlewareGroups(): array;

    /**
     * Get global middleware stack
     */
    public function getGlobalMiddleware(): array;

    /**
     * Add global middleware
     */
    public function addGlobalMiddleware($middleware): self;

    /**
     * Prepend global middleware
     */
    public function prependGlobalMiddleware($middleware): self;

    /**
     * Get route middleware
     */
    public function getRouteMiddleware(): array;

    /**
     * Register route middleware
     */
    public function registerRouteMiddleware(string $alias, string $middleware): self;

    /**
     * Check if kernel is booted
     */
    public function isBooted(): bool;

    /**
     * Get boot time
     */
    public function getBootTime(): ?float;

    /**
     * Load configuration
     */
    public function loadConfiguration(array $config): self;

    /**
     * Get configuration value
     */
    public function getConfig(string $key, $default = null);

    /**
     * Set configuration value
     */
    public function setConfig(string $key, $value): self;

    /**
     * Get all configuration
     */
    public function getAllConfig(): array;

    /**
     * Handle exception during request processing
     */
    public function handleException(\Throwable $e, ServerRequestInterface $request): ResponseInterface;

    /**
     * Shutdown the kernel
     */
    public function shutdown(): void;
}
