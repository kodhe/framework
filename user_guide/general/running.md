# Menjalankan Aplikasi

## Bootstrap minimum (mode kernel CI3-compat)

```php
// index.php aplikasi
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/application/config/config.php'; // array ala CI3
$app    = Kodhe\Framework\Foundation\Application::boot($config);
$app->run();
```

Kernel membaca konfigurasi gaya CI3 (`base_url`, `sess_driver`, `$db['default']`, …)
lalu mendaftarkan service melalui container (`framework/src/Config/Setup.php`).

## Mode paket murni (tanpa kernel)

Bila tidak butuh compat-layer, pakai paket langsung seperti library biasa:

```php
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Pagination\Pagination;

$req  = Request::fromGlobals();
$page = new Pagination(['total_rows' => 500, 'per_page' => 20]);
echo $page->create_links();
```

## Server development

```bash
php -S localhost:8080 -t public
```

Untuk XAMPP/Laragon (`/var/www/html/...`): pastikan `DocumentRoot` menunjuk folder
`public/` (bukan root repo), dan `index.html` bawaan tidak menutupi `index.php`.

## Checklist sebelum deploy dev

1. `composer install --no-dev` (atau `composer dump-autoload -o` bila vendor sudah ada).
2. Konfigurasi session: `sess_driver` + `sess_save_path` TIDAK boleh kosong — lihat
   [troubleshooting](troubleshooting.md).
3. Tabel database pendukung dibuat (sessions, ci_migrations) sesuai README paket.
4. `.env`/config credential tidak ikut ke git.
