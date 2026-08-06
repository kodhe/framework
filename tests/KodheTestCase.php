<?php

namespace Kodhe\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base test case for all Kodhe tests
 * Provides common functionality and setup
 */
abstract class KodheTestCase extends TestCase
{
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Define required constants if not already defined
        $this->defineConstants();
    }

    /**
     * Define common constants needed for tests
     */
    protected function defineConstants(): void
    {
        if (!defined('APPPATH')) {
            define('APPPATH', dirname(__DIR__, 2) . '/');
        }
        
        if (!defined('BASEPATH')) {
            define('BASEPATH', dirname(__DIR__, 2) . '/Framework/');
        }
        
        if (!defined('ENVIRONMENT')) {
            define('ENVIRONMENT', 'testing');
        }
    }

    /**
     * Create a mock for HTTP request
     */
    protected function createMockRequest(array $get = [], array $post = [], array $server = [])
    {
        $defaultServer = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
        ];
        
        return new \Kodhe\Framework\Http\Request(
            $get,
            $post,
            [],
            [],
            array_merge($defaultServer, $server)
        );
    }

    /**
     * Create a mock for HTTP response
     */
    protected function createMockResponse(string $body = '', int $statusCode = 200)
    {
        return new \Kodhe\Framework\Http\Response($body, $statusCode);
    }

    /**
     * Invoke protected or private method on object
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Get protected or private property value
     */
    protected function getProperty(object $object, string $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        
        return $property->getValue($object);
    }

    /**
     * Set protected or private property value
     */
    protected function setProperty(object $object, string $propertyName, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
