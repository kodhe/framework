# Kodhe Email

Package email hasil refaktor library `Email` CodeIgniter 3, dengan namespace `Kodhe\Framework\Email`. Mengirim email lewat protokol `mail()`, Sendmail, atau SMTP, dengan dukungan CC/BCC (termasuk mass BCC berbatas), lampiran, header MIME, mailtype text/html, word-wrap, dan debugger bawaan. Lapisan transport dimodularisasi (`MailTransport`, `SendmailTransport`, `SmtpTransport`) sehingga mudah di-extension atau di-mock saat testing.

## Instalasi

```bash
composer require kodhe/email
```

Persyaratan: PHP >= 8.1. Tidak ada dependensi eksternal wajib (SMTP memakai socket bawaan PHP).

## Quick Start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Kodhe\Framework\Email\Email;

$email = new Email();

$email->from('admin@example.com', 'Admin')
      ->to('user@example.com')
      ->subject('Halo')
      ->message('Ini body email.')
      ->send();
```

Fluent API mengembalikan `EmailInterface`, sehingga bisa dirantai seperti di atas. `send()` mengembalikan `bool`.

## Konfigurasi

Lewat konstruktor atau `initialize()`:

```php
$email = new Email([
    'protocol'  => 'smtp',            // mail | sendmail | smtp
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => 587,
    'smtp_user' => 'user@example.com',
    'smtp_pass' => 'password',
    'SMTPAuth'  => true,
    'mailtype'  => 'html',            // text | html
    'charset'   => 'UTF-8',
    'wordwrap'  => true,
    'wrapchars' => 76,
    'priority'  => 3,                 // 1 (paling tinggi) s.d. 6
]);
```

## Metode Utama

| Metode | Keterangan |
|---|---|
| `from(string $from, string $name = '')` | Alamat pengirim |
| `replyTo(string $replyto, string $name = '')` | Alamat reply-to |
| `to($to)` / `cc($cc)` | Penerima tunggal atau array |
| `bcc($bcc, int $limit = 0)` | BCC; `$limit > 0` = mass BCC per kelompok |
| `subject(string $subject)` / `message(string $body)` | Subjek dan body |
| `attach(string $filename, string $disposition = '', ?string $newname = null, string $mime = '')` | Lampiran (bisa berulang) |
| `send(bool $autoClear = true): bool` | Kirim; auto-clear setelah terkirim |
| `clear(bool $clearAttachments = false)` | Reset state pesan |
| `printDebugger(array $include = ['headers', 'subject', 'body']): string` | Debug tanpa benar-benar mengirim |

### Contoh HTML + lampiran

```php
$email = new Email(['protocol' => 'mail', 'mailtype' => 'html']);

$email->from('admin@example.com')
      ->to(['a@example.com', 'b@example.com'])
      ->subject('Laporan Mingguan')
      ->message('<h1>Laporan</h1><p>Terlampir PDF.</p>')
      ->attach('/path/to/laporan.pdf')
      ->send();
```

### Menekan pengiriman asli saat development

```php
// Jangan panggil send(); cukup lihat output header/body:
echo $email->printDebugger();
```

## Struktur Direktori

```
src/
├── Email.php                     # Kelas utama (API kompatibel CI3)
├── Contracts/                    # EmailInterface, TransportInterface
├── Transports/                   # MailTransport, SendmailTransport, SmtpTransport
├── Message/                      # EmailMessage, Attachment, HeaderCollection
├── Traits/                       # ConfigurableTrait, DebugTrait
├── language/email_lang.php
└── helpers/email.php             # valid_email(), send_email()
```

## Catatan Migrasi dari Namespace Lama

Dokumentasi/test lama memakai `Kodhe\Library\Email\Email` serta metode bergaya CI3 lama (`reply_to()`, `set_mailtype()`, properti publik `word_wrap`). Pada versi Composer modern:

```php
use Kodhe\Framework\Email\Email;
```

Konfigurasi dilakukan lewat array (lihat bagian Konfigurasi), dan penamaan metode memakai camelCase (`replyTo()`). PSR-4 mapping `Kodhe\Framework\Email\` → `email/src/` dideklarasikan di `email/composer.json`.
