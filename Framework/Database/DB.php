<?php
namespace Kodhe\Framework\Database;

/**
 * Database Manager Facade
 * @package Kodhe\Framework\Database
 */
class DB
{
    /**
     * @var array Model instances
     */
    private static $instances = [];
    
    /**
     * Get model instance
     * @param string $model
     * @return Model
     */
    public static function model($model)
    {
        if (!isset(self::$instances[$model])) {
            self::$instances[$model] = new $model();
        }
        
        return self::$instances[$model];
    }
    
    /**
     * Table method facade
     * @param string $table
     * @return Model
     */
    public static function table($table)
    {
        $model = new class extends Model {
            public function __construct()
            {
                parent::__construct();
            }
        };
        
        return $model->table($table);
    }
    
    /**
     * Begin transaction
     * @return void
     */
    public static function beginTransaction()
    {
        $ci =& get_instance();
        $ci->db->trans_begin();
    }
    
    /**
     * Commit transaction
     * @return void
     */
    public static function commit()
    {
        $ci =& get_instance();
        $ci->db->trans_commit();
    }
    
    /**
     * Rollback transaction
     * @return void
     */
    public static function rollback()
    {
        $ci =& get_instance();
        $ci->db->trans_rollback();
    }
    
    /**
     * Raw query
     * @param string $sql
     * @param array $binds
     * @return mixed
     */
    public static function raw($sql, $binds = [])
    {
        $ci =& get_instance();
        return $ci->db->query($sql, $binds);
    }
    
    /**
     * Select raw
     * @param string $expression
     * @param array $bindings
     * @return \CI_DB_query_builder
     */
    public static function rawSelect($expression, $bindings = [])
    {
        $ci =& get_instance();
        return $ci->db->select($expression, false);
    }
}