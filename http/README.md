# CodeIgniter 3 HTTP Component

Modular HTTP component for CodeIgniter 3 with PSR-4/PSR-12 compliance.

## Features

- ✅ **Modular Architecture** - Separated by functionality (Contracts, Http, Controllers, Middleware, etc.)
- ✅ **PSR-4 Autoloading** - Modern autoloading standard
- ✅ **PSR-12 Coding Standards** - Consistent code style
- ✅ **PSR-7 Compatible** - HTTP Message interfaces
- ✅ **PHPUnit Ready** - Full test suite structure
- ✅ **Backward Compatible** - 100% compatible with CodeIgniter 3
- ✅ **Extensible** - Easy to add new middleware, controllers, or response types

## Installation

```bash
cd http/
composer install
```

## Structure

```
http/
├── src/
│   ├── Contracts/          # Interfaces
│   ├── Http/               # Request, Response, Uri implementations
│   ├── Controllers/        # Base controllers
│   ├── Kernel/             # HTTP Kernel & Pipeline
│   ├── Middleware/         # Middleware system
│   ├── Requests/           # Form requests
│   ├── Exceptions/         # Exception classes
│   ├── Support/            # Helper classes (HeaderBag, ParameterBag, etc.)
│   └── helpers/            # CI3 compatible helper functions
├── tests/
│   ├── Unit/               # Unit tests
│   ├── Integration/        # Integration tests
│   └── Fixtures/           # Test fixtures
├── config/
│   └── http.php            # Configuration
├── composer.json
├── phpunit.xml
└── README.md
```

## Usage

### Creating a Request

```php
use CodeIgniter\Http\Http\Request;

$request = new Request($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
$method = $request->getMethod();
$uri = $request->getUri();
$input = $request->getAllInput();
```

### Creating a Response

```php
use CodeIgniter\Http\Http\Response;

$response = new Response();
$response->setStatusCode(200)
    ->setContentType('application/json')
    ->setBody(json_encode(['status' => 'success']));
    
$response->send();
```

### Using Helper Functions

```php
// URL helpers (CI3 compatible)
$url = site_url('controller/method');
$base = base_url('assets/css/style.css');
redirect('login');
$current = current_url();
$link = anchor('page/view', 'View Page');
```

## Running Tests

```bash
composer test
composer phpstan
composer cs-check
```

## License

MIT License
