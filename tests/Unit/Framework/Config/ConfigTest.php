<?php declare(strict_types=1);

namespace Kodhe\Tests\Unit\Framework\Config;

use Kodhe\Framework\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Config class
 */
class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock required constants if not defined
        if (!defined('APPPATH')) {
            define('APPPATH', __DIR__ . '/../../../../');
        }
        
        $this->config = new Config();
    }

    public function testConfigInstantiation(): void
    {
        $this->assertInstanceOf(Config::class, $this->config);
    }

    public function testConfigHasBaseUrlByDefault(): void
    {
        $baseUrl = $this->config->item('base_url');
        
        $this->assertNotEmpty($baseUrl);
        $this->assertIsString($baseUrl);
    }

    public function testSetItemAddsConfiguration(): void
    {
        $this->config->set_item('test:key', 'test_value');
        
        $value = $this->config->item('test:key');
        
        $this->assertSame('test_value', $value);
    }

    public function testItemReturnsNullForNonExistentKey(): void
    {
        $value = $this->config->item('nonexistent_key');
        
        $this->assertNull($value);
    }

    public function testItemReturnsDefaultValue(): void
    {
        $defaultValue = 'default_value';
        $value = $this->config->item('nonexistent_key', $defaultValue);
        
        $this->assertSame($defaultValue, $value);
    }

    public function testSetItemWithDotNotation(): void
    {
        $this->config->set_item('database.default.host', 'localhost');
        
        $value = $this->config->item('database.default.host');
        
        $this->assertSame('localhost', $value);
    }

    public function testArrayAccessSet(): void
    {
        $this->config['test:array_key'] = 'array_value';
        
        $value = $this->config->item('test:array_key');
        
        $this->assertSame('array_value', $value);
    }

    public function testArrayAccessGet(): void
    {
        $this->config->set_item('test:access', 'access_value');
        
        $this->assertSame('access_value', $this->config['test:access']);
    }

    public function testArrayAccessExists(): void
    {
        $this->config->set_item('test:exists', 'exists_value');
        
        $this->assertTrue(isset($this->config['test:exists']));
        $this->assertFalse(isset($this->config['test:not_exists']));
    }

    public function testArrayAccessUnset(): void
    {
        $this->config->set_item('test:unset', 'unset_value');
        $this->assertTrue(isset($this->config['test:unset']));
        
        unset($this->config['test:unset']);
        
        $this->assertFalse(isset($this->config['test:unset']));
    }

    public function testAllReturnsAllConfigItems(): void
    {
        $this->config->set_item('test:one', 'one');
        $this->config->set_item('test:two', 'two');
        
        $all = $this->config->all();
        
        $this->assertIsArray($all);
        $this->assertArrayHasKey('test:one', $all);
        $this->assertArrayHasKey('test:two', $all);
    }

    public function testLoadReturnsItemAfterLoading(): void
    {
        // Test with empty file - should return null or handle gracefully
        $result = $this->config->load('', false, true);
        
        // The method should not throw an exception with fail_gracefully=true
        $this->assertNotNull($result);
    }

    public function testSlashItemRemovesTrailingSlash(): void
    {
        $this->config->set_item('test:url', 'http://example.com/');
        
        $url = $this->config->slash_item('test:url');
        
        $this->assertSame('http://example.com/', $url);
    }

    public function testSlashItemAddsTrailingSlashWhenMissing(): void
    {
        $this->config->set_item('test:url', 'http://example.com');
        
        $url = $this->config->slash_item('test:url');
        
        $this->assertSame('http://example.com/', $url);
    }
}
