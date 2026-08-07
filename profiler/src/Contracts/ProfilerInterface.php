<?php

declare(strict_types=1);

namespace Kodhe\Profiler\Contracts;

/**
 * Profiler Interface
 * 
 * Main interface for the Profiler facade/orchestrator
 */
interface ProfilerInterface
{
    /**
     * Set which sections to display
     *
     * @param array $config
     * @return void
     */
    public function setSections(array $config): void;

    /**
     * Run the profiler and return output
     *
     * @return string
     */
    public function run(): string;

    /**
     * Enable a specific section
     *
     * @param string $section
     * @return void
     */
    public function enableSection(string $section): void;

    /**
     * Disable a specific section
     *
     * @param string $section
     * @return void
     */
    public function disableSection(string $section): void;

    /**
     * Check if a section is enabled
     *
     * @param string $section
     * @return bool
     */
    public function isSectionEnabled(string $section): bool;

    /**
     * Register a custom collector
     *
     * @param string $name
     * @param CollectorInterface $collector
     * @return void
     */
    public function addCollector(string $name, CollectorInterface $collector): void;

    /**
     * Get all available sections
     *
     * @return array
     */
    public function getAvailableSections(): array;
}
