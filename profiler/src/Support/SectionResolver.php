<?php

declare(strict_types=1);

namespace Kodhe\Profiler\Support;

/**
 * Section Resolver
 * 
 * Resolves section names to collector classes and handles mapping
 */
class SectionResolver
{
    private array $sectionCollectorMap = [
        'benchmarks' => \Kodhe\Profiler\Collectors\BenchmarkCollector::class,
        'get' => null, // Handled inline or by generic collector
        'memory_usage' => \Kodhe\Profiler\Collectors\MemoryCollector::class,
        'post' => null, // Handled inline or by generic collector
        'uri_string' => \Kodhe\Profiler\Collectors\UriCollector::class,
        'controller_info' => \Kodhe\Profiler\Collectors\ControllerCollector::class,
        'queries' => \Kodhe\Profiler\Collectors\DatabaseCollector::class,
        'http_headers' => \Kodhe\Profiler\Collectors\HttpHeadersCollector::class,
        'session_data' => \Kodhe\Profiler\Collectors\SessionCollector::class,
        'config' => \Kodhe\Profiler\Collectors\ConfigCollector::class,
    ];

    private array $customCollectors = [];

    public function getCollectorClass(string $section): ?string
    {
        if (isset($this->customCollectors[$section])) {
            return $this->customCollectors[$section];
        }

        return $this->sectionCollectorMap[$section] ?? null;
    }

    public function hasCollector(string $section): bool
    {
        return isset($this->customCollectors[$section]) || 
               (isset($this->sectionCollectorMap[$section]) && $this->sectionCollectorMap[$section] !== null);
    }

    public function registerCollector(string $section, string $collectorClass): void
    {
        $this->customCollectors[$section] = $collectorClass;
    }

    public function getSectionForCollector(string $collectorClass): ?string
    {
        $section = array_search($collectorClass, $this->sectionCollectorMap, true);
        if ($section !== false) {
            return $section;
        }

        $section = array_search($collectorClass, $this->customCollectors, true);
        if ($section !== false) {
            return $section;
        }

        return null;
    }

    public function getAllSections(): array
    {
        return array_keys($this->sectionCollectorMap);
    }

    public function getCustomSections(): array
    {
        return array_keys($this->customCollectors);
    }
}
