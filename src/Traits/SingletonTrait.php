<?php

namespace Kodhe\Calendar\Traits;

/**
 * Trait SingletonTrait
 *
 * Provides singleton pattern implementation
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
trait SingletonTrait
{
    /**
     * Singleton instances
     *
     * @var array|static[]
     */
    private static $instances = [];

    /**
     * Get singleton instance
     *
     * @param array $config Optional configuration for first instantiation
     * @return static
     */
    public static function getInstance(array $config = [])
    {
        $class = static::class;

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static($config);
        }

        return self::$instances[$class];
    }

    /**
     * Reset singleton instance (useful for testing)
     *
     * @return void
     */
    public static function resetInstance(): void
    {
        $class = static::class;
        unset(self::$instances[$class]);
    }

    /**
     * Clear all singleton instances
     *
     * @return void
     */
    public static function clearInstances(): void
    {
        self::$instances = [];
    }
}
