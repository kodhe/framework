<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console\Commands;

use Kodhe\Framework\Console\Command;

/**
 * Migrate Command — jalankan migrasi database dari CLI.
 *
 * Membaca file migrasi di database/migrations/ (format timestamp
 * 2026_09_26_000001_nama_tabel.php) yang mengembalikan anonim-class
 * dengan method up()/down(), mencatat versinya di tabel `migrations`.
 *
 * Usage:
 *   php console migrate              # naik ke versi terbaru (migrate up)
 *   php console migrate --status     # daftar migrasi + status (applied/pending)
 *   php console migrate --rollback   # undo satu batch migrasi terakhir
 *   php console migrate --fresh      # rollback semua, lalu migrate up
 *
 * Opsi:
 *   --path=<dir>    folder migrasi (default: <project>/database/migrations)
 *   --table=<name>  tabel pelacak versi (default: migrations)
 */
class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Jalankan migrasi database (up / rollback / status)';
    protected array $usage = [
        'migrate',
        'migrate --status',
        'migrate --rollback',
        'migrate --fresh',
    ];

    /**
     * Lokasi file MigrateCommand.php ini berada — dipakai untuk menelusuri
     * root package / root project tanpa bergantung pada CWD.
     */
    private string $selfFile;

    public function __construct()
    {
        $this->selfFile = (new \ReflectionClass(self::class))->getFileName();
    }

    public function handle(): int
    {
        // ----------------------------------------------------------
        // 1. Root framework (SYSPATH) — telusuri dari posisi file INI
        //    (realpath mengikuti symlink composer path-repository),
        //    bukan dari CWD dan bukan dari tebakan folder src/Core.
        //
        //    Kasus yang didukung:
        //      a) monorepo : <root>/framework/src/Console/Commands/...
        //      b) install  : <project>/vendor/kodhe/framework/src/...
        //         (symlink ke ../framework juga beres karena realpath)
        // ----------------------------------------------------------
        $syspath = null;
        for ($dir = dirname($this->selfFile); $dir !== '' && $dir !== DIRECTORY_SEPARATOR; $dir = dirname($dir)) {
            // root package  : .../framework          (punya composer.json + src/)
            // atau langsung : .../framework/src      (jarang)
            if (is_file($dir . '/composer.json') && is_dir($dir . '/src')) {
                $syspath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
                break;
            }
        }

        if ($syspath === null) {
            $this->error(
                "Root framework (composer.json + src/) tidak ditemukan di atas file:\n  {$this->selfFile}\n"
                . "Pastikan instalasi composer utuh (`composer install` / `composer dump-autoload`)."
            );
            return 1;
        }

        defined('SYSPATH')     || define('SYSPATH', $syspath);
        defined('BASEPATH')    || define('BASEPATH', SYSPATH);
        defined('ENVIRONMENT') || define('ENVIRONMENT', getenv('CI_ENV') ?: 'production');

        // ----------------------------------------------------------
        // 2. Root project = folder aplikasi yang memanggil console.
        //    Prioritas:
        //      a) KODHE_PROJECT_ROOT (di-set oleh bootstrap/bin/console)
        //      b) argv[0] — folder script yang benar-benar dipanggil,
        //         mis. `php bin/console migrate` atau
        //         `php /path/project/bin/console ...` (tidak peduli CWD!)
        //      c) CWD dan variasinya (fallback lama)
        //      d) monorepo: <syspath>/../kodhe
        //    Project ditandai oleh application/config/database.php
        //    dan/atau folder database/migrations milik aplikasi.
        // ----------------------------------------------------------
        $isProject = function (string $dir): bool {
            $dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
            return file_exists($dir . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php')
                || is_dir($dir . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        };

        /** @var list<string> $candidates */
        $candidates = [];

        if (($envRoot = getenv('KODHE_PROJECT_ROOT')) !== false && $envRoot !== '') {
            $candidates[] = $envRoot;
        }

        // argv[0]: coba apa adanya (relatif thd CWD), lalu absolut dari
        // lokasi script via get_included_files(), lalu dari CWD.
        $argv0 = $_SERVER['argv'][0] ?? '';
        if ($argv0 !== '') {
            $candidates[] = dirname($argv0);                       // bin/ -> project
            $candidates[] = dirname(dirname($argv0));              // ./bin/console dsb.
            foreach (get_included_files() as $inc) {               // script entry-point asli
                if (str_ends_with($inc, 'console') || str_ends_with($inc, 'console.php')) {
                    $candidates[] = dirname($inc);
                    $candidates[] = dirname(dirname($inc));
                }
            }
        }

        $cwd = rtrim(getcwd() ?: '.', '/\\') . DIRECTORY_SEPARATOR;
        $candidates[] = $cwd;
        $candidates[] = dirname($cwd);
        $candidates[] = SYSPATH;                                   // kebetulan project itu sendiri?

        // monorepo: framework berada di <root>/framework, project contoh di <root>/kodhe
        $candidates[] = dirname(rtrim(SYSPATH, '/\\'));
        $candidates[] = dirname(rtrim(SYSPATH, '/\\')) . DIRECTORY_SEPARATOR . 'kodhe';

        $basePath = null;
        foreach ($candidates as $c) {
            $real = realpath($c);
            if ($real !== false && $isProject($real)) {
                $basePath = rtrim($real, '/\\') . DIRECTORY_SEPARATOR;
                break;
            }
        }

        if ($basePath === null) {
            $this->error(
                "Folder project (application/config/database.php atau database/migrations/) tidak ditemukan.\n"
                . "Dicari dari: " . implode('; ', array_filter(array_map('strval', $candidates))) . "\n"
                . "Solusi:\n"
                . "  1) Jalankan dari root project : cd <project> && php bin/console migrate\n"
                . "  2) Atau tunjuk eksplisit     : KODHE_PROJECT_ROOT=/path/project php bin/console migrate\n"
                . "  3) Atau lewat vendor project : php vendor/kodhe/framework/bin/console migrate"
            );
            return 1;
        }

        defined('APPPATH')     || define('APPPATH', $basePath . 'application' . DIRECTORY_SEPARATOR);
        defined('VIEWPATH')    || define('VIEWPATH', APPPATH . 'views' . DIRECTORY_SEPARATOR);
        defined('STORAGEPATH') || define('STORAGEPATH', $basePath . 'storage' . DIRECTORY_SEPARATOR);

        // ----------------------------------------------------------
        // 3. Autoload project (memuat package database, config app, dll.)
        //    Urutan pencarian: project vendor -> framework vendor ->
        //    monorepo root vendor -> autoload composer dari file command.
        // ----------------------------------------------------------
        if (!class_exists(\Kodhe\Framework\Database\Loader::class)) {
            $autoloaders = [
                $basePath . 'vendor/autoload.php',
                SYSPATH . 'vendor/autoload.php',
                dirname(rtrim(SYSPATH, '/\\')) . '/vendor/autoload.php', // monorepo root
            ];

            // vendor/<author>/<pkg>/src/Console/Commands/MigrateCommand.php
            //   -> dirname(selfFile, 5) = vendor/autoload.php
            // monorepo <root>/framework/src/... -> dirname(selfFile, 4) = <root>/vendor/
            foreach ([dirname($this->selfFile, 5), dirname($this->selfFile, 4)] as $d) {
                $autoloaders[] = rtrim($d, '/\\') . '/autoload.php';
            }

            foreach ($autoloaders as $autoload) {
                if (is_file($autoload)) {
                    require_once $autoload;
                    break;
                }
            }
        }

        if (!class_exists(\Kodhe\Framework\Database\Loader::class)) {
            $this->error(
                "Komponen database (kodhe/database) belum ter-install di project.\n"
                . "Tambahkan di composer.json project lalu jalankan 'composer update':"
                . "\n    \"kodhe/database\": \"dev-main\""
            );
            return 1;
        }

        // Helper global kodhe()/config_item() — dimuat lewat composer
        // "files" autoload milik framework. Bila belum ada, definisikan
        // shim singleton Facade DULU (agar require helper bawaan dapat
        // memakai function_exists('kodhe') tanpa mendefinisikan ulang),
        // baru muat file support legacy.
        if (!function_exists('kodhe') && class_exists(\Kodhe\Framework\Support\Facades\Facade::class)) {
            /**
             * Singleton accessor setara helper CI `get_instance()` —
             * mengembalikan service container Facade ($GLOBALS['kodhe']).
             */
            function kodhe()
            {
                return \Kodhe\Framework\Support\Facades\Facade::getInstance();
            }
        }

        if (!function_exists('config_item')) {
            foreach ([
                SYSPATH . 'src/Support/Legacy/common.php',
                SYSPATH . 'src/Support/Helpers.php',
                SYSPATH . 'src/Support/app_path.php',
            ] as $h) {
                if (file_exists($h)) {
                    require_once $h;
                }
            }
        }

        if (!function_exists('kodhe')) {
            $this->error(
                "Helper kodhe() tidak tersedia dan class Facade tidak ditemukan.\n"
                . "Kemungkinan instalasi composer rusak/stale di project:\n  {$basePath}\n"
                . "Jalankan ulang: composer install (atau composer dump-autoload -o)"
            );
            return 1;
        }

        // ----------------------------------------------------------
        // 4. Koneksi database (config: <project>/application/config/database.php)
        // ----------------------------------------------------------
        try {
            \Kodhe\Framework\Database\Loader::database();
            $db = kodhe()->db;
        } catch (\Throwable $e) {
            $this->error('Gagal koneksi database: ' . $e->getMessage());
            return 1;
        }

        if (!$db) {
            $this->error('Koneksi DB kosong — periksa application/config/database.php ($active_group).');
            return 1;
        }

        $table  = is_string($t = $this->option('table')) ? $t : 'migrations';
        $migDir = is_string($p = $this->option('path'))
            ? rtrim($p, '/\\') . DIRECTORY_SEPARATOR
            : $basePath . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR;

        if (!is_dir($migDir)) {
            $this->error("Folder migrasi tidak ada: {$migDir}\nBuat dengan: php console make:migration <nama_table>");
            return 1;
        }

        $this->writeln('<comment>Project:</comment> ' . rtrim($basePath, '/\\'));
        $this->writeln('<comment>Migrations:</comment> ' . rtrim($migDir, '/\\'));

        // Tabel pelacak versi (kolom mengikuti konvensi CI3 migration_table).
        $db->query(
            'CREATE TABLE IF NOT EXISTS ' . $db->protect_identifiers($table) . ' ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
            . 'version VARCHAR(55) NOT NULL DEFAULT 0,'
            . 'class VARCHAR(255) NOT NULL,'
            . 'group_name VARCHAR(255) NOT NULL DEFAULT \'default\','
            . 'batch INTEGER NOT NULL DEFAULT 0'
            . ')'
        );

        $files = glob($migDir . '*.php');
        sort($files, SORT_NATURAL);

        // ----------------------------------------------------------
        // 5. --status
        // ----------------------------------------------------------
        if ($this->option('status')) {
            $applied = $this->appliedVersions($db, $table);
            $this->writeln('<info>Status migrasi</info> (' . count($files) . ' file, ' . count($applied) . ' applied)');
            foreach ($files as $f) {
                $v = basename($f, '.php');
                $tag = isset($applied[$v])
                    ? '<comment>[applied]</comment>'
                    : '<fg=red>[pending]</>';
                $this->writeln('  ' . $tag . ' ' . $v);
            }
            return 0;
        }

        // ----------------------------------------------------------
        // 6. --rollback (undo 1 batch terakhir)
        // ----------------------------------------------------------
        if ($this->option('rollback')) {
            $row = $db->query('SELECT MAX(batch) AS b FROM ' . $db->protect_identifiers($table))->row();
            $lastBatch = (int) ($row->b ?? 0);
            if ($lastBatch < 1) {
                $this->writeln('<comment>Tidak ada batch untuk di-rollback.</comment>');
                return 0;
            }
            $rows = $db->query(
                'SELECT version, class FROM ' . $db->protect_identifiers($table)
                . ' WHERE batch = ' . $lastBatch . ' ORDER BY id DESC'
            )->result_array();

            $this->writeln("<info>Rollback batch {$lastBatch}</info> (" . count($rows) . ' migrasi)');
            foreach ($rows as $r) {
                $file = $migDir . $r['version'] . '.php';
                if (!is_file($file)) {
                    $this->writeln('  <fg=red>×</> file hilang: ' . $r['version']);
                    continue;
                }
                $migration = require $file;
                if (is_object($migration) && method_exists($migration, 'down')) {
                    $migration->down();
                    $db->query('DELETE FROM ' . $db->protect_identifiers($table) . ' WHERE version = ' . $db->escape($r['version']));
                    $this->writeln('  <comment>↓</comment> ' . $r['version']);
                }
            }
            $this->writeln('<info>Rollback selesai.</info>');
            return 0;
        }

        // ----------------------------------------------------------
        // 7. --fresh (rollback semua lalu up)
        // ----------------------------------------------------------
        if ($this->option('fresh')) {
            for ($i = 0; $i < 1000; $i++) {
                $row = $db->query('SELECT MAX(batch) AS b FROM ' . $db->protect_identifiers($table))->row();
                if ((int) ($row->b ?? 0) < 1) {
                    break;
                }
                $rc = $this->rollbackAll($db, $table, $migDir);
                if ($rc !== 0) {
                    return $rc;
                }
            }
            $this->writeln('<info>Fresh: semua migrasi di-rollback, menjalankan ulang…</info>');
        }

        // ----------------------------------------------------------
        // 8. Migrate UP (default)
        // ----------------------------------------------------------
        $applied = $this->appliedVersions($db, $table);
        $row = $db->query('SELECT MAX(batch) AS b FROM ' . $db->protect_identifiers($table))->row();
        $batch = (int) ($row->b ?? 0) + 1;

        $pending = [];
        foreach ($files as $f) {
            $v = basename($f, '.php');
            if (!isset($applied[$v])) {
                $pending[] = [$v, $f];
            }
        }

        if (empty($pending)) {
            $this->writeln('<info>Up to date.</info> Tidak ada migrasi pending.');
            return 0;
        }

        $this->writeln('<info>Migrasi up:</info> ' . count($pending) . ' pending (batch ' . $batch . ')');

        foreach ($pending as [$version, $file]) {
            $migration = require $file;

            if (!is_object($migration) || !method_exists($migration, 'up')) {
                $this->error("Migrasi {$version} tidak valid (harus return objek dengan method up()/down()).");
                return 1;
            }

            $migration->up();

            $db->query(
                'INSERT INTO ' . $db->protect_identifiers($table) . ' (version, class, group_name, batch) VALUES ('
                . $db->escape($version) . ', '
                . $db->escape('Migration_' . $version) . ', '
                . $db->escape('default') . ', '
                . $batch . ')'
            );

            $this->writeln('  <comment>↑</comment> ' . $version);
        }

        $this->writeln('<info>Selesai.</info> Versi aktif: ' . $pending[count($pending) - 1][0]);
        return 0;
    }

    /**
     * Rollback satu batch terakhir (dipakai --fresh berulang sampai kosong).
     */
    private function rollbackAll($db, string $table, string $migDir): int
    {
        $row = $db->query('SELECT MAX(batch) AS b FROM ' . $db->protect_identifiers($table))->row();
        $lastBatch = (int) ($row->b ?? 0);
        if ($lastBatch < 1) {
            return 0;
        }
        $rows = $db->query(
            'SELECT version FROM ' . $db->protect_identifiers($table)
            . ' WHERE batch = ' . $lastBatch . ' ORDER BY id DESC'
        )->result_array();

        foreach ($rows as $r) {
            $file = $migDir . $r['version'] . '.php';
            if (!is_file($file)) {
                $this->error('File migrasi hilang saat rollback: ' . $r['version']);
                return 1;
            }
            $migration = require $file;
            if (is_object($migration) && method_exists($migration, 'down')) {
                $migration->down();
            }
            $db->query('DELETE FROM ' . $db->protect_identifiers($table) . ' WHERE version = ' . $db->escape($r['version']));
            $this->writeln('  <comment>↓</comment> ' . $r['version']);
        }
        return 0;
    }

    /**
     * Daftar versi yang sudah diterapkan => true.
     */
    private function appliedVersions($db, string $table): array
    {
        $applied = [];
        $rows = $db->query('SELECT version FROM ' . $db->protect_identifiers($table))->result_array();
        foreach ($rows as $r) {
            $applied[$r['version']] = true;
        }
        return $applied;
    }
}
