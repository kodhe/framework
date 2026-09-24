# Keamanan (Security)

Halaman ini membahas fitur keamanan yang **didukung Kodhe Framework** saat ini: XSS filtering, CSRF protection, input filtering, password hashing, enkripsi data, cookie & session yang aman, rate limiting, serta validasi input. Setiap bagian menyertakan **cara migrasi dari CodeIgniter 3** bila relevan.

> Semua API di bawah telah diverifikasi terhadap kode sumber paket (`framework/src/Support/Legacy/`, `http/src/`, `session/src/`, `encrypt/src/`).

---

## 1. Ringkasan cepat

| Kebutuhan | Solusi Kodhe | Status vs CI3 |
|---|---|---|
| XSS cleaning | `Kodhe\Framework\Support\Legacy\Security::xss_clean()` + helper global `xss_clean()` | ✅ identik CI3 |
| CSRF | Config `csrf_protection` dkk., diverifikasi otomatis oleh `Input` legacy; untuk router modern buat middleware sendiri | ⚠️ sebagian (lihat §3.4) |
| Input filtering | `$this->input->post('x', true)` (flag xss_clean) | ✅ identik CI3 |
| Password hashing | `password_hash()` / `password_verify()` bawaan PHP (+ polyfill compat) | ✅ sama |
| Enkripsi data | Paket `kodhe/encrypt` — `Kodhe\Framework\Encrypt\Encrypt` | ✅ + metode `migrate()` |
| Cookie aman | config `cookie_secure`, `cookie_httponly`; session `cookie_httponly=true` default | ✅ sama |
| Rate limiting | `RateLimiter` + middleware `ThrottleRequests` (router modern) | 🆕 baru, tidak ada di CI3 |
| Validasi input | `FormValidation` (kompat CI3) atau paket `kodhe/validation` modern | ✅ dua jalur |
| CAPTCHA | helper `create_captcha()` | ✅ port CI3 |

---

## 2. XSS Filtering

### 2.1 API

Kernel kompatibel menyediakan kelas yang merupakan port langsung `CI_Security`:

```php
use Kodhe\Framework\Support\Legacy\Security;

$security = new Security();
$clean    = $security->xss_clean($untrustedHtml);          // string biasa
$isImage  = $security->xss_clean($fileContent, true);      // mode image check
$safe     = $security->sanitize_filename($name);           // nama file aman
$decoded  = $security->entity_decode($str);                // decode entity HTML
```

Method yang tersedia: `xss_clean()`, `sanitize_filename()`, `entity_decode()`, `strip_image_tags()`, `get_random_bytes()`, `xss_hash()`.

Helper global (muat dengan `$this->load->helper('security')`):

```php
xss_clean($str, $is_image = false);
sanitize_filename($filename);
encode_php_tags($str);   // escape tag PHP agar tidak terevalusi
strip_image_tags($str);
do_hash($str, $type = 'sha256');  // deprecated — gunakan hash() PHP native
```

### 2.2 Global XSS filtering (perilaku CI3)

Seperti CI3, input legacy menerapkan XSS clean per-request lewat flag kedua:

```php
$name = $this->input->post('name', true);   // disaring xss_clean()
$id   = $this->input->get('id', true);
```

> **Rekomendasi**: jangan mengandalkan penyaringan global. Simpan data mentah, lalu **escape saat output** (`htmlentities()` / engine view). `xss_clean` cocok untuk input rich-text yang memang akan dirender.

### 2.3 Migrasi dari CI3

Pola CI3 tetap jalan apa adanya di kernel kompatibel:

```php
// CI3 lama — tetap berfungsi
$this->security->xss_clean($data);              // via $this->load->library('security')
$this->input->post('email', TRUE);
```

Untuk kode baru, panggil kelasnya langsung (`new Security(...)`) atau helper global — tidak perlu `$this->load->library('security')`.

---

## 3. CSRF Protection

### 3.1 Konfigurasi (config.php — format CI3 diterima langsung)

```php
$config['csrf_protection']   = TRUE;
$config['csrf_token_name']   = 'csrf_test_name';
$config['csrf_cookie_name']  = 'csrf_cookie_name';
$config['csrf_expire']       = 7200;          // detik
$config['csrf_regenerate']   = TRUE;          // token baru tiap submit
$config['csrf_exclude_uris'] = ['api/webhook/[A-Z0-9-_]+'];  // regex, tanpa delimiter
```

### 3.2 Cara kerja (kernel kompatibel)

Diverifikasi di `Legacy/Input.php`: saat `Input` dibangun dan `csrf_protection` aktif, **semua request POST otomatis** melewati `Security::csrf_verify()` — sama seperti CI3. Token yang tidak valid menghasilkan error **403** ("The action you have requested is not allowed.").

Di dalam form, sisipkan token persis pola CI3:

```php
<input type="hidden"
       name="<?= $this->security->get_csrf_token_name(); ?>"
       value="<?= $this->security->get_csrf_hash(); ?>">
```

URI yang dikecualikan dicocokkan dengan `preg_match('#^'.$excluded.'$#i')` terhadap `uri_string()`.

### 3.3 AJAX

Untuk request AJAX, kirim token pada header dan baca nilainya seperti CI3 — pastikan `csrf_cookie_name` dapat dibaca JS (jika butuh non-httpOnly untuk cookie token), atau sertakan hash di respons JSON awal.

### 3.4 Router modern (kodhe/http)

⚠️ **Known issue**: router modern (`Route::` / `kodhe/http`) **tidak memiliki middleware CSRF bawaan** — verifikasi CSRF hanya terjadi pada pipeline `Input` legacy. Untuk aplikasi murni modern:

1. Buat middleware sendiri (mis. `App\Middleware\CsrfMiddleware`) yang menginstansiasi `Kodhe\Framework\Support\Legacy\Security` lalu memanggil `csrf_verify()` pada method POST/PUT/PATCH/DELETE.
2. Daftarkan aliasnya di `app/Config/middleware.php` (dibaca `MiddlewareRegistry` — kunci `aliases`, `groups`, `priority`).
3. Pasang ke grup/rute: `Route::group(...)->middleware('csrf')`.

### 3.5 Migrasi dari CI3

Tidak ada perubahan yang diperlukan: keenam kunci config di §3.1 dibaca langsung oleh kernel, dan `$this->security->get_csrf_hash()` tetap tersedia. Yang berubah hanya jika Anda pindah ke routing modern penuh — lihat §3.4.

---

## 4. Password Hashing

Gunakan fungsi native PHP — tersedia langsung di PHP 8, dan paket menyediakan polyfill compat (`Support/Legacy/compat/password.php`) untuk lingkungan lama:

```php
$hash = password_hash($password, PASSWORD_DEFAULT);   // bcrypt, cost default
$ok   = password_verify($input, $hashFromDb);

// Rehash transparan bila biaya bcrypt dinaikkan:
if (password_needs_rehash($hashFromDb, PASSWORD_DEFAULT, ['cost' => 12])) {
    $hashFromDb = password_hash($input, PASSWORD_DEFAULT, ['cost' => 12]);
}
```

**Migrasi dari CI3**: sama persis — CI3 juga memakai `password_hash()`. Data hash lama (prefix `$2y$`) tetap bisa diverifikasi. Jangan simpan password dengan `md5`/`sha1`/`do_hash()`; jika masih ada, migrasikan saat user login berikutnya (verifikasi hash lama → simpan hash baru).

---

## 5. Enkripsi Data (kodhe/encrypt)

Kelas `Kodhe\Framework\Encrypt\Encrypt` membungkus OpenSSL AES:

```php
use Kodhe\Framework\Encrypt\Encrypt;

$encrypter = new Encrypt(['encryption_key' => $config['encryption_key']]);
// encryption_key kosong -> EncryptException

$secret = $encrypter->encode('nilai sensitif');
$value  = $encrypter->decode($secret);

$encrypter->set_cipher('AES-256-CBC');   // set_mode('cbc') juga tersedia
$encrypter->encode_many([...]);          // batch
$encrypter->decode_many([...]);
```

Fitur penting untuk migrasi: **`migrate()`** mendeteksi format ciphertext lama (CI3 encrypt lib) dan mengonversinya ke format modern:

```php
if ($encrypter->is_legacy_format($stored)) {
    $stored = $encrypter->migrate($stored);  // decode lama -> encode baru
}
```

> **Catatan**: bedakan *encryption* (rahasia bisa dibuka kembali, untuk data tersimpan) dari *hashing* (untuk password). Jangan pakai `Encrypt` untuk menyimpan password.

**Migrasi dari CI3**: `$this->encrypt->encode()/decode()` → instansiasi `Encrypt` dengan config yang sama (`encryption_key`). Ciphertext lama tidak hilang berkat `is_legacy_format()` + `migrate()`.

---

## 6. Cookie & Session yang Aman

### 6.1 Cookie

```php
$config['cookie_domain']   = '';
$config['cookie_path']     = '/';
$config['cookie_secure']   = TRUE;   // hanya dikirim via HTTPS
$config['cookie_httponly'] = TRUE;   // tidak terbaca JS
```

`Input::set_cookie()` dan `Security::csrf_set_cookie()` menghormati kunci-kunci ini (diverifikasi). Helper `cookie_helper.php` (`set_cookie()`, `get_cookie()`, `delete_cookie()`) juga tersedia. Jika `cookie_secure=TRUE` tapi request tidak HTTPS, cookie CSRF sengaja **tidak** dikirim.

### 6.2 Session (paket kodhe/session)

Default `SessionConfig` sudah aman dan bisa dikonfigurasi:

```php
'driver'               => 'files',        // files/database/redis/memcached
'cookie_name'          => 'ci_session',
'cookie_secure'        => false,          // set true di produksi HTTPS
'cookie_httponly'      => true,           // default sudah true
'expiration'           => 7200,
'match_ip'             => false,          // binding IP opsional
'time_to_update'       => 300,            // regen id berkala (detik)
'regenerate_destroy'   => false,          // destroy data lama saat regen
'sid_length'           => 40,
'sid_bits_per_character' => 5,
```

**Regenerasi ID wajib dilakukan saat perubahan privilege login** (sama seperti saran CI3):

```php
session()->regenerate(true);   // regenerate + destroy data lama
```

**Migrasi dari CI3**: seluruh kunci `sess_*` lama dikenali; tabel `ci_sessions` tetap dipakai (lihat `migrating-from-ci3.md` §7).

---

## 7. Rate Limiting (fitur baru, tidak ada di CI3)

Router modern menyediakan `Kodhe\Framework\Http\Routing\RateLimiter` (storage `CacheInterface`) dan middleware `ThrottleRequests`:

```php
use Kodhe\Framework\Http\Middleware\Routing\ThrottleRequests;

// handle($request, $next, $maxAttempts = 60, $decayMinutes = 1, $key = null)
// -> daftar sebagai alias 'throttle' di app/Config/middleware.php
Route::group('api')->middleware('throttle:60,1')->group(function () {
    Route::get('users', [UserController::class, 'index']);
});
```

API `RateLimiter`: `tooManyAttempts()`, `hit()`, `attempts()`, `remaining()`, `availableIn()`, `getHeaders()` (menghasilkan header `X-RateLimit-*`), `reset()`, `clear()`.

> **Known issue**: kelas tersedia lengkap tetapi belum ada wiring otomatis di Router — daftarkan manual sebagai middleware alias sebelum dipakai.

Untuk pembatasan sederhana di jalur legacy (mis. login attempt), gunakan `RateLimiter` langsung:

```php
$limiter = new RateLimiter($cache, ['decaySeconds' => 300]);
$key = 'login:' . $this->input->ip_address();
if ($limiter->tooManyAttempts($key, 5)) {
    // blokir: sleep/flash error, balas setelah availableIn()
}
$limiter->hit($key);
```

---

## 8. Validasi Input

### 8.1 Jalur kompatibel CI3 — `FormValidation`

API `validation/src/FormValidation.php` adalah port `CI_Form_validation`: `set_rules()`, `run()`, `error_string()`, `set_value()/set_select()/set_radio()/set_checkbox()`, group rules per URI/controller. Aturan CI3 tersedia (`required`, `min_length`, `valid_email`, `is_unique`, dst.).

```php
$this->load->library('form_validation');
$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
if ($this->form_validation->run() === false) { /* tampilkan error_string() */ }
```

### 8.2 Jalur modern — paket kodhe/validation

Validator berkelas dengan kontrak terpisah (`RequiredValidator`, `EmailValidator`, `IntegerValidator`, `NumericValidator`, `RegexValidator`, `UrlValidator`, `CallableValidator`, `CustomRuleValidator`) plus Filters & MessageManager. Lihat [libraries/validation.md](../libraries/validation.md).

> **Keamanan**: `is_unique` pada FormValidation menyentuh database — selalu kombinasikan dengan `allowedFields` di Model (§ dokumentasi app-components) agar mass-assignment tidak membuka celah enumerasi.

---

## 9. CAPTCHA

Helper `captcha_helper.php` adalah port CI3:

```php
$this->load->helper('captcha');
$captcha = create_captcha([
    'img_path' => './captcha/',
    'img_url'  => 'https://example.com/captcha/',
    'font_url' => '',
    'img_id'   => 'imageid',
]);
// $captcha['image'], $captcha['time'], $captcha['word']
```

Simpan kata captcha di session, verifikasi nilai POST terhadapnya. Pastikan `img_path` writable dan berada di luar area sensitif.

---

## 10. Checklist keamanan pra-produksi

- [ ] `encryption_key` terisi (format modern, bukan salt CI3 lama) — tanpa ini `Encrypt` melempar exception.
- [ ] `csrf_protection = TRUE` + semua form state-changing mengandung token.
- [ ] `cookie_secure = TRUE` dan `cookie_httponly = TRUE` di produksi HTTPS.
- [ ] Session: `time_to_update` aktif, `regenerate(true)` dipanggil saat login.
- [ ] `display_errors` mati, `log_threshold` sesuai, file log tidak bisa diakses publik (di luar docroot atau dilindungi).
- [ ] Upload: validasi tipe/ukuran + `sanitize_filename()` sebelum disimpan di disk.
- [ ] Endpoint publik/API dipasang `throttle` (§7) — pengganti guard manual CI3.
- [ ] Query selalu lewat binding/Model, tidak pernah concatenation input user.
- [ ] `base_url()` penuh HTTPS; redirect keluar divalidasi whitelist.

---

## Lihat juga

- [Migrasi dari CI3](migrating-from-ci3.md) — strategi umum & config mapping
- [App Components](app-components.md) — Model (`allowedFields`) & View (escaping output)
- [Services, Modules & Routing](services-modules-routing.md) — pendaftaran middleware
- [Library Validation](../libraries/validation.md) · [Library Session](../libraries/session.md) · [Library Encrypt](../libraries/encrypt.md)
