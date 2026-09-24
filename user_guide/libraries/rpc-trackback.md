# XML-RPC & Trackback

## xmlrpc (klien) — `kodhe/xmlrpc`

```php
use Kodhe\Framework\Xmlrpc\Client;

$client = new Client('https://example.com/xmlrpc');
$result = $client->request('metaWeblog.getRecentPosts', [1, 'user', 'pass', 10]);
```

## xmlrpcs (server) — `kodhe/xmlrpcs`

Daftarkan method pada `application/config/xmlrpc.php` seperti CI3; kelas
`Server` mem-parse request dan memanggil handler bertipe `IXR_*`.

## trackback — `kodhe/trackback`

Validasi & kirim/penerima trackback ping; integrasi otomatis saat posting
(jika dipakai). Detail: [xmlrpc/README.md](../../xmlrpc/README.md),
[xmlrpcs/README.md](../../xmlrpcs/README.md), [trackback/README.md](../../trackback/README.md).
