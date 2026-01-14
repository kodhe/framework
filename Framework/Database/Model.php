<?php
namespace Kodhe\Framework\Database;

defined('BASEPATH') OR exit('No direct script access allowed');

use Exception;
use Closure;

use Kodhe\Framework\Database\ORM\LegacyModel;
/**
 * ORM Model Modern untuk CodeIgniter 3
 * @package Kodhe\Framework\Database
 */
class Model extends LegacyModel
{
    /**
     * @var string Nama tabel
     */
    protected $table = '';
    
    /**
     * @var string Primary key
     */
    protected $primaryKey = 'id';
    
    /**
     * @var string Return type (object|array)
     */
    protected $returnType = 'object';
    
    /**
     * @var bool Gunakan timestamps
     */
    protected $useTimestamps = false;
    
    /**
     * @var string Field created_at
     */
    protected $createdField = 'created_at';
    
    /**
     * @var string Field updated_at
     */
    protected $updatedField = 'updated_at';
    
    /**
     * @var bool Gunakan soft deletes
     */
    protected $useSoftDeletes = false;
    
    /**
     * @var string Field deleted_at
     */
    protected $deletedField = 'deleted_at';
    
    /**
     * @var array Allowed fields untuk mass assignment
     */
    protected $allowedFields = [];
    
    /**
     * @var array Validation rules
     */
    protected $validationRules = [];
    
    /**
     * @var array Validation messages
     */
    protected $validationMessages = [];
    
    /**
     * @var bool Skip validation
     */
    protected $skipValidation = false;
    
    /**
     * @var array Before insert callbacks
     */
    protected $beforeInsert = [];
    
    /**
     * @var array After insert callbacks
     */
    protected $afterInsert = [];
    
    /**
     * @var array Before update callbacks
     */
    protected $beforeUpdate = [];
    
    /**
     * @var array After update callbacks
     */
    protected $afterUpdate = [];
    
    /**
     * @var array Before find callbacks
     */
    protected $beforeFind = [];
    
    /**
     * @var array After find callbacks
     */
    protected $afterFind = [];
    
    /**
     * @var array Before delete callbacks
     */
    protected $beforeDelete = [];
    
    /**
     * @var array After delete callbacks
     */
    protected $afterDelete = [];
    
    /**
     * @var object CI_DB_query_builder instance
     */
    private $builder;
    
    /**
     * @var array Temporary with relations
     */
    private $tempWith = [];
    
    /**
     * @var string Temporary select
     */
    private $tempSelect = '*';
    
    /**
     * @var array Temporary where conditions
     */
    private $tempWhere = [];
    
    /**
     * @var array Temporary order by
     */
    private $tempOrderBy = [];
    
    /**
     * @var int|null Temporary limit
     */
    private $tempLimit = null;
    
    /**
     * @var int Temporary offset
     */
    private $tempOffset = 0;
    
    /**
     * @var array Temporary group by
     */
    private $tempGroupBy = [];
    
    /**
     * @var array Temporary joins
     */
    private $tempJoins = [];
    
    /**
     * @var array Temporary having
     */
    private $tempHaving = [];
    
    /**
     * @var bool Is chunking
     */
    private $isChunking = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        
        // Set table name jika tidak di-set
        if (empty($this->table)) {
            $this->table = $this->_getTableName();
        }
        
        $this->builder = $this->db->from($this->table);
    }

    /**
     * Get table name dari class name
     * @return string
     */
    private function _getTableName()
    {
        $className = get_class($this);
        $className = substr($className, strrpos($className, '\\') + 1);
        $className = str_replace('_model', '', strtolower($className));
        
        // Cek apakah ada helper plural
        if (function_exists('plural')) {
            return plural(strtolower($className));
        }
        
        // Simple pluralization
        $lastChar = substr($className, -1);
        if ($lastChar === 'y') {
            return substr($className, 0, -1) . 'ies';
        } elseif ($lastChar === 's') {
            return $className . 'es';
        } else {
            return $className . 's';
        }
    }

    /**
     * Reset query builder temporary
     * @return void
     */
    private function _resetBuilder()
    {
        $this->tempWith = [];
        $this->tempSelect = '*';
        $this->tempWhere = [];
        $this->tempOrderBy = [];
        $this->tempLimit = null;
        $this->tempOffset = 0;
        $this->tempGroupBy = [];
        $this->tempJoins = [];
        $this->tempHaving = [];
        $this->builder = $this->db->from($this->table);
    }

    /**
     * Set table name
     * @param string $table
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;
        $this->builder = $this->db->from($this->table);
        return $this;
    }

    /**
     * Eager loading relations
     * @param string|array $relations
     * @return $this
     */
    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }
        
        $this->tempWith = array_merge($this->tempWith, $relations);
        return $this;
    }

    /**
     * Select specific columns
     * @param string|array $select
     * @return $this
     */
    public function select($select = '*')
    {
        $this->tempSelect = $select;
        return $this;
    }

    /**
     * Add where condition
     * @param mixed $field
     * @param mixed $value
     * @param bool $escape
     * @return $this
     */
    public function where($field, $value = null, $escape = null)
    {
        if ($field instanceof Closure) {
            $this->db->group_start();
            $field($this);
            $this->db->group_end();
        } elseif (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->tempWhere[] = [
                    'type' => 'where',
                    'field' => $key,
                    'value' => $val,
                    'escape' => $escape
                ];
            }
        } else {
            $this->tempWhere[] = [
                'type' => 'where',
                'field' => $field,
                'value' => $value,
                'escape' => $escape
            ];
        }
        return $this;
    }

    /**
     * Add or where condition
     * @param mixed $field
     * @param mixed $value
     * @param bool $escape
     * @return $this
     */
    public function orWhere($field, $value = null, $escape = null)
    {
        $this->tempWhere[] = [
            'type' => 'or_where',
            'field' => $field,
            'value' => $value,
            'escape' => $escape
        ];
        return $this;
    }

    /**
     * Add where in condition
     * @param string $field
     * @param array $values
     * @return $this
     */
    public function whereIn($field, array $values)
    {
        $this->tempWhere[] = [
            'type' => 'where_in',
            'field' => $field,
            'value' => $values
        ];
        return $this;
    }

    /**
     * Add where not in condition
     * @param string $field
     * @param array $values
     * @return $this
     */
    public function whereNotIn($field, array $values)
    {
        $this->tempWhere[] = [
            'type' => 'where_not_in',
            'field' => $field,
            'value' => $values
        ];
        return $this;
    }

    /**
     * Add like condition
     * @param string $field
     * @param string $match
     * @param string $side
     * @return $this
     */
    public function like($field, $match = '', $side = 'both')
    {
        $this->tempWhere[] = [
            'type' => 'like',
            'field' => $field,
            'value' => $match,
            'side' => $side
        ];
        return $this;
    }

    /**
     * Add or like condition
     * @param string $field
     * @param string $match
     * @param string $side
     * @return $this
     */
    public function orLike($field, $match = '', $side = 'both')
    {
        $this->tempWhere[] = [
            'type' => 'or_like',
            'field' => $field,
            'value' => $match,
            'side' => $side
        ];
        return $this;
    }

    /**
     * Add order by
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function orderBy($field, $direction = 'ASC')
    {
        $this->tempOrderBy[] = [
            'field' => $field,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    /**
     * Add order by random
     * @return $this
     */
    public function inRandomOrder()
    {
        $this->tempOrderBy[] = [
            'field' => 'RAND()',
            'direction' => ''
        ];
        return $this;
    }

    /**
     * Add limit
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit($limit, $offset = 0)
    {
        $this->tempLimit = $limit;
        $this->tempOffset = $offset;
        return $this;
    }

    /**
     * Add group by
     * @param string|array $field
     * @return $this
     */
    public function groupBy($field)
    {
        if (is_array($field)) {
            $this->tempGroupBy = array_merge($this->tempGroupBy, $field);
        } else {
            $this->tempGroupBy[] = $field;
        }
        return $this;
    }

    /**
     * Add having clause
     * @param string $key
     * @param mixed $value
     * @param bool $escape
     * @return $this
     */
    public function having($key, $value = null, $escape = null)
    {
        $this->tempHaving[] = [
            'key' => $key,
            'value' => $value,
            'escape' => $escape
        ];
        return $this;
    }

    /**
     * Join table
     * @param string $table
     * @param string $cond
     * @param string $type
     * @return $this
     */
    public function join($table, $cond, $type = 'inner')
    {
        $this->tempJoins[] = [
            'table' => $table,
            'cond' => $cond,
            'type' => $type
        ];
        return $this;
    }

    /**
     * Left join table
     * @param string $table
     * @param string $cond
     * @return $this
     */
    public function leftJoin($table, $cond)
    {
        return $this->join($table, $cond, 'left');
    }

    /**
     * Right join table
     * @param string $table
     * @param string $cond
     * @return $this
     */
    public function rightJoin($table, $cond)
    {
        return $this->join($table, $cond, 'right');
    }

    /**
     * Execute query builder
     * @return void
     */
    private function _executeBuilder()
    {
        // Select
        $this->builder->select($this->tempSelect);
        
        // Apply where conditions
        foreach ($this->tempWhere as $where) {
            switch ($where['type']) {
                case 'where':
                    $this->builder->where($where['field'], $where['value'], $where['escape'] ?? null);
                    break;
                case 'or_where':
                    $this->builder->or_where($where['field'], $where['value'], $where['escape'] ?? null);
                    break;
                case 'where_in':
                    $this->builder->where_in($where['field'], $where['value']);
                    break;
                case 'where_not_in':
                    $this->builder->where_not_in($where['field'], $where['value']);
                    break;
                case 'like':
                    $this->builder->like($where['field'], $where['value'], $where['side'] ?? 'both');
                    break;
                case 'or_like':
                    $this->builder->or_like($where['field'], $where['value'], $where['side'] ?? 'both');
                    break;
            }
        }
        
        // Apply joins
        foreach ($this->tempJoins as $join) {
            $this->builder->join($join['table'], $join['cond'], $join['type']);
        }
        
        // Apply order by
        foreach ($this->tempOrderBy as $order) {
            if ($order['field'] === 'RAND()') {
                $this->builder->order_by($order['field'], '', false);
            } else {
                $this->builder->order_by($order['field'], $order['direction']);
            }
        }
        
        // Apply group by
        if (!empty($this->tempGroupBy)) {
            $this->builder->group_by($this->tempGroupBy);
        }
        
        // Apply having
        foreach ($this->tempHaving as $having) {
            $this->builder->having($having['key'], $having['value'], $having['escape'] ?? null);
        }
        
        // Soft deletes
        if ($this->useSoftDeletes && !$this->isChunking) {
            $this->builder->where($this->deletedField . ' IS NULL');
        }
        
        // Apply limit & offset
        if ($this->tempLimit !== null) {
            $this->builder->limit($this->tempLimit, $this->tempOffset);
        }
    }

    /**
     * Get all records
     * @param int|null $limit
     * @param int $offset
     * @return array
     */
    public function all($limit = null, $offset = 0)
    {
        return $this->findAll($limit, $offset);
    }

    /**
     * Find all records
     * @param int|null $limit
     * @param int $offset
     * @return array
     */
    public function findAll($limit = null, $offset = 0)
    {
        // Callbacks beforeFind
        foreach ($this->beforeFind as $callback) {
            if (method_exists($this, $callback)) {
                $this->$callback();
            }
        }
        
        if ($limit !== null) {
            $this->limit($limit, $offset);
        }
        
        $this->_executeBuilder();
        $query = $this->builder->get();
        $results = $query->result($this->returnType);
        
        // Eager loading
        if (!empty($this->tempWith) && !empty($results)) {
            $results = $this->_loadRelations($results, $this->tempWith);
        }
        
        // Callbacks afterFind
        foreach ($this->afterFind as $callback) {
            if (method_exists($this, $callback)) {
                $results = $this->$callback($results);
            }
        }
        
        $this->_resetBuilder();
        return $results;
    }

    /**
     * Get first record
     * @return mixed|null
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->findAll();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Find by primary key
     * @param mixed $id
     * @return mixed|null
     */
    public function find($id)
    {
        return $this->where($this->primaryKey, $id)->first();
    }

    /**
     * Find or fail by primary key
     * @param mixed $id
     * @return mixed
     * @throws Exception
     */
    public function findOrFail($id)
    {
        $result = $this->find($id);
        if (!$result) {
            throw new Exception("Record not found with ID: {$id}");
        }
        return $result;
    }

    /**
     * Find or create
     * @param array $attributes
     * @param array $values
     * @return mixed
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        $this->where($attributes);
        $record = $this->first();
        
        if ($record) {
            return $record;
        }
        
        $data = array_merge($attributes, $values);
        $id = $this->insert($data);
        return $this->find($id);
    }

    /**
     * Find or new
     * @param array $attributes
     * @param array $values
     * @return mixed
     */
    public function firstOrNew(array $attributes, array $values = [])
    {
        $this->where($attributes);
        $record = $this->first();
        
        if ($record) {
            return $record;
        }
        
        $data = array_merge($attributes, $values);
        if ($this->returnType === 'object') {
            return (object) $data;
        }
        return $data;
    }

    /**
     * Update or create
     * @param array $attributes
     * @param array $values
     * @return mixed
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        $this->where($attributes);
        $record = $this->first();
        
        if ($record) {
            $id = is_object($record) ? $record->{$this->primaryKey} : $record[$this->primaryKey];
            $this->update($values, $id);
            return $this->find($id);
        }
        
        $data = array_merge($attributes, $values);
        $id = $this->insert($data);
        return $this->find($id);
    }

    /**
     * Get count
     * @return int
     */
    public function count()
    {
        $this->_executeBuilder();
        return $this->builder->count_all_results();
    }

    /**
     * Get sum
     * @param string $column
     * @return float
     */
    public function sum($column)
    {
        $this->builder->select_sum($column);
        $this->_executeBuilder();
        $query = $this->builder->get();
        $result = $query->row();
        return isset($result->$column) ? (float)$result->$column : 0;
    }

    /**
     * Get average
     * @param string $column
     * @return float
     */
    public function avg($column)
    {
        $this->builder->select_avg($column);
        $this->_executeBuilder();
        $query = $this->builder->get();
        $result = $query->row();
        return isset($result->$column) ? (float)$result->$column : 0;
    }

    /**
     * Get max
     * @param string $column
     * @return mixed
     */
    public function max($column)
    {
        $this->builder->select_max($column);
        $this->_executeBuilder();
        $query = $this->builder->get();
        $result = $query->row();
        return isset($result->$column) ? $result->$column : null;
    }

    /**
     * Get min
     * @param string $column
     * @return mixed
     */
    public function min($column)
    {
        $this->builder->select_min($column);
        $this->_executeBuilder();
        $query = $this->builder->get();
        $result = $query->row();
        return isset($result->$column) ? $result->$column : null;
    }

    /**
     * Chunk results
     * @param int $size
     * @param callable $callback
     * @return bool
     */
    public function chunk($size, callable $callback)
    {
        $this->isChunking = true;
        $page = 1;
        
        do {
            $results = $this->limit($size)->offset(($page - 1) * $size)->findAll();
            
            $countResults = count($results);
            
            if ($countResults == 0) {
                break;
            }
            
            if ($callback($results, $page) === false) {
                $this->isChunking = false;
                $this->_resetBuilder();
                return false;
            }
            
            unset($results);
            
            $page++;
        } while ($countResults == $size);
        
        $this->isChunking = false;
        $this->_resetBuilder();
        return true;
    }

    /**
     * Insert record
     * @param array $data
     * @param bool $returnId
     * @return mixed
     */
    public function insert(array $data, $returnId = true)
    {
        // Validasi
        if (!$this->skipValidation && !empty($this->validationRules)) {
            if (!$this->validate($data)) {
                return false;
            }
        }
        
        // Filter allowed fields
        if (!empty($this->allowedFields)) {
            $filteredData = [];
            foreach ($this->allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $filteredData[$field] = $data[$field];
                }
            }
            $data = $filteredData;
        }
        
        // Timestamps
        if ($this->useTimestamps) {
            $currentTime = date('Y-m-d H:i:s');
            if ($this->createdField && !isset($data[$this->createdField])) {
                $data[$this->createdField] = $currentTime;
            }
            if ($this->updatedField) {
                $data[$this->updatedField] = $currentTime;
            }
        }
        
        // Callbacks beforeInsert
        foreach ($this->beforeInsert as $callback) {
            if (method_exists($this, $callback)) {
                $data = $this->$callback($data);
            }
        }
        
        $this->db->insert($this->table, $data);
        $insertId = $this->db->insert_id();
        
        // Callbacks afterInsert
        foreach ($this->afterInsert as $callback) {
            if (method_exists($this, $callback)) {
                $this->$callback($data, $insertId);
            }
        }
        
        return $returnId ? $insertId : $this->db->affected_rows() > 0;
    }

    /**
     * Insert batch
     * @param array $data
     * @return int
     */
    public function insertBatch(array $data)
    {
        // Filter allowed fields
        if (!empty($this->allowedFields)) {
            $filteredData = [];
            foreach ($data as $row) {
                $filteredRow = [];
                foreach ($this->allowedFields as $field) {
                    if (array_key_exists($field, $row)) {
                        $filteredRow[$field] = $row[$field];
                    }
                }
                $filteredData[] = $filteredRow;
            }
            $data = $filteredData;
        }
        
        // Timestamps
        if ($this->useTimestamps) {
            $currentTime = date('Y-m-d H:i:s');
            foreach ($data as &$row) {
                if ($this->createdField && !isset($row[$this->createdField])) {
                    $row[$this->createdField] = $currentTime;
                }
                if ($this->updatedField) {
                    $row[$this->updatedField] = $currentTime;
                }
            }
        }
        
        return $this->db->insert_batch($this->table, $data) ?: 0;
    }

    /**
     * Update record
     * @param array $data
     * @param mixed $where
     * @return int
     */
    public function update(array $data, $where = null)
    {
        // Validasi
        if (!$this->skipValidation && !empty($this->validationRules)) {
            if (!$this->validate($data, true)) {
                return false;
            }
        }
        
        // Filter allowed fields
        if (!empty($this->allowedFields)) {
            $filteredData = [];
            foreach ($this->allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $filteredData[$field] = $data[$field];
                }
            }
            $data = $filteredData;
        }
        
        // Timestamps
        if ($this->useTimestamps && $this->updatedField) {
            $data[$this->updatedField] = date('Y-m-d H:i:s');
        }
        
        // Where conditions
        if ($where !== null) {
            if (is_array($where)) {
                $this->db->where($where);
            } else {
                $this->db->where($this->primaryKey, $where);
            }
        } else {
            // Gunakan temp where
            foreach ($this->tempWhere as $where) {
                if ($where['type'] === 'where') {
                    $this->db->where($where['field'], $where['value'], $where['escape'] ?? null);
                }
            }
        }
        
        // Callbacks beforeUpdate
        foreach ($this->beforeUpdate as $callback) {
            if (method_exists($this, $callback)) {
                $data = $this->$callback($data);
            }
        }
        
        $this->db->update($this->table, $data);
        $affected = $this->db->affected_rows();
        
        // Callbacks afterUpdate
        foreach ($this->afterUpdate as $callback) {
            if (method_exists($this, $callback)) {
                $this->$callback($data, $affected);
            }
        }
        
        $this->_resetBuilder();
        return $affected;
    }

    /**
     * Update batch
     * @param array $data
     * @param string $index
     * @return int
     */
    public function updateBatch(array $data, $index = null)
    {
        $index = $index ?: $this->primaryKey;
        
        // Filter allowed fields
        if (!empty($this->allowedFields)) {
            $filteredData = [];
            foreach ($data as $row) {
                $filteredRow = [];
                foreach ($this->allowedFields as $field) {
                    if (array_key_exists($field, $row)) {
                        $filteredRow[$field] = $row[$field];
                    }
                }
                $filteredRow[$index] = $row[$index];
                $filteredData[] = $filteredRow;
            }
            $data = $filteredData;
        }
        
        // Timestamps
        if ($this->useTimestamps && $this->updatedField) {
            $currentTime = date('Y-m-d H:i:s');
            foreach ($data as &$row) {
                $row[$this->updatedField] = $currentTime;
            }
        }
        
        return $this->db->update_batch($this->table, $data, $index) ?: 0;
    }

    /**
     * Delete record
     * @param mixed $id
     * @param bool $purge
     * @return bool
     */
    public function delete($id = null, $purge = false)
    {
        if ($this->useSoftDeletes && !$purge) {
            // Soft delete
            $data = [$this->deletedField => date('Y-m-d H:i:s')];
            
            if ($id !== null) {
                $this->db->where($this->primaryKey, $id);
            } else {
                foreach ($this->tempWhere as $where) {
                    if ($where['type'] === 'where') {
                        $this->db->where($where['field'], $where['value'], $where['escape'] ?? null);
                    }
                }
            }
            
            // Callbacks beforeDelete
            foreach ($this->beforeDelete as $callback) {
                if (method_exists($this, $callback)) {
                    $this->$callback();
                }
            }
            
            $result = $this->db->update($this->table, $data);
            
            // Callbacks afterDelete
            foreach ($this->afterDelete as $callback) {
                if (method_exists($this, $callback)) {
                    $this->$callback();
                }
            }
            
            $this->_resetBuilder();
            return $result;
        } else {
            // Hard delete
            if ($id !== null) {
                $this->db->where($this->primaryKey, $id);
            } else {
                foreach ($this->tempWhere as $where) {
                    if ($where['type'] === 'where') {
                        $this->db->where($where['field'], $where['value'], $where['escape'] ?? null);
                    }
                }
            }
            
            // Callbacks beforeDelete
            foreach ($this->beforeDelete as $callback) {
                if (method_exists($this, $callback)) {
                    $this->$callback();
                }
            }
            
            $result = $this->db->delete($this->table);
            
            // Callbacks afterDelete
            foreach ($this->afterDelete as $callback) {
                if (method_exists($this, $callback)) {
                    $this->$callback();
                }
            }
        }
        
        $this->_resetBuilder();
        return $result;
    }

    /**
     * Restore soft deleted record
     * @param mixed $id
     * @return bool
     */
    public function restore($id = null)
    {
        if (!$this->useSoftDeletes) {
            throw new Exception('Soft deletes not enabled for this model');
        }
        
        $data = [$this->deletedField => null];
        
        if ($id !== null) {
            $this->db->where($this->primaryKey, $id);
        } else {
            foreach ($this->tempWhere as $where) {
                if ($where['type'] === 'where') {
                    $this->db->where($where['field'], $where['value'], $where['escape'] ?? null);
                }
            }
        }
        
        $this->db->where($this->deletedField . ' IS NOT NULL');
        
        return $this->db->update($this->table, $data);
    }

    /**
     * Force delete (purge)
     * @param mixed $id
     * @return bool
     */
    public function forceDelete($id = null)
    {
        return $this->delete($id, true);
    }

    /**
     * Truncate table
     * @return bool
     */
    public function truncate()
    {
        return $this->db->truncate($this->table);
    }

    /**
     * Validate data
     * @param array $data
     * @param bool $update
     * @return bool
     */
    public function validate(array $data, $update = false)
    {
        $this->load->library('form_validation');
        
        // Reset validation rules
        $this->form_validation->reset_validation();
        
        // Set rules
        if (!empty($this->validationRules)) {
            foreach ($this->validationRules as $field => $rules) {
                $this->form_validation->set_rules($field, ucfirst($field), $rules);
            }
        }
        
        // Set data
        $this->form_validation->set_data($data);
        
        // Run validation
        return $this->form_validation->run();
    }

    /**
     * Get validation errors
     * @return array
     */
    public function errors()
    {
        return $this->form_validation->error_array();
    }

    /**
     * Load relations
     * @param array $results
     * @param array $relations
     * @return array
     */
    private function _loadRelations($results, $relations)
    {
        foreach ($relations as $relation) {
            if (method_exists($this, $relation)) {
                $results = $this->$relation($results);
            }
        }
        return $results;
    }

    /**
     * Define belongs to relation
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     * @return mixed
     */
    protected function belongsTo($model, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->_getForeignKey($model);
        $localKey = $localKey ?: $this->primaryKey;
        
        $instance = new $model();
        
        if (is_array($this->tempWith)) {
            // Eager loading mode
            $keys = [];
            foreach ($this->tempWith as $key => $value) {
                if ($value === $model) {
                    $keys = array_column($this->tempWith, $localKey);
                    break;
                }
            }
            
            $related = $instance->whereIn($foreignKey, array_unique($keys))->findAll();
            
            $relatedMap = [];
            foreach ($related as $item) {
                $key = is_object($item) ? $item->{$foreignKey} : $item[$foreignKey];
                $relatedMap[$key] = $item;
            }
            
            foreach ($this->tempWith as &$item) {
                $key = is_object($item) ? $item->{$localKey} : $item[$localKey];
                if (isset($relatedMap[$key])) {
                    $item->{$model} = $relatedMap[$key];
                }
            }
        }
        
        return $instance;
    }

    /**
     * Define has many relation
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     * @return mixed
     */
    protected function hasMany($model, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->_getForeignKey(get_class($this));
        $localKey = $localKey ?: $this->primaryKey;
        
        $instance = new $model();
        return $instance->where($foreignKey, $this->{$localKey});
    }

    /**
     * Get foreign key name
     * @param string $model
     * @return string
     */
    private function _getForeignKey($model)
    {
        $model = basename(str_replace('\\', '/', $model));
        return strtolower($model) . '_id';
    }

    /**
     * Paginate results
     * @param int $perPage
     * @param int|null $page
     * @return array
     */
    public function paginate($perPage = 15, $page = null)
    {
        $this->load->library('pagination');
        
        $page = $page ?: ($this->input->get('page') ?: 1);
        $offset = ($page - 1) * $perPage;
        
        // Total rows
        $totalRows = $this->count();
        
        // Get data
        $this->_resetBuilder();
        $this->limit($perPage, $offset);
        $this->_executeBuilder();
        $query = $this->builder->get();
        $data = $query->result($this->returnType);
        
        // Eager loading
        if (!empty($this->tempWith) && !empty($data)) {
            $data = $this->_loadRelations($data, $this->tempWith);
        }
        
        // Pagination info
        $lastPage = ceil($totalRows / $perPage);
        
        return [
            'data' => $data,
            'current_page' => (int)$page,
            'per_page' => $perPage,
            'total' => $totalRows,
            'last_page' => $lastPage,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $totalRows),
            'has_more' => $page < $lastPage
        ];
    }

    /**
     * Start transaction
     * @return $this
     */
    public function beginTransaction()
    {
        $this->db->trans_begin();
        return $this;
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit()
    {
        return $this->db->trans_commit();
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback()
    {
        return $this->db->trans_rollback();
    }

    /**
     * Get transaction status
     * @return bool
     */
    public function transactionStatus()
    {
        return $this->db->trans_status();
    }

    /**
     * Get last query
     * @return string
     */
    public function lastQuery()
    {
        return $this->db->last_query();
    }

    /**
     * Get last inserted ID
     * @return int
     */
    public function lastInsertId()
    {
        return $this->db->insert_id();
    }

    /**
     * Get affected rows
     * @return int
     */
    public function affectedRows()
    {
        return $this->db->affected_rows();
    }

    /**
     * Get DB error
     * @return array
     */
    public function dbError()
    {
        return $this->db->error();
    }

    /**
     * Get table fields
     * @return array
     */
    public function getFields()
    {
        return $this->db->field_data($this->table);
    }

    /**
     * Set return type
     * @param string $type
     * @return $this
     */
    public function setReturnType($type)
    {
        $this->returnType = $type;
        return $this;
    }

    /**
     * Magic method untuk dynamic where
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Dynamic where methods (whereUsername, whereEmail, etc)
        if (strpos($method, 'where') === 0) {
            $field = lcfirst(substr($method, 5));
            return $this->where($field, $parameters[0]);
        }
        
        // Dynamic scope methods
        if (method_exists($this, 'scope' . ucfirst($method))) {
            return $this->{'scope' . ucfirst($method)}(...$parameters);
        }
        
        throw new Exception("Method {$method} not found in " . get_class($this));
    }
}