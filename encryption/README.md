# Kodhe Encryption Library for CodeIgniter 3

Refactored Encryption Library for CodeIgniter 3 with PSR-4/PSR-12 compliance, modern design patterns, and comprehensive test coverage.

## Features

- **PSR-4 Autoloading**: Proper namespace-based autoloading
- **PSR-12 Coding Style**: Modern PHP coding standards
- **Strategy Pattern**: Pluggable encryption handlers (OpenSSL, etc.)
- **Dependency Injection**: Easy to test and extend
- **Caching**: Cipher algorithm resolution caching for performance
- **Authenticated Encryption**: Support for GCM mode and CBC+HMAC
- **Batch Operations**: `encrypt_many()` and `decrypt_many()` for bulk operations
- **Full Backward Compatibility**: Drop-in replacement for CI3's native Encryption library

## Installation

### Via Composer

```bash
composer require kodhe/codeigniter-encryption
```

### Manual Installation

1. Copy the `src/` folder to your application:
   ```
   application/libraries/Encryption/
   ```

2. Add PSR-4 autoloading to your `application/config/config.php`:

```php
spl_autoload_register(function ($class) {
    $prefix = 'Kodhe\\Encryption\\';
    $base_dir = APPPATH . 'libraries/Encryption/';

    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
```

## Usage

### Basic Usage (CI3 Compatible)

```php
// In your controller or model
$this->load->library('encryption');

// Encrypt data
$encrypted = $this->encryption->encrypt('secret data');

// Decrypt data
$decrypted = $this->encryption->decrypt($encrypted);
```

### With Custom Configuration

```php
$config = [
    'cipher' => 'aes-256',
    'mode' => 'gcm',  // or 'cbc', 'ctr'
    'key' => 'your-32-byte-secret-key-here!',
];

$this->encryption->initialize($config);
$encrypted = $this->encryption->encrypt('data');
```

### Using Raw Binary Data

```php
// For BLOB columns in database
$encrypted = $this->encryption->encrypt($data, ['raw_data' => true]);
$decrypted = $this->encryption->decrypt($encrypted, ['raw_data' => true]);
```

### Batch Operations

```php
$items = ['item1', 'item2', 'item3'];
$encrypted = $this->encryption->encrypt_many($items);
$decrypted = $this->encryption->decrypt_many($encrypted);
```

### Key Generation

```php
// Generate a 32-byte random key
$key = $this->encryption->create_key(32);

// Generate HKDF-derived key
$derived = $this->encryption->hkdf('master-key', 'sha256', 'salt', 32);
```

## Configuration

Add to `application/config/config.php`:

```php
$config['encryption_key'] = 'your-32-byte-or-longer-secret-key';
```

Optional: Create `application/config/encryption.php`:

```php
$config['encryption'] = [
    'cipher'      => 'aes-256',
    'mode'        => 'gcm',
    'hmac_digest' => 'SHA512',
];
```

## API Reference

### Main Methods

| Method | Description | Parameters |
|--------|-------------|------------|
| `encrypt($data, $params = null)` | Encrypt data | `$data`: string, `$params`: array (optional) |
| `decrypt($data, $params = null)` | Decrypt data | `$data`: string, `$params`: array (optional) |
| `initialize($params)` | Initialize/reconfigure | `$params`: array |
| `create_key($length)` | Generate random key | `$length`: int (bytes) |
| `hkdf($key, $digest, $salt, $length, $info)` | Derive key | See HKDF RFC 5869 |
| `encrypt_many($items, $params)` | Batch encrypt | `$items`: array, `$params`: array |
| `decrypt_many($items, $params)` | Batch decrypt | `$items`: array, `$params`: array |

### Parameter Options

```php
$params = [
    'cipher' => 'aes-256',      // aes-128, aes-256
    'mode' => 'gcm',            // cbc, gcm, ctr, ecb, ofb, cfb
    'key' => 'custom-key',      // Override default key
    'raw_data' => true,         // Return binary instead of base64
    'hmac_digest' => 'SHA512',  // sha256, sha384, sha512
    'hmac_key' => 'hmac-key',   // Override HMAC key
];
```

## Running Tests

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Or directly
vendor/bin/phpunit
```

## Project Structure

```
src/
├── Contracts/
│   ├── EncryptionInterface.php
│   └── HandlerInterface.php
├── Encoding/
│   ├── Base64Encoder.php
│   └── HexEncoder.php
├── Handlers/
│   └── OpenSslHandler.php
├── Key/
│   ├── KeyDeriver.php
│   └── KeyGenerator.php
├── Message/
│   └── EncryptedPayload.php
├── Support/
│   └── CipherAlgorithmResolver.php
└── Encryption.php
```

## Requirements

- PHP 7.4+ or 8.x
- OpenSSL extension
- Hash extension

## License

MIT License - see LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make changes with tests
4. Ensure all tests pass
5. Submit a pull request

## Changelog

### Version 2.0.0
- Complete refactor with PSR-4/PSR-12 compliance
- Strategy pattern for encryption handlers
- Dependency injection for better testability
- Caching for cipher algorithm resolution
- Batch operations support
- Comprehensive unit tests
- Full backward compatibility with CI3 API
