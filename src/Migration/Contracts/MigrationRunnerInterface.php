<?php

namespace Kodhe\Migration\Contracts;

/**
 * Interface MigrationRunnerInterface
 *
 * Strategy pattern untuk menjalankan migration
 *
 * @package Kodhe\Migration\Contracts
 */
interface MigrationRunnerInterface
{
    /**
     * Jalankan migration ke atas (up)
     *
     * @param string $file
     * @return bool
     */
    public function up(string $file): bool;

    /**
     * Jalankan migration ke bawah (down/rollback)
     *
     * @param string $file
     * @return bool
     */
    public function down(string $file): bool;

    /**
     * Get error message terakhir
     *
     * @return string|null
     */
    public function getError(): ?string;
}
