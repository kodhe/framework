<?php

declare(strict_types=0);

namespace Kodhe\Framework\Profiler\Collectors;

use Kodhe\Framework\Profiler\Contracts\CollectorInterface;

/**
 * Memory Collector
 * 
 * Collects memory usage data
 */
class MemoryCollector implements CollectorInterface
{
    protected object $ci;
    protected ?int $memoryUsage = null;
    protected bool $hasMemoryData = false;

    public function setDependencies(object $ci): void
    {
        $this->ci = $ci;
    }

    public function collect(): array
    {
        if ($this->memoryUsage !== null) {
            return ['usage' => $this->memoryUsage];
        }

        $usage = memory_get_usage();
        $this->memoryUsage = $usage;
        $this->hasMemoryData = ($usage !== 0 && $usage !== false);

        return [
            'usage' => $this->memoryUsage,
            'formatted' => $this->hasMemoryData ? number_format($this->memoryUsage) . ' bytes' : null
        ];
    }

    public function hasData(): bool
    {
        if ($this->memoryUsage !== null) {
            return $this->hasMemoryData;
        }
        
        $this->collect();
        return $this->hasMemoryData;
    }

    public function getSectionName(): string
    {
        return 'memory_usage';
    }

    public function getMemoryUsage(): ?int
    {
        if ($this->memoryUsage === null) {
            $this->collect();
        }
        return $this->memoryUsage;
    }
}
