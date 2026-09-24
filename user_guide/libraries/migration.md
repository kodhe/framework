# Migration

Paket: `kodhe/migration` — `Kodhe\Framework\Migration\Migration`.

```php
$m = new Migration(['migrations_path' => APPPATH . 'migrations']);
$m->latest();                 // jalankan semua migration belum diterapkan
$m->version(20260901000000);  // menuju versi spesifik
echo $m->current_version();
```

File migration = class `Migration_<timestamp>` dengan `up()`/`down()`.
Konfigurasi tabel pelacak lewat constructor (tidak lagi butuh CI superobject).
Lengkap: [README paket](../../migration/README.md).
