<?php

declare(strict_types=1);

namespace Kodhe\Profiler\Contracts;

/**
 * Collector Interface
 * 
 * Interface for all data collectors
 */
interface CollectorInterface
{
    /**
     * Collect data for this section
     *
     * @return mixed
     */
    public function collect();

    /**
     * Check if this collector has data to display
     *
     * @return bool
     */
    public function hasData(): bool;

    /**
     * Get the section name
     *
     * @return string
     */
    public function getSectionName(): string;

    /**
     * Set dependencies (CI instance, etc)
     *
     * @param object $ci
     * @return void
     */
    public function setDependencies(object $ci): void;
}
