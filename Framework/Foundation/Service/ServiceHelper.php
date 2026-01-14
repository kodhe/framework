<?php namespace Kodhe\Framework\Foundation\Service;

class ServiceHelper
{
    private static $instances = [];
    private static $providerCache = [];
    
    /**
     * Magic method untuk static call
     * Contoh: ServiceHelper::email() => blog:EmailService
     */
    public static function __callStatic($method, $args)
    {
        // Convert method name to service name
        $serviceName = self::methodToServiceName($method);
        
        // Try to find service with auto-detection
        return self::locate($serviceName);
    }
    
    /**
     * Get service by name dengan auto-detection
     * Bisa dipanggil dengan: ServiceHelper::get('email') tanpa menyebut prefix
     */
    public static function get(string $serviceName, string $prefix = null)
    {
        // Pastikan serviceName sudah dalam format yang benar
        if (!preg_match('/Service$/', $serviceName)) {
            $serviceName = self::methodToServiceName($serviceName);
        }
        
        return self::locate($serviceName, $prefix);
    }
    
    /**
     * Locate service in providers dengan auto-detection
     */
    private static function locate(string $serviceName, string $prefix = null)
    {
        // Buat cache key
        if ($prefix === null) {
            // Auto-detection mode - gunakan serviceName sebagai cache key
            $cacheKey = "auto:{$serviceName}";
        } else {
            $cacheKey = "{$prefix}:{$serviceName}";
        }
        
        // Cek cache dulu
        if (isset(self::$instances[$cacheKey])) {
            return self::$instances[$cacheKey];
        }
        
        $app = kodhe('App');
        
        // Jika prefix diberikan, langsung ambil dari provider tersebut
        if ($prefix !== null) {
            $provider = self::getProvider($prefix);
            
            try {
                $instance = $provider->make($serviceName);
                self::$instances[$cacheKey] = $instance;
                return $instance;
            } catch (\Exception $e) {
                throw new \RuntimeException("Service '{$serviceName}' not found in provider '{$prefix}'");
            }
        }
        
        // AUTO-DETECTION MODE
        // Cari di semua providers yang tersedia
        $availableProviders = [];
        
        foreach ($app->getPrefixes() as $providerPrefix) {
            $provider = self::getProvider($providerPrefix);
            $availableProviders[] = $providerPrefix;
            
            try {
                // Coba buat instance
                $instance = $provider->make($serviceName);
                
                // Cache instance untuk penggunaan selanjutnya
                self::$instances[$cacheKey] = $instance;
                
                // Juga cache dengan prefix yang spesifik untuk akses lebih cepat
                self::$instances["{$providerPrefix}:{$serviceName}"] = $instance;
                
                return $instance;
            } catch (\Exception $e) {
                // Service tidak ditemukan di provider ini, lanjut ke provider berikutnya
                continue;
            }
        }
        
        // Jika tidak ditemukan di semua provider
        throw new \RuntimeException(
            "Service '{$serviceName}' not found in any provider. " .
            "Available providers: " . implode(', ', $availableProviders)
        );
    }
    
    /**
     * Get provider with cache
     */
    private static function getProvider(string $prefix)
    {
        if (!isset(self::$providerCache[$prefix])) {
            $app = kodhe('App');
            self::$providerCache[$prefix] = $app->get($prefix);
        }
        
        return self::$providerCache[$prefix];
    }
    
    /**
     * Convert method name to service name
     */
    private static function methodToServiceName(string $method): string
    {
        // Contoh: email() -> EmailService, email_service() -> EmailService
        $serviceName = str_replace('_', '', ucwords($method, '_'));
        
        // Tambah 'Service' jika belum ada
        if (!preg_match('/Service$/', $serviceName)) {
            $serviceName .= 'Service';
        }
        
        return $serviceName;
    }
    
    /**
     * Helper untuk mendapatkan semua service yang tersedia
     */
    public static function getAvailableServices(string $prefix = null): array
    {
        $app = kodhe('App');
        $services = [];
        
        if ($prefix !== null) {
            $provider = self::getProvider($prefix);
            // Asumsikan provider memiliki method untuk mendapatkan service yang tersedia
            if (method_exists($provider, 'getAvailableServices')) {
                return $provider->getAvailableServices();
            }
            return [];
        }
        
        // Auto-detection: kumpulkan semua service dari semua providers
        foreach ($app->getPrefixes() as $providerPrefix) {
            $provider = self::getProvider($providerPrefix);
            
            if (method_exists($provider, 'getAvailableServices')) {
                $providerServices = $provider->getAvailableServices();
                
                foreach ($providerServices as $service) {
                    $services[] = [
                        'service' => $service,
                        'provider' => $providerPrefix
                    ];
                }
            }
        }
        
        return $services;
    }
    
    /**
     * Clear cache instances
     */
    public static function clearCache(): void
    {
        self::$instances = [];
        self::$providerCache = [];
    }
}