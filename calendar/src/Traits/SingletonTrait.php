<?php

declare(strict_types=1);

namespace Kodhe\Calendar\Traits;

/**
 * Trait SingletonTrait
 *
 * Provides singleton pattern implementation
 *
 * @package Kodhe\Calendar\Traits
 */
trait SingletonTrait
{
    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Reset singleton instance (for testing)
     *
     * @return void
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
