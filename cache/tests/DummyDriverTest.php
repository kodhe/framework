<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cache\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Cache\Drivers\Dummy;

/**
 * Unit tests for Dummy Cache Driver
 */
class DummyDriverTest extends TestCase
{
    private Dummy $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new Dummy();
    }

    public function testIsSupported(): void
    {
        $this->assertTrue($this->driver->isSupported());
    }

    public function testGetAlwaysReturnsFalse(): void
    {
        $result = $this->driver->get('any_key');
        $this->assertFalse($result);
    }

    public function testSaveAlwaysReturnsTrue(): void
    {
        $result = $this->driver->save('key', 'value', 60);
        $this->assertTrue($result);
    }

    public function testDeleteAlwaysReturnsTrue(): void
    {
        $result = $this->driver->delete('any_key');
        $this->assertTrue($result);
    }

    public function testIncrementAlwaysReturnsTrue(): void
    {
        $result = $this->driver->increment('key', 1);
        $this->assertTrue($result);
    }

    public function testDecrementAlwaysReturnsTrue(): void
    {
        $result = $this->driver->decrement('key', 1);
        $this->assertTrue($result);
    }

    public function testCleanAlwaysReturnsTrue(): void
    {
        $result = $this->driver->clean();
        $this->assertTrue($result);
    }

    public function testCacheInfoAlwaysReturnsFalse(): void
    {
        $result = $this->driver->cacheInfo('user');
        $this->assertFalse($result);
    }

    public function testGetMetadataAlwaysReturnsFalse(): void
    {
        $result = $this->driver->getMetadata('any_key');
        $this->assertFalse($result);
    }

    public function testNoDataPersistence(): void
    {
        // Save a value
        $this->driver->save('persist_test', 'should_not_persist', 60);
        
        // Try to retrieve it - should return false as Dummy doesn't store anything
        $result = $this->driver->get('persist_test');
        $this->assertFalse($result);
    }
}
