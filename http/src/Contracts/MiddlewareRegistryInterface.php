<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Contracts;

/**
 * Interface MiddlewareRegistryInterface
 * 
 * Registry for managing middleware
 */
interface MiddlewareRegistryInterface
{
    /**
     * Register a middleware
     */
    public function register(string $alias, string $middleware): self;

    /**
     * Register multiple middleware
     */
    public function registerMany(array $middleware): self;

    /**
     * Get a middleware by alias
     */
    public function get(string $alias): ?string;

    /**
     * Check if middleware exists
     */
    public function has(string $alias): bool;

    /**
     * Remove a middleware
     */
    public function remove(string $alias): self;

    /**
     * Get all registered middleware
     */
    public function all(): array;

    /**
     * Resolve middleware alias to class name
     */
    public function resolve(string $alias): string;

    /**
     * Clear all registered middleware
     */
    public function clear(): self;
}
