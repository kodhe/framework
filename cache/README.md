# Kodhe Framework Cache Component

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue.svg)](http://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.1.0-orange.svg)](https://github.com/kodhe/cache)

A powerful and flexible caching library for PHP applications, providing a unified interface for multiple cache drivers including File, APC, Memcached, Redis, Wincache, and more. Part of the Kodhe Framework ecosystem.

## Features

- **Multiple Cache Drivers**: Support for File, APC, APCu, Memcached, Redis, Wincache, and Dummy drivers
- **Unified Interface**: Consistent API across all cache drivers
- **Automatic Fallback**: Graceful fallback to backup driver when primary is unavailable
- **Key Prefixing**: Built-in support for cache key namespacing
- **TTL Support**: Time-to-live management for cache entries
- **Increment/Decrement**: Atomic operations on numeric cache values
- **Metadata Support**: Retrieve cache item metadata (creation time, expiration)
- **Factory Pattern**: Easy driver instantiation and management
- **PSR-16 Inspired**: Modern interface design following PSR standards

## Installation

### Via Composer

```bash
composer require kodhe/cache
```

### Requirements

- PHP >= 8.1
- kodhe/driver ^1.0

### Optional Extensions

- `ext-apc` - For APC caching
- `ext-memcached` - For Memcached caching  
- `ext-redis` - For Redis caching
- `ext-wincache` - For WinCache caching

## Quick Start

```php
<?php

use Kodhe\Framework\Cache\Cache;

// Initialize cache with file driver
$config = [
    'adapter' => 'file',
    'backup' => 'dummy',
    'cache_path' => '/path/to/cache/',
    'key_prefix' => 'myapp_'
];

$cache = new Cache($config);

// Save data
$cache->save('user_123', ['name' => 'John', 'email' => 'john@example.com'], 3600);

// Retrieve data
$user = $cache->get('user_123');

// Delete data
$cache->delete('user_123');
```

## Usage

### Basic Operations

#### Save Cache Item

```php
// Save with default TTL (60 seconds)
$cache->save('key', 'value');

// Save with custom TTL (1 hour)
$cache->save('key', 'value', 3600);

// Save complex data types
$cache->save('array_key', ['foo' => 'bar', 'baz' => [1, 2, 3]]);
$cache->save('object_key', (object)['prop' => 'value']);
```

#### Get Cache Item

```php
$value = $cache->get('key');

if ($value === false) {
    // Key doesn't exist or has expired
}
```

#### Delete Cache Item

```php
$success = $cache->delete('key');
```

#### Increment/Decrement

```php
// Increment by 1
$newValue = $cache->increment('counter');

// Increment by custom value
$newValue = $cache->increment('counter', 5);

// Decrement by 1
$newValue = $cache->decrement('counter');

// Decrement by custom value
$newValue = $cache->decrement('counter', 3);
```

### Advanced Features

#### Clean All Cache

```php
$success = $cache->clean();
```

#### Get Cache Info

```php
// Get information about cached items
$info = $cache->cache_info('user');
```

#### Get Cache Metadata

```php
$metadata = $cache->get_metadata('key');
// Returns: ['expire' => timestamp, 'mtime' => timestamp]
```

#### Check Driver Support

```php
$isSupported = $cache->is_supported('memcached');
```

### Using Different Drivers

#### File Driver (Default)

```php
$config = [
    'adapter' => 'file',
    'cache_path' => '/tmp/cache/'
];
$cache = new Cache($config);
```

#### Memcached Driver

```php
$config = [
    'adapter' => 'memcached',
    'server' => [
        'host' => '127.0.0.1',
        'port' => 11211
    ]
];
$cache = new Cache($config);
```

#### Redis Driver

```php
$config = [
    'adapter' => 'redis',
    'server' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0
    ]
];
$cache = new Cache($config);
```

#### APC/APCu Driver

```php
$config = [
    'adapter' => 'apcu'
];
$cache = new Cache($config);
```

#### Dummy Driver (For Development/Testing)

```php
$config = [
    'adapter' => 'dummy'
];
$cache = new Cache($config);
```

### Automatic Fallback

The cache system automatically falls back to a backup driver if the primary driver is unavailable:

```php
$config = [
    'adapter' => 'memcached',  // Primary
    'backup' => 'file'         // Fallback
];

// If Memcached is not available, it will automatically use File driver
$cache = new Cache($config);
```

### Key Prefixing

Use key prefixes to namespace your cache entries:

```php
$config = [
    'adapter' => 'file',
    'key_prefix' => 'production_'
];

$cache = new Cache($config);
$cache->save('user_1', $data); 
// Actually saved as 'production_user_1'
```

## Factory Pattern

Use the CacheDriverFactory for advanced driver management:

```php
use Kodhe\Framework\Cache\Factory\CacheDriverFactory;

$factory = new CacheDriverFactory();

// Create a specific driver
$driver = $factory->make('redis', [
    'server' => ['host' => 'localhost', 'port' => 6379]
]);

// Check if driver is available
if ($factory->isAvailable('memcached')) {
    $driver = $factory->make('memcached');
}

// Get list of available drivers
$available = $factory->getAvailableDrivers();

// Register custom driver
CacheDriverFactory::registerDriver('custom', MyCustomDriver::class);
```

## Available Drivers

| Driver | Description | Requirements |
|--------|-------------|--------------|
| **File** | File-based caching | Writable directory |
| **APC** | Alternative PHP Cache | ext-apc |
| **APCu** | APC User Cache | ext-apcu |
| **Memcached** | Memcached distributed caching | ext-memcached |
| **Redis** | Redis in-memory data store | ext-redis |
| **Wincache** | Windows Cache Extension | ext-wincache |
| **Dummy** | No-op driver for testing | None |

## Configuration Options

### Main Cache Configuration

```php
$config = [
    'adapter'      => 'file',        // Primary driver
    'backup'       => 'dummy',       // Fallback driver
    'key_prefix'   => '',           // Key prefix for namespacing
    'cache_path'   => '/tmp/cache/' // Path for file driver
];
```

### Driver-Specific Configuration

#### Memcached

```php
$config = [
    'adapter' => 'memcached',
    'server' => [
        'host' => '127.0.0.1',
        'port' => 11211,
        'weight' => 1
    ],
    'options' => [
        \Memcached::OPT_COMPRESSION => true,
        \Memcached::OPT_BUFFER_WRITES => true
    ]
];
```

#### Redis

```php
$config = [
    'adapter' => 'redis',
    'server' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'timeout' => 0
    ]
];
```

## Testing

Run the unit tests using PHPUnit:

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit tests/

# Run with coverage report
vendor/bin/phpunit --coverage-html ./coverage tests/
```

### Test Coverage

The test suite includes:

- **CacheTest**: Tests for main Cache class
- **FileDriverTest**: Tests for File cache driver
- **DummyDriverTest**: Tests for Dummy cache driver
- **CacheDriverFactoryTest**: Tests for driver factory

## Class Reference

### Cache (Main Class)

```php
class Cache extends DriverLibrary
{
    public function get($id);
    public function save($id, $data, $ttl = 60, $raw = false): bool;
    public function delete($id): bool;
    public function increment($id, $offset = 1);
    public function decrement($id, $offset = 1);
    public function clean(): bool;
    public function cache_info($type = 'user');
    public function get_metadata($id);
    public function is_supported($driver): bool;
}
```

### CacheDriverInterface

```php
interface CacheDriverInterface
{
    public function isSupported(): bool;
    public function get(string $id);
    public function save(string $id, $data, int $ttl = 60, bool $raw = false): bool;
    public function delete(string $id): bool;
    public function increment(string $id, int $offset = 1);
    public function decrement(string $id, int $offset = 1);
    public function clean(): bool;
    public function cacheInfo(?string $type = null);
    public function getMetadata(string $id);
}
```

### CacheDriverFactory

```php
class CacheDriverFactory
{
    public function make(string $name, array $config = []): CacheDriverInterface;
    public function isAvailable(string $name): bool;
    public function getAvailableDrivers(): array;
    public static function registerDriver(string $name, string $class): void;
}
```

## Best Practices

1. **Use Appropriate TTL**: Set reasonable expiration times based on data volatility
2. **Implement Fallback**: Always configure a backup driver for production
3. **Use Key Prefixes**: Namespace your keys to avoid collisions
4. **Monitor Cache Size**: Regularly clean old cache entries
5. **Choose Right Driver**: Select driver based on your infrastructure and requirements
6. **Handle Failures Gracefully**: Always check return values from cache operations

## Common Use Cases

### Caching Database Queries

```php
$userId = 123;
$cacheKey = "user_query_{$userId}";

$user = $cache->get($cacheKey);
if ($user === false) {
    $user = $db->query("SELECT * FROM users WHERE id = ?", [$userId]);
    $cache->save($cacheKey, $user, 3600); // Cache for 1 hour
}
```

### Caching API Responses

```php
$apiUrl = 'https://api.example.com/data';
$cacheKey = 'api_response_' . md5($apiUrl);

$response = $cache->get($cacheKey);
if ($response === false) {
    $response = file_get_contents($apiUrl);
    $cache->save($cacheKey, $response, 1800); // Cache for 30 minutes
}
```

### Counter Implementation

```php
// Initialize counter
if ($cache->get('page_views') === false) {
    $cache->save('page_views', 0, 86400);
}

// Increment counter
$views = $cache->increment('page_views');
```

### Session Storage

```php
$sessionId = session_id();
$sessionData = $_SESSION;

// Save session
$cache->save("session_{$sessionId}", $sessionData, 3600);

// Retrieve session
$_SESSION = $cache->get("session_{$sessionId}");
```

## Troubleshooting

### Cache Not Working

1. Check if the driver is supported on your system
2. Verify permissions for cache directory (file driver)
3. Ensure required PHP extensions are installed
4. Check if fallback driver is being used

### Performance Issues

1. Use appropriate driver for your use case (Redis/Memcached for high traffic)
2. Optimize TTL values
3. Implement cache warming for frequently accessed data
4. Monitor cache hit/miss ratios

### Memory Issues

1. Set appropriate TTL to prevent unbounded growth
2. Implement cache cleanup routines
3. Use LRU eviction policies where available
4. Monitor memory usage regularly

## Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).

## Support

- **Documentation**: https://kodhe.dev/docs/cache
- **Issue Tracker**: https://github.com/kodhe/cache/issues
- **Discussions**: https://github.com/kodhe/cache/discussions

## Credits

- Original CodeIgniter Cache Library by EllisLab Dev Team
- Refactored and modernized by Kodhe Framework Team

## Changelog

### Version 1.1.0 (Current)
- Added PHP 8.1+ type hints
- Improved factory pattern implementation
- Enhanced error handling
- Better test coverage

### Version 1.0.0
- Initial release
- Support for all major cache drivers
- PSR-16 inspired interface
