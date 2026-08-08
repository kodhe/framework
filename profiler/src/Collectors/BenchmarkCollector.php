<?php

declare(strict_types=0);

namespace Kodhe\Framework\Profiler\Collectors;

use Kodhe\Framework\Profiler\Contracts\CollectorInterface;

/**
 * Benchmark Collector
 * 
 * Collects benchmark timing data
 */
class BenchmarkCollector implements CollectorInterface
{
    protected object $ci;
    protected ?array $benchmarks = null;

    public function setDependencies(object $ci): void
    {
        $this->ci = $ci;
    }

    public function collect(): array
    {
        if ($this->benchmarks !== null) {
            return $this->benchmarks;
        }

        $profile = [];

        foreach ($this->ci->benchmark->marker as $key => $val) {
            // Match the "end" marker so that the list ends
            // up in the order that it was defined
            if (preg_match('/(.+?)_end$/i', $key, $match)
                && isset(
                    $this->ci->benchmark->marker[$match[1] . '_end'],
                    $this->ci->benchmark->marker[$match[1] . '_start']
                )
            ) {
                $profile[$match[1]] = $this->ci->benchmark->elapsed_time($match[1] . '_start', $key);
            }
        }

        $this->benchmarks = $profile;
        return $this->benchmarks;
    }

    public function hasData(): bool
    {
        $this->collect();
        return !empty($this->benchmarks);
    }

    public function getSectionName(): string
    {
        return 'benchmarks';
    }

    public function getData(): ?array
    {
        return $this->benchmarks;
    }
}
