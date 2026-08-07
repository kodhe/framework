<?php

declare(strict_types=1);

namespace Kodhe\Profiler\ValueObjects;

/**
 * Profile Data Value Object
 * 
 * Represents collected profile data with metadata
 */
class ProfileData
{
    private string $section;
    private array $data;
    private float $collectionTime;
    private int $memoryUsage;
    private bool $hasData;

    public function __construct(
        string $section,
        array $data,
        float $collectionTime = 0.0,
        int $memoryUsage = 0,
        bool $hasData = true
    ) {
        $this->section = $section;
        $this->data = $data;
        $this->collectionTime = $collectionTime;
        $this->memoryUsage = $memoryUsage;
        $this->hasData = $hasData;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getCollectionTime(): float
    {
        return $this->collectionTime;
    }

    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }

    public function hasData(): bool
    {
        return $this->hasData;
    }

    public function toArray(): array
    {
        return [
            'section' => $this->section,
            'data' => $this->data,
            'collection_time' => $this->collectionTime,
            'memory_usage' => $this->memoryUsage,
            'has_data' => $this->hasData,
        ];
    }

    public static function empty(string $section): self
    {
        return new self($section, [], 0.0, 0, false);
    }
}
