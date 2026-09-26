# <Nama Package>

<!--
Template dokumentasi README untuk setiap package Kodhe Framework.
Cara pakai: salin file ini ke <package>/README.md, lalu isi semua bagian.
Aturan penulisan:
  - Bahasa: Indonesia (boleh campur istilah teknis Inggris).
  - Semua contoh kode harus runnable terhadap API yang benar-benar ada di src/
    (namespace aktual `Kodhe\Framework\<Paket>\...`, bukan namespace lama/test-file).
  - Jangan biarkan placeholder `<...>` saat commit.
-->

Satu–dua paragraf: apa fungsi package ini, dari mana asalnya (mis. hasil refaktor library CodeIgniter 3), dan kapan sebaiknya dipakai.

## Instalasi

```bash
composer require kodhe/<nama-paket>
```

Persyaratan: PHP >= 8.1. Dependensi/saran: <mis. ext-redis, kodhe/driver>.

## Quick Start

Contoh paling minimal agar developer langsung bisa memakai package dalam < 1 menit:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Kodhe\Framework\<Paket>\<Class>;

$contoh = new <Class>();
echo $contoh->metodeUtama();
```

## Struktur Direktori

```
src/
├── Contracts/      # Interface (kontrak)
├── Drivers/        # Implementasi driver (bila ada)
└── ...             # Class utama
```

## Penggunaan

### 1. <Skenario penggunaan pertama>

```php
// contoh kode nyata + komentar seperlunya
```

### 2. <Skenario berikutnya>

```php
// ...
```

## Konfigurasi

| Opsi | Tipe | Default | Keterangan |
|------|------|---------|------------|
| `<key>` | string | `''` | ... |

## API Referensi (Class Utama)

| Method | Deskripsi |
|--------|-----------|
| `method(args)` | ... |

## Kompatibilitas CodeIgniter 3

Bagian ini menjelaskan kompatibilitas mundur (API lama tetap jalan) dan perbedaan perilaku bila ada.

## Catatan & Batasan

- <edge case, ekstensi wajib, keamanan, dsb.>

## Pengujian

```bash
cd <paket> && composer install && vendor/bin/phpunit
```

## Lisensi

MIT. Lihat [LICENSE](../LICENSE).
