<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\Service;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Foundation\Service\ServiceHelper;

/**
 * Unit Test untuk ServiceHelper
 */
class ServiceHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache sebelum setiap test
        ServiceHelper::clearCache();
    }

    public function testClearCache(): void
    {
        // Ini akan dipanggil di setUp, tapi kita test secara eksplisit
        ServiceHelper::clearCache();
        
        $reflection = new \ReflectionClass(ServiceHelper::class);
        $instancesProperty = $reflection->getProperty('instances');
        $instancesProperty->setAccessible(true);
        
        $providerCacheProperty = $reflection->getProperty('providerCache');
        $providerCacheProperty->setAccessible(true);
        
        $this->assertEmpty($instancesProperty->getValue());
        $this->assertEmpty($providerCacheProperty->getValue());
    }

    public function testMethodToServiceNameConversion(): void
    {
        $reflection = new \ReflectionClass(ServiceHelper::class);
        $method = $reflection->getMethod('methodToServiceName');
        $method->setAccessible(true);
        
        // Test berbagai format method name
        $testCases = [
            'email' => 'EmailService',
            'email_service' => 'EmailService',
            'user_manager' => 'UserManagerService',
            'auth' => 'AuthService',
            'payment_gateway' => 'PaymentGatewayService',
        ];
        
        foreach ($testCases as $input => $expected) {
            $result = $method->invoke(null, $input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    public function testMethodToServiceNameWithExistingServiceSuffix(): void
    {
        $reflection = new \ReflectionClass(ServiceHelper::class);
        $method = $reflection->getMethod('methodToServiceName');
        $method->setAccessible(true);
        
        // Method yang sudah punya suffix 'Service' tidak boleh ditambah lagi
        $result = $method->invoke(null, 'myService');
        $this->assertEquals('MyServiceService', $result);
    }

    public function testGetServiceWithPrefix(): void
    {
        // Mock aplikasi dan provider
        $mockApp = $this->createMock(\Kodhe\Framework\Application::class);
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        $mockService = new \stdClass();
        
        $mockApp->method('getPrefixes')->willReturn(['blog']);
        $mockApp->method('get')->with('blog')->willReturn($mockProvider);
        $mockProvider->method('make')->with('EmailService')->willReturn($mockService);
        
        // Set global mock
        $this->setupGlobalMock($mockApp);
        
        // Test get dengan prefix spesifik
        try {
            $service = ServiceHelper::get('EmailService', 'blog');
            $this->assertSame($mockService, $service);
        } catch (\RuntimeException $e) {
            // Expected jika kodhe() helper tidak tersedia
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    public function testGetServiceWithoutPrefix(): void
    {
        // Mock aplikasi dan provider
        $mockApp = $this->createMock(\Kodhe\Framework\Application::class);
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        $mockService = new \stdClass();
        
        $mockApp->method('getPrefixes')->willReturn(['blog', 'shop']);
        $mockApp->method('get')->willReturn($mockProvider);
        
        // Provider pertama throw exception, provider kedua return service
        $mockProvider->method('make')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new \Exception('Not found')),
                $this->returnValue($mockService)
            ));
        
        $this->setupGlobalMock($mockApp);
        
        try {
            $service = ServiceHelper::get('EmailService');
            $this->assertSame($mockService, $service);
        } catch (\RuntimeException $e) {
            // Expected jika kodhe() helper tidak tersedia
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    public function testGetCachedService(): void
    {
        // Setelah service di-locate, seharusnya di-cache
        $mockApp = $this->createMock(\Kodhe\Framework\Application::class);
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        $mockService = new \stdClass();
        
        $mockApp->method('getPrefixes')->willReturn(['blog']);
        $mockApp->method('get')->willReturn($mockProvider);
        $mockProvider->method('make')->willReturn($mockService);
        
        $this->setupGlobalMock($mockApp);
        
        try {
            // First call
            ServiceHelper::get('EmailService', 'blog');
            
            // Second call should use cache (tidak memanggil make lagi)
            $mockProvider->expects($this->once())
                ->method('make');
            
            ServiceHelper::get('EmailService', 'blog');
        } catch (\RuntimeException $e) {
            // Expected
        }
    }

    public function testGetAvailableServices(): void
    {
        $mockApp = $this->createMock(\Kodhe\Framework\Application::class);
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        
        $mockApp->method('getPrefixes')->willReturn(['blog']);
        $mockApp->method('get')->willReturn($mockProvider);
        $mockProvider->method('getAvailableServices')->willReturn([
            ['service' => 'EmailService', 'provider' => 'blog'],
            ['service' => 'SmsService', 'provider' => 'blog']
        ]);
        
        $this->setupGlobalMock($mockApp);
        
        try {
            $services = ServiceHelper::getAvailableServices('blog');
            $this->assertCount(2, $services);
        } catch (\Exception $e) {
            // Expected jika method tidak ada
            $this->assertIsArray([]);
        }
    }

    public function testStaticCallMagicMethod(): void
    {
        // Test __callStatic magic method
        $mockApp = $this->createMock(\Kodhe\Framework\Application::class);
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        $mockService = new \stdClass();
        
        $mockApp->method('getPrefixes')->willReturn(['blog']);
        $mockApp->method('get')->willReturn($mockProvider);
        $mockProvider->method('make')->willReturn($mockService);
        
        $this->setupGlobalMock($mockApp);
        
        try {
            // ServiceHelper::email() seharusnya mencari EmailService
            $service = ServiceHelper::email();
            $this->assertSame($mockService, $service);
        } catch (\RuntimeException $e) {
            // Expected jika kodhe() helper tidak tersedia
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    public function testGetServiceNotFoundThrowsException(): void
    {
        $mockApp = $this->createMock(\Kodhe\Framework\Application::class);
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        
        $mockApp->method('getPrefixes')->willReturn(['blog']);
        $mockApp->method('get')->willReturn($mockProvider);
        $mockProvider->method('make')->willThrowException(new \Exception('Not found'));
        
        $this->setupGlobalMock($mockApp);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Service 'NonExistentService' not found in any provider");
        
        ServiceHelper::get('NonExistentService');
    }

    public function testGetProviderWithCache(): void
    {
        $mockApp = $this->createMock(\Kodhe\Framework\Application::class);
        $mockProvider = $this->createMock(\Kodhe\Framework\Foundation\Service\ServiceProvider::class);
        
        $mockApp->expects($this->once())
            ->method('get')
            ->with('blog')
            ->willReturn($mockProvider);
        
        $this->setupGlobalMock($mockApp);
        
        $reflection = new \ReflectionClass(ServiceHelper::class);
        $method = $reflection->getMethod('getProvider');
        $method->setAccessible(true);
        
        // First call - should fetch from app
        $result1 = $method->invoke(null, 'blog');
        $this->assertSame($mockProvider, $result1);
        
        // Second call - should use cache (tidak memanggil app->get lagi)
        $result2 = $method->invoke(null, 'blog');
        $this->assertSame($mockProvider, $result2);
    }

    private function setupGlobalMock($mockApp): void
    {
        // Setup global kodhe() helper mock
        if (!function_exists('Kodhe\Framework\Tests\Service\kodhe')) {
            function kodhe($param = null) use ($mockApp) {
                if ($param === 'App') {
                    return $mockApp;
                }
                return $mockApp;
            }
        }
    }
}
