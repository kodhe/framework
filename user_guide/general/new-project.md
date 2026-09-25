# Membuat Proyek Baru (`php console new`)

Perintah `new` pada Kodhe Console mem-*scaffold* kerangka aplikasi Kodhe Framework
yang siap jalan: struktur folder standar (PascalCase, tanpa `_`), `composer.json`
dengan PSR-4 `App\`, file bootstrap, konfigurasi, template `.env`, plus kode starter
(controller/model/view/route) sehingga `php console serve` langsung berfungsi.

Dokumen ini mencakup perintah `new` dan `serve` yang menjadi pasangan baku
untuk memulai proyek secara lokal.

---

## 1. Prasyarat

| Kebutuhan | Keterangan |
|---|---|
| PHP | `>= 8.1` |
| Composer | untuk memasang dependensi setelah scaffold |
| Kodhe Console | tersedia dari checkout framework (`bin/console`) atau dari paket `kodhe/framework` |

Anda **tidak** perlu proyek existing — `new` membuat direktori target dari nol.

## 2. Perintah dasar

```bash
php console new my-app
```

Alias yang dikenali: `new:project`, `create-project`.

```bash
php console create-project my-app     # sama dengan: php console new my-app
```

### Opsi

| Opsi | Fungsi |
|---|---|
| `--force` | Scaffold ke dalam direktori yang sudah ada dan **tidak kosong** (file hasil generate akan menimpa file bernama sama) |
| `--min` | Kerangka minimal: hanya folder + config, **tanpa** kode demo (Home controller, Base model, view welcome). Sebagai gantinya dibuat file `.gitkeep` di folder kosong |

Contoh:

```bash
php console new my-app --min          # skeleton bersih, tanpa demo
php console new . --force             # isi direktory current yang sudah ada
```

Jika direktori target sudah berisi file dan `--force` tidak diberikan, perintah
berhenti dengan pesan error dan *exit code* `1`.

## 3. Struktur hasil generate

```
my-app/
├── app/
│   ├── Config/
│   │   ├── config.php            ← konfigurasi utama (routing, session, dsb.)
│   │   ├── routes.php            ← rute gaya CI3 (legacy router)
│   │   └── routes_modern.php     ← rute router modern (Route:: facade)
│   ├── Controllers/
│   │   └── Home.php              ← App\Controllers\Home (dilewati jika --min)
│   ├── Models/
│   │   └── Base.php              ← model contoh (dilewati jika --min)
│   ├── Middleware/               ← (kosong pada mode demo)
│   ├── Libraries/
│   └── Views/
│       └── home/index.php        ← view welcome (dilewati jika --min)
├── bin/
├── database/
│   └── migrations/
├── public/
│   └── index.php                 ← front controller
├── storage/
│   ├── Cache/  Logs/  Session/  Uploads/
├── tests/
├── console                       ← entry point console proyek (chmod +x)
├── composer.json                 ← psr-4 "App\\": "app/", require kodhe/framework ^1.0
├── .env / .env.example           ← APP_*, DB_*, SESS_SAVE_PATH
├── .gitignore
└── readme.md
```

Catatan penting:

- Nama proyek di `composer.json` diturunkan dari nama folder
  (huruf kecil, karakter ilegal → `-`), prefix tetap `app/<nama>`.
- `console` milik proyek mencari `vendor/autoload.php` di root proyek
  (fallback satu level ke atas), jadi ia bekerja setelah `composer install`.
- Konvensi folder mengikuti standar Kodhe: **PascalCase, tanpa underscore**
  (lihat [folder-structure-and-routing](../concepts/folder-structure-and-routing.md)).

## 4. Isi konfigurasi bawaan

### `.env`

```ini
APP_NAME=Kodhe App
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_HOST=127.0.0.1
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=kodhe_app
DB_DRIVER=mysql

SESS_SAVE_PATH=storage/Session
```

`SESS_SAVE_PATH` wajib menunjuk folder yang dapat ditulis saat memakai driver
session file — nilai default `storage/Session` sudah disediakan.

### `app/Config/config.php`

Berisi default aman untuk pengembangan: `index_page` kosong (URL bersih),
router modern aktif berdampingan dengan legacy router, dan
`cache_routes` hanya enabled saat `ENVIRONMENT === 'production'`.

### Routes

- `routes.php` — gaya CI3: `$route['default_controller'] = 'Home/index';`
  dilayani legacy router.
- `routes_modern.php` — gaya fluent: `Route::get('/', 'App\Controllers\Home@index');`
  Router modern diperiksa lebih dulu ketika `enable_modern_routing` aktif,
  sehingga kedua file bisa koeksisten. Detail: [services-modules-routing](../concepts/services-modules-routing.md).

### Kode starter

`App\Controllers\Home` extends `BaseController` dengan method `index()`
(menampilkan `view('home/index', ...)`) dan `hello(string $name)` — cukup untuk
memverifikasi routing, controller, dan view berjalan sebelum Anda menulis kode sendiri.
Standar penulisan komponen: [app-components](../concepts/app-components.md).

## 5. Menjalankan server dev (`serve`)

```bash
cd my-app
composer install          # pasang kodhe/framework + generate autoloader
php console serve         # http://localhost:8080
```

| Opsi | Default | Keterangan |
|---|---|---|
| `--host=` | `localhost` | Antarmuka/address binding (mis. `0.0.0.0` agar diakses dari jaringan) |
| `--port=` | `8080` | Port TCP; validasi 1–65535, selain itu error |

Di balik layar `serve` menjalankan *PHP built-in server* dengan
`public/index.php` sebagai router script, sehingga semua request masuk lewat
front controller. Contoh:

```bash
php console serve --host=0.0.0.0 --port=3000
```

Script composer juga tersedia: `composer serve`.

## 6. Alur cepat dari nol sampai halaman pertama

```bash
php console new toko-saya && cd toko-saya
composer install
php console serve
# buka http://localhost:8080 → welcome page dari App\Controllers\Home@index
```

Lanjutkan mengembangkan dengan generator kode:

```bash
php console make:controller Blog    # app/Controllers/Blog.php (namespace App\Controllers)
php console make:model Post         # app/Models/Post.php
php console make:migration create_posts_table
php console make:middleware Auth
php console make:crud Post title:string body:text   # satu set CRUD lengkap (model+controller+migration+views)
```

Daftar lengkap perintah scaffold: [cli-console](../concepts/cli-console.md).
Panduan step-by-step generator CRUD: [generator-crud](crud-generator.md).

## 7. Catatan & keterbatasan

- `new` **tidak** menjalankan `composer install` otomatis — dependensi dipasang
  manual sesuai instruksi "Next steps" yang dicetak perintah.
- File yang digenerate ditimpa diam-diam bila sudah ada (khususnya saat `--force`);
  commit/backup dulu direktori target Anda.
- Untuk migrasi proyek CI3 yang sudah ada (bukan proyek baru), jangan pakai `new`;
  ikuti prosedur [migrating-from-ci3](../concepts/migrating-from-ci3.md) — `new`
  berguna untuk menyiapkan kerangka Kodhe murni sebagai tujuan migrasi.

---

**Lihat juga:** [installation](../concepts/installation.md) ·
[running](running.md) · [troubleshooting](troubleshooting.md)
