# Database

Paket: `kodhe/database` (+ `kodhe/driver` sebagai abstraksi driver).

## Koneksi

```php
use Kodhe\Framework\Database\Connection;

$conn = Connection::factory([
    'drivername' => 'Mysqli',            // atau 'Pdo'
    'hostname'   => 'localhost',
    'username'   => 'app',
    'password'   => 'secret',
    'database'   => 'myapp',
]);
```

## Query Builder (ala CI3)

```php
$rows = $conn->select('id, title')->from('posts')
             ->where('status', 'publish')
             ->order_by('created', 'desc')
             ->limit(10, 0)
             ->get()->result_array();
```

Driver Mysqli memvalidasi identifier secara aman (guard null sudah diperbaiki —
tidak ada lagi TypeError `strcspn()` pada input kosong).

## Migration

Gunakan paket terpisah `kodhe/migration` — lihat [migration.md](migration.md).

Detail skema config penuh & transaksi: [README paket](../../database/README.md).
