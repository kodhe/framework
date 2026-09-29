<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Config\Config;

/**
 * Unit tests for Config class
 */
class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock required constants if not defined
        if (!defined('APPPATH')) {
            define('APPPATH', __DIR__ . '/');
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', __DIR__ . '/');
        }
        if (!defined('ENVIRONMENT')) {
            define('ENVIRONMENT', 'testing');
        }
        if (!defined('DIRECTORY_SEPARATOR')) {
            define('DIRECTORY_SEPARATOR', '/');
        }
        
        // Mock get_config function if not exists
        if (!function_exists('get_config')) {
            function get_config() {
                return [
                    'base_url' => 'http://localhost/',
                    'index_page' => 'index.php',
                    'url_suffix' => '',
                    'enable_query_strings' => false,
                ];
            }
        }
        
        // Mock other required functions
        $functions = ['is_https', 'log_message', 'show_error', 'set_status_header', 'kodhe'];
        foreach ($functions as $func) {
            if (!function_exists($func)) {
                if ($func === 'is_https') {
                    eval('function is_https() { return false; }');
                } elseif ($func === 'log_message') {
                    eval('function log_message($level, $msg) { }');
                } elseif ($func === 'show_error') {
                    eval('function show_error($msg) { throw new \Exception($msg); }');
                } elseif ($func === 'set_status_header') {
                    eval('function set_status_header($code) { }');
                } elseif ($func === 'kodhe') {
                    eval('function kodhe() { return null; }');
                }
            }
        }
        
        $this->config = new Config();
    }

    /**
     * Test config instantiation
     */
    public function testConfigInstantiation(): void
    {
        $this->assertInstanceOf(Config::class, $this->config);
    }

    /**
     * Test set_item and item methods
     */
    public function testSetItemAndItem(): void
    {
        $this->config->set_item('test_key', 'test_value');
        
        $this->assertEquals('test_value', $this->config->item('test_key'));
    }

    /**
     * Test item returns null for non-existent key
     */
    public function testItemReturnsNullForNonExistent(): void
    {
        $this->assertNull($this->config->item('non_existent_key'));
    }

    /**
     * Test item with index
     */
    public function testItemWithIndex(): void
    {
        $this->config->set_item('parent', [
            'child' => 'child_value'
        ]);
        
        $this->assertEquals('child_value', $this->config->item('child', 'parent'));
    }

    /**
     * Test has_item method
     */
    public function testHasItem(): void
    {
        $this->config->set_item('existing_key', 'value');
        
        $this->assertTrue($this->config->has_item('existing_key'));
        $this->assertFalse($this->config->has_item('non_existing_key'));
    }

    /**
     * Test remove_item method
     */
    public function testRemoveItem(): void
    {
        $this->config->set_item('to_remove', 'value');
        $this->assertTrue($this->config->has_item('to_remove'));
        
        $this->config->remove_item('to_remove');
        
        $this->assertFalse($this->config->has_item('to_remove'));
        $this->assertNull($this->config->item('to_remove'));
    }

    /**
     * Test get_all method
     */
    public function testGetAll(): void
    {
        $all = $this->config->get_all();
        
        $this->assertIsArray($all);
        $this->assertArrayHasKey('base_url', $all);
    }

    /**
     * Test slash_item method
     */
    public function testSlashItem(): void
    {
        $this->config->set_item('path_with_slash', 'test/path/');
        $this->config->set_item('path_without_slash', 'test/path');
        $this->config->set_item('empty_path', '');
        
        $this->assertEquals('test/path/', $this->config->slash_item('path_with_slash'));
        $this->assertEquals('test/path/', $this->config->slash_item('path_without_slash'));
        $this->assertEquals('', $this->config->slash_item('empty_path'));
    }

    /**
     * Test ArrayAccess offsetExists
     */
    public function testArrayAccessOffsetExists(): void
    {
        $this->config->set_item('array_key', 'value');
        
        $this->assertTrue(isset($this->config['array_key']));
        $this->assertFalse(isset($this->config['nonexistent']));
    }

    /**
     * Test ArrayAccess offsetGet
     */
    public function testArrayAccessOffsetGet(): void
    {
        $this->config->set_item('array_key', 'array_value');
        
        $this->assertEquals('array_value', $this->config['array_key']);
    }

    /**
     * Test ArrayAccess offsetSet
     */
    public function testArrayAccessOffsetSet(): void
    {
        $this->config['new_key'] = 'new_value';
        
        $this->assertEquals('new_value', $this->config->item('new_key'));
    }

    /**
     * Test ArrayAccess offsetUnset
     */
    public function testArrayAccessOffsetUnset(): void
    {
        $this->config->set_item('to_unset', 'value');
        unset($this->config['to_unset']);
        
        $this->assertFalse($this->config->has_item('to_unset'));
    }

    /**
     * Test magic __get method
     */
    public function testMagicGet(): void
    {
        $this->config->set_item('magic_key', 'magic_value');
        
        $this->assertEquals('magic_value', $this->config->magic_key);
    }

    /**
     * Test magic __set method
     */
    public function testMagicSet(): void
    {
        $this->config->magic_set_key = 'magic_set_value';
        
        $this->assertEquals('magic_set_value', $this->config->item('magic_set_key'));
    }

    /**
     * Test magic __isset method
     */
    public function testMagicIsset(): void
    {
        $this->config->set_item('isset_key', 'value');
        
        $this->assertTrue(isset($this->config->isset_key));
        $this->assertFalse(isset($this->config->nonexistent_key));
    }

    /**
     * Test magic __unset method
     */
    public function testMagicUnset(): void
    {
        $this->config->set_item('unset_key', 'value');
        unset($this->config->unset_key);
        
        $this->assertFalse($this->config->has_item('unset_key'));
    }

    /**
     * Test site_url with empty uri
     */
    public function testSiteUrlEmpty(): void
    {
        $url = $this->config->site_url();
        
        $this->assertStringContainsString('http://localhost/', $url);
    }

    /**
     * Test site_url with uri string
     */
    public function testSiteUrlWithUri(): void
    {
        $url = $this->config->site_url('controller/method');
        
        $this->assertStringContainsString('controller/method', $url);
    }

    /**
     * Test site_url with array segments
     */
    public function testSiteUrlWithArray(): void
    {
        $url = $this->config->site_url(['controller', 'method', 'param']);
        
        $this->assertStringContainsString('controller/method/param', $url);
    }

    /**
     * Test base_url with empty uri
     */
    public function testBaseUrlEmpty(): void
    {
        $url = $this->config->base_url();
        
        $this->assertEquals('http://localhost/', $url);
    }

    /**
     * Test base_url with uri string
     */
    public function testBaseUrlWithUri(): void
    {
        $url = $this->config->base_url('assets/css/style.css');
        
        $this->assertStringContainsString('assets/css/style.css', $url);
    }

    /**
     * Test base_url with protocol
     */
    public function testBaseUrlWithProtocol(): void
    {
        $url = $this->config->base_url('', 'https://');
        
        $this->assertStringStartsWith('https://', $url);
    }

    /**
     * Test _assign_to_config method
     */
    public function testAssignToConfig(): void
    {
        $items = [
            'custom_key1' => 'custom_value1',
            'custom_key2' => 'custom_value2'
        ];
        
        $this->config->_assign_to_config($items);
        
        $this->assertEquals('custom_value1', $this->config->item('custom_key1'));
        $this->assertEquals('custom_value2', $this->config->item('custom_key2'));
    }

    /**
     * Test _assign_to_config with non-array
     */
    public function testAssignToConfigNonArray(): void
    {
        // Should not throw error
        $this->config->_assign_to_config('not an array');
        
        $this->assertIsArray($this->config->get_all());
    }
}
