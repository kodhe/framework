# Kodhe Framework - Service Providers

Dokumentasi untuk komponen **Service Provider** dalam Kodhe Framework. Komponen ini bertanggung jawab untuk manajemen dependency injection, registrasi service, dan integrasi addon/modul.

## 📋 Daftar Isi

- [Gambaran Umum](#gambaran-umum)
- [Komponen Utama](#komponen-utama)
- [Instalasi & Konfigurasi](#instalasi--konfigurasi)
- [Penggunaan](#penggunaan)
- [Membuat Provider Custom](#membuat-provider-custom)
- [Best Practices](#best-practices)
- [Testing](#testing)

## 🎯 Gambaran Umum

Service Provider adalah sistem inti dalam Kodhe Framework yang memungkinkan:

- **Dependency Injection** - Registrasi dan resolusi dependencies secara otomatis
- **Service Management** - Pengelolaan service dan singleton instances
- **Addon Integration** - Integrasi modul/addon pihak ketiga
- **Namespace Autoloading** - Registrasi namespace otomatis
- **Class Aliasing** - Pembuatan class aliases untuk kompatibilitas
- **Model Registration** - Pendaftaran models dengan dependencies

## 📦 Komponen Utama

### 1. ServiceLocator

Registry untuk menyimpan dan mengelola semua service providers.

```php
use Kodhe\Framework\Foundation\Service\ServiceLocator;
use Kodhe\Framework\Container\Binding\BindingInterface;

$locator = new ServiceLocator($dependencies);
$locator->register('blog', $blogProvider);
$locator->register('shop', $shopProvider);
```

**Methods:**
- `register(string $prefix, ServiceProvider $provider)` - Daftarkan provider baru
- `has(string $prefix): bool` - Cek apakah provider ada
- `get(string $prefix): ServiceProvider` - Ambil provider
- `all(): array` - Dapatkan semua providers

### 2. ServiceManager

Manajer utama untuk setup dan konfigurasi providers.

```php
use Kodhe\Framework\Foundation\Service\ServiceManager;

$manager = new ServiceManager($dependencies, $locator);

// Setup addons dari directory
$manager->setupAddons('/path/to/addons');

// Add provider manual
$provider = $manager->addProvider('/path/to/addon');
```

**Methods:**
- `setupAddons(string $path)` - Setup semua addons dari directory
- `addProvider(string $path, string $file, string $prefix)` - Tambah provider
- `has(string $prefix): bool` - Cek provider
- `get(string $prefix): ServiceProvider` - Get provider
- `getPrefixes(): array` - List semua prefixes
- `getNamespaces(): array` - List semua namespaces
- `getModels(): array` - List semua models
- `setClassAliases()` - Setup class aliases

### 3. ServiceProvider

Base class untuk semua service providers. Extend class ini untuk membuat provider custom.

```php
namespace MyAddon;

use Kodhe\Framework\Foundation\Service\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{
    // Provider akan otomatis load dari addon.setup.php
}
```

**Properties dari Setup File:**
- `namespace` - Namespace PHP untuk addon
- `name` - Nama addon
- `version` - Versi addon
- `author` - Author addon
- `services` - Array services untuk di-register
- `services.singletons` - Array singleton services
- `models` - Array models
- `models.dependencies` - Dependencies untuk models
- `aliases` - Class aliases

### 4. ServiceHelper

Helper static untuk akses cepat ke services.

```php
use Kodhe\Framework\Foundation\Service\ServiceHelper;

// Akses service dengan auto-detection
$emailService = ServiceHelper::email();

// Akses dengan prefix spesifik
$emailService = ServiceHelper::get('EmailService', 'blog');

// List available services
$services = ServiceHelper::getAvailableServices();
```

**Methods:**
- `get(string $serviceName, string $prefix = null)` - Get service
- `clearCache()` - Clear cache instances
- `getAvailableServices(string $prefix = null): array` - List services

## ⚙️ Instalasi & Konfigurasi

### Membuat Setup File

Buat file `addon.setup.php` di root addon Anda:

```php
<?php

return [
    'namespace' => 'MyAddon',
    'name' => 'My Awesome Addon',
    'version' => '1.0.0',
    'author' => 'Your Name',
    
    // Services (instantiated setiap kali diminta)
    'services' => [
        'EmailService' => function($provider) {
            return new \MyAddon\Services\EmailService();
        },
        'SmsService' => 'SmsService', // String akan di-resolve otomatis
    ],
    
    // Singletons (hanya instantiated sekali)
    'services.singletons' => [
        'Database' => function($provider) {
            return new \MyAddon\Database();
        },
    ],
    
    // Models
    'models' => [
        'User' => 'Models\User',
        'Post' => 'Models\Post',
    ],
    
    // Model dependencies
    'models.dependencies' => [
        'User' => ['kodhe:db', 'kodhe:cache'],
    ],
    
    // Class aliases
    'aliases' => [
        'CI_Model' => 'Kodhe\\Framework\\Core\\Model',
        'MyClass' => 'MyAddon\\Classes\\MyClass',
    ],
];
```

### Struktur Directory Addon

```
my_addon/
├── addon.setup.php          # Setup file (required)
├── src/                     # Source code
│   ├── Services/
│   │   ├── EmailService.php
│   │   └── SmsService.php
│   ├── Models/
│   │   ├── User.php
│   │   └── Post.php
│   └── Database.php
└── config/                  # Configuration files
    └── settings.php
```

## 💻 Penggunaan

### Register Provider Manual

```php
$app = kodhe('App');
$manager = $app->getServiceManager();

// Add provider dengan path
$provider = $manager->addProvider('/path/to/addon');

// Add provider dengan custom prefix
$provider = $manager->addProvider('/path/to/addon', 'addon.setup.php', 'custom_prefix');

// Setup semua addons dari directory
$manager->setupAddons('/path/to/addons_directory');
```

### Akses Services

```php
// Via ServiceHelper (recommended)
$emailService = ServiceHelper::email();
$smsService = ServiceHelper::sms();

// Via App container
$app = kodhe('App');
$emailService = $app->make('blog:EmailService');

// Via Provider langsung
$provider = $app->get('blog');
$emailService = $provider->make('EmailService');
```

### Akses Models

```php
$app = kodhe('App');
$models = $app->getModels();

// Output: ['blog:User' => 'Blog\Models\User', ...]
foreach ($models as $alias => $fqcn) {
    echo "{$alias} => {$fqcn}\n";
}
```

### Setup Class Aliases

```php
$app = kodhe('App');
$app->setClassAliases();

// Sekarang bisa gunakan alias
$model = new CI_Model(); // Alias untuk Kodhe\Framework\Core\Model
```

## 🔨 Membuat Provider Custom

### Step 1: Buat Setup File

```php
<?php
// my_module/addon.setup.php

return [
    'namespace' => 'MyModule',
    'name' => 'My Custom Module',
    'version' => '1.0.0',
    'author' => 'Developer Name',
    
    'services' => [
        'Logger' => function($provider) {
            return new \MyModule\Services\Logger();
        },
        'Cache' => 'CacheService',
    ],
    
    'services.singletons' => [
        'Config' => function($provider) {
            return \MyModule\Config::getInstance();
        },
    ],
    
    'models' => [
        'Article' => 'Models\Article',
    ],
];
```

### Step 2: Buat Service Classes

```php
<?php
// my_module/src/Services/Logger.php

namespace MyModule\Services;

class Logger
{
    public function log(string $message): void
    {
        error_log("[MyModule] {$message}");
    }
}
```

### Step 3: Register Provider

```php
$app = kodhe('App');
$manager = $app->getServiceManager();

$manager->addProvider(__DIR__ . '/my_module');
```

### Step 4: Gunakan Service

```php
// Cara 1: Via ServiceHelper
$logger = ServiceHelper::logger();
$logger->log('Test message');

// Cara 2: Via Container
$logger = kodhe('App')->make('mymodule:Logger');
$logger->log('Test message');
```

## 📝 Best Practices

### 1. Gunakan Singleton untuk Service Heavy

```php
'services.singletons' => [
    'Database' => function($provider) {
        return new Database(); // Hanya dibuat sekali
    },
],
```

### 2. Lazy Loading Services

```php
'services' => [
    'EmailService' => function($provider) {
        // Hanya di-load saat dibutuhkan
        return new EmailService(
            $provider->make('Config'),
            $provider->make('Logger')
        );
    },
],
```

### 3. Hindari Circular Dependencies

```php
// ❌ BAD: Circular dependency
'services' => [
    'ServiceA' => fn($p) => new ServiceA($p->make('ServiceB')),
    'ServiceB' => fn($p) => new ServiceB($p->make('ServiceA')),
],

// ✅ GOOD: Break cycle dengan interface atau lazy loading
'services' => [
    'ServiceA' => fn($p) => new ServiceA(fn() => $p->make('ServiceB')),
    'ServiceB' => fn($p) => new ServiceB(),
],
```

### 4. Gunakan Prefix yang Unik

```php
// ❌ BAD: Prefix generik
'myapp' => bisa konflik

// ✅ GOOD: Prefix spesifik
'blog_module', 'shop-addon', 'payment-gateway'
```

### 5. Cache ServiceHelper Calls

```php
// ❌ BAD: Setiap call melakukan lookup
$user = ServiceHelper::user();
$user = ServiceHelper::user();
$user = ServiceHelper::user();

// ✅ GOOD: Cache result
static $userService;
if (!$userService) {
    $userService = ServiceHelper::user();
}
```

## 🧪 Testing

### Test ServiceLocator

```bash
cd /workspace/framework
vendor/bin/phpunit tests/Service/ServiceLocatorTest.php
```

### Test ServiceManager

```bash
vendor/bin/phpunit tests/Service/ServiceManagerTest.php
```

### Test ServiceProvider

```bash
vendor/bin/phpunit tests/Service/ServiceProviderTest.php
```

### Test ServiceHelper

```bash
vendor/bin/phpunit tests/Service/ServiceHelperTest.php
```

### Test Semua Service Tests

```bash
vendor/bin/phpunit tests/Service/
```

### Contoh Unit Test

```php
use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Foundation\Service\ServiceLocator;
use Kodhe\Framework\Foundation\Service\ServiceProvider;

class ServiceLocatorTest extends TestCase
{
    public function testRegisterAndGetProvider()
    {
        $dependencies = $this->createMock(BindingInterface::class);
        $locator = new ServiceLocator($dependencies);
        
        $provider = $this->createMock(ServiceProvider::class);
        $locator->register('test', $provider);
        
        $this->assertTrue($locator->has('test'));
        $this->assertSame($provider, $locator->get('test'));
    }
}
```

## 📊 Class Reference

| Class | Namespace | Description |
|-------|-----------|-------------|
| `ServiceLocator` | `Kodhe\Framework\Foundation\Service` | Registry untuk providers |
| `ServiceManager` | `Kodhe\Framework\Foundation\Service` | Manajer setup & konfigurasi |
| `ServiceProvider` | `Kodhe\Framework\Foundation\Service` | Base class untuk providers |
| `ServiceHelper` | `Kodhe\Framework\Foundation\Service` | Helper static access |

## 🔗 Related Components

- [Dependency Injection Container](../Container/)
- [Autoloader](../Support/Autoloader.md)
- [Application Core](../Application.md)
- [Console Commands](../Console/)

## 📄 License

Kodhe Framework is open-sourced software licensed under the MIT license.

## 🤝 Contributing

Silakan baca [CONTRIBUTING.md](../../../CONTRIBUTING.md) untuk detail cara contribute.

## 📞 Support

Untuk pertanyaan dan issue, silakan buat issue di repository GitHub atau hubungi tim support.

---

**Version:** 1.0.0  
**Last Updated:** 2024  
**Maintainer:** Kodhe Framework Team
