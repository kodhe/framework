# Kodhe Profiler

Package profiling hasil refaktor library `Profiler` CodeIgniter 3, dengan namespace `Kodhe\Framework\Profiler`. Menyisipkan panel debug di bagian bawah halaman HTML (atau keluaran plain-text) yang menampilkan benchmark, query database, memory usage, URI, GET/POST, config, session, dan HTTP headers. Arsitekturnya dimodularisasi menjadi **collector** per seksi dan **renderer** (HTML/Text).

## Instalasi

```bash
composer require kodhe/profiler
```

Persyaratan: PHP >= 8.1. Tidak ada dependensi eksternal wajib.

## Quick Start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Kodhe\Framework\Profiler\Profiler;

$profiler = new Profiler();

echo $pageHtml . $profiler->run();
// Panel debug dirender di akhir output halaman
```

## Konfigurasi & Seleksi Seksi

Seksi dikontrol lewat array konfigurasi `sections` atau API eksplisit:

```php
$profiler = new Profiler([
    'sections' => [
        'benchmarks'   => true,
        'memory_usage' => true,
        'get'          => true,
        'post'         => false,
    ],
]);

// Atau runtime:
$profiler->disableSection('database');
$profiler->enableSection('config');
var_dump($profiler->isSectionEnabled('uri')); // bool
```

| Seksi | Collector | Keterangan |
|---|---|---|
| `benchmarks` | BenchmarkCollector | Mark/named benchmark waktu eksekusi |
| `queries` / `database` | DatabaseCollector | Query + waktu dari DB driver |
| `memory_usage` | MemoryCollector | Peak memory |
| `uri_segment` / `uri` | UriCollector | Segmen URI request |
| `get` / `post` | — | Isi `$_GET` / `$_POST` |
| `config` | ConfigCollector | Item konfigurasi |
| `session_data` | SessionCollector | Isi session |
| `http_headers` | HttpHeadersCollector | Request/response headers |

Daftar lengkap bisa dilihat via `$profiler->getAvailableSections()`.

## Renderer

Dua renderer bawaan tersedia di `src/Renderers/`:

- `HtmlRenderer` — tabel debug ala CI3 (default untuk output HTML).
- `TextRenderer` — cocok untuk CLI/log.

Collector kustom bisa didaftarkan langsung:

```php
use Kodhe\Framework\Profiler\Collectors\MyCustomCollector;

$profiler->addCollector('my_metrics', new MyCustomCollector());
```

## Struktur Direktori

```
src/
├── Profiler.php                  # Kelas utama (API kompatibel CI3)
├── Contracts/                    # ProfilerInterface, CollectorInterface
├── Collectors/                   # Benchmark, Database, Memory, Uri, Config,
│                                 # Session, Controller, HttpHeaders
├── Renderers/                    # HtmlRenderer, TextRenderer
├── Factory/                      # CollectorFactory, RendererFactory
├── Support/
├── ValueObjects/                 # ProfileData, ProfileSection
└── language/profiler_lang.php
```

## Catatan Migrasi dari Namespace Lama

Dokumentasi/test lama memakai `Kodhe\Library\Profiler\Profiler` dan metode `disableAll()`. Pada versi Composer modern gunakan:

```php
use Kodhe\Framework\Profiler\Profiler;
```

Untuk menonaktifkan semua seksi, kirim konfigurasi `'sections' => []` atau panggil `disableSection()` per seksi. PSR-4 mapping `Kodhe\Framework\Profiler\` → `profiler/src/` dideklarasikan di `profiler/composer.json`.
