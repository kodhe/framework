# Form Validation Library

Paket: `kodhe/validation` — kelas utama `Kodhe\Framework\Validation\FormValidation`.
Padanan `CI_Form_validation` CI3, API rules-string identik.

## Instalasi

```bash
composer require kodhe/validation
```

## Cara Pakai (pola CI3)

```php
use Kodhe\Framework\Validation\FormValidation;

$v = new FormValidation();
$v->set_rules('username', 'Username', 'required|min_length[5]|max_length[12]');
$v->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');

if ($v->run($_POST) === false) {
    echo $v->error_string();          // semua error terformat
    echo form_error('email');         // error per-field
} else {
    // data valid — lanjutkan
}
```

## Aturan Bawaan Populer

`required`, `matches`, `valid_email`, `valid_url`, `integer`, `numeric`,
`min_length[n]`, `max_length[n]`, `exact_length[n]`, `alpha`, `alpha_numeric`,
`in_list[a,b,c]`, `is_unique[table.field]`, `callback_method_name`, dst.
Daftar lengkap + aturan custom: lihat [README paket](../../validation/README.md).

## Catatan Migrasi

- Pada controller CI3, `$this->form_validation` tetap tersedia via loader kernel —
  kode lama tidak perlu diubah.
- Untuk PHPStan level tinggi, gunakan `set_value()` yang kini ber-docblock tipe
  lengkap (`@return string|array`).
