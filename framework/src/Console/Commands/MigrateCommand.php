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

    public function handle(): int
    {
        // ----------------------------------------------------------
        // 1. Root framework (SYSPATH) — deteksi lewat autoloader, bukan
        //    menebak folder src/Core. File ini sendiri dimuat composer,
        //    jadi lokasi class Console adalah sumber kebenaran.
        // ----------------------------------------------------------
        try {
            $consoleFile = (new \ReflectionClass(Console::class))->getFileName();
        } catch (\Throwable) {
            $consoleFile = false;
        }

        if (!is_string($consoleFile)) {
            $this->error('Framework tidak ditemukan via autoloader — jalankan dari project yang sudah `composer install`.');
            return 1;
        }

        // vendor/kodhe/framework/src/Console/Console.php -> .../framework/
        // framework/console.php                          -> .../src/
        $syspath = null;
        foreach ([dirname($consoleFile, 3), dirname($consoleFile, 2)] as $candidate) {
            if (is_dir($candidate . DIRECTORY_SEPARATOR . 'src')) {
                $syspath = $candidate . DIRECTORY_SEPARATOR;
                break;
            }
        }

        if ($syspath === null) {
            $this->error("Lokasi framework tidak dikenali dari {$consoleFile}");
            return 1;
        }

        defined('SYSPATH')       || define('SYSPATH', $syspath);
        defined('BASEPATH')      || define('BASEPATH', SYSPATH);
        defined('ENVIRONMENT')   || define('ENVIRONMENT', getenv('CI_ENV') ?: 'production');

        // ----------------------------------------------------------
        // 2. Root project = folder aplikasi yang memanggil console.
        //    Kandidat (berdasarkan CWD saat perintah dijalankan):
        //      a) cwd          -> `cd kodhe && php bin/console ...`
        //      b) cwd/bin      -> `php vendor/kodhe/framework/bin/console ...`
        //      c) cwd/../      -> `php ../framework/bin/console ...`
        //      d) cwd/../bin   -> variasi pemanggilan lain
        //    Project ditandai oleh application/config/database.php dan/atau
        //    folder database/migrations milik aplikasi.
        // ----------------------------------------------------------
        $cwd = rtrim(getcwd() ?: '.', '/\\') . DIRECTORY_SEPARATOR;

        $candidates = [
            $cwd,
            $cwd . 'bin' . DIRECTORY_SEPARATOR,
            dirname($cwd) . DIRECTORY_SEPARATOR,
            dirname($cwd) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR,
        ];

        $basePath = null;
        foreach ($candidates as $c) {
            if (file_exists($c . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php')
                || is_dir($c . 'database' . DIRECTORY_SEPARATOR . 'migrations')) {
                $basePath = $c;
                break;
            }
        }

        if ($basePath === null) {
            $this->error(
                "Folder project tidak ditemukan dari CWD ({$cwd}).\n"
                . "Jalankan dari root project, contoh:\n"
                . "  cd kodhe && php bin/console migrate\n"
                . "  php vendor/kodhe/framework/bin/console migrate"
            );
            return 1;
        }

        defined('APPPATH')     || define('APPPATH', $basePath . 'application' . DIRECTORY_SEPARATOR);
        defined('VIEWPATH')    || define('VIEWPATH', APPPATH . 'views' . DIRECTORY_SEPARATOR);
        defined('STORAGEPATH') || define('STORAGEPATH', $basePath . 'storage' . DIRECTORY_SEPARATOR);

        // ----------------------------------------------------------
        // 3. Autoload project (memuat package database, config app, dll.)
        //    Kalau command dipanggil lewat vendor project, autoload ini
        //    sebenarnya sudah terdaftar — cek class-nya dulu.
        // ----------------------------------------------------------
        if (!class_exists(\Kodhe\Framework\Database\Loader::class)) {
            foreach ([
                $basePath . 'vendor/autoload.php',
                SYSPATH . 'vendor/autoload.php',
                dirname($consoleFile, 4) . '/autoload.php', // vendor/<author>/<pkg>/bin -> vendor/autoload.php
            ] as $autoload) {
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

        // Helper global kodhe()/config_item() — seharusnya ikut ter-load
        // lewat composer "files" autoload milik framework.
        if (!function_exists('kodhe')) {
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
            $this->error('Helper kodhe() tidak tersedia — jalankan dari root project yang sudah `composer install`.');
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
