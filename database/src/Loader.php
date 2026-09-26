<?php

declare(strict_types=1);

namespace Kodhe\Framework\Database;

use Kodhe\Framework\Database\Connection\ConnectionManager;

class Loader
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
        if(!class_exists(ConnectionManager::class)) return;
        
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
     * NOTE: parameter $db menerima instance koneksi driver (class Driver
     * pada Mysqli/Pdo/Sqlite3 yang extends Query\Builder), BUKAN class
     * Kodhe\Framework\Database\Builder. Type-hint lama (?Builder) salah
     * dan memicu TypeError saat dipanggil Loader::dbforge(kodhe()->db).
     *
     * @param mixed $db Database connection (driver) instance
     * @param bool $return Whether to return the utility instance
     * @return Utility|void Returns utility instance or void
     */
    public static function dbutil($db = null, $return = false)
    {
        if (!is_object($db) || !($db instanceof \Kodhe\Framework\Database\Query\Builder)) {
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
     * NOTE: sama seperti dbutil() — $db adalah instance koneksi driver
     * (extends Query\Builder). Jika argumen dilewatkan tapi bukan koneksi
     * valid, kembalikan ke koneksi aktif dari container kodhe().
     *
     * @param mixed $db Database connection (driver) instance
     * @param bool $return Whether to return the forge instance
     * @return Forge|void Returns forge instance or void
     */
    public static function dbforge($db = null, $return = false)
    {
        if (!is_object($db) || !($db instanceof \Kodhe\Framework\Database\Query\Builder)) {
            class_exists('Kodhe\Framework\Database\Query\Builder', false) || self::database();
            $db = &kodhe()->db;
        }

        $driver = ucwords($db->dbdriver ?? '');
        
        if (!empty($db->subdriver)) {
            // Samakan normalisasi dengan ConnectionManager::normalize_subdriver()
            // (mis. subdriver 'sqlite3' -> folder class 'Sqlite') agar Forge
            // yang dicari tidak salah nama / tidak ditemukan.
            static $submap = array(
                '4d' => 'Fourd', 'cubrid' => 'Cubrid', 'dblib' => 'Dblib',
                'firebird' => 'Firebird', 'ibm' => 'Ibm', 'informix' => 'Informix',
                'mysql' => 'Mysql', 'oci' => 'Oci', 'odbc' => 'Odbc',
                'pgsql' => 'Pgsql', 'sqlite' => 'Sqlite', 'sqlite3' => 'Sqlite',
                'sqlsrv' => 'Sqlsrv',
            );
            $key = strtolower($db->subdriver);
            $subdriver = $submap[$key] ?? ucwords($db->subdriver);
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
