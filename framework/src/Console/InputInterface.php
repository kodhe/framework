<?php

declare(strict_types=0);

namespace Kodhe\Framework\Console;

/**
 * Input Interface for Console Commands
 */
interface InputInterface
{
    /**
     * Get all arguments
     */
    public function getArguments(): array;

    /**
     * Get a specific argument by name or index
     */
    public function getArgument(string|int $name, mixed $default = null): mixed;

    /**
     * Check if an argument exists
     */
    public function hasArgument(string|int $name): bool;

    /**
     * Set an argument value
     */
    public function setArgument(string|int $name, mixed $value): void;

    /**
     * Get all options
     */
    public function getOptions(): array;

    /**
     * Get a specific option value
     */
    public function getOption(string $name, mixed $default = null): mixed;

    /**
     * Check if an option exists
     */
    public function hasOption(string $name): bool;

    /**
     * Set an option value
     */
    public function setOption(string $name, mixed $value): void;

    /**
     * Get the first argument (usually command name)
     */
    public function getFirstArgument(): ?string;

    /**
     * Get raw input tokens
     */
    public function getTokens(): array;
}
