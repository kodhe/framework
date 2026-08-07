<?php

namespace Kodhe\Migration\Contracts;

/**
 * Interface MigrationRepositoryInterface
 *
 * Repository pattern untuk mengakses migration files dan metadata
 *
 * @package Kodhe\Migration\Contracts
 */
interface MigrationRepositoryInterface
{
    /**
     * Get daftar semua migration yang tersedia
     *
     * @return array
     */
    public function getAvailable(): array;

    /**
     * Get daftar migration yang sudah dijalankan
     *
     * @return array
     */
    public function getRan(): array;

    /**
     * Get daftar migration yang belum dijalankan
     *
     * @return array
     */
    public function getPending(): array;

    /**
     * Mark migration sebagai sudah dijalankan
     *
     * @param string $file
     * @param int $batch
     * @return void
     */
    public function log(string $file, int $batch): void;

    /**
     * Hapus migration dari daftar yang sudah dijalankan (rollback)
     *
     * @param string $file
     * @return void
     */
    public function delete(string $file): void;

    /**
     * Get batch number terakhir
     *
     * @return int|null
     */
    public function getLastBatchNumber(): ?int;

    /**
     * Get semua migration dalam batch tertentu
     *
     * @param int $batch
     * @return array
     */
    public function getMigrationsByBatch(int $batch): array;

    /**
     * Check apakah migration table sudah ada
     *
     * @return bool
     */
    public function repositoryExists(): bool;

    /**
     * Buat migration repository (table)
     *
     * @return void
     */
    public function createRepository(): void;

    /**
     * Get nama migration file dari version
     *
     * @param int $version
     * @return string|null
     */
    public function getMigrationNameByVersion(int $version): ?string;
}
