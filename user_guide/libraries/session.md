# Session

Paket: `kodhe/session` — driver di `Kodhe\Framework\Session\Drivers\*`
(files, database; cookie/redis tersedia via konfigurasi kernel).

## Konfigurasi (ala CI3)

```php
$config['sess_driver']      = 'database';          // atau 'files'
$config['sess_cookie_name'] = 'ci_session';
$config['sess_expiration']  = 7200;
$config['sess_save_path']   = 'ci_sessions';       // WAJIB: nama tabel utk driver database
$config['sess_match_ip']    = false;
$config['sess_time_to_update'] = 300;
```

## Pemakaian

```php
$CI->session->set('user_id', 42);
$uid = $CI->session->get('user_id');
$CI->session->set_flashdata('msg', 'Tersimpan!');
$CI->session->unset_userdata('user_id');
```

## Skema tabel (driver database)

Lihat [troubleshooting §2](../general/troubleshooting.md) untuk DDL lengkap.
Driver memvalidasi kolom saat read() dan memberi pesan error jelas bila skema
tidak cocok; gc() otomatis mendukung kolom warisan `lastactivity`.

Detail API & opsi: [README paket](../../session/README.md).
