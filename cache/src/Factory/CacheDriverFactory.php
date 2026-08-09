<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cache\Factory;

use Kodhe\Framework\Cache\Contracts\CacheDriverInterface;
use Kodhe\Framework\Cache\Drivers\File;
use Kodhe\Framework\Cache\Drivers\Apc;
use Kodhe\Framework\Cache\Drivers\Dummy;
use Kodhe\Framework\Cache\Drivers\Memcached;
use Kodhe\Framework\Cache\Drivers\Redis;
use Kodhe\Framework\Cache\Drivers\Wincache;
use InvalidArgumentException;

/**
 * Cache Driver Factory
 * 
 * Creates cache driver instances based on configuration
 * 
 * @package     Kodhe\Framework\Cache\Factory
 * @author      EllisLab Dev Team (refactored by Kodhe)
 * @version     2.0.0
 * @license     MIT
 */
class CacheDriverFactory
{
    /**
     * Mapping of driver names to class names
     *
     * @var array
     */
    protected static $driverMap = [
        'file'       => File::class,
        'apc'        => Apc::class,
        'apcu'       => Apc::class, // Alias for APC
        'dummy'      => Dummy::class,
        'memcached'  => Memcached::class,
        'redis'      => Redis::class,
        'wincache'   => Wincache::class,
    ];

    /**
     * Create a cache driver instance
     *
     * @param string $name Driver name
     * @param array $config Configuration options
     * @return CacheDriverInterface
     * @throws InvalidArgumentException If driver is not supported
     */
    public function make(string $name, array $config = []): CacheDriverInterface
    {
        $name = strtolower($name);

        if (!isset(self::$driverMap[$name])) {
            throw new InvalidArgumentException("Cache driver '{$name}' is not supported.");
        }

        $class = self::$driverMap[$name];

        if (!class_exists($class)) {
            throw new InvalidArgumentException("Cache driver class '{$class}' does not exist.");
        }

        return new $class($config);
    }

    /**
     * Check if a driver is available
     *
     * @param string $name Driver name
     * @return bool
     */
    public function isAvailable(string $name): bool
    {
        $name = strtolower($name);

        if (!isset(self::$driverMap[$name])) {
            return false;
        }

        $class = self::$driverMap[$name];
        
        if (!class_exists($class)) {
            return false;
        }

        $driver = new $class();
        return $driver instanceof CacheDriverInterface && $driver->isSupported();
    }

    /**
     * Get list of available drivers
     *
     * @return array
     */
    public function getAvailableDrivers(): array
    {
        $available = [];

        foreach (self::$driverMap as $name => $class) {
            if ($this->isAvailable($name)) {
                $available[] = $name;
            }
        }

        return $available;
    }

    /**
     * Register a custom driver
     *
     * @param string $name Driver name
     * @param string $class Class name implementing CacheDriverInterface
     * @return void
     */
    public static function registerDriver(string $name, string $class): void
    {
        if (!is_subclass_of($class, CacheDriverInterface::class)) {
            throw new InvalidArgumentException("Class '{$class}' must implement CacheDriverInterface.");
        }

        self::$driverMap[strtolower($name)] = $class;
    }
}
