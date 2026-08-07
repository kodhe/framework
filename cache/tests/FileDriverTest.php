<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cache\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Cache\Drivers\File;

/**
 * Unit tests for File Cache Driver
 */
class FileDriverTest extends TestCase
{
    private File $driver;
    private string $testCachePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary cache directory for testing
        $this->testCachePath = sys_get_temp_dir() . '/file_driver_test_' . uniqid() . '/';
        if (!file_exists($this->testCachePath)) {
            mkdir($this->testCachePath, 0755, true);
        }

        // Initialize file driver directly
        $config = ['cache_path' => $this->testCachePath];
        $this->driver = new File($config);
    }

    protected function tearDown(): void
    {
        // Clean up test cache files
        if (is_dir($this->testCachePath)) {
            $files = glob($this->testCachePath . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testCachePath);
        }
        
        parent::tearDown();
    }

    public function testIsSupported(): void
    {
        $this->assertTrue($this->driver->isSupported());
    }

    public function testSaveAndGet(): void
    {
        $key = 'file_test_key';
        $value = 'file_test_value';
        
        $result = $this->driver->save($key, $value, 60);
        $this->assertTrue($result);
        
        $retrieved = $this->driver->get($key);
        $this->assertEquals($value, $retrieved);
    }

    public function testDelete(): void
    {
        $key = 'file_delete_test';
        $value = 'delete_this';
        
        $this->driver->save($key, $value, 60);
        $this->assertEquals($value, $this->driver->get($key));
        
        $deleteResult = $this->driver->delete($key);
        $this->assertTrue($deleteResult);
        
        $this->assertFalse($this->driver->get($key));
    }

    public function testIncrement(): void
    {
        $key = 'file_increment_test';
        
        $this->driver->save($key, 0, 60);
        
        $newValue = $this->driver->increment($key, 5);
        $this->assertEquals(5, $newValue);
        
        $newValue = $this->driver->increment($key, 10);
        $this->assertEquals(15, $newValue);
    }

    public function testDecrement(): void
    {
        $key = 'file_decrement_test';
        
        $this->driver->save($key, 20, 60);
        
        $newValue = $this->driver->decrement($key, 5);
        $this->assertEquals(15, $newValue);
        
        $newValue = $this->driver->decrement($key, 3);
        $this->assertEquals(12, $newValue);
    }

    public function testClean(): void
    {
        $this->driver->save('clean_a', 'val_a', 60);
        $this->driver->save('clean_b', 'val_b', 60);
        $this->driver->save('clean_c', 'val_c', 60);
        
        $result = $this->driver->clean();
        $this->assertTrue($result);
        
        $this->assertFalse($this->driver->get('clean_a'));
        $this->assertFalse($this->driver->get('clean_b'));
        $this->assertFalse($this->driver->get('clean_c'));
    }

    public function testGetMetadata(): void
    {
        $key = 'metadata_file_test';
        $value = 'metadata_content';
        
        $this->driver->save($key, $value, 60);
        
        $metadata = $this->driver->getMetadata($key);
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('expire', $metadata);
        $this->assertArrayHasKey('mtime', $metadata);
        $this->assertGreaterThan(time(), $metadata['expire']);
    }

    public function testCacheInfo(): void
    {
        $this->driver->save('info_file_1', 'info_val_1', 60);
        $this->driver->save('info_file_2', 'info_val_2', 60);
        
        $info = $this->driver->cacheInfo('user');
        $this->assertIsArray($info);
    }

    public function testTtlExpiration(): void
    {
        $key = 'file_ttl_test';
        $value = 'expires_soon_file';
        
        $this->driver->save($key, $value, 1);
        $this->assertEquals($value, $this->driver->get($key));
        
        sleep(2);
        
        $this->assertFalse($this->driver->get($key));
    }

    public function testNonExistentKey(): void
    {
        $result = $this->driver->get('non_existent_file_key');
        $this->assertFalse($result);
    }

    public function testDeleteNonExistentKey(): void
    {
        $result = $this->driver->delete('non_existent_delete_key');
        $this->assertFalse($result);
    }

    public function testIncrementNonNumericValue(): void
    {
        $key = 'increment_non_numeric';
        $this->driver->save($key, 'string_value', 60);
        
        $result = $this->driver->increment($key, 1);
        $this->assertFalse($result);
    }

    public function testDecrementNonNumericValue(): void
    {
        $key = 'decrement_non_numeric';
        $this->driver->save($key, 'string_value', 60);
        
        $result = $this->driver->decrement($key, 1);
        $this->assertFalse($result);
    }

    public function testRawValueStorage(): void
    {
        $key = 'raw_value_test';
        $value = ['raw' => 'data'];
        
        $result = $this->driver->save($key, $value, 60, true);
        $this->assertTrue($result);
        
        $retrieved = $this->driver->get($key);
        $this->assertEquals($value, $retrieved);
    }
}
