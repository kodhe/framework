# Kodhe Database

Package database hasil refaktor sistem database CodeIgniter 3: koneksi multi-driver, query builder fluent, ORM berbasis Model, dan migrasi. Namespace PSR-4: `Kodhe\Framework\Database\`.

Sub-komponen:

- `DB` — facade statis (`DB::table()`, `DB::model()`, transaksi, raw query)
- `Builder` — fluent query builder (`where`, `join`, `orderBy`, `paginate`, dst.)
- `ORM\Model` / `Model` — active-record style model + relasi di `ORM/Relationships`
- `Connection\` — `ConnectionManager` dan driver per engine (MySQL, PostgreSQL, SQLite, dll.)
- `Migrations\` — skema migrasi versioned

## Instalasi

```bash
composer require kodhe/database
```

Persyaratan: PHP >= 8.1 + ekstensi PDO/driver terkait (`ext-pdo_mysql`, `ext-pdo_pgsql`, `ext-pdo_sqlite`).

## Quick Start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Kodhe\Framework\Database\DB;

// Query builder via facade statis
$users = DB::table('users')
    ->where('active', 1)
    ->orderBy('name', 'asc')
    ->take(10)
    ->get();

foreach ($users as $u) {
    echo $u->name . PHP_EOL;
}
```

## Penggunaan

### 1. Query Builder

```php
$rows = DB::table('orders')
    ->select(['id', 'total'])
    ->whereBetween('created_at', ['2026-01-01', '2026-12-31'])
    ->whereIn('status', ['paid', 'shipped'])
    ->orWhere('express', true)
    ->paginate(perPage: 20, page: 2);
```

Method utama `Builder`: `where`, `orWhere`, `whereIn`, `whereNotIn`, `whereNull`, `whereNotNull`, `whereBetween`, `whereLike`, `select`, `join`, `leftJoin`, `rightJoin`, `orderBy`, `groupBy`, `having`, `take`, `skip`, `with`, `get`, `first`, `count`, `paginate`.

### 2. Model (Active Record)

```php
use Kodhe\Framework\Database\Model;

class Product extends Model
{
    // Nama tabel default diturunkan dari nama class (Product -> products)
}

$produk   = Product::find(1);
$semuaBaru = Product::where('stock', '>', 0)->get();
```

### 3. Transaksi

```php
DB::beginTransaction();
try {
    DB::table('accounts')->where('id', 1)->update(...);
    DB::table('accounts')->where('id', 2)->update(...);
    DB::commit();
} catch (\Throwable $e) {
    DB::rollback();
    throw $e;
}
```

### 4. Raw query dengan binding

```php
$result = DB::raw('SELECT * FROM logs WHERE level = ? AND created_at > ?', ['error', '2026-01-01']);
```

## Struktur Direktori

```
src/
├── DB.php            # Facade statis
├── Builder.php       # Fluent query builder
├── Model.php         # Model modern (extends ORM\LegacyModel)
├── Loader.php        # Loader konfigurasi/koneksi CI3-compatible
├── Connection/       # ConnectionInterface, ConnectionManager, Drivers/
├── ORM/              # Model, LegacyModel, CI_Model, Relationships/, README.md
└── Migrations/       # Kelas migrasi & schema
```

## Kompatibilitas CodeIgniter 3

- `$this->db->get('table')` gaya lama tetap dilayani lewat `Loader`/`CI_Model` shim di `ORM/`.
- Konfigurasi connection memakai array key CI3 (`hostname`, `username`, `database`, `dbdriver`, dst.).

## Catatan & Batasan

- Contoh query di atas mengasumsikan koneksi aktif telah dikonfigurasi melalui `ConnectionManager`/`Loader`; lihat `database/src/Connection/Drivers` untuk engine yang didukung.
- Dokumen lama di `doc/database_README.md` merangkum versi awal; file ini adalah rujukan utama package.

## Pengujian

```bash
cd database && composer install && vendor/bin/phpunit
```

## Lisensi

MIT. Lihat [LICENSE](../LICENSE).
