<?php

declare(strict_types=1);

namespace Kodhe\Database\Connections;

use Kodhe\Database\Contracts\ConnectionInterface;
use Kodhe\Database\Traits\ManagesConnectionTrait;

/**
 * Database Connection Manager
 */
class Connection implements ConnectionInterface
{
    use ManagesConnectionTrait;

    /**
     * @var string Driver name
     */
    protected $driver = 'mysqli';

    /**
     * Constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->setConfig($config);
        }
    }

    /**
     * Connect to database
     * @param bool $persistent
     * @return mixed
     */
    public function connect(bool $persistent = false)
    {
        $ci =& get_instance();
        $ci->load->database($this->config, $persistent);
        $this->connection = $ci->db;

        return $this->connection;
    }

    /**
     * Disconnect from database
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * Execute raw query
     * @param string $sql
     * @param array $binds
     * @return mixed
     */
    public function query(string $sql, array $binds = [])
    {
        $db = $this->getConnection();
        return $db->query($sql, $binds);
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction(): bool
    {
        $db = $this->getConnection();
        return $db->trans_begin();
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit(): bool
    {
        $db = $this->getConnection();
        return $db->trans_commit();
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback(): bool
    {
        $db = $this->getConnection();
        return $db->trans_rollback();
    }

    /**
     * Get last insert ID
     * @return int|string
     */
    public function getLastInsertId()
    {
        $db = $this->getConnection();
        return $db->insert_id();
    }

    /**
     * Get affected rows
     * @return int
     */
    public function getAffectedRows(): int
    {
        $db = $this->getConnection();
        return $db->affected_rows();
    }

    /**
     * Check if connected
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connection !== null && $this->connection->conn_id !== false;
    }

    /**
     * Get connection config
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set driver
     * @param string $driver
     * @return self
     */
    public function setDriver(string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get driver name
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Escape identifier
     * @param string $identifier
     * @return string
     */
    public function escapeIdentifier(string $identifier): string
    {
        $db = $this->getConnection();
        return $db->escape_identifiers($identifier);
    }

    /**
     * Escape value
     * @param mixed $value
     * @return mixed
     */
    public function escapeValue($value)
    {
        $db = $this->getConnection();
        return $db->escape($value);
    }
}
