# Kodhe Framework Routing Component

A modular, PSR-4/PSR-12 compliant routing component for CodeIgniter 3 applications with full backward compatibility.

## Features

- **Modular Architecture**: Clean separation of concerns with dedicated namespaces for Contracts, Core, Matching, Dispatching, Registration, Groups, Middleware, and more
- **PSR-4 Autoloading**: Full PSR-4 compliance for modern autoloading
- **PSR-12 Coding Style**: All code follows PSR-12 coding standards
- **PHPUnit Ready**: Comprehensive test suite with unit and integration tests
- **CodeIgniter 3 Backward Compatible**: 100% backward compatible with existing CI3 applications
- **Easy to Extend**: Simple to add new storage types, discount/tax types, and custom route handlers

## Installation

```bash
composer require kodhe/routing:^2.0
```

## Directory Structure

```
routing/
├── src/
│   ├── Contracts/          # Interface definitions
│   │   ├── RouterInterface.php
│   │   ├── RouteInterface.php
│   │   ├── RouteCollectionInterface.php
│   │   ├── RouteRegistrarInterface.php
│   │   ├── RouteHandlerInterface.php
│   │   └── ControllerExecutorInterface.php
│   │
│   ├── Core/               # Core implementations
│   │   ├── Router.php
│   │   ├── Route.php
│   │   ├── RouteCollection.php
│   │   └── RouteItem.php
│   │
│   ├── Matching/           # Route matching logic
│   ├── Dispatching/        # Route dispatching
│   ├── Registration/       # Route registration
│   ├── Groups/             # Route group handling
│   ├── Middleware/         # Middleware resolution
│   ├── RateLimiting/       # Rate limiting support
│   ├── Compatibility/      # CI3 backward compatibility
│   ├── Support/            # Helper classes
│   ├── Exceptions/         # Exception classes
│   └── helpers/            # Helper functions
│
├── tests/
│   ├── Unit/               # Unit tests
│   ├── Integration/        # Integration tests
│   └── Fixtures/           # Test fixtures
│
├── config/                 # Configuration files
├── composer.json
├── phpunit.xml
└── README.md
```

## Usage

### Basic Routing

```php
use Kodhe\Framework\Routing\Route;

// GET route
Route::get('/users', 'UserController@index');

// POST route
Route::post('/users', 'UserController@store');

// Route with parameters
Route::get('/users/{id}', 'UserController@show');

// Named route
Route::get('/users', 'UserController@index')->name('users.index');

// Route with middleware
Route::get('/admin', 'AdminController@index')->middleware(['auth', 'admin']);
```

### Route Groups

```php
Route::group(['prefix' => 'api', 'middleware' => ['api']], function() {
    Route::get('/users', 'Api\UserController@index');
    Route::post('/users', 'Api\UserController@store');
});
```

### Resource Routes

```php
Route::resource('posts', 'PostController');
// Creates: index, create, store, show, edit, update, destroy routes

Route::apiResource('articles', 'ArticleController');
// Creates: index, store, show, update, destroy routes (no create/edit)
```

### Generating URLs

```php
// Generate URL for named route
$url = Route::url('users.index');

// With parameters
$url = Route::url('users.show', ['id' => 123]);
```

## Running Tests

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run PHPStan static analysis
composer phpstan

# Run code style check
composer phpcs

# Auto-fix code style issues
composer phpcbf
```

## Backward Compatibility

This routing component maintains 100% backward compatibility with CodeIgniter 3:

- All legacy router methods are preserved
- Existing route configurations continue to work
- No breaking changes to the public API

## Extending the Router

### Adding Custom Route Handlers

```php
use Kodhe\Framework\Routing\Contracts\RouteHandlerInterface;
use Kodhe\Framework\Routing\Contracts\RouteInterface;

class CustomHandler implements RouteHandlerInterface
{
    public function handle(RouteInterface $route, array $parameters = []): mixed
    {
        // Custom handling logic
    }
    
    public function supports(RouteInterface $route): bool
    {
        // Determine if this handler supports the route
    }
}
```

### Adding Custom Storage

The modular architecture makes it easy to add new storage types for routes:

```php
use Kodhe\Framework\Routing\Contracts\RouteCollectionInterface;

class DatabaseRouteCollection implements RouteCollectionInterface
{
    // Implement interface methods with database storage
}
```

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Version

2.0.0 - Major refactor with modular architecture
