# Kodhe Driver Library - PSR-4 Compatible Driver System for CodeIgniter 3

Refactored modular driver library system for CodeIgniter 3 dengan PSR-4 autoloading dan design patterns modern.

## 📁 Struktur

```
driver/
├── src/
│   ├── Contracts/
│   │   ├── DriverInterface.php          # Interface untuk setiap driver
│   │   └── DriverLibraryInterface.php   # Interface untuk parent library
│   ├── AbstractDriver.php               # Base abstract class untuk drivers
│   ├── DriverLibrary.php                # Base class untuk parent library
│   ├── NullDriver.php                   # Null object pattern fallback
│   ├── Resolvers/
│   │   └── DriverFileResolver.php       # Resolve & load driver files
│   ├── Registry/
│   │   └── DriverInstanceRegistry.php   # Registry pattern untuk caching instances
│   └── Traits/
│       ├── ConfigurableTrait.php        # Configuration handling
│       └── MagicCallTrait.php           # Magic method delegation
├── tests/
│   ├── DriverLibraryTest.php
│   ├── AbstractDriverTest.php
│   ├── Resolvers/
│   │   └── DriverFileResolverTest.php
│   └── Registry/
│       └── DriverInstanceRegistryTest.php
└── composer.json
```

## ✨ Fitur

- **PSR-4 Autoloading** - Namespace `Kodhe\Driver\`
- **PSR-12 Coding Standards** - Clean code style
- **Design Patterns**:
  - Registry Pattern (instance caching)
  - Factory Pattern (driver instantiation)
  - Proxy/Delegation Pattern (magic methods)
  - Template Method Pattern (abstract driver)
  - Null Object Pattern (fallback driver)
- **Dependency Injection** - Resolver dan registry di-inject via constructor
- **Performance Optimized**:
  - Path caching untuk file lookup
  - Instance caching per request
  - Lazy loading drivers
- **100% Backward Compatible** - API CI3 tetap berfungsi

## 🔧 Instalasi

```bash
composer require kodhe/driver:^2.0
```

Atau tambahkan ke `composer.json`:

```json
{
  "require": {
    "kodhe/driver": "^2.0"
  }
}
```

## 📖 Penggunaan

### Membuat Library Multi-Driver

```php
// application/libraries/Cache.php
class Cache extends \Kodhe\Driver\DriverLibrary
{
    protected $valid_drivers = ['file', 'apcu', 'redis', 'memcached'];
    
    public function __construct()
    {
        parent::__construct();
    }
}
```

### Membuat Driver

```php
// application/libraries/Cache/drivers/Cache_file.php
class Cache_file extends \Kodhe\Driver\AbstractDriver
{
    public function isSupported(): bool
    {
        return is_writable(APPPATH . 'cache/');
    }
    
    public function get($key)
    {
        // Implementation
    }
    
    public function save($key, $data, $ttl = 60)
    {
        // Implementation
    }
}
```

### Menggunakan di Controller

```php
class UserController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('cache');
    }
    
    public function index()
    {
        // Akses driver via magic getter (lazy loaded)
        $data = $this->cache->file->get('user_data');
        
        // Atau explicit load
        $this->cache->load_driver('file');
        $data = $this->cache->file->get('user_data');
        
        // Cek apakah driver supported
        if ($this->cache->file->isSupported()) {
            $this->cache->file->save('key', 'value', 300);
        }
    }
}
```

## 🎯 Design Patterns

### Registry Pattern
```php
$registry = new DriverInstanceRegistry();
$registry->set('Cache', 'file', $driverInstance);
$cached = $registry->get('Cache', 'file'); // Returns cached instance
```

### Null Object Pattern
```php
// Fallback aman ketika driver tidak ditemukan
$driver = $this->isValidDriver($name) 
    ? $this->load_driver($name) 
    : new NullDriver();
```

### Dependency Injection
```php
$registry = new DriverInstanceRegistry();
$resolver = new DriverFileResolver();
$library = new MyLibrary($registry, $resolver);
```

## 🧪 Testing

```bash
composer test
composer test:coverage
```

## 📊 Perbandingan

| Area | Sebelum (CI3 Native) | Sesudah (Kodhe\Driver) |
|------|---------------------|------------------------|
| Struktur | 2 classes monolithic | Modular (8+ components) |
| Namespace | None | PSR-4 `Kodhe\Driver\` |
| Testing | Sulit (hardcoded paths) | Mudah (DI + interfaces) |
| Caching | Manual per library | Built-in registry |
| Extension | Sulit | Mudah (implement interface) |
| PSR Compliance | None | PSR-4, PSR-12 |

## 🔒 Backward Compatibility

Semua fitur CI3 Driver system tetap berfungsi:
- ✅ `extends CI_Driver_Library` → `extends DriverLibrary`
- ✅ `extends CI_Driver` → `extends AbstractDriver`
- ✅ `$this->lib->driver_name->method()`
- ✅ `$this->parent->method()` dari dalam driver
- ✅ Property `valid_drivers` dan `lib_name`
- ✅ Magic methods `__get`, `__call`

Class aliases tersedia untuk backward compatibility penuh:

```php
// Di compat.php (optional)
class_alias(\Kodhe\Driver\DriverLibrary::class, 'CI_Driver_Library');
class_alias(\Kodhe\Driver\AbstractDriver::class, 'CI_Driver');
```

## 📝 License

MIT License - lihat [LICENSE](LICENSE) untuk detail.
