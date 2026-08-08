<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Support\Autoloader;

/**
 * Unit tests for Autoloader class
 */
class AutoloaderTest extends TestCase
{
    private Autoloader $autoloader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->autoloader = Autoloader::getInstance();
    }

    /**
     * Test autoloader instantiation via singleton
     */
    public function testGetInstance(): void
    {
        $this->assertInstanceOf(Autoloader::class, $this->autoloader);
    }

    /**
     * Test getInstance returns same instance (singleton)
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = Autoloader::getInstance();
        $instance2 = Autoloader::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test addPrefix adds namespace prefix mapping
     */
    public function testAddPrefix(): void
    {
        $this->autoloader->addPrefix('Test\\Namespace', '/path/to/test');
        
        // Use reflection to check internal state
        $reflection = new \ReflectionClass($this->autoloader);
        $property = $reflection->getProperty('prefixes');
        $property->setAccessible(true);
        
        $prefixes = $property->getValue($this->autoloader);
        
        $this->assertArrayHasKey('Test\\Namespace', $prefixes);
        $this->assertEquals('/path/to/test/', $prefixes['Test\\Namespace']);
    }

    /**
     * Test addPrefix trims trailing slash
     */
    public function testAddPrefixTrimsSlash(): void
    {
        $this->autoloader->addPrefix('Test\\NS', '/path/with/slash/');
        
        $reflection = new \ReflectionClass($this->autoloader);
        $property = $reflection->getProperty('prefixes');
        $property->setAccessible(true);
        
        $prefixes = $property->getValue($this->autoloader);
        
        $this->assertEquals('/path/with/slash/', $prefixes['Test\\NS']);
    }

    /**
     * Test addSpace adds multiple paths for namespace
     */
    public function testAddSpace(): void
    {
        $this->autoloader->addSpace('Multi\\Path', '/first/path');
        $this->autoloader->addSpace('Multi\\Path', '/second/path');
        
        $reflection = new \ReflectionClass($this->autoloader);
        $property = $reflection->getProperty('spaces');
        $property->setAccessible(true);
        
        $spaces = $property->getValue($this->autoloader);
        
        $this->assertArrayHasKey('Multi\\Path', $spaces);
        $this->assertCount(2, $spaces['Multi\\Path']);
    }

    /**
     * Test addSpace prevents duplicate paths
     */
    public function testAddSpacePreventsDuplicates(): void
    {
        $this->autoloader->addSpace('No\\Dupes', '/same/path');
        $this->autoloader->addSpace('No\\Dupes', '/same/path');
        $this->autoloader->addSpace('No\\Dupes', '/same/path/');
        
        $reflection = new \ReflectionClass($this->autoloader);
        $property = $reflection->getProperty('spaces');
        $property->setAccessible(true);
        
        $spaces = $property->getValue($this->autoloader);
        
        $this->assertCount(1, $spaces['No\\Dupes']);
    }

    /**
     * Test addSpace normalizes paths with trailing slashes
     */
    public function testAddSpaceNormalizesPaths(): void
    {
        $this->autoloader->addSpace('Normalized', '/test/path');
        $this->autoloader->addSpace('Normalized', '/test/path/');
        
        $reflection = new \ReflectionClass($this->autoloader);
        $property = $reflection->getProperty('spaces');
        $property->setAccessible(true);
        
        $spaces = $property->getValue($this->autoloader);
        
        // Both should be normalized to same path
        $this->assertCount(1, $spaces['Normalized']);
        $this->assertEquals(['/test/path/'], $spaces['Normalized']);
    }

    /**
     * Test register adds autoloader to SPL
     */
    public function testRegister(): void
    {
        $result = $this->autoloader->register();
        
        $this->assertSame($this->autoloader, $result);
        
        // Check if autoloader is registered
        $autoloadFunctions = spl_autoload_functions();
        $this->assertContains([$this->autoloader, 'loadClass'], $autoloadFunctions);
    }

    /**
     * Test unregister removes autoloader from SPL
     */
    public function testUnregister(): void
    {
        // First register
        $this->autoloader->register();
        
        // Then unregister
        $result = $this->autoloader->unregister();
        
        $this->assertSame($this->autoloader, $result);
        
        // Check if autoloader is unregistered
        $autoloadFunctions = spl_autoload_functions();
        $this->assertNotContains([$this->autoloader, 'loadClass'], $autoloadFunctions);
    }

    /**
     * Test fluent interface for addPrefix
     */
    public function testAddPrefixFluentInterface(): void
    {
        $result = $this->autoloader->addPrefix('Fluent\\Test', '/fluent/path');
        
        $this->assertSame($this->autoloader, $result);
    }

    /**
     * Test fluent interface for addSpace
     */
    public function testAddSpaceFluentInterface(): void
    {
        $result = $this->autoloader->addSpace('Fluent\\Space', '/fluent/space');
        
        $this->assertSame($this->autoloader, $result);
    }

    /**
     * Test loadClass method exists
     */
    public function testLoadClassMethodExists(): void
    {
        $this->assertTrue(method_exists($this->autoloader, 'loadClass'));
    }

    /**
     * Test multiple prefixes can be added
     */
    public function testMultiplePrefixes(): void
    {
        $this->autoloader->addPrefix('First\\NS', '/first/path');
        $this->autoloader->addPrefix('Second\\NS', '/second/path');
        $this->autoloader->addPrefix('Third\\NS', '/third/path');
        
        $reflection = new \ReflectionClass($this->autoloader);
        $property = $reflection->getProperty('prefixes');
        $property->setAccessible(true);
        
        $prefixes = $property->getValue($this->autoloader);
        
        $this->assertCount(3, $prefixes);
        $this->assertArrayHasKey('First\\NS', $prefixes);
        $this->assertArrayHasKey('Second\\NS', $prefixes);
        $this->assertArrayHasKey('Third\\NS', $prefixes);
    }

    /**
     * Test replacing existing prefix
     */
    public function testReplaceExistingPrefix(): void
    {
        $this->autoloader->addPrefix('Replace\\NS', '/original/path');
        $this->autoloader->addPrefix('Replace\\NS', '/new/path');
        
        $reflection = new \ReflectionClass($this->autoloader);
        $property = $reflection->getProperty('prefixes');
        $property->setAccessible(true);
        
        $prefixes = $property->getValue($this->autoloader);
        
        // Should replace, not append
        $this->assertCount(1, $prefixes);
        $this->assertEquals('/new/path/', $prefixes['Replace\\NS']);
    }
}
