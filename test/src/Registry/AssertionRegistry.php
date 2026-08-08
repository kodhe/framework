<?php

declare(strict_types=0);

namespace Kodhe\Framework\Test\Registry;

use Kodhe\Framework\Test\Contracts\AssertionInterface;
use Kodhe\Framework\Test\Exceptions\AssertionException;

/**
 * Registry for managing assertion implementations
 */
class AssertionRegistry
{
    /**
     * @var AssertionInterface[]
     */
    private $assertions = [];

    /**
     * @var bool Whether to prevent duplicate registration
     */
    private $preventDuplicates = true;

    /**
     * Constructor
     *
     * @param bool $preventDuplicates Whether to prevent duplicate registration
     */
    public function __construct(bool $preventDuplicates = true)
    {
        $this->preventDuplicates = $preventDuplicates;
        $this->registerDefaultAssertions();
    }

    /**
     * Register default assertions
     *
     * @return void
     */
    private function registerDefaultAssertions(): void
    {
        // Default assertions are handled by the built-in logic
        // Custom assertions can be added via register()
    }

    /**
     * Register an assertion
     *
     * @param string              $name       Assertion name
     * @param AssertionInterface  $assertion  Assertion instance
     * @param bool                $override   Whether to override existing registration
     * @return void
     * @throws AssertionException If duplicate registration attempted
     */
    public function register(string $name, AssertionInterface $assertion, bool $override = false): void
    {
        if ($this->preventDuplicates && !$override && isset($this->assertions[$name])) {
            throw new AssertionException(
                sprintf('Assertion "%s" is already registered.', $name)
            );
        }

        $this->assertions[$name] = $assertion;
    }

    /**
     * Resolve an assertion by name
     *
     * @param string $name Assertion name
     * @return AssertionInterface|null
     */
    public function resolve(string $name): ?AssertionInterface
    {
        return $this->assertions[$name] ?? null;
    }

    /**
     * Check if an assertion is registered
     *
     * @param string $name Assertion name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->assertions[$name]);
    }

    /**
     * Unregister an assertion
     *
     * @param string $name Assertion name
     * @return void
     */
    public function unregister(string $name): void
    {
        unset($this->assertions[$name]);
    }

    /**
     * Get all registered assertions
     *
     * @return AssertionInterface[]
     */
    public function getAll(): array
    {
        return $this->assertions;
    }

    /**
     * Clear all registered assertions
     *
     * @return void
     */
    public function clear(): void
    {
        $this->assertions = [];
    }
}
