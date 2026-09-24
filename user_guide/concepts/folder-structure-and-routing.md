# Struktur Folder & Router (Perbedaan dari CI3)

> ⚠️ **Isu migrasi yang perlu diketahui.** Kodhe Framework punya konvensi
> penamaan folder dan sistem routing sendiri yang **berbeda dari CodeIgniter 3**.
> Halaman ini memetakan perbedaannya secara jujur beserta strategi
> koeksistensi/adapter. Terkait prosedur pemindahan aplikasi, lihat
> [Migrasi dari CodeIgniter 3](migrating-from-ci3.md).

## 1. Perbandingan struktur folder

| Area | CodeIgniter 3 | Kodhe (konvensi modern) |
|---|---|---|
| Root aplikasi | `application/` (APPPATH) | `app/` — `MakeCommand` menghasilkan controller ke `app/Controllers`, model ke `app/Models`, command ke `app/Console/Commands` (`framework/src/Console/Commands/MakeCommand.php`) |
| Huruf folder | semua **lowercase**: `controllers/`, `models/`, `core/` | **PascalCase huruf besar di awal**: `Controllers/`, `Models/`, `Console/` |
| Underscore pada nama | banyak: `libraries/form_validation.php`, `core/Security.php` dst. | **tanpa `_`** — kelas & direktori PSR-4 PascalCase (`FormValidation`, `ImageLib`). Nama bergaya underscore hanya dipakai internal kernel legacy (`framework/src/Support/Legacy/...`) |
| Routing | satu file: `application/config/routes.php` (array `$route`) | dua jalur: `routes.php` gaya CI3 (legacy) **plus** route modern `APPPATH.'routes/web.php'`, `routes/api.php`, `routes/console.php`, dan `routes/web.php` / `routes/api.php` per modul |
| Views | `application/views/` | `Kodhe\Framework\View\Parser` membaca path yang Anda berikan — tidak ada folder wajib; konvensi modern menaruhnya di bawah `app/Views` |
| Namespace controller | global (`class Welcome extends CI_Controller`) | `App\Controllers\...` (PSR-4, butuh autoload mapping manual di composer.json karena kernel tidak mendaftarkan prefix `App\` secara otomatis) |

Konsekuensi penting: **di filesystem case-sensitive (Linux), `controllers/` ≠
`Controllers/`**. Aplikasi CI3 lama tetap dibaca kernel apa adanya (loader dan
`LegacyRouter` memakai `APPPATH.'controllers/'` dengan `ucfirst($class)`), tetapi
kode/modul baru sebaiknya mengikuti konvensi PascalCase agar cocok dengan router
modern dan `Autoloader` fleksibel.

## 2. Autoloader fleksibel meredam sebagian masalah

`Kodhe\Framework\Support\Autoloader` (kernel) sengaja dibuat toleran terhadap
gaya CI3. Untuk setiap prefix namespace yang terdaftar, ia mencoba pola
(`tryLoadClass()`):

1. Path PSR-4 asli (folder PascalCase);
2. Semua bagian lowercase (gaya CI3);
3. Folder lowercase saja;
4. Gaya underscore CI3 (`Product_Reviews` → `product_reviews.php`);
5. Variasi controller berakhiran `Controller` (`HomeController` → `Home.php`);
6. Pencarian **case-insensitive** sebagai upaya terakhir (scandir per segmen).

Jadi `App\Controllers\ProductController` bisa tetap ter-load walau foldernya
produk lama bernama `controllers/product.php`. Batasannya: toleransi ini milik
autoloader kernel — bila Anda berjalan murni per-paket tanpa `kodhe/framework`,
yang berlaku adalah PSR-4 kaku Composer, sehingga **nama folder & kelas harus
persis** sesuai namespace.

## 3. Router Kodhe: dua mesin dalam satu kelas

`Kodhe\Framework\Http\Routing\Router` (`http/src/Routing/Router.php`) mewarisi
`LegacyRouter` (mesin CI3 murni) dan menambah lapisan modern. Konfigurasinya:

```php
'enable_modern_routing' => true,   // Route::get()/post() dkk. (RouteCollection)
'enable_legacy_routing' => true,   // array $route gaya CI3 dari config/routes.php
'prefer_modern'         => true,   // modern dicocokkan lebih dulu, fallback legacy
'cache_routes'          => ENVIRONMENT === 'production',
'auto_detect_namespace' => true,   // URI berisi '\' → FQCN controller langsung
'controller_suffix'     => '',
'default_404_controller'=> 'FileNotFound',
```

Alur resolusi request:

1. **Route modern** (`matchRequest()`): URI dicocokkan terhadap
   `RouteCollection` yang dimuat dari `routes/web.php|api.php|console.php` di
   APPPATH dan di setiap folder modul (`Modules::folders()`). Cache rute aktif
   di production — jangan lupa `clearCache()` setelah mengubah file route.
2. **Namespace eksplisit di URI**: segmen yang mengandung `\` di-resolve lewat
   `parseNamespaceSegments()` — separator aksi `::`, `@`, atau `/`; mis.
   `App\Controllers\Home@index`.
3. **Fallback legacy** (`locate()` → `LegacyRouter`): `$route['uri']` dari
   `config/routes.php`, lalu auto-detect controller dengan aturan `ucfirst()`:
   - module-aware: `/{module}/{controller}/{method}` via `Modules::locations`;
   - `controllers/{Segment}.php`, subfolder, pola folder-same-name, sampai
     percobaan filename lowercase;
   - default controller dicari pada nama `Home`, `Index`, `Main`, `Welcome`
     (`DEFAULT_CONTROLLER_NAMES`) — **berbeda dari CI3** yang hanya mengenal
     satu `default_controller` dari config;
   - `translate_uri_dashes` menggantikan `-`→`_` pada 3 segmen pertama.

Perbedaan perilaku yang paling sering mengejutkan pemigran CI3:

| Hal | CI3 | Kodhe Router |
|---|---|---|
| Sumber rute | hanya `config/routes.php` | `config/routes.php` + `routes/*.php` (modern) + routes per modul; modern menang duluan |
| Penulisan target | string `'kelas/metode'` | closure, `'Kelas@metode'`, `[Kelas::class, 'metode']`, atau FQCN dengan namespace |
| Default controller | 1 nama dari config | daftar kandidat `Home/Index/Main/Welcome` |
| Modul | tidak ada | segmen URI pertama = nama modul (`Modules`) |
| Cache | tidak ada | cache route collection di production |

## 4. Strategi koeksistensi saat migrasi

1. **Jangan rename folder CI3 lama** — kernel masih menunjuk
   `APPPATH.'controllers/'`. Biarkan struktur lama hidup apa adanya.
2. **Struktur baru mengikuti konvensi Kodhe**: buat `app/Controllers`,
   `app/Models` (PascalCase, tanpa `_`), daftarkan autoload-nya sendiri:
   ```json
   { "autoload": { "psr-4": { "App\\": "app/" } } }
   ```
   lalu `composer dump-autoload -o`.
3. **Jembatani URL lama → kelas baru lewat route modern**, bukan lewat rename:
   ```php
   // app/routes/web.php (APPPATH.'routes/web.php')
   use Kodhe\Framework\Http\Routing\Route;
   use App\Controllers\Produk;

   Route::get('produk', [Produk::class, 'index']);
   Route::get('produk/(:num)', [Produk::class, 'show']);
   ```
   Dengan `prefer_modern = true`, rute ini menang atas auto-detect legacy,
   sehingga URL CI3 lama tetap hidup sambil kode pindah ke `App\Controllers`.
4. **Nama method/kelas tanpa underscore**: tulis `ProductReviews`, bukan
   `Product_reviews`. Autotoleransi kernel menutupi kekurangan ini di mode
   compat, tetapi tidak di mode paket murni (PSR-4 kaku).
5. **Sadari benturan nama**: karena `LegacyRouter` melakukan `ucfirst()` pada
   segmen URI, `GET /produk` mencari `Produk.php`; bila file lama bernama
   lowercase `produk.php`, Linux-case-sensitivity dapat menggagalkan lookup —
   solusinya rute modern eksplisit (poin 3) atau rename file mengikuti kelas.

## 5. Ringkasan risiko (known issues)

- Inkonsistensi kapitalisasi `controllers/` (legacy runtime) vs `Controllers/`
  (scaffold `make:controller` & MakeCommand modern) — dua lokasi berbeda bisa
  terbentuk dalam satu aplikasi; putuskan mana yang authoritative per proyek.
- Prefix namespace `App\` **tidak didaftarkan otomatis** oleh kernel; tanpa
  psr-4 di composer.json, controller hasil `make:controller` tidak akan
  ter autoload di mode paket murni.
- Cache route di production membuat perubahan `routes/*.php` tidak terlihat
  sampai cache dibersihkan (`Router::clearCache()`); file cache berada di
  `{cache_path}/routes.cache.php` (default `STORAGEPATH.'cache/'`, lihat
  `RouteCollection::__construct()`).
- `isValidMethod()` pada Router saat ini selalu mengembalikan `true` — validasi
  method eksis terjadi belakangan (saat eksekusi), sehingga salah ketik method
  baru muncul error di tahap yang lebih lambat daripada CI3.

Bacaan terkait: [arsitektur](architecture.md) ·
[peta namespace](namespaces-migration.md) ·
[migrasi dari CI3](migrating-from-ci3.md) ·
[troubleshooting](../general/troubleshooting.md)
