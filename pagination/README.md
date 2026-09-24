# Kodhe Pagination

Package pagination hasil refaktor library `Pagination` CodeIgniter 3, dengan namespace `Kodhe\Framework\Pagination`. Selain API CI3 yang utuh (`initialize()` + `create_links()`), package ini menambahkan lapisan modular: **renderer** (default / Bootstrap / Tailwind) dan **URL builder** (URI segment / query string) yang bisa dipilih lewat konfigurasi, plus cache link internal.

## Instalasi

```bash
composer require kodhe/pagination
```

Persyaratan: PHP >= 8.1. Tidak ada dependensi eksternal.

## Quick Start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Kodhe\Framework\Pagination\Pagination;

$config = [
    'base_url'   => 'http://localhost/artikel/index/',
    'total_rows' => 200,          // total seluruh baris data
    'per_page'   => 10,           // baris per halaman
    'num_links'  => 2,            // jumlah link tetangga di kiri/kanan halaman aktif
    'uri_segment'=> 3,            // posisi segmen URI yang memuat nomor halaman
];

$pagination = new Pagination($config);

echo $pagination->create_links();
// <ul class="pagination">... <li><a href=".../index/2">2</a></li> ...</ul>
```

## Struktur Direktori

```
src/
├── Pagination.php              # Kelas utama (API kompatibel CI3)
├── Contracts/                  # RendererInterface, UrlBuilderInterface
├── Renderers/                  # DefaultRenderer, BootstrapRenderer, TailwindRenderer
├── Url/                        # SegmentUrlBuilder, QueryStringUrlBuilder
├── Factory/RendererFactory.php
├── Support/                    # PaginationBuilder, AttributeHelper, LinkCache
├── ValueObjects/               # LinkData, PaginationConfig
├── language/pagination_lang.php
└── helpers.php                 # render_pagination() — helper Bootstrap 5 cepat
```

## Penggunaan

### 1. Gaya CI3 (initialize terpisah)

```php
$pagination = new Pagination();
$pagination->initialize([
    'base_url'   => site_url('artikel/index/'),
    'total_rows' => $this->artikel_model->count_all(),
    'per_page'   => 10,
    'cur_page'   => (int) $this->uri->segment(3), // opsional; default dibaca dari uri_segment
]);

$data['links'] = $pagination->create_links();
```

### 2. Halaman via query string (`?page=N`)

```php
$pagination = new Pagination([
    'base_url'             => 'http://localhost/artikel',
    'total_rows'           => 200,
    'per_page'             => 10,
    'page_query_string'    => true,
    'query_string_segment' => 'page',   // default 'per_page', ubah ke 'page'
    'use_page_numbers'     => true,
]);
echo $pagination->create_links();       // http://localhost/artikel?page=2
```

### 3. Memilih renderer (Bootstrap / Tailwind)

Properti publik `renderer_name` dibaca saat `initialize()`:

```php
$pagination = new Pagination();
$pagination->renderer_name = 'bootstrap';  // atau 'tailwind', default 'default'
$pagination->initialize($config);
echo $pagination->create_links();
```

- `default` — markup polos dengan tag pembuka/penutup dari config CI3 (`full_tag_open`, `cur_tag_open`, dst.)
- `bootstrap` — `<nav><ul class="pagination">…</ul></nav>` siap pakai Bootstrap 5
- `tailwind` — markup utility-class Tailwind

### 4. Kustomisasi label & tampilan (kompatibel CI3)

```php
$pagination->initialize([
    'base_url'        => 'http://localhost/produk/index/',
    'total_rows'      => 500,
    'per_page'        => 25,
    'first_link'      => '&laquo; Awal',
    'last_link'       => 'Akhir &raquo;',
    'next_link'       => 'Berikutnya',
    'prev_link'       => 'Sebelumnya',
    'full_tag_open'   => '<nav class="mx-auto">',
    'full_tag_close'  => '</nav>',
    'cur_tag_open'    => '<span class="badge bg-primary">',
    'cur_tag_close'   => '</span>',
    'display_pages'   => true,
    'anchor_class'    => 'link-underline',
]);
```

### 5. Helper cepat Bootstrap 5

Untuk proyek CI3 dengan instance `kodhe()`, helper prosedural tersedia:

```php
echo render_pagination(site_url('artikel'), 200, 10, 'page');
```

(Membutuhkannya di-load manual: `require` `pagination/src/helpers.php` atau via loader helper framework.)

## Konfigurasi

| Properti | Default | Keterangan |
|---|---|---|
| `base_url` | `''` | URL dasar pagination |
| `total_rows` | `0` | Total baris data (wajib) |
| `per_page` | `10` | Baris per halaman |
| `num_links` | `2` | Jumlah link nomor di sekitar halaman aktif |
| `cur_page` | `0` | Halaman aktif (0 = baca otomatis) |
| `uri_segment` | `3` | Segmen URI penyimpan nomor halaman |
| `use_page_numbers` | `false` | `true`: link berupa nomor halaman; `false`: offset |
| `page_query_string` | `false` | `true`: gunakan query string, bukan URI segment |
| `query_string_segment` | `'per_page'` | Nama parameter query string |
| `first_link`/`next_link`/`prev_link`/`last_link` | CI3 | Label navigasi |
| `*_tag_open`/`*_tag_close` | `''` | Pembungkus tiap bagian |
| `first_url` | `''` | URL khusus untuk halaman pertama |
| `display_pages` | `true` | Sembunyikan daftar nomor halaman |
| `prefix` / `suffix` | `''` | Tambahan sebelum/sesudah angka halaman |
| `reuse_query` | CI3 | Pertahankan parameter GET lain pada link |
| `renderer_name` | `'default'` | `default` \| `bootstrap` \| `tailwind` |

## Referensi API (ringkas)

| Method | Keterangan |
|---|---|
| `__construct(array $params = [])` | Buat instance, optional langsung `initialize()` |
| `initialize($params = [])` | Set konfigurasi & siapkan renderer/url-builder; return `$this` |
| `create_links()` | Generate HTML pagination (string) |
| `parse_string($template, $data, $return)` | Render template sederhana dengan data |

## Kompatibilitas CodeIgniter 3

Semua properti dan perilaku `CI_Pagination` dipertahankan — drop-in. Yang baru hanya `renderer_name` dan komponen modular internal; jika tidak dipakai, output identik dengan CI3.

## Catatan

- Cache link (`LinkCache`) aktif bawaan (`enable_cache = true`) dan dibersihkan tiap `initialize()`.
- `create_links()` mengembalikan string kosong bila `total_rows <= 0` atau hanya 1 halaman.
- Layer modular (`Renderers/`, `Url/`, `Support/`) masih berkembang; API stabil adalah `Pagination` + helper.

## Pengujian

```bash
vendor/bin/phpunit --filter Pagination
```
