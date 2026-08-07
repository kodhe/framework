<?php

namespace Kodhe\Migration;

use CI_DB_forge;
use Kodhe\Migration\Contracts\MigrationRepositoryInterface;
use Kodhe\Migration\Contracts\MigrationRunnerInterface;
use Kodhe\Migration\Factory\MigrationComponentFactory;
use Kodhe\Migration\Exceptions\MigrationNotFoundException;
use Kodhe\Migration\Exceptions\DuplicateVersionException;

/**
 * Migration Library for CodeIgniter 3
 *
 * Mengelola database migrations dengan fitur:
 * - Run migration ke version tertentu
 * - Rollback batch terakhir
 * - Run semua migration (latest)
 * - Cache untuk performa
 *
 * @package Kodhe\Migration
 * @author  Your Name
 * @version 2.0.0
 * @license MIT
 */
class Migration
{
    /**
     * @var string Path ke folder migrations
     */
    private string $migrationPath;

    /**
     * @var MigrationRepositoryInterface Repository untuk metadata migration
     */
    private MigrationRepositoryInterface $repository;

    /**
     * @var MigrationRunnerInterface Runner untuk eksekusi migration
     */
    private MigrationRunnerInterface $runner;

    /**
     * @var string|null Error message terakhir
     */
    private ?string $errorString = null;

    /**
     * @var bool Apakah enabled
     */
    private bool $enabled = true;

    /**
     * Constructor
     *
     * @param array $config Konfigurasi migration
     */
    public function __construct(array $config = [])
    {
        // Setup path
        $this->migrationPath = $config['migration_path'] ?? APPPATH . 'migrations/';
        $this->migrationPath = rtrim($this->migrationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Setup enabled flag
        $this->enabled = $config['migration_enabled'] ?? true;

        // Get dbforge jika tersedia
        $dbForge = null;
        $ci = get_instance();
        if ($ci && isset($ci->dbforge)) {
            $dbForge = $ci->dbforge;
        }

        // Buat components via factory
        $components = MigrationComponentFactory::makeComponents($this->migrationPath, $dbForge);
        $this->repository = $components['repository'];
        $this->runner = $components['runner'];
    }

    /**
     * Migrate ke version tertentu
     *
     * @param int|null $targetVersion Version target (null untuk latest)
     * @return bool
     */
    public function version(?int $targetVersion = null): bool
    {
        if (!$this->enabled) {
            $this->errorString = 'Migrations are disabled';
            return false;
        }

        // Jika null, migrate ke latest
        if ($targetVersion === null) {
            return $this->latest();
        }

        // Pastikan repository ada
        if (!$this->repository->repositoryExists()) {
            $this->repository->createRepository();
        }

        $currentVersion = $this->getCurrentVersion();

        // Jika sudah di version target
        if ($currentVersion === $targetVersion) {
            return true;
        }

        // Tentukan arah migration
        if ($targetVersion > $currentVersion) {
            return $this->migrateUp($targetVersion);
        } else {
            return $this->migrateDown($targetVersion);
        }
    }

    /**
     * Migrate ke version terbaru
     *
     * @return bool
     */
    public function latest(): bool
    {
        if (!$this->enabled) {
            $this->errorString = 'Migrations are disabled';
            return false;
        }

        // Pastikan repository ada
        if (!$this->repository->repositoryExists()) {
            $this->repository->createRepository();
        }

        $available = $this->repository->getAvailable();
        
        if (empty($available)) {
            $this->errorString = 'No migrations found';
            return false;
        }

        $latestVersion = max(array_keys($available));
        return $this->version($latestVersion);
    }

    /**
     * Migrate ke current version (alias untuk version())
     *
     * @param int|null $targetVersion
     * @return bool
     */
    public function current(?int $targetVersion = null): bool
    {
        return $this->version($targetVersion);
    }

    /**
     * Find all migration files
     *
     * @return array
     */
    public function find_migrations(): array
    {
        return $this->repository->getAvailable();
    }

    /**
     * Get error string
     *
     * @return string|null
     */
    public function error_string(): ?string
    {
        return $this->errorString;
    }

    /**
     * Get current version
     *
     * @return int
     */
    public function getCurrentVersion(): int
    {
        $ran = $this->repository->getRan();
        
        if (empty($ran)) {
            return 0;
        }

        // Return version tertinggi yang sudah dijalankan
        $versions = array_map(function ($file) {
            return (int) pathinfo($file, PATHINFO_FILENAME);
        }, $ran);

        return max($versions);
    }

    /**
     * Get last batch number
     *
     * @return int|null
     */
    public function getLastBatch(): ?int
    {
        return $this->repository->getLastBatchNumber();
    }

    /**
     * Rollback batch terakhir
     *
     * @return bool
     */
    public function rollback(): bool
    {
        if (!$this->enabled) {
            $this->errorString = 'Migrations are disabled';
            return false;
        }

        $lastBatch = $this->repository->getLastBatchNumber();
        
        if ($lastBatch === null || $lastBatch <= 0) {
            $this->errorString = 'Nothing to rollback';
            return false;
        }

        $migrations = $this->repository->getMigrationsByBatch($lastBatch);

        foreach ($migrations as $version) {
            $file = $this->repository->getMigrationNameByVersion($version);
            
            if ($file === null) {
                $this->errorString = "Migration file for version {$version} not found";
                return false;
            }

            // Jalankan down migration
            if (!$this->runner->down($file)) {
                $this->errorString = $this->runner->getError() ?? "Failed to rollback migration {$file}";
                return false;
            }

            // Hapus dari log
            $this->repository->delete($file);
        }

        return true;
    }

    /**
     * Force migrate ke version tertentu (bypass checks)
     *
     * @param int $targetVersion
     * @return bool
     */
    public function force(int $targetVersion): bool
    {
        $this->errorString = null;
        return $this->version($targetVersion);
    }

    /**
     * Set repository instance (untuk testing/DI)
     *
     * @param MigrationRepositoryInterface $repository
     * @return void
     */
    public function setRepository(MigrationRepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * Set runner instance (untuk testing/DI)
     *
     * @param MigrationRunnerInterface $runner
     * @return void
     */
    public function setRunner(MigrationRunnerInterface $runner): void
    {
        $this->runner = $runner;
    }

    /**
     * Migrate up ke target version
     *
     * @param int $targetVersion
     * @return bool
     */
    private function migrateUp(int $targetVersion): bool
    {
        $pending = $this->repository->getPending();
        
        if (empty($pending)) {
            return true;
        }

        // Get batch number baru
        $batch = $this->repository->getLastBatchNumber() ?? 0;
        $batch++;

        $lastBatch = $this->repository->getLastBatchNumber();
        $newBatch = ($lastBatch ?? 0) + 1;

        foreach ($pending as $version => $file) {
            // Stop jika sudah melewati target
            if ($version > $targetVersion) {
                break;
            }

            // Jalankan migration
            if (!$this->runner->up($file)) {
                $this->errorString = $this->runner->getError() ?? "Failed to run migration {$file}";
                return false;
            }

            // Log migration
            $this->repository->log($file, $newBatch);
        }

        return true;
    }

    /**
     * Migrate down ke target version
     *
     * @param int $targetVersion
     * @return bool
     */
    private function migrateDown(int $targetVersion): bool
    {
        $ran = $this->repository->getRan();

        if (empty($ran)) {
            return true;
        }

        // Sort descending untuk rollback dari yang terbaru
        rsort($ran);

        foreach ($ran as $file) {
            $version = (int) pathinfo($file, PATHINFO_FILENAME);

            // Stop jika sudah mencapai target
            if ($version <= $targetVersion) {
                break;
            }

            // Jalankan down migration
            if (!$this->runner->down($file)) {
                $this->errorString = $this->runner->getError() ?? "Failed to rollback migration {$file}";
                return false;
            }

            // Hapus dari log
            $this->repository->delete($file);
        }

        return true;
    }
}
