<?php

declare(strict_types=1);

namespace Kodhe\Database\Traits;

/**
 * Trait untuk Connection Management
 */
trait ManagesConnectionTrait
{
    /**
     * @var mixed Database connection
     */
    protected $connection;

    /**
     * @var array Connection configuration
     */
    protected $config = [];

    /**
     * Get database connection
     * @return mixed
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->connect();
        }

        return $this->connection;
    }

    /**
     * Connect to database
     * @return mixed
     */
    protected function connect()
    {
        // Check if running in CI environment
        if (function_exists('get_instance')) {
            $ci =& get_instance();
            $ci->load->database();
            return $ci->db;
        }
        
        // For testing without CI
        return null;
    }

    /**
     * Set connection
     * @param mixed $connection
     * @return self
     */
    public function setConnection($connection): self
    {
        $this->connection = $connection;
        return $this;
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
     * Execute within transaction
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Check if connected
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Get config value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set config
     * @param array $config
     * @return self
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
}
