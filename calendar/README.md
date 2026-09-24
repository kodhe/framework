# Kodhe Calendar

Package kalender hasil refaktor library `Calendar` CodeIgniter 3, dengan namespace `Kodhe\Framework\Calendar`. Menghasilkan tampilan kalender bulanan (HTML tabel atau JSON), mendukung navigasi prev/next, nama hari/bulan pendek/panjang, mulai Minggu/Senin, menampilkan hari bulan lain, serta menyisipkan tautan event per tanggal.

## Instalasi

```bash
composer require kodhe/calendar
```

Persyaratan: PHP >= 8.1. Tidak ada dependensi eksternal.

## Quick Start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Kodhe\Framework\Calendar\Calendar;

$calendar = new Calendar();

echo $calendar->generate(2024, 1);
// <table class="calendar"> ... </table>
```

Dengan navigasi dan data event (tanggal => URL):

```php
$calendar = new Calendar([
    'show_next_prev' => true,
    'next_prev_url'  => '/calendar/',
]);

$data = [
    1  => '/events/new-year',
    15 => '/events/meeting',
    25 => '/events/workshop',
];

echo $calendar->generate(2024, 1, $data);
```

## Konfigurasi

Array konfigurasi diterima lewat konstruktor atau bersifat idempoten per instance:

| Kunci | Tipe | Default | Keterangan |
|---|---|---|---|
| `start_day` | string | `sunday` | Hari pertama minggu: `sunday` / `monday` |
| `month_type` | string | `long` | Nama bulan: `long` (January) atau `abr` (Jan) |
| `day_type` | string | `abr` | Nama hari: `long` atau `abr` |
| `locale` | string | `en` | Locale untuk nama hari/bulan |
| `template` | array\|null | `null` | Template CSS kustom (`default_template()` sebagai basis) |
| `show_next_prev` | bool | `false` | Tampilkan tautan bulan sebelumnya/berikutnya |
| `next_prev_url` | string | `''` | URL dasar untuk tautan navigasi |
| `show_other_days` | bool | `false` | Tampilkan tanggal dari bulan sebelum/sesudah |
| `local_time` | int\|null | `null` | Timestamp acuan "sekarang" (null = waktu sistem) |

Contoh kalender yang dimulai hari Senin dengan nama pendek:

```php
$calendar = new Calendar([
    'start_day'       => 'monday',
    'show_other_days' => true,
    'month_type'      => 'abr',
]);

echo $calendar->generate(2024, 1);
```

## Metode Utama

### `generate(int|string $year = '', int|string $month = '', array $data = []): string`

Membangun HTML kalender untuk bulan/tahun tertentu. Parameter kosong berarti bulan/tahun berjalan. `$data` memetakan nomor tanggal ke URL event (`[15 => '/event']`); nilai non-array pada tanggal juga dirender apa adanya.

### `asJson(int|string $year = '', int|string $month = '', array $data = []): string`

Hasil kalender dalam bentuk JSON — berguna untuk kalender dinamis di sisi frontend.

### `setRenderer(CalendarRendererInterface $renderer): self`

Mengganti renderer secara runtime (mis. renderer kustom untuk markup Tailwind).

### Helper utilitas (kompatibel CI3 + varian camelCase)

```php
$calendar->get_month_name(1);        // "January"  (alias: getMonthName)
$calendar->get_day_names('abr');     // ['Sunday','Monday',...] short (alias: getDayNames)
$calendar->adjust_date(12, 2024);    // [1, 2025]  (alias: adjustDate)
$calendar->get_total_days(2, 2024);  // 29         (alias: getTotalDays)
$calendar->get_last_day(2, 2024);    // 4 (indeks hari) (alias: getLastDay)
$calendar->get_total_weeks(2, 2024); // 5          (alias: getTotalWeeks)
```

## Struktur Direktori

```
src/
├── Calendar.php                  # Kelas utama (API kompatibel CI3)
├── Contracts/                    # CalendarInterface, CalendarRendererInterface
├── Generators/MonthGenerator.php # Logika pembentukan sel kalender
├── Renderers/                    # HtmlTableRenderer, JsonRenderer
├── Traits/                       # ConfigurableTrait, SingletonTrait
├── ValueObjects/                 # CalendarDate, CalendarEvent
└── helpers.php                   # days_in_month() dsb.
```

## Catatan Migrasi dari Namespace Lama

Versi dokumentasi/test lama memakai `Kodhe\Library\Calendar\Calendar`. Untuk instalasi Composer modern, gunakan:

```php
use Kodhe\Framework\Calendar\Calendar;
```

PSR-4 mapping `Kodhe\Framework\Calendar\` → `calendar/src/` dideklarasikan di `calendar/composer.json`.
