<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cache\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Cache\Cache;
use Kodhe\Framework\Cache\Drivers\File;
use Kodhe\Framework\Cache\Drivers\Dummy;
use Kodhe\Framework\Cache\Factory\CacheDriverFactory;

/**
 * Unit tests for Cache class
 */
class CacheTest extends TestCase
{
    private Cache $cache;
    private string $testCachePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary cache directory for testing
        $this->testCachePath = sys_get_temp_dir() . '/cache_test_' . uniqid() . '/';
        if (!file_exists($this->testCachePath)) {
            mkdir($this->testCachePath, 0755, true);
        }

        // Initialize cache with file driver
        $config = [
            'adapter' => 'file',
            'backup' => 'dummy',
            'cache_path' => $this->testCachePath,
            'key_prefix' => 'test_'
        ];
        
        $this->cache = new Cache($config);
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

    public function testSaveAndGet(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        
        $result = $this->cache->save($key, $value, 60);
        $this->assertTrue($result);
        
        $retrieved = $this->cache->get($key);
        $this->assertEquals($value, $retrieved);
    }

    public function testGetNonExistentKey(): void
    {
        $result = $this->cache->get('non_existent_key');
        $this->assertFalse($result);
    }

    public function testDelete(): void
    {
        $key = 'delete_test';
        $value = 'delete_me';
        
        $this->cache->save($key, $value, 60);
        $this->assertEquals($value, $this->cache->get($key));
        
        $deleteResult = $this->cache->delete($key);
        $this->assertTrue($deleteResult);
        
        $this->assertFalse($this->cache->get($key));
    }

    public function testIncrement(): void
    {
        $key = 'increment_test';
        
        // Start from zero
        $this->cache->save($key, 0, 60);
        
        $newValue = $this->cache->increment($key, 5);
        $this->assertEquals(5, $newValue);
        
        $newValue = $this->cache->increment($key, 3);
        $this->assertEquals(8, $newValue);
    }

    public function testDecrement(): void
    {
        $key = 'decrement_test';
        
        // Start from 10
        $this->cache->save($key, 10, 60);
        
        $newValue = $this->cache->decrement($key, 3);
        $this->assertEquals(7, $newValue);
        
        $newValue = $this->cache->decrement($key, 2);
        $this->assertEquals(5, $newValue);
    }

    public function testClean(): void
    {
        // Save multiple items
        $this->cache->save('clean_1', 'value1', 60);
        $this->cache->save('clean_2', 'value2', 60);
        $this->cache->save('clean_3', 'value3', 60);
        
        $this->assertEquals('value1', $this->cache->get('clean_1'));
        $this->assertEquals('value2', $this->cache->get('clean_2'));
        $this->assertEquals('value3', $this->cache->get('clean_3'));
        
        $result = $this->cache->clean();
        $this->assertTrue($result);
        
        // All items should be gone
        $this->assertFalse($this->cache->get('clean_1'));
        $this->assertFalse($this->cache->get('clean_2'));
        $this->assertFalse($this->cache->get('clean_3'));
    }

    public function testIsSupported(): void
    {
        $this->assertTrue($this->cache->is_supported('file'));
        $this->assertTrue($this->cache->is_supported('dummy'));
    }

    public function testKeyPrefix(): void
    {
        $key = 'prefixed_key';
        $value = 'prefixed_value';
        
        $this->cache->save($key, $value, 60);
        
        // Direct file check to verify prefix is applied
        $filePath = $this->testCachePath . 'test_' . $key;
        $this->assertFileExists($filePath);
    }

    public function testTtlExpiration(): void
    {
        $key = 'ttl_test';
        $value = 'expires_soon';
        
        // Save with 1 second TTL
        $this->cache->save($key, $value, 1);
        
        // Should exist immediately
        $this->assertEquals($value, $this->cache->get($key));
        
        // Wait for expiration
        sleep(2);
        
        // Should be expired now
        $this->assertFalse($this->cache->get($key));
    }

    public function testCacheInfo(): void
    {
        $this->cache->save('info_test', 'info_value', 60);
        
        $info = $this->cache->cache_info('user');
        $this->assertIsArray($info);
        $this->assertNotEmpty($info);
    }

    public function testGetMetadata(): void
    {
        $key = 'metadata_test';
        $value = 'metadata_value';
        
        $this->cache->save($key, $value, 60);
        
        $metadata = $this->cache->get_metadata($key);
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('expire', $metadata);
        $this->assertArrayHasKey('mtime', $metadata);
    }

    public function testComplexDataTypes(): void
    {
        $arrayData = ['key1' => 'value1', 'key2' => [1, 2, 3]];
        $objectData = (object)['prop1' => 'val1', 'prop2' => 123];
        
        $this->cache->save('array_data', $arrayData, 60);
        $this->cache->save('object_data', $objectData, 60);
        
        $retrievedArray = $this->cache->get('array_data');
        $retrievedObject = $this->cache->get('object_data');
        
        $this->assertEquals($arrayData, $retrievedArray);
        $this->assertEquals($objectData, $retrievedObject);
    }

    public function testFallbackToBackupDriver(): void
    {
        // Test that cache falls back to dummy driver when primary is unavailable
        $config = [
            'adapter' => 'apc', // APC might not be available
            'backup' => 'dummy',
        ];
        
        $cache = new Cache($config);
        $this->assertInstanceOf(Cache::class, $cache);
    }
}
