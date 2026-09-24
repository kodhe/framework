# Email

Paket: `kodhe/email` — `Kodhe\Framework\Email\Email` (mail/smtp/sendmail).

```php
use Kodhe\Framework\Email\Email;

$email = new Email([
    'protocol' => 'smtp',
    'smtp_host' => 'ssl://smtp.googlemail.com',
    'smtp_port' => 465,
    'smtp_user' => 'you@example.com',
    'smtp_pass' => 'secret',
    'mailtype'  => 'html',
    'charset'   => 'utf-8',
]);

$email->from('you@example.com', 'Toko')
      ->to('pelanggan@example.com')
      ->replyTo('cs@example.com')
      ->subject('Terima kasih')
      ->message('<h1>Pesanan diterima</h1>')
      ->attach('/path/faktur.pdf')
      ->send();
```

Perubahan vs CI3: chaining fluent, `reply_to()` → `replyTo()`, hasil debug lewat
`printDebugger()`. Rincian: [README paket](../../email/README.md).
