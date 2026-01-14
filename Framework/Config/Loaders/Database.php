<?php namespace Kodhe\Framework\Config\Loaders;

use Kodhe\Framework\Database\Connection\ConnectionManager;
use Kodhe\Framework\Database\Query\Builder;
use Kodhe\Framework\Database\Connection\Drivers\Utility;
use Kodhe\Framework\Database\Connection\Drivers\Forge;

class Database
{
    /**
     * Load the database connection
     *
     * @param mixed $params Connection parameters
     * @param bool $return Whether to return the connection instance
     * @param mixed $query_builder Query builder configuration
     * @return mixed|bool Returns connection instance or false
     */
    public static function database($params = '', $return = false, $query_builder = null)
    {
        // Check if database is already loaded and we don't need to return it
        $kodhe = kodhe();
        
        if (!$return && isset($kodhe->db) && is_object($kodhe->db) && !empty($kodhe->db->conn_id)) {
            return false;
        }

        if ($return === true) {
            return ConnectionManager::Database($params, $query_builder);
        }

        if ($kodhe->has('db')) {
            return;
        }
        
        // Load the DB class
        $kodhe->set('db', ConnectionManager::Database($params, $query_builder));
    }

    /**
     * Load database utility class
     *
     * @param Builder|null $db Database connection instance
     * @param bool $return Whether to return the utility instance
     * @return Utility|void Returns utility instance or void
     */
    public static function dbutil(?Builder $db = null, $return = false)
    {
        if (!is_object($db) || !($db instanceof Builder)) {
            class_exists('Kodhe\Framework\Database\Query\Builder', false) || self::database();
            $db = &kodhe()->db;
        }

        $driver = ucwords($db->dbdriver ?? '');
        $className = 'Kodhe\Framework\Database\Connection\Drivers\\' . $driver . '\Utility';

        if (!class_exists($className)) {
            throw new \RuntimeException("Database utility class not found: {$className}");
        }

        if ($return === true) {
            return new $className($db);
        }

        $kodhe = kodhe();
        if ($kodhe->has('dbutil')) {
            return;
        }
        
        $kodhe->set('dbutil', new $className($db));
    }

    /**
     * Load database forge class
     *
     * @param Builder|null $db Database connection instance
     * @param bool $return Whether to return the forge instance
     * @return Forge|void Returns forge instance or void
     */
    public static function dbforge(?Builder $db = null, $return = false)
    {
        if (!is_object($db) || !($db instanceof Builder)) {
            class_exists('Kodhe\Framework\Database\Query\Builder', false) || self::database();
            $db = &kodhe()->db;
        }

        $driver = ucwords($db->dbdriver ?? '');
        
        if (!empty($db->subdriver)) {
            $subdriver = ucwords($db->subdriver);
            $className = 'Kodhe\Framework\Database\Connection\Drivers\\' . $driver . '\Subdrivers\\' . $subdriver . '\Forge';
        } else {
            $className = 'Kodhe\Framework\Database\Connection\Drivers\\' . $driver . '\Forge';
        }

        if (!class_exists($className)) {
            throw new \RuntimeException("Database forge class not found: {$className}");
        }

        if ($return === true) {
            return new $className($db);
        }

        $kodhe = kodhe();
        if ($kodhe->has('dbforge')) {
            return;
        }
        
        $kodhe->set('dbforge', new $className($db));
    }
}