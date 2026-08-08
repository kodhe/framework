<?php

declare(strict_types=0);

namespace Kodhe\Framework\Upload\Factory;

use Kodhe\Framework\Upload\Contracts\FilenameStrategyInterface;

/**
 * Filename Strategy Factory
 * 
 * Creates filename generation strategies
 * 
 * @package Kodhe\Upload\Factory
 */
class FilenameStrategyFactory
{
    /**
     * Registered strategies
     *
     * @var array
     */
    private static $strategies = [];

    /**
     * Register a strategy
     *
     * @param string $name
     * @param string $class
     * @return void
     */
    public static function register(string $name, string $class): void
    {
        if (!is_subclass_of($class, FilenameStrategyInterface::class)) {
            throw new \InvalidArgumentException(
                "Strategy class must implement FilenameStrategyInterface"
            );
        }
        self::$strategies[$name] = $class;
    }

    /**
     * Create a strategy instance
     *
     * @param string $name
     * @return FilenameStrategyInterface
     */
    public static function create(string $name): FilenameStrategyInterface
    {
        if (!isset(self::$strategies[$name])) {
            throw new \InvalidArgumentException("Unknown strategy: {$name}");
        }

        $class = self::$strategies[$name];
        return new $class();
    }

    /**
     * Get default strategy (original)
     *
     * @return FilenameStrategyInterface
     */
    public static function default(): FilenameStrategyInterface
    {
        return new \Kodhe\Framework\Upload\Drivers\OriginalFilenameStrategy();
    }

    /**
     * Get encrypt strategy
     *
     * @return FilenameStrategyInterface
     */
    public static function encrypt(): FilenameStrategyInterface
    {
        return new \Kodhe\Framework\Upload\Drivers\EncryptFilenameStrategy();
    }

    /**
     * Get increment strategy for duplicate handling
     *
     * @return FilenameStrategyInterface
     */
    public static function increment(): FilenameStrategyInterface
    {
        return new \Kodhe\Framework\Upload\Drivers\IncrementFilenameStrategy();
    }
}
