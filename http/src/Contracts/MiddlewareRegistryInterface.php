<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Contracts;

/**
 * Middleware Registry Interface
 */
interface MiddlewareRegistryInterface
{
    /**
     * Register a middleware
     */
    public function register(string $alias, string $middleware): self;

    /**
     * Register multiple middlewares
     */
    public function registerMany(array $middlewares): self;

    /**
     * Check if middleware is registered
     */
    public function has(string $alias): bool;

    /**
     * Get a middleware by alias
     */
    public function get(string $alias): ?string;

    /**
     * Remove a middleware registration
     */
    public function remove(string $alias): self;

    /**
     * Get all registered middlewares
     */
    public function all(): array;

    /**
     * Resolve a middleware alias to class name
     */
    public function resolve(string $alias): string;

    /**
     * Register a middleware group
     */
    public function registerGroup(string $name, array $middlewares): self;

    /**
     * Get a middleware group
     */
    public function getGroup(string $name): array;

    /**
     * Check if middleware group exists
     */
    public function hasGroup(string $name): bool;

    /**
     * Get all middleware groups
     */
    public function allGroups(): array;
}
