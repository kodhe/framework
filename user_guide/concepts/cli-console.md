# CLI Console (Barisan Perintah)

Kodhe Framework punya komponen Console bawaan (`framework/src/Console/`,
namespace `Kodhe\Framework\Console\`) untuk menjalankan tugas development dan
maintenance dari command line: code generation, migrasi, clear cache, jobs,
dan command bisnis custom Anda sendiri.

> Ini adalah **console kernel modern** (gaya Symfony/Laravel), berbeda dari
> `$this->cli` CI3 yang terikat pada URI segment. Aplikasi CI3 lama yang
> memakai `CI_Controller` untuk CLI tetap jalan via kernel kompatibel —
> lihat §6 untuk cara memigrasikannya.

## 1. Arsitektur Singkat

| Kelas | Peran |
|---|---|
| `Console` | Manager aplikasi CLI — **singleton** (`Console::getInstance()`), registry command, eksekusi `run()` |
| `Command` | Base class abstrak untuk semua command; subclass wajib mengimplementasi `handle(): int` |
| `Input` | Parser token argv → argumen posisional/nama + opsi (`--foo=bar`, `-f`) |
| `Output` | Writer berwarna: `writeln/info/success/warning/error/debug/table` |
| `Commands\HelpCommand` | `help [command]` — bantuan umum atau per command |
| `Commands\ListCommand` | `list` — daftar semua command terdaftar |
| `Commands\VersionCommand` | `version` — versi aplikasi console |
| `Commands\MakeCommand` | `make:*` — generator boilerplate (lihat §5) |

Empat command di atas otomatis terdaftar saat `Console` dibuat
(`registerDefaultCommands()`). Command custom ditambahkan via
`addCommand()` / `addCommands()`.

## 2. Menjalankan Console

Buat bootstrap sekali jalan di proyek Anda (mis. `bin/console`):

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

use Kodhe\Framework\Console\Console;

$console = Console::getInstance();
$console->setName('Aplikasi Saya');
$console->setVersion('1.0.0');

// Registrasi command custom proyek (opsional)
foreach (glob(__DIR__ . '/../app/Console/Commands/*.php') as $file) {
    $class = 'App\\Console\\Commands\\' . basename($file, '.php');
    if (class_exists($class)) {
        $console->addCommand(new $class());
    }
}

exit($console->run());   // parse argv, jalankan command, kembalikan exit code
```

```bash
php bin/console list
php bin/console make:model User
php bin/console help migrate
```

`run(?Input $input = null, ?Output $output = null)` memakai argv asli bila
tidak diberi argumen — sehingga juga bisa dipanggil dari kode dengan
`$console->runCommand('make:model', ['User'], $output)` untuk pengujian.

## 3. Membuat Command Sendiri

Extend `Command`, deklarasikan properti, implementasi `handle()`:

```php
namespace App\Console\Commands;

use Kodhe\Framework\Console\Command;

class SyncDataCommand extends Command
{
    protected string $name = 'sync:data';
    protected string $description = 'Sinkronisasi data ke sistem eksternal';
    protected array $usage = ['sync:data [--force] [--limit=100]'];
    protected array $arguments = ['source' => 'Nama sumber data'];
    protected array $options = [
        'force' => 'Paksa sinkron meski sudah berjalan hari ini',
        'limit' => 'Batas jumlah baris per siklus',
    ];
    protected array $aliases = ['sd'];

    public function handle(): int
    {
        $source = $this->argument('source');          // argumen by nama/posisi
        $force  = $this->hasOption('force');          // flag boolean
        $limit  = (int) $this->option('limit', 100);  // opsi + default

        $this->info("Sinkron sumber: {$source}");
        // ... bisnis logic (pakai Model / Service container seperti biasa) ...
        $this->success('Selesai.');
        return 0;   // 0 = sukses; bukan-0 dipakai sebagai exit code
    }
}
```

API helper yang tersedia di dalam `handle()` (semua dari base `Command`):

- Input: `argument()`, `option()`, `hasArgument()`, `hasOption()`
- Output: `write()`, `writeln()`, `info()`, `success()`, `warning()`,
  `error()`, `debug()`, `table($headers, $rows)`
- Interaktif: `ask($pertanyaan, $default)`, `confirm($q, $default)`,
  `choice($q, array $opsi, $default)`

Parse `Input` mendukung argumen posisional (`$this->argument(0)`), opsi panjang
`--nama=value` / `--flag`, dan opsi pendek gabungan `-abc`.

## 4. Konvensi Lokasi & Namespace

| Item | Lokasi standar | Namespace |
|---|---|---|
| Command custom | `app/Console/Commands/` | `App\Console\Commands` |
| Generator scaffold | — | dibuat otomatis oleh `make:command` |

Sama seperti komponen lain: folder PascalCase, tanpa underscore
(lihat [struktur-folder-routing](folder-structure-and-routing.md)).

## 5. Code Generation (`make:*`)

Dari `MakeCommand` (alias: `generate`, `g`):

```bash
php bin/console make:command sync_data     # -> app/Console/Commands      (App\Console\Commands)
php bin/console make:controller Article    # -> app/Controllers           (App\Controllers)
php bin/console make:model Article         # -> app/Models                (App\Models)
php bin/console make:migration create_articles_table
php bin/console make:middleware ThrottleApi
php bin/console make:crud Article title:string body:text published:bool   # lihat §5b
```

Opsi global generator: `--force` (timpa file yang ada), `--path=` (lokasi
output custom). Detail tiap artefak hasil generate ada di
[app-components](app-components.md).

## 5b. Generator CRUD (`make:crud`)

Satu perintah menghasilkan **satu set lengkap CRUD** (ala framework modern,
tetap bergaya CI3/native): Model + Controller resource penuh + migration
(`Loader::dbforge()` stack modern) + 5 view CI3-style
(`index/create/edit/show/_form`) di `app/Views/<resource>/`.

```bash
php console make:crud Article title:string slug:string body:text published:bool
# -> app/Models/Article.php
#    app/Controllers/ArticleController.php
#    database/migrations/<ts>_create_articles_table.php
#    app/Views/articles/{index,create,edit,show,_form}.php
```

Panduan user guide lengkap (tipe field, peta route, alur kerja, catatan
validasi/CSRF): [generator-crud](../general/crud-generator.md).

## 5a. Membuat Proyek Baru (`new`) & Dev Server (`serve`)

Selain generator kode, Console menyediakan dua perintah bootstrap proyek:

```bash
php console new my-app            # scaffold kerangka proyek Kodhe lengkap
php console serve                 # dev server di http://localhost:8080
```

- `new` (alias `create-project`, `new:project`) membuat struktur folder standar,
  `composer.json` PSR-4 `App\`, config, routes (legacy + modern), `.env`, dan
  kode starter. Opsi: `--force` (direktori tidak kosong), `--min` (tanpa demo).
- `serve` menjalankan PHP built-in server dengan `public/index.php` sebagai
  router; opsi `--host=` dan `--port=`.

Panduan lengkap (struktur hasil generate, isi config, alur cepat):
[membuat-proyek-baru](../general/new-project.md).

## 6. Migrasi CLI dari CI3

CI3 biasanya membuat "controller khusus CLI" (`class Cli extends CI_Controller`
dengan cek `is_cli()`). Peta konversinya:

| Pola CI3 | Padanan Kodhe Console |
|---|---|
| `if (!is_cli()) show_404();` | tidak perlu — `Console::run()` hanya dieksekusi dari CLI |
| Method controller sebagai "command" | satu kelas `Command` per tugas, nama eksplisit (`sync:data`) |
| `$argv[2]` manual | `$this->argument(...)` / `$this->option(...)` |
| `echo` / `PHP_EOL` | `$this->writeln()/success()/error()/table()` |
| Exit via `show_error()` | `return 1;` dari `handle()` |
| Cron memanggil `index.php cli/sync` | cron memanggil `php bin/console sync:data` |

Langkah migrasi:

1. Jalur lama tetap jalan: selama kernel kompatibel aktif, URL `cli/*` model
   CI3 dapat dieksekusi lewat `php index.php cli/sync` seperti biasa — cron
   tidak langsung harus diubah.
2. Buat command baru dengan `make:command <nama>`, pindahkan isi method
   controller lama ke `handle()`; akses service/model pakai pola modern
   (`new App\Models\...` atau container), bukan `$this->load` superobject.
3. Update crontab/systemd timer satu per satu ke `php bin/console <command>`.
4. Setelah semua job pindah, hapus controller CLI legacy.

Keuntungan setelah migrasi: exit code yang benar untuk monitoring, output
berwarna + tabel untuk log, perintah interaktif (`confirm()`) untuk operasi
berbahaya, dan command ter-registrasi rapi (`list`/`help` otomatis).

## Tautan

- Sumber teknis: [`framework/src/Console/README.md`](../../framework/src/Console/README.md)
- [app-components](app-components.md) — artefak yang dihasilkan `make:*`
- [migrating-from-ci3](migrating-from-ci3.md) — strategi migrasi menyeluruh
