# Kodhe Typography

Library auto-formatting teks → HTML hasil refaktor `Typography` CodeIgniter 3 (namespace `Kodhe\Framework\Typography`). Mengubah paragraf kosong menjadi `<p>`, baris baru dalam `<pre>` menjadi `<br>`, memperbaiki spasi setelah tanda baca, menyingkat angka ribuan (`1000000` → `1.000.000`), dsb. Versi Kodhe memecah internals ke `Formatters/` dan `Parsers/` serta menambah cache regex & proteksi tag HTML.

## Instalasi

```bash
composer require kodhe/typography
```

Persyaratan: PHP >= 8.1. Tidak ada dependensi eksternal.

## Quick Start

```php
<?php

declare(strict_types=1);

use Kodhe\Framework\Typography\Typography;

$typo = new Typography();

echo $typo->auto_typography('Halo dunia! Ini tulisan kedua...');
// <p>Halo dunia! Ini tulisan kedua&hellip;</p>
```

## Struktur Direktori

```
src/
├── Typography.php              # Kelas utama (API kompatibel CI3)
├── Contracts/                  # FormatterInterface, ParserInterface
├── Formatters/                 # CharacterFormatter, ParagraphFormatter
├── Parsers/                    # HtmlParser, TextParser
├── Support/                    # HtmlProtect, RegexCache
├── ValueObjects/TypographicConfig.php
├── Factory/
├── helpers/
└── Exceptions/TypographyException.php
```

## Penggunaan

### 1. Auto-typography penuh

```php
$str = "Baris pertama.\n\nBaris kedua setelah paragraf kosong.";
echo $typo->auto_typography($str);
// <p>Baris pertama.</p>\n\n<p>Baris kedua setelah paragraf kosong.</p>

// Redam newline berlebih (>2 jadi 2):
echo $typo->auto_typography($str, true); // argumen $reduce_linebreaks
```

Yang dilakukan `auto_typography()`:
- bungus blok teks dengan `<p> ... </p>`
- `<br />` untuk baris baru di dalam `<pre>`
- tambah satu spasi setelah `.` `!` `?` `:` `;` bila hilang
- ganti tiga titik `...` menjadi `&hellip;`
- format angka ≥ 1 juta dengan titik ribuan (`2000000` → `2.000.000`)
- rapikan spasi ganda di awal paragraf

### 2. Hanya perbaikan karakter (tanpa membuat `<p>`)

```php
echo $typo->format_characters('Angka 1000000 dan ellipsis...');
// Angka 1000000 -> tetap; '...' -> '&hellip;' (sesuai aturan karakter)
```

### 3. nl2br yang menghormati `<pre>`

```php
echo $typo->nl2br_except_pre("baris1\nbaris2");   // baris1<br />baris2
// tapi di dalam blok <pre>, \n dibiarkan apa adanya
```

### 4. Proteksi kutip berkurator `{}`

```php
$typo->protect_braced_quotes = TRUE;   // default FALSE
echo $typo->protect_braced_quotes($html);
// simpan {...} dari manipulasi regex, lalu kembalikan via pemanggilan balik
```

### 5. Konfigurasi elemen yang dikenai/dilewati

```php
$typo->initialize([
    // daftar tag yang dianggap block element (regex alternation)
    'block_elements'      => 'address|blockquote|div|h\\d|p|pre|table|ul',
    // tag yang isinya tidak boleh diformat ulang
    'skip_elements'       => 'p|pre|ol|ul|dl|table|h\\d',
    'inline_elements'     => 'a|abbr|b|br|code|em|i|img|span|strong',
    'inner_block_required'=> ['blockquote'],
    'protect_braced_quotes' => true,
]);

print_r($typo->getConfig());  // array config aktif
```

## Referensi API (ringkas)

| Method | Keterangan |
|---|---|
| `__construct(array $config = [])` | Buat instance, opsional langsung `initialize()` |
| `initialize(array $config)` | Set konfigurasi (sinkron ke properti publik lama) |
| `getConfig(): array` | Ambil config aktif |
| `auto_typography($str, $reduce_linebreaks = false)` | Format penuh → HTML |
| `format_characters($str)` | Perbaikan karakter/angka saja |
| `nl2br_except_pre($str)` | `nl2br` kecuali di dalam `<pre>` |
| `protect_braced_quotes($str)` | Lindungi `{...}` dari regex |

## Kompatibilitas CodeIgniter 3

Perilaku sama dengan `CI_Typography`; properti publik penyesuaian (`block_elements`, `skip_elements`, `inline_elements`, `last_block_element`, `protect_braced_quotes`) tetap ada sehingga kode lama jalan tanpa perubahan selain nama kelas/namespace.

## Catatan

- Hasil `auto_typography()` bukan pengganti sanitizer — jangan pakai untuk memfilter input tak tepercaya.
- Layer modular (`Formatters/`, `Parsers/`, `RegexCache`) internal; API stabil adalah kelas `Typography`.

## Pengujian

```bash
vendor/bin/phpunit --filter Typography
```
