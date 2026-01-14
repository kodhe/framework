<?php

namespace Kodhe\Framework\Database\ORM;

use Exception;

/**
 * Base Model dengan CRUD operations
 */
class Model extends LegacyModel
{
    protected $table;
    protected $primary_key = 'id';
    protected $soft_delete = false;
    protected $soft_delete_field = 'deleted_at';
    protected $created_field = 'created_at';
    protected $updated_field = 'updated_at';
    protected $return_type = 'object';
    protected $protected_fields = [];
    
    public function __construct()
    {
        parent::__construct();
        
        // Auto set table name dari class name
        if (empty($this->table)) {
            $this->table = strtolower(get_class($this));
        }
    }
    
    /**
     * Get all records
     */
    public function all($columns = '*', $order_by = null)
    {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if ($order_by) {
            $this->db->order_by($order_by);
        }
        
        $query = $this->db->select($columns)
                         ->get($this->table);
        
        return $this->return_type == 'array' 
            ? $query->result_array() 
            : $query->result();
    }
    
    /**
     * Find by ID
     */
    public function find($id, $columns = '*')
    {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        $query = $this->db->select($columns)
                         ->where($this->primary_key, $id)
                         ->get($this->table);
        
        return $this->return_type == 'array' 
            ? $query->row_array() 
            : $query->row();
    }
    
    /**
     * Find by conditions
     */
    public function find_by($conditions = [], $columns = '*', $order_by = null, $limit = null)
    {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }
        
        if ($order_by) {
            $this->db->order_by($order_by);
        }
        
        if ($limit) {
            $this->db->limit($limit);
        }
        
        $query = $this->db->select($columns)
                         ->get($this->table);
        
        return $this->return_type == 'array' 
            ? $query->result_array() 
            : $query->result();
    }
    
    /**
     * Find single record by conditions
     */
    public function find_one($conditions = [], $columns = '*')
    {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }
        
        $query = $this->db->select($columns)
                         ->limit(1)
                         ->get($this->table);
        
        return $this->return_type == 'array' 
            ? $query->row_array() 
            : $query->row();
    }
    
    /**
     * Create new record
     */
    public function create($data)
    {
        // Filter protected fields
        $data = $this->_filter_protected_fields($data);
        
        // Set timestamps
        if ($this->created_field && !isset($data[$this->created_field])) {
            $data[$this->created_field] = date('Y-m-d H:i:s');
        }
        
        $this->db->insert($this->table, $data);
        
        return $this->db->insert_id();
    }
    
    /**
     * Update record
     */
    public function update($id, $data)
    {
        // Filter protected fields
        $data = $this->_filter_protected_fields($data);
        
        // Set updated timestamp
        if ($this->updated_field && !isset($data[$this->updated_field])) {
            $data[$this->updated_field] = date('Y-m-d H:i:s');
        }
        
        $this->db->where($this->primary_key, $id);
        return $this->db->update($this->table, $data);
    }
    
    /**
     * Delete record
     */
    public function delete($id)
    {
        if ($this->soft_delete) {
            $data = [$this->soft_delete_field => date('Y-m-d H:i:s')];
            return $this->update($id, $data);
        }
        
        return $this->db->where($this->primary_key, $id)
                       ->delete($this->table);
    }
    
    /**
     * Paginate results
     */
    public function paginate($per_page = 10, $page = 1, $conditions = [], $columns = '*', $order_by = null)
    {
        $offset = ($page - 1) * $per_page;
        
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }
        
        if ($order_by) {
            $this->db->order_by($order_by);
        }
        
        // Get total rows
        $total_rows = $this->db->count_all_results($this->table, FALSE);
        
        // Get paginated data
        $query = $this->db->select($columns)
                         ->limit($per_page, $offset)
                         ->get();
        
        $data = $this->return_type == 'array' 
            ? $query->result_array() 
            : $query->result();
        
        return [
            'data' => $data,
            'total' => $total_rows,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total_rows / $per_page)
        ];
    }
    
    /**
     * Count records
     */
    public function count($conditions = [])
    {
        if ($this->soft_delete) {
            $this->db->where($this->soft_delete_field, null);
        }
        
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }
        
        return $this->db->count_all_results($this->table);
    }
    
    /**
     * Filter protected fields
     */
    private function _filter_protected_fields($data)
    {
        if (!empty($this->protected_fields)) {
            foreach ($this->protected_fields as $field) {
                if (isset($data[$field])) {
                    unset($data[$field]);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Begin transaction
     */
    public function begin_transaction()
    {
        $this->db->trans_begin();
    }
    
    /**
     * Commit transaction
     */
    public function commit_transaction()
    {
        $this->db->trans_commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback_transaction()
    {
        $this->db->trans_rollback();
    }
    
    /**
     * Check if transaction has errors
     */
    public function transaction_status()
    {
        return $this->db->trans_status();
    }
}