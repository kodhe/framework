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
 *   --path=<dir>    folder migrasi (default: ./database/migrations)
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
        // 1. Tentukan root framework (SYSPATH) & root project
        // ----------------------------------------------------------
        $basePath = getcwd() . DIRECTORY_SEPARATOR;

        $sysCandidates = [
            // Project composer install: vendor/kodhe/framework
            $basePath . 'vendor/kodhe/framework/',
            // Monorepo/dev inline: repo induk di samping project (../framework/)
            dirname($basePath) . '/framework/',
            // Symlink path repository composer
            $basePath . 'vendor/kodhe/framework/../kodhe/framework/',
            // Jalankan langsung dari dalam folder framework itu sendiri
            $basePath,
        ];
        $syspath = null;
        foreach ($sysCandidates as $c) {
            if (is_dir($c . 'src/Core')) { $syspath = rtrim($c, '/\\') . DIRECTORY_SEPARATOR; break; }
        }
        if ($syspath === null) {
            $this->error('Framework (src/Core) tidak ditemukan — jalankan dari root project yang sudah `composer install`.');
            return 1;
        }
        defined('SYSPATH') || define('SYSPATH', $syspath);
        defined('BASEPATH') || define('BASEPATH', SYSPATH);
        defined('APPPATH')  || define('APPPATH', $basePath . 'application' . DIRECTORY_SEPARATOR);
        defined('VIEWPATH') || define('VIEWPATH', APPPATH . 'views' . DIRECTORY_SEPARATOR);
        defined('STORAGEPATH') || define('STORAGEPATH', $basePath . 'storage' . DIRECTORY_SEPARATOR);
        defined('ENVIRONMENT') || define('ENVIRONMENT', getenv('CI_ENV') ?: 'production');

        // Composer autoload milik PROJECT (memuat config app + package lain).
        $projectAutoload = $basePath . 'vendor/autoload.php';
        if (!class_exists(\Kodhe\Framework\Database\Loader::class, false)) {
            if (file_exists($projectAutoload)) {
                require_once $projectAutoload;
            } elseif (file_exists(SYSPATH . 'vendor/autoload.php')) {
                require_once SYSPATH . 'vendor/autoload.php';
            } else {
                $this->error("vendor/autoload.php tidak ditemukan. Jalankan 'composer install' di project.");
                return 1;
            }
        }

        // Helper global kodhe()/get_instance()/config_item().
        // Prioritas: bootstrap/app.php milik PROJECT (berisi definisi helper
        // + inisialisasi sistem). File ini TIDAK memanggil die(), jadi aman
        // dipakai dari CLI. Jika tidak ada, fallback ke common.php framework.
        if (!function_exists('kodhe')) {
            $bootCandidates = [
                $basePath . 'bootstrap/app.php',
                SYSPATH . 'bootstrap/app.php',
                SYSPATH . 'src/Support/Legacy/common.php',
            ];
            foreach ($bootCandidates as $h) {
                if (file_exists($h)) {
                    require_once $h;
                    if (function_exists('kodhe')) { break; }
                }
            }
        }
        if (!function_exists('kodhe')) {
            $this->error('Helper kodhe() tidak tersedia — periksa bootstrap/app.php project.');
            return 1;
        }

        // ----------------------------------------------------------
        // 2. Koneksi database
        //    Loader::database() memakai helper kodhe()/config_item()
        //    yang tersedia begitu autoloader + konstanta di atas siap;
        //    bootstrap/app.php sengaja TIDAK di-require penuh di sini
        //    agar tidak memicu header() warning pada SAPI CLI.
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

        $table = is_string($t = $this->option('table')) ? $t : 'migrations';
        $migDir = is_string($p = $this->option('path'))
            ? rtrim($p, '/\\') . DIRECTORY_SEPARATOR
            : $basePath . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR;

        if (!is_dir($migDir)) {
            $this->error("Folder migrasi tidak ada: {$migDir}");
            return 1;
        }

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
        // 3. --status
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
        // 4. --rollback (undo 1 batch terakhir)
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
        // 5. --fresh (rollback semua lalu up)
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
        // 6. Migrate UP (default)
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

        $this->writeln('<info>Selesai.</info> Versi aktif: ' . array_pop(array_column($pending, 0)));
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
            if (is_file($file)) {
                $migration = require $file;
                if (is_object($migration) && method_exists($migration, 'down')) {
                    $migration->down();
                }
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
        $out = [];
        foreach ($db->query('SELECT version FROM ' . $db->protect_identifiers($table))->result_array() as $r) {
            $out[$r['version']] = true;
        }
        return $out;
    }
}
