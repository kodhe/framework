<?php

declare(strict_types=1);

namespace Kodhe\Database;

/**
 * Database Facade untuk backward compatibility
 * dengan CodeIgniter 3
 */
class DB
{
    /**
     * @var array Model instances
     */
    private static $instances = [];

    /**
     * @var mixed Database connection
     */
    private static $connection = null;

    /**
     * Get model instance
     * @param string $model
     * @return BaseModel
     */
    public static function model($model): BaseModel
    {
        if (!isset(self::$instances[$model])) {
            self::$instances[$model] = new $model();
        }

        return self::$instances[$model];
    }

    /**
     * Table method facade
     * @param string $table
     * @return Builders\QueryBuilder
     */
    public static function table($table)
    {
        return new Builders\QueryBuilder($table);
    }

    /**
     * Get connection instance
     * @param array $config
     * @return Connections\Connection
     */
    public static function connect(array $config = []): Connections\Connection
    {
        if (!self::$connection) {
            self::$connection = new Connections\Connection($config);
        }

        return self::$connection;
    }

    /**
     * Begin transaction
     * @return void
     */
    public static function beginTransaction(): void
    {
        $db = self::connect();
        $db->beginTransaction();
    }

    /**
     * Commit transaction
     * @return void
     */
    public static function commit(): void
    {
        $db = self::connect();
        $db->commit();
    }

    /**
     * Rollback transaction
     * @return void
     */
    public static function rollback(): void
    {
        $db = self::connect();
        $db->rollback();
    }

    /**
     * Raw query
     * @param string $sql
     * @param array $binds
     * @return mixed
     */
    public static function raw($sql, $binds = [])
    {
        $db = self::connect();
        return $db->query($sql, $binds);
    }

    /**
     * Select raw
     * @param string $expression
     * @param array $bindings
     * @return mixed
     */
    public static function rawSelect($expression, $bindings = [])
    {
        $ci =& get_instance();
        return $ci->db->select($expression, false);
    }

    /**
     * Execute within transaction
     * @param callable $callback
     * @return mixed
     */
    public static function transaction(callable $callback)
    {
        $db = self::connect();
        return $db->transaction($callback);
    }

    /**
     * Clear instances
     * @return void
     */
    public static function clearInstances(): void
    {
        self::$instances = [];
    }

    /**
     * Disconnect
     * @return void
     */
    public static function disconnect(): void
    {
        if (self::$connection) {
            self::$connection->disconnect();
            self::$connection = null;
        }
    }
}
