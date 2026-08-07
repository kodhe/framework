<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\Service;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Foundation\Service\ServiceProvider;
use Kodhe\Framework\Container\Binding\BindingInterface;
use Kodhe\Framework\Support\Autoloader;

/**
 * Unit Test untuk ServiceProvider
 */
class ServiceProviderTest extends TestCase
{
    private BindingInterface $mockDependencies;
    private string $testPath;
    private array $testData;
    private ServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockDependencies = $this->createMock(BindingInterface::class);
        $this->testPath = '/tmp/test_provider_' . uniqid();
        mkdir($this->testPath, 0777, true);
        mkdir($this->testPath . '/config', 0777, true);
        
        $this->testData = [
            'namespace' => 'Test\\Addon',
            'name' => 'Test Addon',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'services' => [],
            'models' => [],
            'aliases' => []
        ];
        
        $this->provider = new ServiceProvider(
            $this->mockDependencies,
            $this->testPath,
            $this->testData
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testPath)) {
            $this->removeDirectory($this->testPath);
        }
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(ServiceProvider::class, $this->provider);
    }

    public function testGetPath(): void
    {
        $this->assertEquals($this->testPath, $this->provider->getPath());
    }

    public function testGetAuthor(): void
    {
        $this->assertEquals('Test Author', $this->provider->getAuthor());
    }

    public function testGetName(): void
    {
        $this->assertEquals('Test Addon', $this->provider->getName());
    }

    public function testGetVersion(): void
    {
        $this->assertEquals('1.0.0', $this->provider->getVersion());
    }

    public function testGetNamespace(): void
    {
        $this->assertEquals('Test\\Addon', $this->provider->getNamespace());
    }

    public function testGetServices(): void
    {
        $services = $this->provider->getServices();
        $this->assertIsArray($services);
        $this->assertCount(0, $services);
    }

    public function testGetSingletons(): void
    {
        $singletons = $this->provider->getSingletons();
        $this->assertIsArray($singletons);
        $this->assertCount(0, $singletons);
    }

    public function testGetModels(): void
    {
        $models = $this->provider->getModels();
        $this->assertIsArray($models);
        $this->assertCount(0, $models);
    }

    public function testGetModelDependencies(): void
    {
        $dependencies = $this->provider->getModelDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(0, $dependencies);
    }

    public function testSetAndGetConfigPath(): void
    {
        $newPath = '/new/config/path';
        $this->provider->setConfigPath($newPath);
        $this->assertEquals($newPath, $this->provider->getConfigPath());
    }

    public function testSetAndGetCallLocation(): void
    {
        $location = 'test/location';
        $this->provider->setCallLocation($location);
        $this->assertEquals($location, $this->provider->getCallLocation());
    }

    public function testSetPrefix(): void
    {
        $this->provider->setPrefix('test_prefix');
        $this->assertEquals('test_prefix', $this->provider->getPrefix());
    }

    public function testSetPrefixTwiceThrowsException(): void
    {
        $this->provider->setPrefix('first_prefix');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot override provider prefix.');
        
        $this->provider->setPrefix('second_prefix');
    }

    public function testSetAutoloader(): void
    {
        $mockAutoloader = $this->createMock(Autoloader::class);
        $mockAutoloader->expects($this->once())
            ->method('addPrefix')
            ->with('Test\\Addon', $this->testPath);
        
        $this->provider->setAutoloader($mockAutoloader);
    }

    public function testGetWithExistingKey(): void
    {
        $result = $this->provider->get('name');
        $this->assertEquals('Test Addon', $result);
    }

    public function testGetWithNonExistentKey(): void
    {
        $result = $this->provider->get('nonexistent', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testGetWithMapFunction(): void
    {
        $data = [
            'items' => [1, 2, 3, 4, 5]
        ];
        
        $provider = new ServiceProvider(
            $this->mockDependencies,
            $this->testPath,
            $data
        );
        
        $result = $provider->get('items', [], function($item) {
            return $item * 2;
        });
        
        $this->assertEquals([2, 4, 6, 8, 10], $result);
    }

    public function testSetClassAliases(): void
    {
        $data = [
            'namespace' => 'Test\\Addon',
            'aliases' => [
                'CI_Model' => 'Kodhe\\Framework\\Tests\\Service\\TestClass'
            ]
        ];
        
        // Buat class test untuk alias
        if (!class_exists('Kodhe\Framework\Tests\Service\TestClass')) {
            class_alias(\stdClass::class, 'Kodhe\Framework\Tests\Service\TestClass');
        }
        
        $provider = new ServiceProvider(
            $this->mockDependencies,
            $this->testPath,
            $data
        );
        
        $provider->setPrefix('test');
        $provider->setClassAliases();
        
        $this->assertTrue(class_exists('CI_Model'));
    }

    public function testRegisterWithPrefix(): void
    {
        $this->provider->setPrefix('myprefix');
        
        $mockObject = new \stdClass();
        
        $this->mockDependencies->expects($this->once())
            ->method('register')
            ->with('myprefix:service_name', $mockObject);
        
        $this->provider->register('service_name', $mockObject);
    }

    public function testRegisterSingletonWithPrefix(): void
    {
        $this->provider->setPrefix('myprefix');
        
        $mockObject = new \stdClass();
        
        $this->mockDependencies->expects($this->once())
            ->method('registerSingleton')
            ->with('myprefix:singleton_name', $mockObject);
        
        $this->provider->registerSingleton('singleton_name', $mockObject);
    }

    public function testMakeWithPrefix(): void
    {
        $this->provider->setPrefix('myprefix');
        
        $this->mockDependencies->expects($this->once())
            ->method('make')
            ->with('myprefix:service_name');
        
        $this->provider->make('service_name');
    }

    public function testRegisterServiceWithNameContainingColon(): void
    {
        $data = [
            'namespace' => 'Test\\Addon',
            'services' => [
                'invalid:name' => function() { return 'test'; }
            ]
        ];
        
        $provider = new ServiceProvider(
            $this->mockDependencies,
            $this->testPath,
            $data
        );
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Service names cannot contain ':'.");
        
        $provider->setPrefix('test');
    }

    public function testRegisterSingletonWithNameContainingColon(): void
    {
        $data = [
            'namespace' => 'Test\\Addon',
            'services' => [],
            'services.singletons' => [
                'invalid:name' => function() { return 'test'; }
            ]
        ];
        
        $provider = new ServiceProvider(
            $this->mockDependencies,
            $this->testPath,
            $data
        );
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Service names cannot contain ':'.");
        
        $provider->setPrefix('test');
    }

    public function testRegisterServicesWithStringClosure(): void
    {
        $className = 'MyService';
        
        $data = [
            'namespace' => 'Test\\Addon',
            'services' => [
                'myservice' => $className
            ]
        ];
        
        $provider = new ServiceProvider(
            $this->mockDependencies,
            $this->testPath,
            $data
        );
        
        // Mock register untuk menangkap closure yang di-register
        $capturedClosure = null;
        $this->mockDependencies->method('register')
            ->willReturnCallback(function($name, $closure) use (&$capturedClosure) {
                $capturedClosure = $closure;
                return $this->mockDependencies;
            });
        
        $provider->setPrefix('test');
        
        $this->assertIsCallable($capturedClosure);
    }

    public function testBindMethod(): void
    {
        $this->provider->setPrefix('myprefix');
        
        $mockObject = new \stdClass();
        
        // Bind should work without throwing exception
        $result = $this->provider->bind('service_name', $mockObject);
        
        $this->assertNotNull($result);
    }

    public function testGetWithClosureInModels(): void
    {
        $data = [
            'namespace' => 'Test\\Addon',
            'models' => [
                'User' => function($provider) {
                    return 'CustomUserModel';
                }
            ]
        ];
        
        $provider = new ServiceProvider(
            $this->mockDependencies,
            $this->testPath,
            $data
        );
        
        $models = $provider->getModels();
        
        $this->assertArrayHasKey('User', $models);
        $this->assertEquals('CustomUserModel', $models['User']);
    }

    public function testGetModelsWithRootNamespace(): void
    {
        $data = [
            'namespace' => 'Test\\Addon',
            'models' => [
                'ExternalModel' => '\\External\\Namespace\\Model'
            ]
        ];
        
        $provider = new ServiceProvider(
            $this->mockDependencies,
            $this->testPath,
            $data
        );
        
        $models = $provider->getModels();
        
        $this->assertArrayHasKey('ExternalModel', $models);
        $this->assertEquals('\\External\\Namespace\\Model', $models['ExternalModel']);
    }

    public function testEnsurePrefixWithApp(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('ensurePrefix');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->provider, 'App');
        
        $this->assertEquals('kodhe:App', $result);
    }

    public function testEnsurePrefixWithoutColon(): void
    {
        $this->provider->setPrefix('myapp');
        
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('ensurePrefix');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->provider, 'Service');
        
        $this->assertEquals('myapp:Service', $result);
    }

    public function testEnsurePrefixWithExistingColon(): void
    {
        $this->provider->setPrefix('myapp');
        
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('ensurePrefix');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->provider, 'other:Service');
        
        $this->assertEquals('other:Service', $result);
    }
}
