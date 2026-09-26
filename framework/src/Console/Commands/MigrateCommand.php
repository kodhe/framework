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
        // 0. Root project — sudah disiapkan oleh Console::run() via
        //    Kodhe\Framework\Console\Bootstrap (dipanggil dari
        //    bin/console, padanan index.php untuk CLI). Konstanta
        //    APPPATH/VIEWPATH/STORAGEPATH dan helper kodhe() juga
        //    sudah tersedia pada titik ini.
        // ----------------------------------------------------------
        $basePath = \Kodhe\Framework\Console\Bootstrap::projectRoot();

        if ($basePath === null) {
            // Fallback: telusuri root framework (composer.json + src/)
            // dari posisi file INI (realpath mengikuti symlink composer
            // path-repository), lalu pakai konvensi monorepo <root>/kodhe.
            $isProject = function (string $dir): bool {
                $dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
                return file_exists($dir . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php')
                    || is_dir($dir . 'database' . DIRECTORY_SEPARATOR . 'migrations');
            };

            $syspath = null;
            for ($dir = dirname($this->selfFile); $dir !== '' && $dir !== DIRECTORY_SEPARATOR; $dir = dirname($dir)) {
                if (is_file($dir . '/composer.json') && is_dir($dir . '/src')) {
                    $syspath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
                    break;
                }
            }

            $candidates = [];
            if ($syspath !== null) {
                $candidates[] = dirname(rtrim($syspath, '/\\'));                          // project == folder framework?
                $candidates[] = dirname(rtrim($syspath, '/\\')) . DIRECTORY_SEPARATOR . 'kodhe'; // monorepo
            }
            $argv0 = $_SERVER['argv'][0] ?? '';
            if ($argv0 !== '') {
                $candidates[] = dirname($argv0);
                $candidates[] = dirname(dirname($argv0));
            }
            $cwd = rtrim(getcwd() ?: '.', '/\\') . DIRECTORY_SEPARATOR;
            $candidates[] = $cwd;
            $candidates[] = dirname($cwd);

            foreach ($candidates as $c) {
                $real = realpath($c);
                if ($real !== false && $isProject($real)) {
                    $basePath = rtrim($real, '/\\') . DIRECTORY_SEPARATOR;
                    break;
                }
            }
        }

        if ($basePath === null) {
            $this->error(
                "Folder project (application/config/database.php atau database/migrations/) tidak ditemukan.\n"
                . "Solusi:\n"
                . "  1) Jalankan dari root project : cd <project> && php bin/console migrate\n"
                . "  2) Atau tunjuk eksplisit     : KODHE_PROJECT_ROOT=/path/project php bin/console migrate"
            );
            return 1;
        }

        defined('SYSPATH')     || define('SYSPATH', dirname(dirname($this->selfFile)) . DIRECTORY_SEPARATOR);
        defined('BASEPATH')    || define('BASEPATH', SYSPATH);
        defined('ENVIRONMENT') || define('ENVIRONMENT', getenv('CI_ENV') ?: 'production');
        defined('APPPATH')     || define('APPPATH', $basePath . 'application' . DIRECTORY_SEPARATOR);
        defined('VIEWPATH')    || define('VIEWPATH', APPPATH . 'views' . DIRECTORY_SEPARATOR);
        defined('STORAGEPATH') || define('STORAGEPATH', $basePath . 'storage' . DIRECTORY_SEPARATOR);

        // ----------------------------------------------------------
        // 2. Autoload package database (kompatibilitas: bila project
        //    belum me-require kodhe/database, coba autoload alternatif).
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

        // ----------------------------------------------------------
        // 3. Helper global kodhe()/app()/get_instance() sudah disiapkan
        //    oleh Console::run() -> Console\Bootstrap::boot() sebelum
        //    command ini dieksekusi (bin/console = padanan index.php).
        // ----------------------------------------------------------
        if (!function_exists('kodhe')) {
            \Kodhe\Framework\Console\Bootstrap::boot(rtrim($basePath, '/\\'));
        }

        if (!function_exists('kodhe')) {
            $this->error(
                "Helper kodhe() tetap tidak tersedia setelah Console\\Bootstrap::boot().\n"
                . "Pastikan class Kodhe\\Framework\\Support\\Facades\\Facade dapat di-autoload."
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
        // SQL dibuat per-dialect: sintaks SQLite (AUTOINCREMENT) tidak valid
        // di MySQL/MariaDB, dan sebaliknya.
        $this->ensureMigrationsTable($db, $table);

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
     * Buat tabel pelacak migrasi bila belum ada, dengan dialek yang sesuai
     * driver koneksi aktif (sqlite / pdo-sqlite vs mysql / mysqli / lainnya).
     */
    private function ensureMigrationsTable($db, string $table): void
    {
        $dialect = strtolower((string) ($db->dbdriver ?? ''));
        if ($dialect === 'pdo') {
            $dialect = 'pdo-' . strtolower((string) ($db->subdriver ?? ''));
        }

        $t = $db->protect_identifiers($table);

        if (str_contains($dialect, 'sqlite')) {
            $db->query('CREATE TABLE IF NOT EXISTS ' . $t . ' ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
                . 'version VARCHAR(55) NOT NULL DEFAULT 0,'
                . 'class VARCHAR(255) NOT NULL,'
                . "group_name VARCHAR(255) NOT NULL DEFAULT 'default',"
                . 'batch INTEGER NOT NULL DEFAULT 0'
                . ')');
            return;
        }

        if (in_array($dialect, ['mysql', 'mysqli', 'pdo-mysql'], true)) {
            $db->query('CREATE TABLE IF NOT EXISTS ' . $t . ' ('
                . 'id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,'
                . 'version VARCHAR(55) NOT NULL DEFAULT 0,'
                . 'class VARCHAR(255) NOT NULL,'
                . "group_name VARCHAR(255) NOT NULL DEFAULT 'default',"
                . 'batch INT(11) NOT NULL DEFAULT 0,'
                . 'PRIMARY KEY (id),'
                . 'KEY `batch` (batch)'
                . ') DEFAULT CHARSET=utf8');
            return;
        }

        // Fallback ANSI — coba PostgreSQL lebih dulu, lalu generic.
        try {
            $db->query('CREATE TABLE IF NOT EXISTS ' . $t . ' ('
                . 'id SERIAL PRIMARY KEY,'
                . 'version VARCHAR(55) NOT NULL DEFAULT 0,'
                . 'class VARCHAR(255) NOT NULL,'
                . "group_name VARCHAR(255) NOT NULL DEFAULT 'default',"
                . 'batch INTEGER NOT NULL DEFAULT 0'
                . ')');
            return;
        } catch (\Throwable) {
            // bukan postgres — lanjut ke variasi generic di bawah
        }

        $db->query('CREATE TABLE IF NOT EXISTS ' . $t . ' ('
            . 'id INTEGER PRIMARY KEY,'
            . 'version VARCHAR(55) NOT NULL DEFAULT 0,'
            . 'class VARCHAR(255) NOT NULL,'
            . "group_name VARCHAR(255) NOT NULL DEFAULT 'default',"
            . 'batch INTEGER NOT NULL DEFAULT 0'
            . ')');
    }

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
