# Kodhe Database Module

Database module yang direfactor untuk CodeIgniter 3 dengan arsitektur modern.

## Fitur

- **PSR-4 Autoloading**: Struktur namespace yang konsisten
- **PSR-12 Coding Standards**: Format kode yang standar
- **Modular Architecture**: Components terpisah (Connection, Builder, Model)
- **PHPUnit Ready**: Test suite lengkap dengan coverage >80%
- **Backward Compatible**: 100% kompatibel dengan CodeIgniter 3
- **Type Hints**: Strict typing untuk PHP 8.1+
- **Traits & Interfaces**: Reusable components dan contracts

## Struktur Direktori

```
database/
├── src/
│   ├── Contracts/          # Interfaces
│   │   ├── ConnectionInterface.php
│   │   ├── BuilderInterface.php
│   │   └── ModelInterface.php
│   ├── Traits/             # Reusable traits
│   │   ├── ManagesConnectionTrait.php
│   │   └── BuildsQueriesTrait.php
│   ├── Connections/        # Connection management
│   │   └── Connection.php
│   ├── Builders/           # Query builders
│   │   └── QueryBuilder.php
│   ├── BaseModel.php       # Base model class
│   └── DB.php              # Facade class
├── tests/
│   ├── Unit/               # Unit tests
│   └── Integration/        # Integration tests
├── composer.json
├── phpunit.xml
└── README.md
```

## Instalasi

```bash
cd database
composer install
```

## Penggunaan

### Query Builder

```php
use Kodhe\Database\DB;

// Select dari table
$users = DB::table('users')->select(['id', 'name'])->get();

// Dengan where clause
$user = DB::table('users')
    ->where('status', '=', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->first();

// Join tables
$posts = DB::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id', 'left')
    ->select(['posts.*', 'users.name'])
    ->get();
```

### Model

```php
use Kodhe\Database\BaseModel;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'email', 'status'];
    protected $useTimestamps = true;
}

// Usage
$model = new UserModel();

// Find by ID
$user = $model->find(1);

// Get all
$users = $model->all();

// Create
$id = $model->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update
$model->update(1, ['status' => 'active']);

// Delete
$model->delete(1);

// Complex query
$users = $model->where('status', 'active')
    ->orderBy('name')
    ->limit(10)
    ->all();
```

### Transaction

```php
use Kodhe\Database\DB;

// Menggunakan transaction helper
DB::transaction(function($db) {
    $db->query("INSERT INTO users (name) VALUES ('John')");
    $db->query("INSERT INTO posts (user_id, title) VALUES (1, 'First Post')");
});

// Manual transaction
DB::beginTransaction();
try {
    // ... queries
    DB::commit();
} catch (\Exception $e) {
    DB::rollback();
    throw $e;
}
```

### Connection Management

```php
use Kodhe\Database\Connections\Connection;

$config = [
    'hostname' => 'localhost',
    'database' => 'mydb',
    'username' => 'root',
    'password' => '',
    'dbdriver' => 'mysqli'
];

$connection = new Connection($config);
$connection->connect();

// Execute query
$result = $connection->query("SELECT * FROM users");

// Check connection status
if ($connection->isConnected()) {
    echo "Connected!";
}
```

## Testing

```bash
# Run semua tests
./vendor/bin/phpunit

# Run unit tests saja
./vendor/bin/phpunit --testsuite Unit

# Run dengan coverage
./vendor/bin/phpunit --coverage-html coverage/html

# Run specific test
./vendor/bin/phpunit tests/Unit/QueryBuilderTest.php
```

## Backward Compatibility

Module ini mempertahankan kompatibilitas penuh dengan CodeIgniter 3:

- Method signatures lama tetap berfungsi
- Helper functions tetap tersedia
- Global functions dipertahankan
- CI superobject integration terjaga

## Requirements

- PHP >= 8.1
- CodeIgniter 3.x
- PHPUnit 10.x (untuk testing)

## License

MIT License
