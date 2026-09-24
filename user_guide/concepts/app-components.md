# Komponen Aplikasi Standar Kodhe (Controller, Model, Library, View/Template)

Halaman ini adalah **standar penulisan komponen aplikasi** di Kodhe Framework —
folder, namespace, dan kelas dasar yang benar — beserta **cara memigrasikan tiap
komponen dari CodeIgniter 3**. Untuk strategi migrasi menyeluruh lihat
[migrating-from-ci3](migrating-from-ci3.md); untuk perbedaan struktur folder &
router lihat [folder-structure-and-routing](folder-structure-and-routing.md).

## Ringkasan Cepat

| Komponen | Standar Kodhe | Support Legacy CI3 | Scaffold CLI |
|---|---|---|---|
| Controller | `app/Controllers/*.php`, namespace `App\Controllers`, extend `Kodhe\Framework\Http\Controllers\BaseController` | `$this->load` tetap tersedia via `Controller` + `LegacyLoader` | `make:controller` |
| Model | `app/Models/*.php`, namespace `App\Models`, extend `Kodhe\Framework\Database\Model` | Extend `CI_Model` lama jalan apa adanya | `make:model` |
| Library | Instansiasi langsung `new Kodhe\Framework\<Paket>\<Class>()` atau container | `$this->load->library('x')` dipetakan otomatis oleh `LegacyLoader` | — |
| View/Template | Helper global `view()` → `Kodhe\Framework\View\ViewFactory` (PHP/Blade/Twig, layout, theme) | `$this->load->view()` tetap jalan; folder view lama dapat dipakai ulang | — |

Semua nama folder Kodhe memakai **huruf kapital di awal dan tanpa underscore**
(`Controllers`, `Models`, `Middleware`) — autoloader kernel toleran terhadap
perbedaan case, tetapi mode paket murni PSR-4 menuntut kepatuhan penuh.

---

## 1. Controller

### Standar Kodhe

```php
<?php
// app/Controllers/Home.php
declare(strict_types=1);

namespace App\Controllers;

use Kodhe\Framework\Http\Controllers\BaseController;

class Home extends BaseController
{
    public function index()
    {
        return view('home/index', ['title' => 'Beranda']);
    }

    public function detail($id = null)
    {
        $item = \App\Models\ItemModel::find($id);
        return view('home/detail', ['item' => $item]);
    }
}
```

Fakta dari kode (`http/src/Controllers/`):

- `Controller` (base) menyiapkan `Facade` aplikasi, menyimpan referensi
  controller lama (`__legacy_controller`), dan memanggil
  `load->initialize()` sehingga `$this->load` (model/view/library/helper gaya
  CI3) **tetap berfungsi di dalam controller modern**.
- `BaseController` menambahkan beban otomatis yang opsional (helper `string`,
  library `user_agent`, registrasi `theme` bila paket `kodhe/view` terpasang).
  Setiap kegagalan bersifat *fail-soft* (paket boleh tidak terpasang).
- Tersedia juga `RESTController` sebagai base untuk API.

### Migrasi dari CI3

CI3:

```php
class Welcome extends CI_Controller {
    public function index() {
        $this->load->model('item_model');
        $data['items'] = $this->item_model->get_all();
        $this->load->view('welcome_message', $data);
    }
}
```

Langkah konversi:

1. Pindahkan file ke `app/Controllers/` dengan **nama file PascalCase sesuai
   class** (`Welcome.php` berisi `class Welcome`).
2. Tambahkan `namespace App\Controllers;` dan `use
   Kodhe\Framework\Http\Controllers\BaseController;`, ganti `extends
   CI_Controller` → `extends BaseController`.
3. Nama method publik tetap sama — router legacy maupun modern akan
   menemukannya. Router modern default mencari `Home/Index/Main/Welcome`
   sebagai controller halaman depan.
4. `$this->load->model()/view()/library()/helper()` **tidak wajib diubah** pada
   tahap pertama (dimediasi `LegacyLoader`). Konsolidasi bertahap:
   - model → instansiasi `App\Models\...` langsung / DI,
   - view → helper `view()`,
   - library → `new Kodhe\Framework\...` (lihat §3).
5. Daftarkan rute lama ke controller baru bila memakai router modern:

```php
Route::get('welcome', [App\Controllers\Welcome::class, 'index']);
```

> ⚠️ Jangan campur dua namespace controller dalam satu file, dan hindari
> underscore pada nama class (`item_model` → `ItemModel`) agar patuh PSR-4.

---

## 2. Model

### Standar Kodhe

Model modern ada di paket `kodhe/database`: `Kodhe\Framework\Database\Model`
(mewarisi `ORM\LegacyModel`) dengan properti deklaratif dan query chainable.

```php
<?php
// app/Models/ItemModel.php
declare(strict_types=1);

namespace App\Models;

use Kodhe\Framework\Database\Model;

class ItemModel extends Model
{
    protected $table          = 'items';
    protected $primaryKey     = 'id';
    protected $returnType     = 'object';      // atau 'array'
    protected $allowedFields  = ['name', 'price', 'category_id'];
    protected $useTimestamps  = true;           // created_at / updated_at
    protected $useSoftDeletes = false;          // deleted_at
    protected $validationRules = [];            // integrasi kodhe/validation
}
```

API utama (verifikasi dari `database/src/Model.php`):

- Query builder: `select() where() orWhere() whereIn() like() orderBy()
  limit() groupBy() having() join() leftJoin() rightJoin() inRandomOrder()`
- Relasi eager: `with(['category'])`
- Eksekusi: `all() findAll($limit,$offset) first() find($id) findOrFail($id)
  count() sum() avg() max() min() chunk($size, fn)`
- Write: `insert($data, $returnId) insertBatch() update($data, $where)
  updateBatch() delete($id) restore() forceDelete() truncate()`
- Upsert: `firstOrCreate() firstOrNew() updateOrCreate()`
- Tabel bisa diganti runtime: `$model->table('items_archive')`

Contoh pemakaian di controller:

```php
$cheap = App\Models\ItemModel::where('price', '<', 100)->orderBy('name')->findAll(20);
```

### Migrasi dari CI3

CI3:

```php
class Item_model extends CI_Model {
    public function get_all() {
        return $this->db->get('items')->result();
    }
}
```

Peta konversi:

| CI3 | Kodhe |
|---|---|
| `class Item_model extends CI_Model` (file `item_model.php`) | `class ItemModel extends Model` (file `app/Models/ItemModel.php`) |
| `$this->db->get('items')->result()` | `$this->findAll()` / `ItemModel::all()` |
| `$this->db->where(...)->get(...)->row()` | `$this->where(...)->first()` |
| `$this->db->get_where('items', ['id'=>$id])->row()` | `$this->find($id)` |
| `$this->db->insert('items', $data)` | `$this->insert($data)` (butuh `allowedFields`) |
| `$this->db->update('items', $data, ['id'=>$id])` | `$this->update($data, ['id' => $id])` |
| `$this->db->delete(...)` | `$this->delete($id)` / soft delete via `useSoftDeletes` |

Catatan:

- Model lama `extends CI_Model` **tetap jalan** di kernel compat — tidak harus
  dikonversi sekaligus. Konversikan per model saat menyentuhnya.
- Mass assignment modern dibatasi `$allowedFields`; kosong = ditolak. Saat
  mengonversi, daftarkan kolom writable Anda.
- Timestamp & soft delete menggantikan kebiasaan manual `date('Y-m-d H:i:s')`
  di CI3 — cukup aktifkan propertinya dan pastikan kolomnya ada di tabel.

---

## 3. Library

### Standar Kodhe

Setiap library CI3 telah menjadi paket Composer ber-namespace
`Kodhe\Framework\<Paket>\*`. Cara baku memakainya: **instansiasi langsung**
atau lewat service container — bukan `$this->load->library()` lagi.

```php
use Kodhe\Framework\Email\Email;

$email = new Email();
$email->from('a@b.c')->replyTo('a@b.c')->to('x@y.z')->subject('Hi')->message('...');
$email->send();
```

### Jalur kompatibel: `$this->load->library()`

`framework/src/Config/Loaders/LegacyLoader.php` berisi peta nama library CI3 →
kelas modern, sehingga kode lama tetap jalan tanpa sentuh:

| Pemanggilan CI3 | Kelas yang dimuat |
|---|---|
| `$this->load->library('email')` | `Kodhe\Framework\Email\Email` |
| `'session'` | `Kodhe\Framework\Session\Session` |
| `'form_validation'` | `Kodhe\Framework\Validation\Validation` |
| `'pagination'` | `Kodhe\Framework\Pagination\Pagination` |
| `'user_agent'` / `'agent'` | `Kodhe\Framework\Agent\UserAgent` |
| `'cache'` `'calendar'` `'cart'` `'encrypt'` `'encryption'` `'ftp'` `'image_lib'` `'table'` `'typography'` `'upload'` `'zip'` `'parser'` dll. | padanan `Kodhe\Framework\...` masing-masing paket |

Syaratnya: **paket terkait harus terpasang via Composer** (`composer require
kodhe/email`, dst.) — loader hanya memetakan nama, tidak menyediakan kodenya.
Library custom milik aplikasi (`application/libraries/Mylib.php`) dimuat seperti
biasanya oleh loader kernel (case-insensitive).

### Rekomendasi bertahap

1. Fase migrasi: biarkan `$this->load->library(...)` berjalan via LegacyLoader.
2. Fase konsolidasi: ganti ke `new Class()` / inject via container; hapus
   dependensi pada superobject. Detail di
   [migrating-from-ci3 §9](migrating-from-ci3.md).

---

## 4. View & Template

### Standar Kodhe

Paket `kodhe/view` menyediakan `ViewFactory` multi-engine dengan helper global
`view()` (dari `view/src/helpers/template.php`):

```php
// render langsung
view('home/index', ['title' => 'Halo']);

// dengan layout — argumen ke-4 view(), atau helper set_layout() sebelumnya
view('home/index', $data, false, 'layouts/main');
set_layout('layouts/main');   // berlaku untuk pemanggilan view() berikutnya

// kembalikan sebagai string
$html = view('emails/greeting', $data, true);
```

Fitur `ViewFactory` (verifikasi dari `view/src/`):

- **Engine**: `PhpEngine`, `BladeEngine`, `TwigEngine` — dipilih otomatis per
  file (`.php/.blade.php/.twig`) atau eksplisit: `render($view, $data, $return,
  $engine)`.
- **Layout**: `layout($name)` + `set($key, $value)` untuk data global template.
- **Theme**: aktif via config `theme_enabled`; path tema dicari di
  `APPPATH/themes` lalu `FCPATH/themes`; view tema diprioritaskan sebelum
  path biasa (`ThemeManager`, plus `AssetManager` untuk aset tema).
- **Varian device**: `VariantManager` (mobile/tablet/desktop).
- **Modul HMVC**: helper `module_view()` merender view milik modul.

Konfigurasi minimum:

```php
// config/view.php (dibaca ViewFactory)
'theme_enabled' => false,
'views_path'    => APPPATH . 'Views',   // folder view lama tetap dipakai
```

Folder view: Kodhe memakai `APPPATH/Views` (PascalCase, tanpa `_`), tetapi
kernel loader tetap menemukan `application/views` lama — jadi **file view CI3
tidak perlu direstrukturisasi pada tahap awal**; rename ke `Views/` dilakukan
saat konsolidasi.

### Migrasi dari CI3

| CI3 | Kodhe |
|---|---|
| `$this->load->view('page', $data)` | tetap jalan (loader), atau `view('page', $data)` |
| `$this->load->view('page', $data, TRUE)` | `view('page', $data, true)` mengembalikan string |
| `$this->load->vars($data)` / pass-by-reference | `ViewFactory::set($key, $value)` atau array data |
| `$config['pi']['twig']` dsb. | `ViewFactory` engine `twig`/`blade` via parameter/config |
| parser template `{data}` | paket `kodhe/parser` (`Parser`) tetap tersedia |
| duplicate render view | gunakan `$return=true` lalu simpan ke variabel |

Tidak ada perubahan sintaks di dalam file view (HTML + `<?php ?>` CI3 valid
di PhpEngine). Yang perlu dicek: pemanggilan `$this->` di dalam view CI3
(seperti `$this->security->xss_clean()`) — ganti dengan instansiasi paket
modern karena view Kodhe tidak terikat pada controller.

---

## 5. Middleware & Lainnya (ringkas)

- **Middleware**: `app/Middleware/` (namespace `App\Middleware`), implement
  `Kodhe\Framework\Http\Routing\Contracts\MiddlewareInterface` — panduan penuh
  di [services-modules-routing](services-modules-routing.md).
- **Helper custom**: letakkan di `app/Helpers/` (kernel) atau muat via
  `HelperManager`; pola CI3 `$this->load->helper('x')` tetap dilayani loader.
- **Migration & Seeder**: lihat [libraries/migration](../libraries/migration.md).

## 6. Scaffold Otomatis

Perintah CLI (diverifikasi dari `framework/src/Console/Commands/MakeCommand.php`)
membuat file langsung di lokasi standar Kodhe:

```bash
php kodhe make:controller Home        # -> app/Controllers/Home.php  (App\Controllers)
php kodhe make:model ItemModel        # -> app/Models/ItemModel.php  (App\Models)
php kodhe make:migration create_items # -> folder migrations
php kodhe make:middleware Auth        # -> app/Middleware/Auth.php   (App\Middleware)
php kodhe make:command sync_data      # -> app/Console/Commands      (App\Console\Commands)
```

Opsi: `--force` (timpa file yang ada), `--path=...` (lokasi custom).

> Cara bootstrap `bin/console`, membuat command custom, dan migrasi job cron
> `is_cli()` CI3 → lihat [cli-console](cli-console.md).

Agar `App\*` terbaca autoload, daftarkan di `composer.json` proyek:

```json
"autoload": { "psr-4": { "App\\": "app/" } }
```

lalu `composer dump-autoload`.

## Lihat Juga

- [Panduan migrasi dari CI3](migrating-from-ci3.md)
- [Struktur folder & routing](folder-structure-and-routing.md)
- [Service, modul & routing modern](services-modules-routing.md)
- [Arsitektur](architecture.md) · [Indeks library](../libraries/index.md)
