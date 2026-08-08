<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\Contracts;

/**
 * Interface for method registry
 */
interface MethodRegistryInterface
{
    /**
     * Register a method
     *
     * @param string $name
     * @param array $definition
     * @return void
     */
    public function register(string $name, array $definition): void;

    /**
     * Get a method definition
     *
     * @param string $name
     * @return array|null
     */
    public function get(string $name): ?array;

    /**
     * Check if a method exists
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get all registered methods
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get method names
     *
     * @return array
     */
    public function getMethodNames(): array;
}
