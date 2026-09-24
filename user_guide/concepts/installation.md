# Instalasi

## Persyaratan

- PHP >= 7.4 (disarankan 8.1+; CI dijalankan pada 8.2)
- Composer 2
- Ekstensi sesuai paket: `gd`/`imagick` (image), `mysqli|pdo` (database),
  `openssl` (encrypt/encryption), `zip` (zip).

## Opsi A — Root framework (aplikasi full-stack)

```bash
composer require kodhe/framework
```

Mendaftarkan autoload PSR-4 prefix `Kodhe\Framework\*` untuk SEMUA paket sekaligus
plus kernel `framework/`. Gunakan ini bila aplikasi memakai banyak library.

## Opsi B — Per paket (hemat dependensi)

```bash
composer require kodhe/validation kodhe/pagination
```

Hanya pasang yang dipakai. Nama paket = folder di repo (lihat indeks libraries).
Catatan: sebagian kecil paket lama memakai nama composer berbeda — cek
`composer.json` paket sebelum install (mis. `kodhe/parser`, `kodhe/encrypt`).

## Instalasi dari source (monorepo dev)

```bash
git clone https://github.com/kodhe/framework.git
cd framework && composer install
```

Kernel `framework/` memetakan namespace tiap paket ke folder root monorepo, sehingga
perubahan paket langsung terasa tanpa publish.

## Verifikasi cepat

```php
require 'vendor/autoload.php';
$v = new Kodhe\Framework\Validation\FormValidation();
var_dump($v->run(['email' => 'a@b.co'], ['email' => ['rules' => 'valid_email']])); // true
```
