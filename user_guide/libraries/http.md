# HTTP (Request / Response / Middleware)

Paket: `kodhe/http`.

```php
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;

$req = Request::fromGlobals();
$path = $req->getPath();
$json = $req->input('payload');

$res = new Response(status: 200);
$res->json(['ok' => true])->send();
```

Middleware callable: implementasi `MiddlewareInterface` menerima `$next`; rantai
dieksekusi bergaya onion. Dokumentasi penuh header, cookies, file upload request,
dan daftar middleware bawaan: [README paket](../../http/README.md).
