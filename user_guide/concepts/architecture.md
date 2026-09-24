# Arsitektur

Kodhe Framework adalah hasil modulisasi library CodeIgniter 3 menjadi **paket-paket
Composer independen** ber-namespace `Kodhe\Framework\*` (PSR-4), dibungkus kernel
compatibility-layer (`framework/`) agar aplikasi CI3 lama tetap berjalan.

## Lapisan

```
Aplikasi Anda (controller, model, config)
        │
┌───────▼───────────────────────────────┐
│ framework/  (kernel compatibility)    │  Container, FileLoader dual-mode,
│   - Service container & Setup factory │  Setup config, Console, Autoloader
│   - $CI->load->library() legacy API   │
└───────┬───────────────────────────────┘
        │ resolve via packageMap → FQCN Kodhe\Framework\<Paket>\*
┌───────▼───────────────────────────────┐
│ Paket library (28 paket mandiri)      │  validation/, pagination/, database/,
│ masing-masing composer.json + README  │  session/, http/, email/, dst.
└───────────────────────────────────────┘
```

## Loader Dual-Mode

`FileLoader` mendukung dua gaya pemuatan:

1. **Legacy CI3**: `$CI->load->library('user_agent')` — nama lowercase, di-resolve
   lewat `packageMap` internal ke FQCN modern (`Kodhe\Framework\Agent\UserAgent`),
   lalu alias kelas `CI_*` dibuat otomatis agar kode lama tidak berubah.
2. **Modern PSR-4**: `use Kodhe\Framework\Validation\FormValidation;` langsung —
   disarankan untuk kode baru; tidak bergantung pada loader sama sekali.

## Service Container

`framework/src/Config/Setup.php` mendaftarkan factory per layanan (session, cache,
database, dst.) yang membaca array konfigurasi ala CI3 (`$config['sess_driver']`,
`$db['default']`, …) dan mengembalikan instance paket modern. Ini jalur bootstrap
yang dipakai saat aplikasi boot.

## Prinsip Paket

- Tidak ada dependensi antar-paket wajib kecuali benar-benar perlu (mis. `http` →
  `pagination` untuk `PaginationFactoryInterface`).
- Setiap paket standalone-installable: `composer require kodhe/<paket>`.
- README paket = sumber kebenaran API; `user_guide/` = narasi pembelajaran.
