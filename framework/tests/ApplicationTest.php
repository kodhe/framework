<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Container\Container;
use Kodhe\Framework\Foundation\Application;
use RuntimeException;

/**
 * Unit tests for Application class
 */
class ApplicationTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    /**
     * Test application creation
     */
    public function testApplicationCreation(): void
    {
        $app = new Application($this->container);
        
        $this->assertInstanceOf(Application::class, $app);
        $this->assertFalse($app->isBooted());
    }

    /**
     * Test static create method
     */
    public function testStaticCreate(): void
    {
        $app = Application::create($this->container);
        
        $this->assertInstanceOf(Application::class, $app);
    }

    /**
     * Test application bootstrap
     */
    public function testBootstrap(): void
    {
        $app = new Application($this->container);
        
        $result = $app->bootstrap();
        
        $this->assertInstanceOf(Application::class, $result);
        $this->assertTrue($app->isBooted());
    }

    /**
     * Test double bootstrap doesn't boot twice
     */
    public function testDoubleBootstrap(): void
    {
        $app = new Application($this->container);
        
        $app->bootstrap();
        $app->bootstrap();
        
        $this->assertTrue($app->isBooted());
    }

    /**
     * Test getContainer returns correct instance
     */
    public function testGetContainer(): void
    {
        $app = new Application($this->container);
        
        $this->assertSame($this->container, $app->getContainer());
    }

    /**
     * Test handle requires booted application
     */
    public function testHandleRequiresBooted(): void
    {
        $app = new Application($this->container);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Application must be booted before handling requests');
        
        // Create a mock request
        $request = new \stdClass();
        $app->handle($request);
    }

    /**
     * Test terminate resets boot state
     */
    public function testTerminate(): void
    {
        $app = new Application($this->container);
        $app->bootstrap();
        
        $this->assertTrue($app->isBooted());
        
        $app->terminate();
        
        $this->assertFalse($app->isBooted());
    }

    /**
     * Test getKernel returns kernel instance
     */
    public function testGetKernel(): void
    {
        $app = new Application($this->container);
        
        $kernel = $app->getKernel();
        
        $this->assertIsObject($kernel);
        $this->assertEquals('Kodhe\Http\Kernel\Kernel', get_class($kernel));
    }
}
