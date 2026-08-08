<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cache\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Cache\Factory\CacheDriverFactory;
use Kodhe\Framework\Cache\Drivers\File;
use Kodhe\Framework\Cache\Drivers\Dummy;
use InvalidArgumentException;

/**
 * Unit tests for CacheDriverFactory
 */
class CacheDriverFactoryTest extends TestCase
{
    private CacheDriverFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new CacheDriverFactory();
    }

    public function testMakeFileDriver(): void
    {
        $driver = $this->factory->make('file', ['cache_path' => sys_get_temp_dir() . '/']);
        $this->assertInstanceOf(File::class, $driver);
    }

    public function testMakeDummyDriver(): void
    {
        $driver = $this->factory->make('dummy');
        $this->assertInstanceOf(Dummy::class, $driver);
    }

    public function testMakeDriverCaseInsensitive(): void
    {
        $driver = $this->factory->make('FILE', ['cache_path' => sys_get_temp_dir() . '/']);
        $this->assertInstanceOf(File::class, $driver);
        
        $driver = $this->factory->make('DUMMY');
        $this->assertInstanceOf(Dummy::class, $driver);
    }

    public function testMakeInvalidDriver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cache driver 'invalid_driver' is not supported.");
        
        $this->factory->make('invalid_driver');
    }

    public function testIsAvailableForSupportedDriver(): void
    {
        // Dummy driver should always be available
        $this->assertTrue($this->factory->isAvailable('dummy'));
    }

    public function testIsAvailableForUnsupportedDriver(): void
    {
        // APC might not be available on all systems
        $result = $this->factory->isAvailable('apc');
        $this->assertIsBool($result);
    }

    public function testIsAvailableForNonExistentDriver(): void
    {
        $result = $this->factory->isAvailable('nonexistent');
        $this->assertFalse($result);
    }

    public function testGetAvailableDrivers(): void
    {
        $available = $this->factory->getAvailableDrivers();
        $this->assertIsArray($available);
        
        // At minimum, dummy and file should be available
        $this->assertContains('dummy', $available);
        $this->assertContains('file', $available);
    }

    public function testRegisterCustomDriver(): void
    {
        // Create a mock custom driver
        $customDriverClass = CustomTestDriver::class;
        
        CacheDriverFactory::registerDriver('custom', $customDriverClass);
        
        $driver = $this->factory->make('custom');
        $this->assertInstanceOf($customDriverClass, $driver);
    }

    public function testRegisterInvalidDriver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must implement CacheDriverInterface");
        
        CacheDriverFactory::registerDriver('invalid', \stdClass::class);
    }

    public function testApcAlias(): void
    {
        // APCU should map to Apc driver
        try {
            $driver = $this->factory->make('apcu', ['cache_path' => sys_get_temp_dir() . '/']);
            $this->assertInstanceOf(\Kodhe\Framework\Cache\Drivers\Apc::class, $driver);
        } catch (\Exception $e) {
            // If APC is not available, this is expected
            $this->assertStringContainsString('unavailable', $e->getMessage());
        }
    }
}

/**
 * Custom test driver for testing registration
 */
class CustomTestDriver implements \Kodhe\Framework\Cache\Contracts\CacheDriverInterface
{
    public function isSupported(): bool
    {
        return true;
    }

    public function get(string $id)
    {
        return false;
    }

    public function save(string $id, $data, int $ttl = 60, bool $raw = false): bool
    {
        return true;
    }

    public function delete(string $id): bool
    {
        return true;
    }

    public function increment(string $id, int $offset = 1)
    {
        return false;
    }

    public function decrement(string $id, int $offset = 1)
    {
        return false;
    }

    public function clean(): bool
    {
        return true;
    }

    public function cacheInfo(?string $type = null)
    {
        return false;
    }

    public function getMetadata(string $id)
    {
        return false;
    }
}
