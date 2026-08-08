<?php

declare(strict_types=1);

namespace Kodhe\Database\Contracts;

/**
 * Database Connection Interface
 */
interface ConnectionInterface
{
    /**
     * Connect to database
     * @param bool $persistent
     * @return mixed
     */
    public function connect(bool $persistent = false);

    /**
     * Disconnect from database
     * @return void
     */
    public function disconnect(): void;

    /**
     * Execute raw query
     * @param string $sql
     * @param array $binds
     * @return mixed
     */
    public function query(string $sql, array $binds = []);

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * Commit transaction
     * @return bool
     */
    public function commit(): bool;

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback(): bool;

    /**
     * Get last insert ID
     * @return int|string
     */
    public function getLastInsertId();

    /**
     * Get affected rows
     * @return int
     */
    public function getAffectedRows(): int;

    /**
     * Check if connected
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Get connection config
     * @return array
     */
    public function getConfig(): array;
}
