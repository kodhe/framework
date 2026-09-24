# Kodhe Validation

Package validasi form hasil refaktor library `Form_validation` CodeIgniter 3, dengan namespace modern (`Kodhe\Framework\Validation`). Cocok dipakai untuk memvalidasi input POST/GET aplikasi CI3-style maupun standalone melalui `set_data()`. Menyertakan helper form (`form_open`, `form_input`, dll.) dan lapisan modular baru (factory validator, rule parser, message manager) di bawah `src/`.

## Instalasi

```bash
composer require kodhe/validation
```

Persyaratan: PHP >= 8.1. Helper `validation/src/helpers/form.php` di-autoload otomatis via `composer.json` (key `files`).

## Quick Start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Kodhe\Framework\Validation\FormValidation;

$validation = new FormValidation();

$validation->set_rules('username', 'Username', 'required|alpha_numeric|min_length[4]|max_length[20]');
$validation->set_rules('email', 'Email', 'required|valid_email');
$validation->set_rules('password', 'Password', 'required|min_length[8]|matches[password_confirm]');

if ($validation->run() === false) {
    // Ada error — tampilkan per field atau sekaligus
    echo $validation->error('email');        // <p>The Email field must contain a valid email address.</p>
    echo $validation->error_string();        // semua error tergabung
} else {
    // Valid — lanjutkan proses
}
```

> Catatan: tanpa instance CodeIgniter, `run()` membaca data dari `$_POST`. Gunakan `set_data()` untuk sumber data lain.

## Struktur Direktori

```
src/
├── FormValidation.php          # Kelas utama (API kompatibel CI3)
├── Exceptions/                 # ValidationException, RuleNotFoundException
├── Factory/ValidatorFactory.php
├── Filters/BaseFilter.php
├── Messages/MessageManager.php
├── Contracts/                  # ValidatorInterface, FilterInterface, RuleInterface
├── Support/                    # RuleParser, RuleCache
├── Validators/                 # RequiredValidator, EmailValidator, RegexValidator, ...
├── ValueObjects/               # ValidationError, FieldConfig
└── helpers/form.php            # Helper HTML form (form_open, form_input, ...)
```

## Penggunaan

### 1. Validasi data non-POST (`set_data`)

```php
$validation->set_data([
    'nama' => 'Budi',
    'umur' => '17',
]);
$validation->set_rules('nama', 'Nama', 'required|alpha');
$validation->set_rules('umur', 'Umur', 'required|integer|greater_than[17]');

if ($validation->run()) {
    // lolos
}
```

### 2. Menampilkan error

```php
// Error satu field (pakai delimiters default <p>...</p>)
echo $validation->error('nama');

// Error satu field dengan delimiter custom
echo $validation->error('nama', '<span class="err">', '</span>');

// Semua error sebagai array: ['field' => 'pesan', ...]
$errors = $validation->error_array();

// Semua error sebagai satu string
echo $validation->error_string('<div>', '</div>');

// Ganti delimiters global
$validation->set_error_delimiters('<em>', '</em>');
```

### 3. Pesan error custom

```php
$validation->set_rules('email', 'Email', 'required|valid_email');

// Per ruleset
$validation->set_message('required', '%s wajib diisi.');
$validation->set_message('valid_email', 'Format %s tidak valid.');

// Per field (prioritas lebih tinggi): format key = {rules}_{field}
$validation->set_message('required_email', 'Silakan isi alamat email Anda.');
```

Placeholder `%s` diganti dengan label field.

### 4. Repopulasi form (helper form)

```php
<input type="text" name="username" value="<?php echo set_value('username'); ?>">
<input type="checkbox" name="hobi[]" value="mancing" <?php echo set_checkbox('hobi[]', 'mancing'); ?>>
```

`set_value()` / `set_select()` / `set_radio()` / `set_checkbox()` adalah method `FormValidation` yang membaca data POST terakhir agar form terisi ulang setelah gagal validasi.

### 5. Aturan (rules) bawaan

| Kategori | Rules |
|---|---|
| Umum | `required`, `regex_match[/.../]`, `matches[field]`, `differs[field]`, `is_unique[table.field]`* |
| String | `min_length[n]`, `max_length[n]`, `exact_length[n]`, `alpha`, `alpha_numeric`, `alpha_numeric_spaces`, `alpha_dash` |
| Angka | `numeric`, `integer`, `decimal`, `greater_than[n]`, `greater_than_equal_to[n]`, `less_than[n]`, `less_than_equal_to[n]`, `in_list[a,b,c]`, `is_natural`, `is_natural_no_zero` |
| Format | `valid_email`, `valid_emails`, `valid_url`, `valid_ip`, `valid_base64` |
| Sanitasi | `prep_for_form`, `prep_url`, `strip_image_tags`, `encode_php_tags` |

\* `is_unique` memerlukan koneksi database aktif.

Rules digabung dengan pipe: `'required|min_length[5]'`.

### 6. Rule callback custom

```php
class Buku_model
{
    // Dipakai pada rules sebagai 'callback_cek_judul'
    public function cek_judul($str)
    {
        if ($str === strip_tags($str)) {
            return true;
        }
        $this->form_validation->set_message('cek_judul', 'Judul tidak boleh mengandung HTML.');
        return false;
    }
}
```

### 7. Reset & cek rule

```php
$validation->has_rule('email');   // bool
$validation->reset_validation();  // bersihkan seluruh state
```

## Konfigurasi

Konstruktor menerima array rules berformat CI3:

```php
$rules = [
    ['field' => 'email', 'label' => 'Email', 'rules' => 'required|valid_email'],
];
$validation = new FormValidation($rules);
```

Atau menumpuk via `set_rules($field, $label, $rules, $errors)` seperti di Quick Start.

## Referensi API (ringkas)

| Method | Keterangan |
|---|---|
| `set_rules($field, $label, $rules, $errors)` | Daftarkan aturan per field |
| `set_data(array $data)` | Ganti sumber data (default `$_POST`) |
| `run($group = '')` | Jalankan validasi; return `bool` |
| `error($field, $prefix, $suffix)` | Pesan error satu field |
| `error_array()` | Semua error sebagai array |
| `error_string($prefix, $suffix)` | Semua error sebagai string |
| `set_message($lang, $val)` | Custom pesan per rule/field |
| `set_error_delimiters($open, $close)` | Delimiter pembungkus error |
| `set_value/set_select/set_radio/set_checkbox` | Repopulasi form |
| `has_rule($field)` / `reset_validation()` | Introspeksi & reset |

## Kompatibilitas CodeIgniter 3

API `FormValidation` identik dengan `CI_Form_validation` CI3 — drop-in replacement. Perbedaan: nama kelas `FormValidation` (bukan `Form_validation`) dan namespace `Kodhe\Framework\Validation`. Jika dijalankan dalam aplikasi CI3, referensi CI internal tetap resolve ke instance CI sehingga `is_unique` dan helper form bekerja normal.

## Catatan

- `is_unique[...]` memerlukan database yang terhubung (aktifkan `autoload['database']`).
- Layer modular (`Factory/`, `Validators/`, `Support/`, `Messages/`) masih berkembang; API publik yang stabil adalah `FormValidation`.

## Pengujian

```bash
vendor/bin/phpunit --filter Validation
```

Lihat `tests/` untuk contoh pemakaian end-to-end.
