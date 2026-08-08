<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\Service;

use Kodhe\Framework\Container\Binding\BindingInterface;
use Kodhe\Framework\Foundation\Service\ServiceLocator;
use Kodhe\Framework\Foundation\Service\ServiceProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit Test untuk ServiceManager
 */
class ServiceManagerTest extends TestCase
{
    private BindingInterface $mockDependencies;
    private ServiceLocator $mockLocator;
    private \Kodhe\Framework\Foundation\Service\ServiceManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockDependencies = $this->createMock(BindingInterface::class);
        $this->mockLocator = new ServiceLocator($this->mockDependencies);
        $this->manager = new \Kodhe\Framework\Foundation\Service\ServiceManager(
            $this->mockDependencies,
            $this->mockLocator
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(\Kodhe\Framework\Foundation\Service\ServiceManager::class, $this->manager);
    }

    public function testGetDependencies(): void
    {
        $dependencies = $this->manager->getDependencies();
        $this->assertSame($this->mockDependencies, $dependencies);
    }

    public function testSetAndGetRequest(): void
    {
        $mockRequest = $this->createMock(\Kodhe\Http\Request::class);
        
        $this->assertNull($this->manager->getRequest());
        
        $this->manager->setRequest($mockRequest);
        
        $this->assertSame($mockRequest, $this->manager->getRequest());
    }

    public function testSetAndGetResponse(): void
    {
        $mockResponse = $this->createMock(\Kodhe\Http\Response::class);
        
        $this->assertNull($this->manager->getResponse());
        
        $this->manager->setResponse($mockResponse);
        
        $this->assertSame($mockResponse, $this->manager->getResponse());
    }

    public function testHasProvider(): void
    {
        $mockProvider = $this->createMock(ServiceProvider::class);
        $this->mockLocator->register('test', $mockProvider);
        
        $this->assertTrue($this->manager->has('test'));
        $this->assertFalse($this->manager->has('nonexistent'));
    }

    public function testGetProvider(): void
    {
        $mockProvider = $this->createMock(ServiceProvider::class);
        $this->mockLocator->register('myprovider', $mockProvider);
        
        $retrieved = $this->manager->get('myprovider');
        $this->assertSame($mockProvider, $retrieved);
    }

    public function testGetPrefixes(): void
    {
        $mockProvider1 = $this->createMock(ServiceProvider::class);
        $mockProvider2 = $this->createMock(ServiceProvider::class);
        
        $this->mockLocator->register('prefix1', $mockProvider1);
        $this->mockLocator->register('prefix2', $mockProvider2);
        
        $prefixes = $this->manager->getPrefixes();
        
        $this->assertCount(2, $prefixes);
        $this->assertContains('prefix1', $prefixes);
        $this->assertContains('prefix2', $prefixes);
    }

    public function testGetNamespaces(): void
    {
        $mockProvider = $this->createMock(ServiceProvider::class);
        $mockProvider->method('getNamespace')->willReturn('Test\\Namespace');
        
        $this->mockLocator->register('test', $mockProvider);
        
        $namespaces = $this->manager->getNamespaces();
        
        $this->assertArrayHasKey('test:test', $namespaces);
        $this->assertEquals('Test\\Namespace', $namespaces['test:test']);
    }

    public function testGetProviders(): void
    {
        $mockProvider1 = $this->createMock(ServiceProvider::class);
        $mockProvider2 = $this->createMock(ServiceProvider::class);
        
        $this->mockLocator->register('provider1', $mockProvider1);
        $this->mockLocator->register('provider2', $mockProvider2);
        
        $providers = $this->manager->getProviders();
        
        $this->assertCount(2, $providers);
        $this->assertArrayHasKey('provider1', $providers);
        $this->assertArrayHasKey('provider2', $providers);
    }

    public function testGetModels(): void
    {
        $mockProvider = $this->createMock(ServiceProvider::class);
        $mockProvider->method('getModels')->willReturn([
            'User' => 'App\\Models\\User',
            'Post' => 'App\\Models\\Post'
        ]);
        
        $this->mockLocator->register('blog', $mockProvider);
        
        $models = $this->manager->getModels();
        
        $this->assertArrayHasKey('blog:User', $models);
        $this->assertArrayHasKey('blog:Post', $models);
        $this->assertEquals('App\\Models\\User', $models['blog:User']);
        $this->assertEquals('App\\Models\\Post', $models['blog:Post']);
    }

    public function testSetClassAliases(): void
    {
        $mockProvider = $this->createMock(ServiceProvider::class);
        $mockProvider->expects($this->once())
            ->method('setClassAliases');
        
        $this->mockLocator->register('test', $mockProvider);
        
        $this->manager->setClassAliases();
    }

    public function testAddProviderWithValidSetupFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/service_manager_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        $setupContent = "<?php\nreturn [\n    'namespace' => 'Test\\\\Addon',\n    'name' => 'Test Addon',\n    'version' => '1.0.0',\n    'author' => 'Test Author',\n];";
        
        file_put_contents($tempDir . '/addon.setup.php', $setupContent);
        
        try {
            $provider = $this->manager->addProvider($tempDir);
            
            $this->assertInstanceOf(ServiceProvider::class, $provider);
            $this->assertTrue($this->manager->has(basename($tempDir)));
            $this->assertEquals('Test\\Addon', $provider->getNamespace());
            $this->assertEquals('Test Addon', $provider->getName());
        } finally {
            unlink($tempDir . '/addon.setup.php');
            rmdir($tempDir);
        }
    }

    public function testAddProviderWithNonExistentSetupFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/service_manager_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        try {
            $result = $this->manager->addProvider($tempDir);
            $this->assertNull($result);
        } finally {
            rmdir($tempDir);
        }
    }

    public function testAddProviderWithCustomPrefix(): void
    {
        $tempDir = sys_get_temp_dir() . '/service_manager_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        $setupContent = "<?php\nreturn [\n    'namespace' => 'Test\\\\Addon',\n    'name' => 'Test Addon',\n    'version' => '1.0.0',\n    'author' => 'Test Author',\n];";
        
        file_put_contents($tempDir . '/addon.setup.php', $setupContent);
        
        try {
            $provider = $this->manager->addProvider($tempDir, 'addon.setup.php', 'custom_prefix');
            
            $this->assertInstanceOf(ServiceProvider::class, $provider);
            $this->assertTrue($this->manager->has('custom_prefix'));
            $this->assertEquals('custom_prefix', $provider->getPrefix());
        } finally {
            unlink($tempDir . '/addon.setup.php');
            rmdir($tempDir);
        }
    }

    public function testAddProviderWithInvalidSetupFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/service_manager_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        file_put_contents($tempDir . '/addon.setup.php', "<?php\nreturn 'invalid';");
        
        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("Setup file must return an array:");
            
            $this->manager->addProvider($tempDir);
        } finally {
            unlink($tempDir . '/addon.setup.php');
            rmdir($tempDir);
        }
    }

    public function testSetupAddonsWithNonExistentPath(): void
    {
        $nonExistentPath = '/path/that/does/not/exist';
        
        // Should not throw exception, just log error
        $this->manager->setupAddons($nonExistentPath);
        
        // Verify no providers were added
        $this->assertCount(0, $this->manager->getPrefixes());
    }

    public function testSetupAddonsWithValidDirectory(): void
    {
        $tempDir = sys_get_temp_dir() . '/service_manager_test_' . uniqid();
        $addonDir = $tempDir . '/test_addon';
        mkdir($addonDir, 0777, true);
        
        $setupContent = "<?php\nreturn [\n    'namespace' => 'Test\\\\Addon',\n    'name' => 'Test Addon',\n    'version' => '1.0.0',\n    'author' => 'Test Author',\n];";
        
        file_put_contents($addonDir . '/addon.setup.php', $setupContent);
        
        try {
            $this->manager->setupAddons($tempDir);
            
            $this->assertCount(1, $this->manager->getPrefixes());
            $this->assertTrue($this->manager->has('test_addon'));
        } finally {
            unlink($addonDir . '/addon.setup.php');
            rmdir($addonDir);
            rmdir($tempDir);
        }
    }

    public function testForwardMethod(): void
    {
        $mockProvider1 = $this->createMock(ServiceProvider::class);
        $mockProvider1->method('getNamespace')->willReturn('Namespace1');
        
        $mockProvider2 = $this->createMock(ServiceProvider::class);
        $mockProvider2->method('getNamespace')->willReturn('Namespace2');
        
        $this->mockLocator->register('provider1', $mockProvider1);
        $this->mockLocator->register('provider2', $mockProvider2);
        
        $result = $this->manager->forward('getNamespace');
        
        $this->assertArrayHasKey('provider1:getNamespace', $result);
        $this->assertArrayHasKey('provider2:getNamespace', $result);
        $this->assertEquals('Namespace1', $result['provider1:getNamespace']);
        $this->assertEquals('Namespace2', $result['provider2:getNamespace']);
    }

    public function testForwardMethodWithMissingMethod(): void
    {
        $mockProvider = $this->createMock(ServiceProvider::class);
        // Provider tidak memiliki method 'nonexistentMethod'
        
        $this->mockLocator->register('test', $mockProvider);
        
        $result = $this->manager->forward('nonexistentMethod');
        
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }
}
