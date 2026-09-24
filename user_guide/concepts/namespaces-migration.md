# Migrasi Namespace (CI3 → Kodhe Framework)

> Halaman ini adalah **peta referensi nama**. Untuk prosedur migrasi lengkap
> langkah demi langkah (instalasi, bootstrap, config, checklist verifikasi,
> rollback), lihat [Migrasi dari CodeIgniter 3](migrating-from-ci3.md).

Ada tiga generasi namespace. Kode aplikasi modern cukup mengenal yang ketiga.

| Generasi | Contoh | Status |
|---|---|---|
| CI3 asli | `CI_Session`, `$this->load->library('session')` | Masih jalan via alias loader |
| Legacy Kodhe | `Kodhe\Session\Session`, `Kodhe\Library\*` | **Usang** — tidak ter-autoload, jangan ditulis baru |
| Aktif (PSR-4) | `Kodhe\Framework\Session\DatabaseDriver` dst. | ✅ Standar semua paket |

## Peta nama library loader → kelas aktif

Loader lama (`$CI->load->library('<nama>')`) memetakan ke:

| Nama loader | Kelas aktif | Paket composer |
|---|---|---|
| `user_agent`, `agent` | `Kodhe\Framework\Agent\UserAgent` | kodhe/agent |
| `form_validation` | `Kodhe\Framework\Validation\FormValidation` | kodhe/validation |
| `pagination` | `Kodhe\Framework\Pagination\Pagination` | kodhe/pagination |
| `email` | `Kodhe\Framework\Email\Email` | kodhe/email |
| `session` | driver di `Kodhe\Framework\Session\Drivers\*` | kodhe/session |
| `image_lib` | `Kodhe\Framework\Image\Image` (+`Drivers\GdDriver`) | kodhe/image |
| `upload` | `Kodhe\Framework\Upload\Upload` | kodhe/upload |
| `database` | `Kodhe\Framework\Database\*` | kodhe/database |
| `table` | `Kodhe\Framework\Table\Table` | kodhe/table |
| `parser` | `Kodhe\Framework\Parser\*` | kodhe/parser |
| `encrypt` / `encryption` | `Kodhe\Framework\Encrypt\*` / `Encryption\*` | kodhe/encrypt, kodhe/encryption |
| `ftp` | `Kodhe\Framework\Ftp\Ftp` | kodhe/ftp |
| `zip` | `Kodhe\Framework\Zip\Zip` | kodhe/zip |
| `calendar` | `Kodhe\Framework\Calendar\Calendar` | kodhe/calendar |
| `cart` | `Kodhe\Framework\Cart\Cart` | kodhe/cart |
| `typography` | `Kodhe\Framework\Typography\Typography` | kodhe/typography |
| `javascript` | `Kodhe\Framework\Javascript\Javascript` | kodhe/javascript |
| `migration` | `Kodhe\Framework\Migration\Migration` | kodhe/migration |
| `trackback` | `Kodhe\Framework\Trackback\Trackback` | kodhe/trackback |
| `xmlrpc` / `xmlrpcs` | `Kodhe\Framework\Xmlrpc\*` / `Xmlrpcs\*` | kodhe/xmlrpc, kodhe/xmlrpcs |
| `profiler` | `Kodhe\Framework\Profiler\Profiler` | kodhe/profiler |
| `cache` | factory `Kodhe\Framework\Cache\*` | kodhe/cache |

## Aturan penulisan kode baru

1. Selalu `use Kodhe\Framework\<Paket>\<Class>;` — jangan pernah menulis
   `Kodhe\Library\*` atau `Kodhe\<Paket>\<Class>` (tanpa `Framework`).
2. Jangan mengandalkan alias `CI_*`; itu hanya safety-net untuk kode warisan.
3. Bila muncul `Class "Kodhe\<X>\<Y>" not found`: kelas legacy tidak autoload —
   ganti ke FQCN pada tabel di atas dan jalankan `composer dump-autoload`.

## Perubahan API kecil saat migrasi

Sebagian method dinormalisasi camelCase, mis. Email: `reply_to()` → `replyTo()`,
`set_mailtype()` digantikan opsi config; Session DB memakai kolom skema CI3 baru
(`timestamp`, bukan `lastactivity` — fallback gc masih mendukung yang lama).
Selalu cek README paket untuk signature final.
