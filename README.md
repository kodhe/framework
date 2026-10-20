# Kodhe Framework

<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue)
![License](https://img.shields.io/badge/License-MIT-green)
![Version](https://img.shields.io/badge/Version-1.1.1-orange)

**Core Framework Components - Container, Config, Foundation, Exceptions & Support**

</div>

---

## 📖 Description

Kodhe Framework is the core package that provides the main foundation for Kodhe-based applications. It includes a Dependency Injection Container, a configuration system, application bootstrap, exception handling, controller base classes, and various helper functions.

It is designed to work seamlessly with other Kodhe packages such as `kodhe/http`, `kodhe/view`, `kodhe/database`, and more.

## ✨ Key Features

### 🔧 **Dependency Injection Container**
- Service registration and resolution
- Singleton pattern support
- Automatic dependency resolution
- Closure-based bindings
- Namespace-aware service naming

### ⚙️ **Configuration System**
- Multi-format config loading (PHP arrays, legacy CI3 format)
- Module-aware configuration
- Config caching support
- ArrayAccess interface for easy access
- Runtime config manipulation

### 🚀 **Application Foundation**
- Modern application entry point
- Kernel bootstrapping
- Service provider registration
- Auto-loading integration
- Request/Response lifecycle management

### 🎯 **Service Management**
- Service Locator pattern
- Service Manager for core components
- Service Provider abstraction
- Lazy loading support
- Module service registration

### 🎮 **Controller System**
- Base Controller class
- Error handling controllers (404, exceptions)
- Integration with HTTP layer
- View rendering helpers

### 🛠️ **Exception Handling**
- Hierarchical exception classes
- Auth exceptions
- Database exceptions
- HTTP exceptions
- Middleware exceptions
- Routing exceptions
- Service exceptions

### 🔌 **Support Utilities**
- Autoloader (PSR-4 compatible)
- Helper functions (global helpers)
- Module system
- Facade pattern support
- Legacy CodeIgniter 3 compatibility layer
- File/Namespace helper loaders

## 📦 Installation

### Requirements

- PHP >= 8.1
- Composer
- kodhe/http package (required)

### Install via Composer

```bash
composer require kodhe/framework
```

The package automatically installs these dependencies:
- `paragonie/random_compat` ^9.99
- `kodhe/http` *

### Optional Packages (Suggested)

```bash
# Session handling
composer require kodhe/session

# Caching
composer require kodhe/cache

# Database & ORM
composer require kodhe/database

# Templating / Views (REQUIRED for app('view'))
composer require kodhe/view

# Form validation
composer require kodhe/validation

# User agent library
composer require kodhe/agent
```

## 🏗️ Package Structure

```
framework/
├── Resources/
│   ├── fonts/              # Font files for typography
│   └── language/           # Translation files
├── src/
│   ├── Config/
│   │   ├── Loaders/
│   │   │   ├── ArrayLoader.php     # Loads config from an array
│   │   │   ├── PhpLoader.php       # Loads config from PHP files
│   │   │   ├── FileLoader.php      # Loads config from files
│   │   │   └── LegacyLoader.php    # CI3 compatibility loader
│   │   ├── Config.php              # Main config class
│   │   ├── ConfigInterface.php     # Config contract
│   │   └── Setup.php               # Setup configuration
│   │
│   ├── Container/
│   │   ├── Binding/
│   │   │   ├── BindingInterface.php
│   │   │   └── ConcreteBinding.php
│   │   └── Container.php           # DI Container implementation
│   │
│   ├── Controllers/
│   │   ├── Error/
│   │   │   ├── FileNotFound.php    # 404 handler
│   │   │   └── CPException.php     # Control panel exception
│   │   └── BaseController.php      # Base controller class
│   │
│   ├── Exceptions/
│   │   ├── Auth/                   # Authentication exceptions
│   │   ├── Database/               # Database exceptions
│   │   ├── Http/                   # HTTP exceptions
│   │   ├── Middleware/             # Middleware exceptions
│   │   ├── Routing/                # Routing exceptions
│   │   └── Service/                # Service exceptions
│   │
│   ├── Foundation/
│   │   ├── Application.php         # Main application class
│   │   └── Service/
│   │       ├── ServiceManager.php  # Core service manager
│   │       ├── ServiceLocator.php  # Service locator pattern
│   │       ├── ServiceProvider.php # Service provider abstract
│   │       └── ServiceHelper.php   # Service helper utilities
│   │
│   └── Support/
│       ├── Facades/                # Facade classes
│       ├── Helpers/                # Helper functions
│       ├── Loaders/
│       │   ├── FileHelperLoader.php
│       │   ├── NamespaceHelperLoader.php
│       │   ├── HelperLoaderFacade.php
│       │   └── HelperLoaderInterface.php
│       ├── Legacy/                 # CodeIgniter 3 compatibility
│       │   ├── compat/
│       │   │   ├── hash.php
│       │   │   ├── password.php
│       │   │   └── standard.php
│       │   ├── Security.php
│       │   ├── Exceptions.php
│       │   ├── Log.php
│       │   ├── LegacyOutput.php
│       │   ├── Benchmark.php
│       │   ├── URI.php
│       │   ├── Input.php
│       │   └── common.php
│       ├── Autoloader.php          # PSR-4 Autoloader
│       ├── Modules.php             # Module system
│       └── Helpers.php             # Global helper functions
│
├── composer.json
├── LICENSE
└── README.md
```

## 🚀 Usage

### 1. **Application Bootstrap**

```php
<?php

require_once 'vendor/autoload.php';

use Kodhe\Framework\Foundation\Application;
use Kodhe\Framework\Container\Container;

// Create container (optional - will create default if not provided)
$container = new Container();

// Create application instance
$app = Application::create($container);

// Bootstrap the application
$app->bootstrap();

// Run the application
$app->run();
```

### 2. **Dependency Injection Container**

```php
<?php

use Kodhe\Framework\Container\Container;

$container = new Container();

// Register a service
$container->bind('database', function($app) {
    return new Database\Connection([
        'host' => 'localhost',
        'user' => 'root',
        'password' => 'secret'
    ]);
});

// Register a singleton
$container->singleton('cache', function($app) {
    return new Cache\Manager();
});

// Resolve a service
$db = $container->resolve('database');
$cache = $container->resolve('cache');

// Check if service exists
if ($container->has('database')) {
    echo "Database service is registered";
}

// Get all bindings
$bindings = $container->getBindings();
$singletons = $container->getSingletonBindings();
```

### 3. **Configuration Loading**

```php
<?php

use Kodhe\Framework\Config\Config;

$config = new Config();

// Load a config file
$config->load('database');

// Access config values
$dbHost = $config->item('database.default.hostname');
$dbUser = $config->item('database.default.username');

// Set config at runtime
$config->set_item('app_name', 'My Application');
$config->set_item('debug_mode', true);

// Check if config exists
if ($config->has('app_name')) {
    echo $config->item('app_name');
}

// Array access
echo $config['app_name'];
$config['debug_mode'] = false;
```

### 4. **Service Providers**

```php
<?php

use Kodhe\Framework\Foundation\Service\ServiceProvider;
use Kodhe\Framework\Container\Container;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->container->singleton('db', function($app) {
            $config = $app->resolve('config');
            return new Database\Connection($config->item('database'));
        });
    }

    public function boot()
    {
        // Called after all services are registered
        $db = $this->container->resolve('db');
        $db->connect();
    }
}

// Register in Application
$app->registerProvider(DatabaseServiceProvider::class);
```

### 5. **Base Controller**

```php
<?php

namespace App\Controllers;

use Kodhe\Framework\Http\Controllers\BaseController;

class UserController extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        $users = $this->resolve('userModel')->getAll();
        
        return $this->response->view('users.index', [
            'users' => $users
        ]);
    }

    public function show($id)
    {
        $user = $this->resolve('userModel')->find($id);
        
        if (!$user) {
            return $this->response->notFound();
        }
        
        return $this->response->view('users.show', [
            'user' => $user
        ]);
    }
}
```

### 6. **Exception Handling**

```php
<?php

use Kodhe\Framework\Exceptions\Http\HttpException;
use Kodhe\Framework\Exceptions\Auth\AuthenticationException;
use Kodhe\Framework\Exceptions\Database\QueryException;

try {
    // Some operation
    throw new HttpException(404, 'Resource not found');
    
} catch (AuthenticationException $e) {
    // Handle auth exception
    return redirect()->to('login');
    
} catch (HttpException $e) {
    // Handle HTTP exception
    return response()->json([
        'error' => $e->getMessage()
    ], $e->getStatusCode());
    
} catch (QueryException $e) {
    // Handle database exception
    log_error($e->getMessage());
    return response()->json(['error' => 'Database error'], 500);
}
```

### 7. **Helper Functions**

```php
<?php

// Load a class instance
$db = load_class('Database', 'libraries');

// Get application instance
$app = kodhe();

// Access services via app() helper
$config = app('config');
$request = app('request');
$response = app('response');
$view = app('view');

// Check if running in CLI
if (is_cli()) {
    echo "Running from command line";
}

// Get config item
$baseUrl = config_item('base_url');

// Log message
log_message('info', 'User logged in');

// Show error
show_error('Something went wrong', 500);
show_404('Page not found');
```

### 8. **Module System**

```php
<?php

use Kodhe\Framework\Support\Modules;

// Find module file
list($path, $file) = Modules::find('config', 'blog', 'config/');

// Load module file
$data = Modules::load_file('routes', 'blog', 'config');

// Get module path
$modulePath = Modules::locate('blog');
```

## 📚 Class Reference

### Core Classes

| Class | Namespace | Description |
|-------|-----------|-------------|
| `Container` | `Kodhe\Framework\Container` | Dependency Injection Container |
| `Application` | `Kodhe\Framework\Foundation` | Main application entry point |
| `Config` | `Kodhe\Framework\Config` | Configuration manager |
| `ServiceManager` | `Kodhe\Framework\Foundation\Service` | Core service manager |
| `ServiceLocator` | `Kodhe\Framework\Foundation\Service` | Service locator registry |
| `ServiceProvider` | `Kodhe\Framework\Foundation\Service` | Service provider abstract |
| `Autoloader` | `Kodhe\Framework\Support` | PSR-4 autoloader |
| `Modules` | `Kodhe\Framework\Support` | Module system |

### Config Loaders

| Class | Description |
|-------|-------------|
| `ArrayLoader` | Load configuration from PHP arrays |
| `PhpLoader` | Load configuration from PHP files |
| `FileLoader` | Load configuration from files |
| `LegacyLoader` | CodeIgniter 3 compatibility loader |

### Exception Classes

| Namespace | Purpose |
|-----------|---------|
| `Auth` | Authentication & authorization exceptions |
| `Database` | Database query and connection exceptions |
| `Http` | HTTP status code exceptions |
| `Middleware` | Middleware pipeline exceptions |
| `Routing` | Route matching and generation exceptions |
| `Service` | Service resolution exceptions |

### Controllers

| Class | Description |
|-------|-------------|
| `BaseController` | Base controller with common functionality |
| `FileNotFound` | 404 error handler controller |
| `CPException` | Control panel exception handler |

## ⚙️ Configuration

### Config Paths

By default, the framework looks for configuration files in:

```php
APPPATH . 'config/'
```

You can add additional paths:

```php
$config->_config_paths[] = '/path/to/custom/config';
```

### Service Registration

Register service providers in your application:

```php
$app->registerProvider(App\Providers\AppServiceProvider::class);
$app->registerProvider(App\Providers\RouteServiceProvider::class);
$app->registerProvider(App\Providers\DatabaseServiceProvider::class);
```

### Container Configuration

```php
$container = new Container();

// Enable/disable duplicate registration exceptions
$container->setThrowOnDuplicate(false);

// Check current setting
$throwOnDuplicate = $container->getThrowOnDuplicate();
```

### Legacy Hooks & PSR-14 Bridge

The CI3-style hook system (`config/hooks.php`, container service `hooks`) is
still supported. To migrate gradually, the framework ships a two-way adapter
in `src/Support/Legacy/Bridge/` that works with **any** PSR-14 dispatcher
(`kodhe/events`, Symfony, League, …) — it does not live in the events package,
so events stay a general-purpose subsystem:

```php
use Kodhe\Framework\Support\Legacy\Bridge\HookBridge;

// hooks -> events: every call_hook('pre_system') also emits "hook.pre_system"
$hooks = new HookBridge($legacyHooks, $psr14Dispatcher);
$app->instance('hooks', $hooks);

// events -> hooks: forward new PSR-14 events back into existing hook definitions
$psr14Dispatcher->on('hook.*', $hooks->asListener());
```

## 🧪 Testing

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/ContainerTest.php
vendor/bin/phpunit tests/ApplicationTest.php
vendor/bin/phpunit tests/ConfigTest.php
vendor/bin/phpunit tests/AutoloaderTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Run with verbose output
vendor/bin/phpunit -v
```

### Test Coverage

This package includes unit tests for:

- **ContainerTest** - Dependency Injection Container (register, make, singleton, bindings)
- **ApplicationTest** - Application bootstrap and lifecycle
- **ConfigTest** - Configuration loading and manipulation
- **AutoloaderTest** - PSR-4 autoloading functionality


## 🔄 Migration from Previous Versions

### Changes in 1.1.1

- ✅ Extracted the CodeIgniter 3 legacy loader (`LegacyLoader`) into a separate package: `kodhe/legacy-loader`
- ✅ `FileLoader` still extends `LegacyLoader` for full CI3 compatibility
- ✅ The framework now requires `kodhe/legacy-loader` automatically

If you are upgrading from a previous version:

```bash
composer update kodhe/framework
```

The legacy loader will be installed automatically.

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

Kodhe Framework is licensed under the [MIT License](LICENSE).

## 🆘 Support

For questions, issues, or feature requests:

- 📧 Email: support@kodhe.com
- 💬 GitHub Issues: https://github.com/kodhe/kodhe/issues
- 📖 Documentation: https://kodhe.com/docs

## 🙏 Credits

Created and maintained by the [Karya Kode Team](https://github.com/kodhe).

Special thanks to:
- The CodeIgniter 3 team for the legacy-compatibility inspiration
- All Kodhe Framework contributors

---

<div align="center">

**Happy Coding! 🎉**

Made with ❤️ by Karya Kode

</div>
