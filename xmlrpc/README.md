# Kodhe XML-RPC (Client)

Library XML-RPC sisi **klien** hasil refaktor `Xmlrpc` CodeIgniter 3 (namespace `Kodhe\Framework\Xmlrpc`). Digunakan untuk memanggil method pada server XML-RPC remote (mis. WordPress `xmlrpc.php`, Blogger, layanan web RPC lama). Tersedia dua gaya API: prosedural CI3 (`server() → method() → request() → send_request()`) dan objek modern `Client\XmlRpcClient::call()` dengan transport/encoder/decoder yang dapat disuntik.

## Instalasi

```bash
composer require kodhe/xmlrpc
```

Persyaratan: PHP >= 8.1, extension `curl` (transport default), lib `xml`.

## Quick Start

```php
<?php

declare(strict_types=1);

use Kodhe\Framework\Xmlrpc\Xmlrpc;

$rpc = new Xmlrpc();

$rpc->server('https://example.com/xmlrpc.php', 443);
$rpc->method('wp.getPostFormats');
$rpc->request([
    ['postId' => 1, 'username' => 'admin', 'password' => 'rahasia'],
]);

if ($rpc->send_request() !== false) {
    print_r($rpc->display_response());   // array hasil dekode
} else {
    echo $rpc->display_error();          // fault string
}
```

## Struktur Direktori

```
src/
├── Xmlrpc.php               # Kelas utama kompatibel CI3
├── Client/XmlRpcClient.php  # Klien OO modern (setServer/call)
├── Message/                 # XmlRpcMessage (request), XmlRpcResponse
├── Encoder/ Decoder/        # encode/decode payload XML-RPC
├── Transport/ + Factory/    # HTTP transport (curl/stream)
├── ValueObjects/XmlRpcValue.php
├── Contracts/ Support/ Exceptions/
```

## Penggunaan

### 1. Memakai tipe data XML-RPC (konstanta `xmlrpc_*`)

```php
$rpc->request([
    $rpc->xmlrpc_int,      // <int>
    $rpc->xmlrpc_boolean,  // <boolean>
    $rpc->xmlrpc_string,   // <string>
    $rpc->xmlrpc_base64,   // <base64>
    $rpc->xmlrpc_struct,   // <struct> (assoc array)
    $rpc->xmlrpc_array,    // <array>  (list array)
    $rpc->xmlrpc_datetime, // <dateTime.iso8601>
]);
```

Properti publik ini identik dengan konstanta `CI_Xmlrpc::$xmlrpc_*` lama.

### 2. Proxy & timeout

```php
$rpc->server('http://rpc.example.com/xmlrpc.php', 80,
            'proxy.example.com', 8080);  // lewat proxy
$rpc->timeout(15);                        // detik
$rpc->set_debug(true);                    // log request/response
```

### 3. Gaya modern: `XmlRpcClient`

```php
use Kodhe\Framework\Xmlrpc\Client\XmlRpcClient;

$client = new XmlRpcClient();           // factory memilih transport/encoder default
$client->setServer('https://example.com/xmlrpc.php', 443)
       ->setTimeout(10)
       ->setDebug(false);

$result = $client->call('system.listMethods');     // array | false
if ($result === false) {
    echo $client->getLastError();
}
```

Transport, encoder, dan decoder bisa diganti lewat konstruktor selama mengimplementasikan `Contracts/*Interface` (lihat `Factory/TransportFactory::create()` untuk contoh default).

### 4. Bangkitkan respons manual (utilitas server-side kecil)

```php
$rpc->send_error_message(404, 'Method tidak ditemukan'); // panggil fault XML
$rpc->send_response(['ok' => true]);                     // panggil success XML
```

### 5. Membaca fault response

```php
$fault = $rpc->display_error();  // string pesan fault dari server
```

## Referensi API (`Xmlrpc`)

| Method | Keterangan |
|---|---|
| `initialize(array $config)` | Set preferensi massal |
| `server($url, $port, $proxy, $proxy_port)` | Tentukan endpoint |
| `timeout($seconds)` | Timeout koneksi |
| `method($function)` | Nama method remote |
| `request($incoming)` | Susun parameter (array of values / typed) |
| `values_parsing($value)` | Normalisasi nilai bertipe |
| `send_request()` | Kirim; return response object / FALSE |
| `display_response()` | Hasil dekode (array/scalar) |
| `display_error()` | Fault sebagai string |
| `send_error_message($n,$msg)` / `send_response($r)` | Output XML balasan |
| `set_debug($flag)` | Mode debug |

## Kompatibilitas CodeIgniter 3

`Xmlrpc` adalah pengganti drop-in `CI_Xmlrpc`: nama method, properti `$xmlrpc_*`, dan format payload sama. Perbedaan hanya namespace. Helper CI (`log_message`, curl config) tetap dipakai di jalur transport lama.

## Catatan

- `send_request()` mengembalikan `false` saat transport gagal ATAU server mengirim fault — cek `display_error()` untuk membedakan.
- Payload besar: naikkan batas memori/timeout; parser memakai SimpleXML internal via `Decoder/`.

## Pengujian

```bash
vendor/bin/phpunit --filter Xmlrpc
```
