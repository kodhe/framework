# Migrasi dari CodeIgniter 3 ke Kodhe Framework

Panduan ini adalah jalur lengkap memindahkan aplikasi **CodeIgniter 3** ke
**Kodhe Framework**. Prinsip besarnya: Kodhe bukan framework baru yang harus
dipelajari dari nol — ia adalah library-library CI3 yang dimodernisasi menjadi
paket Composer ber-namespace `Kodhe\Framework\*`, dibungkus kernel
compatibility-layer agar kode CI3 lama tetap berjalan. Anda boleh bermigrasi
**bertahap**, modul per modul.

Bacaan terkait:
[peta namespace](namespaces-migration.md) ·
[arsitektur](architecture.md) ·
[instalasi](installation.md) ·
[menjalankan aplikasi](../general/running.md) ·
[troubleshooting](../general/troubleshooting.md)

## 0. Strategi: dua jalur, boleh dicampur

| Jalur | Cocok untuk | Cara |
|---|---|---|
| **A. Compat-layer (big-bang ringan)** | Aplikasi CI3 besar, ingin cepat jalan lagi di PHP 8 | Pasang kernel `framework/`, jalankan controller/model CI3 apa adanya; loader `$CI->load->library()` tetap berfungsi + alias `CI_*` otomatis |
| **B. Paket murni (incremental)** | Kode baru / refactor bertahap | `composer require kodhe/<paket>` lalu pakai kelas PSR-4 langsung (`use Kodhe\Framework\...`) tanpa loader |

Rekomendasi: mulai dari **A** supaya aplikasi hidup lagi, lalu pindahkan modul
per modul ke **B**. Keduanya bisa berjalan berdampingan dalam satu aplikasi
(loader dual-mode — lihat [arsitektur](architecture.md)).

## 1. Prasyarat & audit kode lama

Sebelum menyentuh composer, audit dulu:

```bash
# pola yang sudah mati di PHP 8
grep -rn "mysql_\|mcrypt_\|each(\|create_function" application/ | wc -l

# semua pemakaian library/loader CI3 yang perlu dipetakan
grep -rn "\$this->load->library(" application/controllers application/core | sort

# pemanggilan API legacy (email, session DB lama, dsb.)
grep -rn "reply_to\|set_mailtype\|lastactivity" application/
```

Pastikan aplikasi CI3 asli sudah jalan di **PHP 8.1+** dengan error reporting
strict — perbaiki dulu deprecated warning warisan PHP 7 (variabel tak
terdefinisi, `strlen(null)`, dll.) sebelum menambah lapisan migrasi.

## 2. Instalasi

Detail di [instalasi](installation.md). Ringkasnya:

```bash
# Jalur A — aplikasi full-stack (kernel + semua paket)
composer require kodhe/framework

# Jalur B — hanya paket yang dipakai
composer require kodhe/validation kodhe/session kodhe/http
```

Struktur folder aplikasi CI3 (`application/config`, `controllers`, `models`,
`views`, `migrations`) **tidak berubah** — kernel membacunya apa adanya.

## 3. Bootstrap aplikasi

Ganti bootstrap pada `index.php`. CI3:

```php
// CI3 asli
require_once '../application/vendor/codeigniter/core/CodeIgniter.php';
```

Kodhe (mode kernel):

```php
require_once __DIR__ . '/vendor/autoload.php';

$app = Kodhe\Framework\Foundation\Application::create();
$app->bootstrap();   // boot kernel: daftarkan services ke container
$app->run();         // tangani request globals → kirim response
```

Mode paket murni tidak butuh bootstrap sama sekali — cukup
`require 'vendor/autoload.php'` lalu instantiate kelas yang dibutuhkan
(lihat [running.md § Mode paket murni](../general/running.md)).

> `Application` mensyaratkan paket `kodhe/http`; bila belum terpasang Anda akan
> mendapat `RuntimeException` dengan instruksi `composer require kodhe/http`.

## 4. Konfigurasi: array CI3 diterima apa adanya

Kernel membaca konfigurasi gaya CI3 (`config.php`, `database.php`) dan
menerjemahkannya ke service container (`framework/src/Config/Setup.php`).
Anda TIDAK perlu menulis ulang file config. Yang wajib dicek/diubah:

| Kunci CI3 | Status di Kodhe |
|---|---|
| `base_url`, `index_page`, `uri_protocol` | ✅ sama |
| `sess_driver`, `sess_cookie_name`, `sess_expiration` | ✅ sama; **`sess_save_path` wajib terisi** (nama tabel utk driver `database`, folder utk `files`) |
| `$db['default']` (hostname, username, …) | ✅ sama; driver `mysqli`/`pdo` |
| `encryption_key` | ⚠️ hanya relevan utk paket `encrypt` lama; `encryption` modern memakai key sendiri — lihat [encrypt.md](../libraries/encrypt.md) |

## 5. Peta namespace & loader

Ini inti migrasi. Tabel lengkap nama-loader → FQCN → paket ada di
**[peta migrasi namespace](namespaces-migration.md)**. Pola umumnya:

| CI3 asli | Kodhe (kode baru) |
|---|---|
| `CI_Session`, `$this->load->library('session')` | `Kodhe\Framework\Session\Drivers\*` |
| `CI_Form_validation` | `Kodhe\Framework\Validation\FormValidation` (paket `kodhe/validation`) |
| `CI_Pagination` | `Kodhe\Framework\Pagination\Pagination` |
| `CI_Email` | `Kodhe\Framework\Email\Email` |
| `CI_Upload` | `Kodhe\Framework\Upload\Upload` |
| `CI_Image_lib` | `Kodhe\Framework\Image\Image` |
| `$this->db->…` (query builder) | `Kodhe\Framework\Database\*` |
| `Kodhe\Session\Session` (legacy Kodhe) | ❌ usang — selalu pakai prefix `Kodhe\Framework\` |

Aturan praktis:

1. Kode lama `$this->load->library('x')` tetap jalan via compat-layer — jangan
   sentuh dulu; biarkan loader me-resolve lewat `packageMap`.
2. Kode baru selalu `use Kodhe\Framework\<Paket>\<Class>;` — jangan pernah
   menulis `CI_*` atau `Kodhe\<Paket>` tanpa `Framework`.
3. Setelah mengganti FQCN: `composer dump-autoload -o`.

## 6. Perubahan API yang perlu dicari di kode

Sebagian method dinormalisasi ke camelCase dan beberapa config pindah tempat.
Cek README paket masing-masing (sumber kebenaran API); ringkasannya:

| Area | CI3 | Kodhe |
|---|---|---|
| Email | `$this->email->reply_to(…)`, `set_mailtype(…)` | `replyTo(…)`; mailtype jadi opsi konfigurasi |
| Session DB | kolom `lastactivity` | skema CI3 modern: `timestamp` (gc fallback masih mendukung kolom lama) — DDL di [troubleshooting §2](../general/troubleshooting.md) |
| Migration | butuh superobject `$CI` | `new Migration(['migrations_path' => APPPATH.'migrations'])` — lihat [migration.md](../libraries/migration.md) |
| Encrypt/Encryption | satu library | dua paket terpisah: `kodhe/encrypt` (warisan) & `kodhe/encryption` (modern); dekripsi data lama dengan `encrypt`, simpan ulang dengan `encryption` |
| Pagination | `$config` + `initialize()` | tetap kompatibel; tersedia juga factory via integrasi `kodhe/http` |

Setiap halaman di [libraries/](../libraries/index.md) punya seksi **Catatan
Migrasi** per paket — gunakan sebagai checklist saat grep-per-ganti.

## 7. Database, session, dan tabel pendukung

1. Buat/perbaiki tabel `ci_sessions` sesuai skema baru (lihat
   [troubleshooting §2](../general/troubleshooting.md)), lalu pastikan
   `sess_save_path` menunjuk namanya.
2. Tabel `ci_migrations` tetap dipakai paket `kodhe/migration`; file migration
   lama (`NNN_nama.php` berisi class `Migration_*`) **tidak perlu diubah**.
3. Query builder `$this->db` pada model CI3 berjalan di atas
   `Kodhe\Framework\Database\*` melalui container — tidak ada perubahan sintaks
   untuk usage standar (`get`, `where`, `insert_batch`, transactions).

## 8. Checklist verifikasi bertahap

Jalankan berurutan; tiap langkah harus hijau sebelum lanjut:

- [ ] `composer install` tanpa konflik; `composer dump-autoload -o`
- [ ] Halaman statis / route default tampil (kernel boot sukses)
- [ ] Controller CI3 lama merespons; cek log: tidak ada `Class "..." not found`
- [ ] Login/logout: session read-write OK (driver files dulu, lalu database)
- [ ] Upload gambar + image resizing (butuh `gd`/`imagick`)
- [ ] Kirim email via SMTP test
- [ ] `form_validation` pada form tersulit (rules custom callback)
- [ ] Migration `latest()` / `version()` pada DB staging
- [ ] Enkripsi: dekripsi data lama → enkripsi ulang → verifikasi
- [ ] Suite tes regresi / smoke-test manual end-to-end

Error yang paling sering muncul pada tahap ini sudah terdokumentasi beserta
solusinya di [troubleshooting](../general/troubleshooting.md).

## 9. Konsolidasi bertahap ke mode modern

Untuk setiap modul yang disentuh pasca-migrasi awal:

1. Ganti akses `$CI`/`$this->load` dengan instansiasi eksplisit:
   ```php
   use Kodhe\Framework\Validation\FormValidation;

   $validation = new FormValidation();
   $ok = $validation->run($post, 'login');
   ```
2. Bila helper bawaan kernel dipakai, pertimbangkan `HelperManager`
   (`framework/src/Support/Modern/HelperManager.php`) untuk lazy-loading
   helpers ala modern.
3. Bila satu area sudah 100% mode paket, hapus dependensi kernel-nya —
   aplikasi boleh akhirnya berjalan tanpa `framework/` sama sekali.

## 10. Rollback

Karena Jalur A tidak mengubah struktur aplikasi maupun file config, rollback
semudah mengembalikan `index.php` bootstrap ke versi CI3 asal dan melepas
dependensi composer. Data (session, ciphertext lama, tabel migrations) sengaja
dibuat kompatibel dua arah selama mungkin — kecuali hasil re-enkripsi dengan
`kodhe/encryption` (langkah 8), yang hanya terbaca oleh paket modern. Karena itu
lakukan re-enkripsi hanya setelah aplikasi stabil.
