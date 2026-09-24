# Kodhe XML-RPC (Server)

Library pembuat **server** XML-RPC hasil refaktor `Xmlrpcs` CodeIgniter 3 (namespace `Kodhe\Framework\Xmlrpcs`). Extend package `kodhe/xmlrpc` (kelas klien `Xmlrpc`) — jadi satu-satunya dependensi internal. Cocok untuk mengekspos API RPC: daftar method, introspeksi (`system.*`), dan `multiCall`.

## Instalasi

```bash
composer require kodhe/xmlrpcs   # otomatis menarik kodhe/xmlrpc
```

Persyaratan: PHP >= 8.1, extension `xml`; kelas `Xmlrpc` harus termuat sebelum `Xmlrpcs` (dijamin oleh autoload composer).

## Quick Start

```php
<?php

declare(strict_types=1);

use Kodhe\Framework\Xmlrpcs\Xmlrpcs;

header('Content-Type: text/xml');

$server = new Xmlrpcs([
    'blog.post' => [
        'function' => 'postArticle',          // callable global atau method controller CI
        'signature' => [[
            ['name' => 'postId',   'type' => 'int'],
            ['name' => 'username', 'type' => 'string'],
            ['name' => 'password', 'type' => 'string'],
            ['name' => 'content',  'type' => 'struct'],
        ]],
        'docstring' => 'Publikasikan satu artikel blog.',
    ],
]);

$server->serve();   // baca php://input, dispatch, cetak XML respons
```

## Struktur Direktori

```
src/
└── Xmlrpcs.php     # Kelas server: peta method, parsing request, dispatch
                    # (+ memanfaatkan Encoder/Decoder/Message dari kodhe/xmlrpc)
```

## Penggunaan

### 1. Mendefinisikan method lewat array config

Format tiap entri persis seperti `$config['webservices']` CI3:

```php
new Xmlrpcs([
    'nama.method' => [
        'function'  => 'callback_php',       // string nama fungsi / array call-able
        'signature' => [[[ ['name'=>'arg','type'=>'string'] ], ['returns','int'] ]],
        'docstring' => 'Penjelasan singkat',
    ],
]);
```

Atau set massal setelah konstruksi:

```php
$server->set_methods($metodeMapArray);
```

### 2. Menambah method satu per satu

```php
$server->add_to_map(
    'echo.halo',
    'fn_echo',                                  // function fn_echo($m) { ... }
    [[['name' => 'pesan', 'type' => 'string']]],
    'Mengembalikan pesan yang dikirim.'
);
```

### 3. Method callback & objek request

Fungsi callback menerima satu argumen objek request ter-dekode:

```php
function postArticle($req)
{
    $params = $req->output_parameters();   // array parameter pemanggil
    // ... proses ...
    return $req->send_response(42);        // kembalikan nilai ke klien
}
```

### 4. Sistem introspeksi bawaan

Secara default server mendaftarkan `system.*`:

```php
$server->set_system_methods();  // dipanggil otomatis bila ada 'i18n' config khusus
// menyediakan: system.listMethods, system.methodSignature,
//              system.methodHelp, system.multiCall
```

Klien bisa memanggil:

```php
$methods = $client->call('system.listMethods');
```

### 5. Validasi request manual

```php
if (!$server->verify_request($rawXml)) {
    http_response_code(400);
}
$request = $server->parseRequest($rawXml);   // objek request siap dipakai ulang
```

### 6. Integrasi sebagai controller CI3-style

```php
class Webservice extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('xmlrpc');      // klien dulu...
        $this->load->library('xmlrpcs', $this->config->item('webservices'));
    }

    public function index()
    {
        if ($this->input->method() === 'post') {
            $this->xmlrpcs->serve();
        }
    }
}
```

## Referensi API (`Xmlrpcs`)

| Method | Keterangan |
|---|---|
| `__construct(array $config)` | Inisialisasi + daftarkan peta method |
| `initialize($config)` | Set ulang konfigurasi |
| `serve()` | Proses request masuk (STDIN POST) → keluar respons XML |
| `add_to_map($method, $function, $sig, $doc)` | Daftarkan satu method |
| `set_methods(array $methods)` | Ganti seluruh peta method |
| `set_system_methods()` | Aktifkan `system.*` (listMethods, methodSignature, methodHelp, multicall) |
| `verify_request(string $data)` | Cek validitas XML request |
| `parseRequest($data)` | Dekode body menjadi objek request |
| `listMethods($m)` / `methodSignature($m)` / `methodHelp($m)` / `multicall($m)` | Handler `system.*` |

## Kompatibilitas CodeIgniter 3

Perilaku dispatch, format signature, fault code, dan method `system.*` sama dengan `CI_Xmlrpcs`. Perbedaan hanya namespace/nama kelas (`Xmlrpcs`, extend `Kodhe\Framework\Xmlrpc\Xmlrpc`).

## Catatan

- Selalu kirim header `Content-Type: text/xml` sebelum `serve()`.
- Callback yang melempar exception menghasilkan *fault* `-32700`/internal error — tangani sendiri di dalam fungsi agar pesan tidak bocor.
- Untuk auth, periksa parameter username/password di dalam callback (tidak disediakan middleware bawaan).

## Pengujian

```bash
vendor/bin/phpunit --filter Xmlrpcs
```
