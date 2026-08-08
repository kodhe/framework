<?php

declare(strict_types=1);

namespace Kodhe\Framework\Profiler\ValueObjects;

/**
 * Profile Section Value Object
 * 
 * Represents a profiler section with its metadata
 */
class ProfileSection
{
    private string $name;
    private bool $enabled;
    private ?string $collectorClass = null;
    private ?array $data = null;
    private bool $dataCollected = false;

    public function __construct(string $name, bool $enabled = true, ?string $collectorClass = null)
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->collectorClass = $collectorClass;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getCollectorClass(): ?string
    {
        return $this->collectorClass;
    }

    public function setCollectorClass(string $collectorClass): void
    {
        $this->collectorClass = $collectorClass;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;
        $this->dataCollected = true;
    }

    public function hasData(): bool
    {
        return $this->dataCollected && $this->data !== null;
    }

    public function clearData(): void
    {
        $this->data = null;
        $this->dataCollected = false;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'enabled' => $this->enabled,
            'collector_class' => $this->collectorClass,
            'has_data' => $this->hasData(),
        ];
    }
}
