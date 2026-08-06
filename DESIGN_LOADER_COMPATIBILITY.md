# Design Loader & Compatibility Layer untuk Modernisasi CodeIgniter 3

## Analisis Masalah

### 1. Konteks Saat Ini
Framework ini bertujuan untuk:
- Memodernisasi CodeIgniter 3 dengan standar PSR (PSR-4, PSR-12)
- Tetap mempertahankan kompatibilitas dengan project legacy CI3
- Menghindari refactoring total yang dapat merusak project existing

### 2. Daftar Library CI3 yang Perlu Didukung
```
agent, cache, calendar, cart, database, driver, email, encrypt, encryption, 
ftp, image, javascript, migration, pagination, parser, profiler, session, 
table, test, trackback, typography, upload, validation, xmlrpc, xmlrpcs, zip
```

### 3. Masalah Utama yang Ditemukan

#### A. Loading File Helper (Non-Namespace)
**Masalah:**
- CI3 menggunakan `load->helper('name')` tanpa namespace
- File helper berada di folder `helpers/` dengan naming convention `name_helper.php`
- Tidak ada autoloading untuk helper functions

**Solusi yang Ada:**
- `/workspace/framework/src/Support/Helpers.php` - Berisi helper functions global
- `/workspace/framework/src/Support/Helpers/` - Directory berisi helper files
- Fungsi `resolve_path()` untuk handle case-insensitive paths

#### B. Loading Library (Class-based)
**Masalah:**
- CI3: `$this->load->library('email')` → loads `CI_Email`
- Modern: `new \Kodhe\Library\Email\Email()`
- Konflik antara old style (`CI_`) dan new style (namespace)

**Solusi yang Ada:**
- `load_class()` function di `Helpers.php` mencoba:
  1. Namespaced classes (`Kodhe\...`)
  2. Legacy CI classes (`CI_...`)
  3. Subclass prefix (`MY_...`)

#### C. Loading Drivers
**Masalah:**
- Driver system CI3 sangat spesifik dengan dynamic loading
- Structure: `Library_DriverName` atau `Library\Drivers\DriverName`

**Solusi yang Ada:**
- `/workspace/driver/src/Library.php` - Base driver library
- `/workspace/driver/src/Driver.php` - Base driver class
- Support untuk namespace `Kodhe\{Lib}\Drivers\{Driver}`

---

## Desain Arsitektur Loader

### 1. Unified Loading System

```php
namespace Kodhe\Framework\Foundation\Service;

class LoaderManager
{
    /**
     * Load helper file dengan support multi-strategy
     */
    public function helper($helpers): self
    {
        foreach ((array)$helpers as $helper) {
            $this->loadHelper($helper);
        }
        return $this;
    }
    
    /**
     * Load library dengan support multi-strategy
     */
    public function library($library, $params = null, $objectName = null): self
    {
        $this->loadLibrary($library, $params, $objectName);
        return $this;
    }
    
    /**
     * Load driver
     */
    public function driver($driver): self
    {
        $this->loadDriver($driver);
        return $this;
    }
}
```

### 2. Strategy Pattern untuk Helper Loading

```php
namespace Kodhe\Framework\Support\Loaders;

interface HelperLoaderInterface
{
    public function canLoad(string $helper): bool;
    public function load(string $helper): void;
}

/**
 * Strategy 1: Load dari namespace modern
 */
class NamespaceHelperLoader implements HelperLoaderInterface
{
    public function canLoad(string $helper): bool
    {
        $className = "Kodhe\\Framework\\Support\\Helpers\\" . ucfirst($helper) . 'Helper';
        return class_exists($className);
    }
    
    public function load(string $helper): void
    {
        $className = "Kodhe\\Framework\\Support\\Helpers\\" . ucfirst($helper) . 'Helper';
        $className::register(); // Static method to register helper functions
    }
}

/**
 * Strategy 2: Load dari file helper tradisional CI3
 */
class FileHelperLoader implements HelperLoaderInterface
{
    protected $searchPaths = [];
    
    public function __construct(array $paths)
    {
        $this->searchPaths = $paths;
    }
    
    public function canLoad(string $helper): bool
    {
        return $this->findHelperFile($helper) !== null;
    }
    
    public function load(string $helper): void
    {
        $file = $this->findHelperFile($helper);
        if ($file) {
            require_once $file;
        }
    }
    
    protected function findHelperFile(string $helper): ?string
    {
        $patterns = [
            '{path}/{helper}_helper.php',
            '{path}/helpers/{helper}_helper.php',
            '{path}/{Helper}Helper.php',
        ];
        
        foreach ($this->searchPaths as $path) {
            foreach ($patterns as $pattern) {
                $file = str_replace(
                    ['{path}', '{helper}', '{Helper}'],
                    [$path, $helper, ucfirst($helper)],
                    $pattern
                );
                
                if (file_exists($file)) {
                    return $file;
                }
            }
        }
        
        return null;
    }
}

/**
 * Strategy 3: Load dari package/addon
 */
class PackageHelperLoader implements HelperLoaderInterface
{
    public function canLoad(string $helper): bool
    {
        // Check in registered packages
        return false;
    }
    
    public function load(string $helper): void
    {
        // Load from package
    }
}

/**
 * Facade untuk Helper Loading
 */
class HelperLoaderFacade
{
    protected $loaders = [];
    protected $loaded = [];
    
    public function addLoader(HelperLoaderInterface $loader, int $priority = 0)
    {
        $this->loaders[$priority][] = $loader;
        ksort($this->loaders);
    }
    
    public function load(string $helper): void
    {
        if (isset($this->loaded[$helper])) {
            return; // Already loaded
        }
        
        foreach ($this->loaders as $loaderGroup) {
            foreach ($loaderGroup as $loader) {
                if ($loader->canLoad($helper)) {
                    $loader->load($helper);
                    $this->loaded[$helper] = true;
                    return;
                }
            }
        }
        
        throw new \Exception("Helper '{$helper}' not found");
    }
}
```

### 3. Enhanced Library Loader dengan Dependency Injection

```php
namespace Kodhe\Framework\Foundation\Service;

class LibraryLoader
{
    protected $container;
    protected $loaded = [];
    protected $aliases = [];
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Load library dengan strategy bertingkat
     */
    public function load($library, $params = null, $objectName = null)
    {
        if (is_array($library)) {
            foreach ($library as $lib) {
                $this->load($lib, $params);
            }
            return;
        }
        
        $libName = is_string($library) ? $library : get_class($library);
        
        if (isset($this->loaded[$libName])) {
            return $this->loaded[$libName];
        }
        
        // Strategy 1: Try modern namespace first
        $instance = $this->tryModernNamespace($library, $params);
        
        // Strategy 2: Try legacy CI class
        if (!$instance) {
            $instance = $this->tryLegacyClass($library, $params);
        }
        
        // Strategy 3: Try service container
        if (!$instance) {
            $instance = $this->tryFromContainer($library, $params);
        }
        
        if (!$instance) {
            throw new \Exception("Unable to load library: {$library}");
        }
        
        // Store instance
        $name = $objectName ?: $libName;
        $this->loaded[$libName] = $instance;
        
        // Make available to CI super object
        $ci =& kodhe();
        $ci->$name = $instance;
        
        return $instance;
    }
    
    protected function tryModernNamespace($library, $params)
    {
        $classNames = [
            "Kodhe\\Library\\" . ucfirst($library) . "\\" . ucfirst($library),
            "Kodhe\\Framework\\Library\\" . ucfirst($library) . "\\" . ucfirst($library),
            "Kodhe\\" . ucfirst($library) . "\\" . ucfirst($library),
        ];
        
        foreach ($classNames as $class) {
            if (class_exists($class)) {
                return $params ? new $class($params) : new $class();
            }
        }
        
        return null;
    }
    
    protected function tryLegacyClass($library, $params)
    {
        $classNames = [
            'CI_' . ucfirst($library),
            config_item('subclass_prefix') . ucfirst($library),
            ucfirst($library),
        ];
        
        foreach ($classNames as $class) {
            if (class_exists($class)) {
                return $params ? new $class($params) : new $class();
            }
        }
        
        // Try to load from file
        $file = $this->findLegacyFile($library);
        if ($file) {
            require_once $file;
            return $this->tryLegacyClass($library, $params);
        }
        
        return null;
    }
    
    protected function tryFromContainer($library, $params)
    {
        if ($this->container->has($library)) {
            return $this->container->make($library, $params);
        }
        
        return null;
    }
    
    protected function findLegacyFile($library)
    {
        $paths = [
            APPPATH . 'libraries/',
            BASEPATH . 'Core/Support/Legacy/',
            BASEPATH . 'libraries/',
        ];
        
        $variations = [
            $library . '.php',
            ucfirst($library) . '.php',
            strtolower($library) . '.php',
        ];
        
        foreach ($paths as $path) {
            foreach ($variations as $file) {
                $fullPath = $path . $file;
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
        }
        
        return null;
    }
}
```

### 4. Driver Loader Enhancement

```php
namespace Kodhe\Framework\Foundation\Service;

class DriverLoader
{
    protected $validDrivers = [];
    protected $loaded = [];
    
    /**
     * Load driver dengan fallback strategy
     */
    public function load($parentLib, $driver, $params = null)
    {
        $driverKey = $parentLib . '::' . $driver;
        
        if (isset($this->loaded[$driverKey])) {
            return $this->loaded[$driverKey];
        }
        
        // Strategy 1: Modern namespace
        $instance = $this->loadModernDriver($parentLib, $driver, $params);
        
        // Strategy 2: Legacy CI style
        if (!$instance) {
            $instance = $this->loadLegacyDriver($parentLib, $driver, $params);
        }
        
        if (!$instance) {
            throw new \Exception("Unable to load driver: {$driver} for {$parentLib}");
        }
        
        $this->loaded[$driverKey] = $instance;
        return $instance;
    }
    
    protected function loadModernDriver($parentLib, $driver, $params)
    {
        // Remove namespace from parent lib name
        $libName = basename(str_replace('\\', '/', $parentLib));
        
        $classNames = [
            "Kodhe\\{$libName}\\Drivers\\" . ucfirst($driver),
            "Kodhe\\Library\\{$libName}\\Drivers\\" . ucfirst($driver),
        ];
        
        foreach ($classNames as $class) {
            if (class_exists($class)) {
                $instance = $params ? new $class($params) : new $class();
                
                // Decorate with parent if it's a Driver object
                if ($instance instanceof \Kodhe\Driver\Driver) {
                    $instance->decorate($this->getParentInstance($parentLib));
                }
                
                return $instance;
            }
        }
        
        return null;
    }
    
    protected function loadLegacyDriver($parentLib, $driver, $params)
    {
        $prefix = config_item('subclass_prefix');
        $libName = str_replace(['CI_', $prefix], '', $parentLib);
        
        $classNames = [
            $prefix . $libName . '_' . ucfirst($driver),
            'CI_' . $libName . '_' . ucfirst($driver),
            $libName . '_' . ucfirst($driver),
        ];
        
        foreach ($classNames as $class) {
            if (class_exists($class)) {
                $instance = $params ? new $class($params) : new $class();
                
                if (method_exists($instance, 'decorate')) {
                    $instance->decorate($this->getParentInstance($parentLib));
                }
                
                return $instance;
            }
        }
        
        // Try to load from file
        $file = $this->findDriverFile($libName, $driver);
        if ($file) {
            require_once $file;
            return $this->loadLegacyDriver($parentLib, $driver, $params);
        }
        
        return null;
    }
    
    protected function findDriverFile($libName, $driver)
    {
        $paths = [
            APPPATH . 'libraries/' . $libName . '/drivers/',
            BASEPATH . 'libraries/' . $libName . '/drivers/',
        ];
        
        $variations = [
            ucfirst($driver) . '.php',
            strtolower($driver) . '.php',
        ];
        
        foreach ($paths as $path) {
            foreach ($variations as $file) {
                $fullPath = $path . $file;
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
        }
        
        return null;
    }
    
    protected function getParentInstance($parentLib)
    {
        $ci =& kodhe();
        
        if (is_object($ci->$parentLib)) {
            return $ci->$parentLib;
        }
        
        // Load parent if not exists
        $loader = new LibraryLoader(kodhe()->container);
        return $loader->load($parentLib);
    }
}
```

### 5. Integration dengan Service Manager

```php
namespace Kodhe\Framework\Foundation\Service;

class ServiceManager
{
    protected $helperLoader;
    protected $libraryLoader;
    protected $driverLoader;
    
    public function __construct(
        ContainerInterface $container,
        ServiceLocator $registry
    ) {
        $this->helperLoader = new HelperLoaderFacade();
        $this->libraryLoader = new LibraryLoader($container);
        $this->driverLoader = new DriverLoader();
        
        $this->registerDefaultLoaders();
    }
    
    protected function registerDefaultLoaders()
    {
        // Register helper loaders with priority
        $this->helperLoader->addLoader(new NamespaceHelperLoader(), 100);
        $this->helperLoader->addLoader(new FileHelperLoader([
            APPPATH,
            BASEPATH . 'Core/Support/',
            BASEPATH,
        ]), 50);
        $this->helperLoader->addLoader(new PackageHelperLoader(), 25);
    }
    
    /**
     * Backward compatible load method
     */
    public function load($type, $name, $params = null)
    {
        switch ($type) {
            case 'helper':
                return $this->helperLoader->load($name);
            
            case 'library':
                return $this->libraryLoader->load($name, $params);
            
            case 'driver':
                return $this->driverLoader->load($params['parent'], $name, $params);
            
            default:
                throw new \Exception("Unknown loader type: {$type}");
        }
    }
}
```

### 6. Configuration File untuk Mapping

Buat file konfigurasi untuk mapping legacy ke modern:

```php
// config/loader_mapping.php
return [
    'helpers' => [
        'url' => 'Kodhe\Framework\Support\Helpers\UrlHelper',
        'form' => 'Kodhe\Framework\Support\Helpers\FormHelper',
        'html' => 'Kodhe\Framework\Support\Helpers\HtmlHelper',
        // Fallback ke file jika tidak ada mapping
    ],
    
    'libraries' => [
        'email' => 'Kodhe\Library\Email\Email',
        'upload' => 'Kodhe\Library\Upload\Upload',
        'session' => 'Kodhe\Library\Session\Session',
        'database' => 'Kodhe\Database\Database',
        // ... dst untuk semua 23 libraries
    ],
    
    'drivers' => [
        'session' => [
            'files' => 'Kodhe\Library\Session\Drivers\Files',
            'database' => 'Kodhe\Library\Session\Drivers\Database',
            'redis' => 'Kodhe\Library\Session\Drivers\Redis',
            'memcached' => 'Kodhe\Library\Session\Drivers\Memcached',
        ],
        'cache' => [
            'files' => 'Kodhe\Library\Cache\Drivers\Files',
            'memcached' => 'Kodhe\Library\Cache\Drivers\Memcached',
            'redis' => 'Kodhe\Library\Cache\Drivers\Redis',
        ],
    ],
];
```

---

## Implementasi Prioritas

### Phase 1: Foundation (Critical)
1. ✅ Buat `HelperLoaderFacade` dengan strategy pattern
2. ✅ Buat `LibraryLoader` dengan multi-strategy loading
3. ✅ Buat `DriverLoader` dengan fallback mechanism
4. ✅ Integrate dengan `ServiceManager` yang sudah ada

### Phase 2: Library Migration
1. Migrate setiap library CI3 ke namespace `Kodhe\Library\{Name}`
2. Buat compatibility wrapper untuk setiap library
3. Test backward compatibility dengan code CI3 existing

### Phase 3: Helper Standardization
1. Convert semua helper functions ke class-based dengan static registration
2. Maintain backward compatibility dengan `load->helper()`
3. Add type hints dan strict typing

### Phase 4: Documentation & Testing
1. Dokumentasi lengkap untuk migration path
2. Unit tests untuk semua loader strategies
3. Integration tests untuk backward compatibility

---

## Contoh Penggunaan

### Old CI3 Style (Tetap Bekerja)
```php
$this->load->helper('url');
$this->load->library('email');
$this->load->driver('cache', ['default_driver' => 'files']);

site_url('page');
$this->email->send();
$this->cache->get('key');
```

### New Modern Style (Recommended)
```php
use Kodhe\Framework\Support\Facades\Helper;
use Kodhe\Library\Email\Email;
use Kodhe\Library\Cache\CacheManager;

Helper::load('url');
$email = new Email($config);
$cache = CacheManager::driver('files');

site_url('page');
$email->send();
$cache->get('key');
```

### Mixed Style (During Migration)
```php
// Load dengan cara lama
$this->load->library('email');

// Gunakan dengan cara baru
use Kodhe\Library\Email\EmailInterface;

$email = kodhe()->email; // Instance dari CI_Email atau Kodhe\Email
```

---

## Kesimpulan

Desain ini memberikan:
1. **Backward Compatibility**: Code CI3 lama tetap bekerja tanpa modifikasi
2. **Forward Compatibility**: Code baru bisa menggunakan namespace modern
3. **Gradual Migration**: Bisa migrate library per library tanpa breaking changes
4. **Extensibility**: Mudah menambah loader strategies baru
5. **Performance**: Lazy loading dan caching untuk loaded items
