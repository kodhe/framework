# Utilities (Paket Pendukung)

Halaman ringkas; tiap baris menaut ke README kanonik paket.

| Paket | Kelas utama | Quick note |
|---|---|---|
| [agent](../../agent/README.md) | `Agent\UserAgent` | Deteksi browser/platform/mobile/robot; `$CI->agent->browser()` |
| [image](../../image/README.md) | `Image\Image` + `Drivers\GdDriver` | resize/crop/rotate/watermark via GD |
| [upload](../../upload/README.md) | `Upload\Upload` | Validasi & pemindahan file upload |
| [ftp](../../ftp/README.md) | `Ftp\Ftp` | Klien FTP (login, put/get, dir listing) |
| [zip](../../zip/README.md) | `Zip\Zip` | Buat/ekstrak arsip ZIP |
| [table](../../table/README.md) | `Table\Table` | Generate HTML table dari array/query result |
| [parser](../../parser/README.md) | `Parser\*` | Template tag-pair `{name}` ala CI3 parser |
| [calendar](../../calendar/README.md) | `Calendar\Calendar` | Kalender HTML monthly/weekly/daily |
| [javascript](../../javascript/README.md) | `Javascript\Javascript` | Prototype/jQuery/JQueryLib helper JS |
| [typography](../../typography/README.md) | `Typography\Typography` | autoyp, smart_quotes, nl2br_br |
| [profiler](../../profiler/README.md) | `Profiler\Profiler` | Section & collector debugging waktu/memory/query |
| [cache](../../cache/README.md) | Factory cache | File/apc/redis/memcached/wincache dev-null |
| [cart](../../cart/README.md) | `Cart\Cart` | Keranjang belanja session-based |

Contoh umum (pilih salah satu untuk mulai):

```php
use Kodhe\Framework\Agent\UserAgent;
$ua = new UserAgent($_SERVER);
if ($ua->is_mobile()) { /* … */ }

use Kodhe\Framework\Cache\CacheFactory;
$cache = CacheFactory::file(['store_path' => sys_get_temp_dir() . '/cache']);
$cache->save('sidebar', $html, 600);
```
