# Pagination Library

Paket: `kodhe/pagination` — kelas utama `Kodhe\Framework\Pagination\Pagination`.

## Quick Start

```php
use Kodhe\Framework\Pagination\Pagination;

$config = [
    'base_url'   => 'https://example.com/listings/',
    'total_rows' => 1000,
    'per_page'   => 25,
    'cur_page'   => (int) ($_GET['page'] ?? 1),
];
$pagination = new Pagination($config);
echo $pagination->create_links(); // "<ul class='pagination'>…"
$page = $pagination->cur_page();
$offset = ($page - 1) * $config['per_page'];
```

## Inti API

| Method | Fungsi |
|---|---|
| `initialize(array|object $params)` | (re)konfigurasi instance |
| `create_links(): string` | Render HTML link halaman |
| `cur_page(): int` | Halaman aktif saat ini |
| `total_pages(): int` | Jumlah halaman |

Renderer dapat diganti lewat config `query_string_segment`, `page_query_string`,
dsb. Detail lengkap & contoh tema: [README paket](../../pagination/README.md).
