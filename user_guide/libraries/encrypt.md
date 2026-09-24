# Encryption

Dua paket berbeda — jangan tertukar:

| Paket | Kelas | Kegunaan |
|---|---|---|
| `kodhe/encrypt` | `Kodhe\Framework\Encrypt\Encrypt` | Enkripsi CI3 lama (mcrypt-era, kompatibilitas data existing) |
| `kodhe/encryption` | `Kodhe\Framework\Encryption\*` | Enkripsi modern berbasis OpenSSL — **untuk data baru** |

```php
use Kodhe\Framework\Encryption\Encrypter;

$crypt = new Encrypter($key, 'AES-256-CBC');
$sealed = $crypt->encrypt('rahasia');
$plain  = $crypt->decrypt($sealed);
```

Aturan praktis: data lama didekripsi dengan `encrypt`, migrasikan lalu simpan ulang
dengan `encryption`. Detail: README masing-masing paket.
