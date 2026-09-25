# Generator CRUD (`make:crud`)

`php console make:crud <NamaResource>` membuat **satu set lengkap CRUD** dalam
satu perintah — seperti `php artisan make:*` di framework modern, tetapi hasil
generate-nya tetap bergaya CI3/native PHP khas Kodhe Framework (tanpa Blade,
tanpa atribut router).

Cocok untuk: memindahkan cepat satu entitas dari aplikasi CI3, atau membuat
modul admin baru (list → create → edit → show → delete) di proyek Kodhe.

## 1. Sintaks

```bash
php console make:crud <NamaResource> [field:tipe ...] [opsi]
```

| Bagian | Keterangan |
|---|---|
| `<NamaResource>` | Wajib. **UpperCamelCase**, bentuk tunggal: `Post`, `Article`, `ProductTag` |
| `field:tipe` | Opsional, boleh banyak. Daftar kolom tabel + model. Contoh: `title:string body:text published:bool` |
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
| `app/Views/articles/index.php` | Tabel listing + tombol Show/Edit/Delete |
| `app/Views/articles/create.php` | Form tambah (POST ke resource) |
| `app/Views/articles/edit.php` | Form ubah (POST ke `update/{id}`) |
| `app/Views/articles/show.php` | Detail satu record |
| `app/Views/articles/_form.php` | Field form bersama — **wajib Anda sesuaikan** setelah generate (lihat §4 langkah 3) |

Penamaan otomatis: tabel = snake_case jamak (`ProductTag` → `product_tags`),
segmen URL = kebab-case jamak (`/product-tags`). Pluralizer masih sederhana —
cek ulang nama tabel/URL untuk kata tak beraturan (mis. `Category` →
`categories` benar, tetapi istilah teknis asing bisa salah) dan sesuaikan bila
perlu.

## 3. Peta route

Generator **tidak** menulis route (rute adalah konfigurasi proyek). Tambahkan
sesuai gaya routing yang Anda pakai:

**Legacy CI3-style** (`application/config/routes.php`):

```php
$route['articles']              = 'article/index';
$route['articles/create']       = 'article/create';
$route['articles/store']        = 'article/store';         // POST
$route['articles/show/(:num)']  = 'article/show/$1';
$route['articles/edit/(:num)']   = 'article/edit/$1';
$route['articles/update/(:num)'] = 'article/update/$1';    // POST
$route['articles/delete/(:num)'] = 'article/delete/$1';    // POST
```

**Modern router** (`routes/web.php`): daftarkan tiap method controller per URI
di atas — pola segment persis sama dengan tabel komentar pada controller hasil
generate (`GET /articles`, `POST /articles`, `POST /articles/update/{id}`, dst).

> Konvensi: aksi tulis (`store/update/delete`) menerima **POST**. View listing
> sudah memakai `<form method="post">` untuk delete, jadi cocok dengan route ini.

## 4. Alur kerja lengkap (contoh nyata)

```bash
# 1) Generate seluruh stack
php console make:crud Article title:string slug:string body:text published:bool

# 2) Jalankan migration (via kodhe/migration)
php -r "require 'vendor/autoload.php';
$m = new Kodhe\Framework\Migration\Migration(['migrations_path' => __DIR__.'/database/migrations']);
$m->latest();"

# 3) Sesuaikan _form.php — generator hanya menulis 1 input contoh;
#    tambahkan input per field yang Anda deklarasikan, mis.:
#    <input type="text" name="title" value="...$article->title...">
#    <textarea name="body">...</textarea>
#    <select name="published"><option value="1">Ya</option><option value="0">Tidak</option></select>

# 4) Daftarkan route (lihat §3)

# 5) Uji
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
