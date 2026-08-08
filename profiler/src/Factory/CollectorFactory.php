<?php

declare(strict_types=1);

namespace Kodhe\Framework\Profiler\Factory;

use Kodhe\Framework\Profiler\Contracts\CollectorInterface;
use Kodhe\Framework\Profiler\Support\SectionResolver;

/**
 * Collector Factory
 * 
 * Creates and manages collector instances
 */
class CollectorFactory
{
    private SectionResolver $sectionResolver;
    private object $ci;
    private array $instances = [];
    private array $cache = [];

    public function __construct(object $ci, ?SectionResolver $sectionResolver = null)
    {
        $this->ci = $ci;
        $this->sectionResolver = $sectionResolver ?? new SectionResolver();
    }

    /**
     * Create a collector instance for a section
     */
    public function create(string $section): ?CollectorInterface
    {
        // Return cached instance if available
        if (isset($this->instances[$section])) {
            return $this->instances[$section];
        }

        $collectorClass = $this->sectionResolver->getCollectorClass($section);

        if ($collectorClass === null) {
            return null;
        }

        if (!class_exists($collectorClass)) {
            throw new \RuntimeException("Collector class {$collectorClass} does not exist");
        }

        $collector = new $collectorClass();

        if (!$collector instanceof CollectorInterface) {
            throw new \RuntimeException(
                "Collector {$collectorClass} must implement CollectorInterface"
            );
        }

        $collector->setDependencies($this->ci);
        $this->instances[$section] = $collector;

        return $collector;
    }

    /**
     * Check if a collector exists for a section
     */
    public function has(string $section): bool
    {
        return $this->sectionResolver->hasCollector($section);
    }

    /**
     * Register a custom collector
     */
    public function register(string $section, string $collectorClass): void
    {
        $this->sectionResolver->registerCollector($section, $collectorClass);
        // Clear cache if it exists
        unset($this->instances[$section]);
    }

    /**
     * Get all registered sections
     */
    public function getRegisteredSections(): array
    {
        return $this->sectionResolver->getAllSections();
    }

    /**
     * Clear cached instances (useful for testing)
     */
    public function clearCache(): void
    {
        $this->instances = [];
        $this->cache = [];
    }

    /**
     * Get collector instance without caching
     * Useful for one-time collection
     */
    public function createFresh(string $section): ?CollectorInterface
    {
        $collectorClass = $this->sectionResolver->getCollectorClass($section);

        if ($collectorClass === null) {
            return null;
        }

        if (!class_exists($collectorClass)) {
            return null;
        }

        $collector = new $collectorClass();
        $collector->setDependencies($this->ci);

        return $collector;
    }
}
