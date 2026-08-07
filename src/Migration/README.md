# Kodhe\Migration - CodeIgniter 3 Migration Library Refactored

Library migration modular untuk CodeIgniter 3 dengan arsitektur PSR-4 dan design patterns modern.

## Features

- ✅ **100% Backward Compatible** dengan API CI3 original
- ✅ **PSR-4 Autoloading** dengan namespace `Kodhe\Migration`
- ✅ **Design Patterns**: Repository, Factory, Strategy, Command
- ✅ **Performance Optimized**: Caching scan folder, lazy loading
- ✅ **Testable**: PHPUnit ready dengan dependency injection
- ✅ **Type Safe**: Strict types dan PHPDoc lengkap

## Installation

### Via Composer (Recommended)

```bash
composer require kodhe/ci3-migration
```

### Manual Installation

1. Copy folder `src/Migration` ke `application/libraries/`
2. Tambahkan autoloader di `application/config/config.php`:

```php
spl_autoload_register(function ($class) {
    $prefix = 'Kodhe\\Migration\\';
    $base_dir = APPPATH . 'libraries/Migration/';

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

### Basic Usage (CI3 Style)

```php
$this->load->library('migration');

// Migrate ke version terbaru
if ($this->migration->latest() === FALSE) {
    show_error($this->migration->error_string());
}

// Migrate ke version tertentu
$this->migration->version(5);

// Rollback batch terakhir
$this->migration->rollback();

// Get semua migration files
$migrations = $this->migration->find_migrations();
```

### Advanced Usage (Direct Namespace)

```php
use Kodhe\Migration\Migration;

$migration = new Migration([
    'migration_path' => APPPATH . 'migrations/',
    'migration_enabled' => true,
]);

// Run migrations
$migration->latest();

// Get current version
$currentVersion = $migration->getCurrentVersion();

// Rollback
$migration->rollback();
```

## Migration File Format

File migration harus mengikuti format: `{version}_{description}.php`

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_users extends CI_Migration {

    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('users');
    }

    public function down()
    {
        $this->dbforge->drop_table('users');
    }
}
```

## API Reference

### Main Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `current($version = null)` | Migrate ke version tertentu | bool |
| `latest()` | Migrate ke version terbaru | bool |
| `version($version)` | Migrate ke version spesifik | bool |
| `rollback()` | Rollback batch terakhir | bool |
| `find_migrations()` | Get semua migration files | array |
| `error_string()` | Get error message terakhir | string\|null |
| `getCurrentVersion()` | Get current migration version | int |
| `getLastBatch()` | Get last batch number | int\|null |

### Configuration Options

```php
$config['migration_enabled'] = TRUE;
$config['migration_path'] = APPPATH . 'migrations/';
$config['migration_table'] = 'migrations';
```

## Architecture

```
src/Migration/
├── Migration.php              # Main class (CI3 compatible)
├── MigrationLib.php           # CI3 wrapper class
├── Contracts/
│   ├── MigrationRepositoryInterface.php
│   └── MigrationRunnerInterface.php
├── Repository/
│   └── FileMigrationRepository.php
├── Runner/
│   └── MigrationFileRunner.php
├── Factory/
│   └── MigrationComponentFactory.php
├── Parser/
│   └── MigrationFilenameParser.php
├── Support/
│   └── MigrationHelper.php
└── Exceptions/
    ├── MigrationNotFoundException.php
    ├── InvalidMigrationFileException.php
    └── DuplicateVersionException.php
```

## Design Patterns Used

### Repository Pattern
`FileMigrationRepository` mengelola akses ke migration files dan metadata.

### Factory Pattern
`MigrationComponentFactory` membuat instance repository dan runner.

### Strategy Pattern
`MigrationRunnerInterface` memungkinkan implementasi runner yang berbeda.

### Command Pattern
Setiap migration file adalah command dengan `up()` dan `down()` methods.

## Performance Features

1. **Scan Cache**: Folder migration di-scan sekali per request
2. **Lazy Loading**: Database connection hanya saat diperlukan
3. **Batch Operations**: Multiple migrations dalam satu batch

## Testing

Jalankan tests dengan PHPUnit:

```bash
vendor/bin/phpunit tests/Migration/
```

### Test Coverage

- ✅ Latest migration
- ✅ Version-specific migration
- ✅ Rollback functionality
- ✅ Find migrations
- ✅ Error handling
- ✅ Filename parsing
- ✅ Repository operations
- ✅ Runner execution

## Examples

### Example 1: Run All Migrations

```php
class Install extends CI_Controller {
    public function index() {
        $this->load->library('migration');
        
        if ($this->migration->latest() === FALSE) {
            show_error($this->migration->error_string());
        }
        
        echo 'Database installed successfully!';
    }
}
```

### Example 2: Check Current Version

```php
$this->load->library('migration');
$version = $this->migration->getCurrentVersion();
echo "Current database version: {$version}";
```

### Example 3: Rollback on Error

```php
$this->load->library('migration');

if ($this->migration->latest() === FALSE) {
    log_message('error', $this->migration->error_string());
    
    // Rollback jika ada error
    $this->migration->rollback();
}
```

## Troubleshooting

### Migration Not Found
Pastikan file migration ada di folder yang benar dengan format nama yang valid.

### Already at Version X
Migration sudah berada di version target, tidak perlu action.

### Nothing to Rollback
Tidak ada migration yang bisa di-rollback (belum ada yang dijalankan).

## License

MIT License

## Author

Your Name <your.email@example.com>
