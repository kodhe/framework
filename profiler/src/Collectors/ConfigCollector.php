<?php

declare(strict_types=1);

namespace Kodhe\Profiler\Collectors;

use Kodhe\Profiler\Contracts\CollectorInterface;

/**
 * Config Collector
 * 
 * Collects configuration data
 */
class ConfigCollector implements CollectorInterface
{
    protected object $ci;
    protected ?array $configData = null;

    public function setDependencies(object $ci): void
    {
        $this->ci = $ci;
    }

    public function collect(): array
    {
        if ($this->configData !== null) {
            return $this->configData;
        }

        $this->configData = $this->ci->config->config ?? [];
        return $this->configData;
    }

    public function hasData(): bool
    {
        // Always has data (config always exists)
        return true;
    }

    public function getSectionName(): string
    {
        return 'config';
    }

    public function getConfigData(): array
    {
        if ($this->configData === null) {
            $this->collect();
        }
        return $this->configData;
    }

    public function getConfigItem(string $key, mixed $default = null): mixed
    {
        if ($this->configData === null) {
            $this->collect();
        }
        return $this->configData[$key] ?? $default;
    }
}
