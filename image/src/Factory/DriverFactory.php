<?php

declare(strict_types=1);

namespace Kodhe\Framework\Image\Factory;

use Kodhe\Framework\Image\Contracts\ImageDriverInterface;
use Kodhe\Framework\Image\Drivers\GdDriver;
use Kodhe\Framework\Image\Drivers\ImagickDriver;
use Kodhe\Framework\Image\Support\ImageMetadataCache;
use RuntimeException;

/**
 * Class DriverFactory
 *
 * Factory for creating image driver instances.
 * Implements Factory Pattern for driver instantiation.
 *
 * @package     Kodhe\Image
 * @author      CodeIgniter Refactored
 * @version     2.0.0
 * @license     MIT
 */
class DriverFactory
{
    /**
     * @var array
     */
    private static $driverMap = [
        'gd' => GdDriver::class,
        'gd2' => GdDriver::class,
        'imagemagick' => ImagickDriver::class,
        'netpbm' => null, // Not yet implemented
    ];

    /**
     * Create a driver instance
     *
     * @param string $type
     * @return ImageDriverInterface
     * @throws RuntimeException
     */
    public static function make(string $type): ImageDriverInterface
    {
        $type = strtolower($type);

        if (!isset(self::$driverMap[$type])) {
            throw new RuntimeException("Unknown image driver type: {$type}");
        }

        $class = self::$driverMap[$type];
        if ($class === null) {
            throw new RuntimeException("Driver '{$type}' is not yet implemented");
        }

        if (!class_exists($class)) {
            throw new RuntimeException("Driver class '{$class}' does not exist");
        }

        return new $class();
    }

    /**
     * Check if a driver is available
     *
     * @param string $type
     * @return bool
     */
    public static function isAvailable(string $type): bool
    {
        $type = strtolower($type);

        if (!isset(self::$driverMap[$type])) {
            return false;
        }

        $class = self::$driverMap[$type];
        if ($class === null) {
            return false;
        }

        return class_exists($class);
    }

    /**
     * Register a custom driver
     *
     * @param string $type
     * @param string $class
     * @return void
     */
    public static function register(string $type, string $class): void
    {
        if (!is_subclass_of($class, ImageDriverInterface::class)) {
            throw new RuntimeException(
                "Custom driver class must implement " . ImageDriverInterface::class
            );
        }

        self::$driverMap[strtolower($type)] = $class;
    }

    /**
     * Get all registered drivers
     *
     * @return array
     */
    public static function getRegisteredDrivers(): array
    {
        return array_keys(self::$driverMap);
    }

    /**
     * Clear metadata cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        ImageMetadataCache::clear();
    }
}
