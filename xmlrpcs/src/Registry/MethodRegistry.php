<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\Registry;

use Kodhe\Framework\Xmlrpcs\Contracts\MethodRegistryInterface;

/**
 * Registry for XML-RPC methods
 */
class MethodRegistry implements MethodRegistryInterface
{
    /**
     * Registered methods
     *
     * @var array
     */
    protected array $methods = [];

    /**
     * Cache for method names
     *
     * @var array|null
     */
    protected ?array $methodNamesCache = null;

    /**
     * Register a method
     *
     * @param string $name
     * @param array $definition
     * @return void
     */
    public function register(string $name, array $definition): void
    {
        $this->methods[$name] = $definition;
        $this->methodNamesCache = null; // Invalidate cache
    }

    /**
     * Get a method definition
     *
     * @param string $name
     * @return array|null
     */
    public function get(string $name): ?array
    {
        return $this->methods[$name] ?? null;
    }

    /**
     * Check if a method exists
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->methods[$name]);
    }

    /**
     * Get all registered methods
     *
     * @return array
     */
    public function all(): array
    {
        return $this->methods;
    }

    /**
     * Get method names
     *
     * @return array
     */
    public function getMethodNames(): array
    {
        if ($this->methodNamesCache === null) {
            $this->methodNamesCache = array_keys($this->methods);
        }
        
        return $this->methodNamesCache;
    }

    /**
     * Merge methods from another registry or array
     *
     * @param array|MethodRegistryInterface $methods
     * @return void
     */
    public function merge(array|MethodRegistryInterface $methods): void
    {
        if ($methods instanceof MethodRegistryInterface) {
            foreach ($methods->all() as $name => $definition) {
                $this->register($name, $definition);
            }
        } else {
            foreach ($methods as $name => $definition) {
                $this->register($name, $definition);
            }
        }
    }

    /**
     * Clear all registered methods
     *
     * @return void
     */
    public function clear(): void
    {
        $this->methods = [];
        $this->methodNamesCache = null;
    }
}
