<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\Service;

use Kodhe\Framework\Container\Binding\BindingInterface;
use Kodhe\Framework\Foundation\Service\ServiceLocator;
use PHPUnit\Framework\TestCase;

/**
 * Unit Test untuk ServiceLocator
 */
class ServiceLocatorTest extends TestCase
{
    private BindingInterface $mockDependencies;
    private ServiceLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock dependencies
        $this->mockDependencies = $this->createMock(BindingInterface::class);
        $this->locator = new ServiceLocator($this->mockDependencies);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(ServiceLocator::class, $this->locator);
    }

    public function testRegisterProvider(): void
    {
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        
        $this->locator->register('test', $mockProvider);
        
        $this->assertTrue($this->locator->has('test'));
    }

    public function testHasProvider(): void
    {
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        
        $this->assertFalse($this->locator->has('nonexistent'));
        
        $this->locator->register('existing', $mockProvider);
        
        $this->assertTrue($this->locator->has('existing'));
    }

    public function testGetProvider(): void
    {
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        
        $this->locator->register('myprovider', $mockProvider);
        
        $retrieved = $this->locator->get('myprovider');
        
        $this->assertSame($mockProvider, $retrieved);
    }

    public function testGetNonExistentProviderThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unknown prefix: 'nonexistent'");
        
        $this->locator->get('nonexistent');
    }

    public function testRegisterDuplicateProviderThrowsException(): void
    {
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        
        $this->locator->register('duplicate', $mockProvider);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Addon of name duplicate already registered.");
        
        $this->locator->register('duplicate', $mockProvider);
    }

    public function testAllProviders(): void
    {
        $mockProvider1 = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        $mockProvider2 = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        
        $this->locator->register('provider1', $mockProvider1);
        $this->locator->register('provider2', $mockProvider2);
        
        $all = $this->locator->all();
        
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('provider1', $all);
        $this->assertArrayHasKey('provider2', $all);
        $this->assertSame($mockProvider1, $all['provider1']);
        $this->assertSame($mockProvider2, $all['provider2']);
    }

    public function testEmptyAllProviders(): void
    {
        $all = $this->locator->all();
        
        $this->assertIsArray($all);
        $this->assertCount(0, $all);
    }

    public function testMultipleProviders(): void
    {
        $providers = [];
        for ($i = 0; $i < 5; $i++) {
            $providers[$i] = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
            $this->locator->register("provider{$i}", $providers[$i]);
        }
        
        $this->assertCount(5, $this->locator->all());
        
        foreach ($providers as $i => $provider) {
            $this->assertTrue($this->locator->has("provider{$i}"));
            $this->assertSame($provider, $this->locator->get("provider{$i}"));
        }
    }
}
