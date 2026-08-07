<?php

declare(strict_types=1);

namespace Kodhe\Agent\Contracts;

/**
 * Interface AgentDriverInterface
 * 
 * Interface for agent detection drivers
 * 
 * @package Kodhe\Agent\Contracts
 * @author  Your Name
 * @version 2.0.0
 */
interface AgentDriverInterface
{
    /**
     * Detect the specific agent type
     *
     * @return void
     */
    public function detect(): void;

    /**
     * Get the detected value
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Check if a specific key matches
     *
     * @param string|null $key Optional key to check
     * @return bool
     */
    public function isMatch(?string $key = null): bool;
}
