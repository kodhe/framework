<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Container\Container;

/**
 * Unit tests for Container class
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    /**
     * Test container instantiation
     */
    public function testContainerInstantiation(): void
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    /**
     * Test has method returns false for unregistered service
     */
    public function testHasReturnsFalseForUnregistered(): void
    {
        $this->assertFalse($this->container->has('unregistered'));
    }

    /**
     * Test register and has methods
     */
    public function testRegisterAndHas(): void
    {
        $this->container->register('test:service', function() {
            return new \stdClass();
        });

        $this->assertTrue($this->container->has('test:service'));
    }

    /**
     * Test register adds native prefix automatically
     */
    public function testRegisterAddsNativePrefix(): void
    {
        $this->container->register('myservice', function() {
            return new \stdClass();
        });

        $this->assertTrue($this->container->has('kodhe:myservice'));
        $this->assertTrue($this->container->has('myservice'));
    }

    /**
     * Test make returns instance from closure
     */
    public function testMakeFromClosure(): void
    {
        $this->container->register('test:factory', function($container) {
            return new \stdClass();
        });

        $instance = $this->container->make('test:factory');

        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * Test make returns same instance for closures (not singleton)
     */
    public function testMakeCreatesNewInstanceEachTime(): void
    {
        $counter = 0;
        
        $this->container->register('test:counter', function($container) use (&$counter) {
            $counter++;
            return new \stdClass();
        });

        $instance1 = $this->container->make('test:counter');
        $instance2 = $this->container->make('test:counter');

        $this->assertEquals(2, $counter);
        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test registerSingleton creates singleton
     */
    public function testRegisterSingleton(): void
    {
        $counter = 0;
        
        $this->container->registerSingleton('test:singleton', function($container) use (&$counter) {
            $counter++;
            return new \stdClass();
        });

        $instance1 = $this->container->make('test:singleton');
        $instance2 = $this->container->make('test:singleton');

        $this->assertEquals(1, $counter);
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test make throws exception for unregistered service
     */
    public function testMakeThrowsExceptionForUnregistered(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dependency Injection: Unregistered service "kodhe:unregistered"');

        $this->container->make('unregistered');
    }

    /**
     * Test register with object instance
     */
    public function testRegisterWithObject(): void
    {
        $service = new \stdClass();
        $service->name = 'test';

        $this->container->register('test:object', $service);

        $retrieved = $this->container->make('test:object');

        $this->assertSame($service, $retrieved);
        $this->assertEquals('test', $retrieved->name);
    }

    /**
     * Test getBindings returns registered bindings
     */
    public function testGetBindings(): void
    {
        $this->container->register('test:one', function() { return 1; });
        $this->container->register('test:two', function() { return 2; });

        $bindings = $this->container->getBindings();

        $this->assertContains('kodhe:test:one', $bindings);
        $this->assertContains('kodhe:test:two', $bindings);
        $this->assertCount(2, $bindings);
    }

    /**
     * Test replace method
     */
    public function testReplace(): void
    {
        $this->container->register('test:replace', function() {
            return 'original';
        });

        $this->container->replace('test:replace', function() {
            return 'replaced';
        });

        $result = $this->container->make('test:replace');

        $this->assertEquals('replaced', $result);
    }

    /**
     * Test replace throws exception for non-existent binding
     */
    public function testReplaceThrowsExceptionForNonExistent(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot replace non-existent binding');

        $this->container->replace('nonexistent', function() { return null; });
    }

    /**
     * Test set method (no exception on duplicate)
     */
    public function testSet(): void
    {
        $this->container->set('test:set', function() { return 'first'; });
        $this->container->set('test:set', function() { return 'second'; });

        $result = $this->container->make('test:set');

        $this->assertEquals('second', $result);
    }

    /**
     * Test register throws exception on duplicate by default
     */
    public function testRegisterThrowsExceptionOnDuplicate(): void
    {
        $this->container->register('test:duplicate', function() { return 1; });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Attempt to reregister existing class');

        $this->container->register('test:duplicate', function() { return 2; });
    }

    /**
     * Test setThrowOnDuplicate disables exception
     */
    public function testSetThrowOnDuplicate(): void
    {
        $this->container->setThrowOnDuplicate(false);
        
        $this->container->register('test:nothrow', function() { return 1; });
        $this->container->register('test:nothrow', function() { return 2; });

        $result = $this->container->make('test:nothrow');

        $this->assertEquals(2, $result);
    }

    /**
     * Test getSingletonBindings
     */
    public function testGetSingletonBindings(): void
    {
        $this->container->registerSingleton('test:singleton1', function() { return 1; });
        $this->container->registerSingleton('test:singleton2', function() { return 2; });

        // Trigger singleton creation
        $this->container->make('test:singleton1');
        $this->container->make('test:singleton2');

        $singletons = $this->container->getSingletonBindings();

        $this->assertCount(2, $singletons);
    }

    /**
     * Test bind method returns ConcreteBinding
     */
    public function testBind(): void
    {
        $binding = $this->container->bind('test:bind', function() { return 1; });

        $this->assertInstanceOf(\Kodhe\Framework\Container\Binding\ConcreteBinding::class, $binding);
    }

    /**
     * Test make with additional arguments
     */
    public function testMakeWithArguments(): void
    {
        $this->container->register('test:args', function($container, $arg1, $arg2) {
            return ['arg1' => $arg1, 'arg2' => $arg2];
        });

        $result = $this->container->make('test:args', 'value1', 'value2');

        $this->assertEquals(['arg1' => 'value1', 'arg2' => 'value2'], $result);
    }
}
