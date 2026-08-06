<?php declare(strict_types=1);

namespace Kodhe\Tests\Unit\Framework\Container;

use Kodhe\Framework\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for the Container class
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    public function testContainerInstantiation(): void
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    public function testHasReturnsFalseForNonExistentService(): void
    {
        $this->assertFalse($this->container->has('nonexistent'));
    }

    public function testBindRegistersService(): void
    {
        $this->container->bind('test:service', fn() => new stdClass());
        
        $this->assertTrue($this->container->has('test:service'));
    }

    public function testBindWithoutPrefixAddsNativePrefix(): void
    {
        $this->container->bind('simpleservice', fn() => new stdClass());
        
        $this->assertTrue($this->container->has('simpleservice'));
    }

    public function testGetResolvesService(): void
    {
        $expected = new stdClass();
        $this->container->bind('test:service', fn() => $expected);
        
        $actual = $this->container->get('test:service');
        
        $this->assertSame($expected, $actual);
    }

    public function testGetThrowsExceptionForNonExistentService(): void
    {
        $this->expectException(\Exception::class);
        $this->container->get('nonexistent');
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $this->container->singleton('test:singleton', fn() => new stdClass());
        
        $instance1 = $this->container->get('test:singleton');
        $instance2 = $this->container->get('test:singleton');
        
        $this->assertSame($instance1, $instance2);
    }

    public function testGetBindingsReturnsAllBindings(): void
    {
        $this->container->bind('test:one', fn() => new stdClass());
        $this->container->bind('test:two', fn() => new stdClass());
        
        $bindings = $this->container->getBindings();
        
        $this->assertCount(2, $bindings);
        $this->assertContains('kodhe:test:one', $bindings);
        $this->assertContains('kodhe:test:two', $bindings);
    }

    public function testGetSingletonBindingsReturnsSingletons(): void
    {
        $this->container->singleton('test:singleton', fn() => new stdClass());
        
        $singletons = $this->container->getSingletonBindings();
        
        $this->assertCount(1, $singletons);
        $this->assertContains('kodhe:test:singleton', $singletons);
    }

    public function testSetThrowOnDuplicate(): void
    {
        $result = $this->container->setThrowOnDuplicate(false);
        
        $this->assertSame($this->container, $result);
        $this->assertFalse($this->container->getThrowOnDuplicate());
    }

    public function testDuplicateRegistrationThrowsExceptionByDefault(): void
    {
        $this->container->bind('test:duplicate', fn() => new stdClass());
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Attempt to reregister existing class');
        
        $this->container->bind('test:duplicate', fn() => new stdClass());
    }

    public function testDuplicateRegistrationAllowedWhenDisabled(): void
    {
        $this->container->setThrowOnDuplicate(false);
        
        $this->container->bind('test:duplicate', fn() => new stdClass());
        $this->container->bind('test:duplicate', fn() => new stdClass());
        
        $this->assertTrue($this->container->has('test:duplicate'));
    }

    public function testResolveWithDependencies(): void
    {
        $dependency = new stdClass();
        $this->container->bind('test:dependency', fn() => $dependency);
        $this->container->bind('test:service', function ($c) {
            return ['dep' => $c->get('test:dependency')];
        });
        
        $resolved = $this->container->get('test:service');
        
        $this->assertSame($dependency, $resolved['dep']);
    }

    public function testForgetRemovesBinding(): void
    {
        $this->container->bind('test:remove', fn() => new stdClass());
        $this->assertTrue($this->container->has('test:remove'));
        
        $this->container->forget('test:remove');
        
        $this->assertFalse($this->container->has('test:remove'));
    }

    public function testFlushClearsAllBindings(): void
    {
        $this->container->bind('test:one', fn() => new stdClass());
        $this->container->bind('test:two', fn() => new stdClass());
        
        $this->container->flush();
        
        $this->assertCount(0, $this->container->getBindings());
    }
}
