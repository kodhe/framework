# Kodhe HTTP Component

Modular HTTP component for CodeIgniter 3 with PSR-4/PSR-12 compliance.

## Features

- **PSR-7 Compatible**: Full PSR-7 HTTP Message implementation
- **PSR-15 Middleware**: Standard middleware support
- **PSR-17 Factory**: HTTP factories for creating messages
- **CodeIgniter 3 Backward Compatible**: Works seamlessly with existing CI3 code
- **Modular Architecture**: Easy to extend and customize
- **PHPUnit Ready**: Comprehensive test suite included
- **Type Safe**: Strict typing for better IDE support

## Installation

```bash
composer require kodhe/http
```

## Usage

### Creating a Request

```php
use Kodhe\Framework\Http\Http\Request;
use Kodhe\Framework\Http\Http\Uri;

$uri = new Uri('https://example.com/api/users');
$request = new Request('GET', $uri);

// Or from globals
$request = Request::createFromGlobals($_SERVER, $_GET, $_POST, $_COOKIE);
```

### Creating a Response

```php
use Kodhe\Framework\Http\Http\Response;
use Kodhe\Framework\Http\Http\JsonResponse;
use Kodhe\Framework\Http\Http\RedirectResponse;

// Basic response
$response = new Response(200, ['Content-Type' => 'text/html'], '<html>...</html>');

// JSON response
$response = new JsonResponse(['status' => 'success', 'data' => $data]);

// Redirect response
$response = new RedirectResponse('/dashboard', 302);
```

### Using Middleware

```php
use Kodhe\Framework\Http\Middleware\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthMiddleware extends Middleware
{
    public function process(RequestInterface $request, callable $handler): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return new RedirectResponse('/login');
        }
        
        return $handler->handle($request);
    }
    
    protected function isAuthenticated(): bool
    {
        // Check authentication
        return true;
    }
}
```

### Pipeline

```php
use Kodhe\Framework\Http\Kernel\Pipeline;

$pipeline = new Pipeline();

$response = $pipeline
    ->send($request)
    ->through([AuthMiddleware::class, RateLimitMiddleware::class])
    ->to(function ($request) {
        return new JsonResponse(['message' => 'Hello World']);
    });
```

## Directory Structure

```
http/
├── src/
│   ├── Contracts/          # Interfaces
│   ├── Http/               # Core HTTP classes
│   ├── Controllers/        # Controller base classes
│   ├── Kernel/             # HTTP Kernel & Pipeline
│   ├── Middleware/         # Middleware implementations
│   ├── Requests/           # Form requests & validation
│   ├── Exceptions/         # HTTP exceptions
│   ├── Support/            # Helper classes
│   └── helpers/            # Helper functions
├── tests/
│   ├── Unit/               # Unit tests
│   ├── Integration/        # Integration tests
│   └── Fixtures/           # Test fixtures
├── config/                 # Configuration files
├── composer.json
├── phpunit.xml
└── README.md
```

## Testing

```bash
# Run all tests
composer test

# Run unit tests only
vendor/bin/phpunit --testsuite Unit

# Run integration tests only
vendor/bin/phpunit --testsuite Integration

# Generate coverage report
vendor/bin/phpunit --coverage-html build/coverage
```

## License

MIT License - see [LICENSE](LICENSE) file for details.
