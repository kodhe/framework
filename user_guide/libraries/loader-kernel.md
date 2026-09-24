# Loader & Kernel (Core Components)

Kernel `framework/` menyediakan pengalaman loading ala CI3 di atas paket modern.

## load->library() / model() / helper()

```php
$CI->load->library('user_agent');   // → Kodhe\Framework\Agent\UserAgent + alias CI_User_agent
$CI->load->helper('url');           // helper file dimuat bila tersedia di path aplikasi
```

Pemetaan nama→kelas ada di `FileLoader::packageMap()` (sumber tunggal). Bila Anda
menambah paket baru, daftarkan di sana agar loader legacy mengenalinya.

## Service Container & Setup

`Config/Setup.php` membangun array konfigurasi layanan dari config aplikasi dan
mendaftarkan factory per service ke Container. Accessor:

```php
$session = $container->get('session');       // driver sesuai sess_driver
$db      = $container->get('database');      // Kodhe\Framework\Database\Connection
```

## Dual-mode

Seluruh framework berjalan dengan ATAU tanpa kernel: import kelas paket langsung
(PSR-4 murni) selalu boleh dan disarankan untuk kode baru.
