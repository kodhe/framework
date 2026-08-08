<?php

declare(strict_types=1);

namespace Kodhe\Framework\Profiler\Support;

/**
 * Profiler Configuration
 * 
 * Handles profiler configuration options
 */
class ProfilerConfig
{
    private array $availableSections = [
        'benchmarks',
        'get',
        'memory_usage',
        'post',
        'uri_string',
        'controller_info',
        'queries',
        'http_headers',
        'session_data',
        'config'
    ];

    private int $queryToggleCount = 25;
    private array $enabledSections = [];
    private bool $allSectionsEnabledByDefault = true;

    public function __construct(array $config = [])
    {
        // Default: all sections enabled unless explicitly disabled
        foreach ($this->availableSections as $section) {
            $this->enabledSections[$section] = empty($config[$section]) ? true : (bool)$config[$section];
        }

        if (isset($config['query_toggle_count'])) {
            $this->queryToggleCount = (int)$config['query_toggle_count'];
        }
    }

    public function getAvailableSections(): array
    {
        return $this->availableSections;
    }

    public function getQueryToggleCount(): int
    {
        return $this->queryToggleCount;
    }

    public function setQueryToggleCount(int $count): void
    {
        $this->queryToggleCount = $count;
    }

    public function isSectionEnabled(string $section): bool
    {
        if (!in_array($section, $this->availableSections)) {
            return false;
        }
        return $this->enabledSections[$section] ?? false;
    }

    public function enableSection(string $section): void
    {
        if (in_array($section, $this->availableSections)) {
            $this->enabledSections[$section] = true;
        }
    }

    public function disableSection(string $section): void
    {
        if (in_array($section, $this->availableSections)) {
            $this->enabledSections[$section] = false;
        }
    }

    public function setSections(array $config): void
    {
        if (isset($config['query_toggle_count'])) {
            $this->setQueryToggleCount((int)$config['query_toggle_count']);
            unset($config['query_toggle_count']);
        }

        foreach ($config as $section => $enable) {
            if (in_array($section, $this->availableSections)) {
                $this->enabledSections[$section] = ($enable !== false);
            }
        }
    }

    public function getEnabledSections(): array
    {
        $enabled = [];
        foreach ($this->availableSections as $section) {
            if ($this->isSectionEnabled($section)) {
                $enabled[] = $section;
            }
        }
        return $enabled;
    }

    public function addSection(string $section): void
    {
        if (!in_array($section, $this->availableSections)) {
            $this->availableSections[] = $section;
            $this->enabledSections[$section] = true;
        }
    }
}
