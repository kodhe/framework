# Generator CRUD (`make:crud`)

`php console make:crud <NamaResource>` membuat **satu set lengkap CRUD** dalam
satu perintah — seperti `php artisan make:*` di framework modern. Hasil
generate-nya bisa mengikuti gaya yang Anda pilih:

- **Gaya routing**: kodhe (router modern, `Route::resource`) dan/atau CI3
  (legacy `$route[...]`) — via `--style`.
- **Engine view**: PHP native khas CI3 (`*.php`) atau Blade (`*.blade.php`)
  — via `--engine`.

Route **otomatis didaftarkan** ke file konfigurasi proyek, jadi tidak ada lagi
edit manual setelah generate. Cocok untuk: memindahkan cepat satu entitas dari
aplikasi CI3, atau membuat modul admin baru (list → create → edit → show →
delete) di proyek Kodhe.

## 1. Sintaks

```bash
php console make:crud <NamaResource> [field:tipe ...] [opsi]
```

| Bagian | Keterangan |
|---|---|
| `<NamaResource>` | Wajib. **UpperCamelCase**, bentuk tunggal: `Post`, `Article`, `ProductTag` |
| `field:tipe` | Opsional, boleh banyak. Daftar kolom tabel + model. Contoh: `title:string body:text published:bool` |
| `--style=kodhe\|ci3\|both` | Gaya route yang didaftarkan otomatis (default: `both`). `kodhe` → `app/Config/routes_modern.php`, `ci3` → `app/Config/routes.php` |
| `--engine=php\|blade` | Engine view hasil generate (default: `php`). `blade` menghasilkan `*.blade.php` yang me-resolve lewat ViewFactory Kodhe |
| `--force` | Timpa file yang sudah ada (default: generator berhenti tanpa menulis apa pun bila ada konflik) |
| `--path=` | Lokasi output custom (opsi global `make:*`) |

Alias invocation yang sama: `php console g:crud Post`, `php console generate crud Post`.

### Tipe field yang dikenali

| Ditulis | Jadi kolom (migration Forge) | Catatan |
|---|---|---|
| `string` *(default)* | `VARCHAR(255)` | Salah ketik tipe apa pun otomatis di-fallback ke `string` |
| `text` | `TEXT` | |
| `integer` / `int` | `INT(9)` | |
| `boolean` / `bool` | `TINYINT(1)` | |
| `date` / `datetime` / `decimal` | sesuai namanya | |

Kolom `id` (auto-increment primary key) serta `created_at`/`updated_at`
(`DATETIME`) ditambahkan otomatis — jangan deklarasikan ulang. Tanpa daftar
field, generator memakai contoh default `name:string`.

## 2. Artefak yang dihasilkan

Contoh: `php console make:crud Article title:string slug:string body:text published:bool`

| File | Isi |
|---|---|
| `app/Models/Article.php` | Model extends `Kodhe\Framework\Database\Model` (ORM CI3-compatible). `$table = 'articles'`, `$useTimestamps = true`, dan **`$allowedFields` terisi otomatis** dari daftar field (tanpa `id`) sehingga mass-assignment `insert()/update()` langsung aman dipakai |
| `app/Controllers/ArticleController.php` | Controller extends `Kodhe\Framework\Http\Controllers\BaseController` dengan aksi penuh: `index` `create` `store` `show` `edit` `update` `delete`. Nama class diberi suffix `Controller` agar tidak bentrok dengan Model bernama sama |
| `database/migrations/<timestamp>_create_articles_table.php` | Migration anonymous-class `up()/down()` memakai **stack database modern**: `Loader::dbforge(kodhe()->db, true)` + `add_field/add_key/create_table` (bukan `CI::load_dbutil` legacy) |
| `app/Views/articles/index.php` | Tabel listing + tombol Show/Edit/Delete (ekstensi `.blade.php` bila `--engine=blade`) |
| `app/Views/articles/create.php` | Form tambah (POST ke resource) |
| `app/Views/articles/edit.php` | Form ubah (POST ke `update/{id}`) |
| `app/Views/articles/show.php` | Detail satu record |
| `app/Views/articles/_form.php` | Field form bersama — **sudah terisi otomatis** sesuai daftar `field:tipe` yang Anda deklarasikan (`<input>`/`<textarea>`/`<select>` per kolom, lengkap dengan nilai lama untuk mode edit) |
| `app/Config/routes_modern.php` *(jika `--style=kodhe/both`)* | Route modern ditambahkan otomatis: `Route::resource(...)` + rute POST `update/{id}` dan DELETE `{id}` yang menunjuk method controller hasil generate |
| `app/Config/routes.php` *(jika `--style=ci3/both`)* | Peta `$route['articles/...']` gaya CI3 ditambahkan otomatis (7 pola URI: index/create/store/show/edit/update/delete) |

Penamaan otomatis: tabel = snake_case jamak (`ProductTag` → `product_tags`),
segmen URL = kebab-case jamak (`/product-tags`). Pluralizer masih sederhana —
cek ulang nama tabel/URL untuk kata tak beraturan (mis. `Category` →
`categories` benar, tetapi istilah teknis asing bisa salah) dan sesuaikan bila
perlu.

## 3. Route otomatis (--style) & engine view (--engine)

Generator **langsung mendaftarkan route** sesuai gaya yang dipilih — tidak perlu
edit manual lagi. File tujuan dibuat otomatis bila belum ada, dan penulisan
bersifat **idempoten**: setiap blok ditandai marker `/* make:crud:<res> */`,
sehingga menjalankan ulang perintah yang sama tidak menghasilkan duplikat
(output cukup `Routes already present`).

| `--style` | Ditulis ke | Isi yang ditambahkan |
|---|---|---|
| `kodhe` | `app/Config/routes_modern.php` | `Route::resource('articles', 'App\Controllers\ArticleController', ['except' => ['destroy']])` + `Route::post('/articles/update/{articles}', ...@update)` + `Route::delete('/articles/{articles}', ...@delete)` |
| `ci3` | `app/Config/routes.php` | 7 pola `$route['articles...'] = 'article/<aksi>'` gaya legacy CI3 |
| `both` *(default)* | kedua file di atas | keduanya sekaligus |

Catatan teknis:

- **Kenapa `except destroy` + rute DELETE manual?** `Route::resource` modern
  memanggil method `destroy()`, sedangkan controller hasil generate memakai
  konvensi CI3 `delete()`. Rute DELETE eksplisit menjembatani tanpa mengubah
  router `kodhe/http`.
- **Update via POST.** Router CI3 memetakan berdasarkan URI (satu array
  `$route`, tanpa pemisahan method), jadi bentuk legacy-nya `POST
  /articles/update/{id}`. Agar konsisten di kedua gaya, view hasil generate
  selalu mengirim form edit ke `/articles/update/{id}` dan generator ikut
  mendaftarkan `Route::post(.../update/{id})` di sisi modern. (`PUT
  /articles/{id}` standar resource tetap tersedia bila Anda ingin memakai
  `_method` spoofing.)
- **Modern routing aktif** hanya bila `$config['enable_modern_routing'] = TRUE`
  pada konfigurasi proyek; file `routes_modern.php` dimuat saat itu.
- Aksi tulis (`store/update/delete`) menerima **POST**; tombol hapus pada view
  listing sudah berupa `<form method="post">` sehingga cocok dengan route ini.

### Engine view

| `--engine` | Hasil | Kapan dipakai |
|---|---|---|
| `php` *(default)* | `app/Views/articles/*.php` — sintaks native PHP/CI3 (`<?= base_url(...) ?>`, `htmlspecialchars()`) | Proyek bergaya CI3 murni |
| `blade` | `app/Views/articles/*.blade.php` — direktif Blade (`{{ }}`, `@foreach`) | Proyek yang memakai ViewFactory Kodhe (default engine: blade) |

Controller hasil generate otomatis merujuk nama view dengan ekstensi yang benar
sesuai `--engine`, jadi tidak perlu penyesuaian tambahan.

Contoh kombinasi lazim:

```bash
# Proyek lama CI3 -> hanya routes.php, view native
php console make:crud Post title:string body:text --style=ci3

# Proyek Kodhe modern -> hanya routes_modern.php, view Blade
php console make:crud Post title:string body:text --style=kodhe --engine=blade

# Default: daftarkan kedua gaya routing, view native
php console make:crud Post title:string body:text
```

## 4. Alur kerja lengkap (contoh nyata)

```bash
# 1) Generate seluruh stack (route langsung terdaftar, form langsung terisi)
php console make:crud Article title:string slug:string body:text published:bool

# 2) Jalankan migration (via kodhe/migration)
php -r "require 'vendor/autoload.php';
$m = new Kodhe\Framework\Migration\Migration(['migrations_path' => __DIR__.'/database/migrations']);
$m->latest();"

# 3) Uji — tidak ada langkah edit manual lagi
php console serve
# buka http://localhost:8080/articles
```

## 5. Catatan penting

- **Validasi & CSRF belum otomatis.** Aksi `store()/update()` memanggil
  `$this->input->post(NULL, TRUE)` lalu langsung `insert()`. Untuk produksi,
  tambahkan `$this->load->library('form_validation')` (atau paket
  `kodhe/validation`) di controller, dan aktifkan CSRF seperti panduan
  [keamanan](../concepts/security.md).
- **Migration stub memakai komponen modern** `Kodhe\Framework\Database\Loader::dbforge()`
  — bukan dbutil CI3. Layer ORM CI3-style pada Model dipertahankan sebagai
  kompatibilitas legacy; kode baru sebaiknya menarget API paket hasil refactor.
- **Perubahan berikutnya jangan di-generate ulang.** Setelah file hasil CRUD
  dikustomisasi, regenerasi dengan `--force` akan menimpa kustomisasi Anda —
  gunakan `make:model`/`make:controller` terpisah untuk iterasi manual.
- Generator bersifat atomik sebelum menulis: bila salah satu dari 8 file sudah
  ada, tidak ada file yang ditulis (pesan error menyebut file mana).

## Tautan

- Cara bootstrap console & command custom: [cli-console](../concepts/cli-console.md)
- Standar tiap artefak (Model/Controller/View): [app-components](../concepts/app-components.md)
- Eksekusi migration: [libraries/migration](../libraries/migration.md)
- Sumber teknis generator: [`framework/src/Console/Commands/MakeCommand.php`](../../framework/src/Console/Commands/MakeCommand.php)
