<?php
namespace Kodhe\Framework\Database;


use CI_Model;

/**
 * Query Builder dengan Fluent Interface
 * @package Kodhe\Framework\Database
 */
class Builder
{
    /**
     * @var CI_Model
     */
    protected $model;
    
    /**
     * @var array Query conditions
     */
    protected $conditions = [];
    
    /**
     * @var array Select columns
     */
    protected $selects = ['*'];
    
    /**
     * @var array Joins
     */
    protected $joins = [];
    
    /**
     * @var array Order by
     */
    protected $orders = [];
    
    /**
     * @var array Group by
     */
    protected $groups = [];
    
    /**
     * @var array Having conditions
     */
    protected $havings = [];
    
    /**
     * @var int|null Limit
     */
    protected $limit = null;
    
    /**
     * @var int Offset
     */
    protected $offset = 0;
    
    /**
     * @var array With relations
     */
    protected $with = [];
    
    /**
     * Constructor
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    
    /**
     * Add where condition
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If column is a closure
        if ($column instanceof Closure) {
            $this->conditions[] = [
                'type' => 'nested',
                'callback' => $column,
                'boolean' => $boolean
            ];
            return $this;
        }
        
        // If only two arguments provided, assume operator is '='
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->conditions[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        
        return $this;
    }
    
    /**
     * Add or where condition
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }
    
    /**
     * Add where in condition
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn($column, array $values, $boolean = 'and', $not = false)
    {
        $this->conditions[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not
        ];
        
        return $this;
    }
    
    /**
     * Add where not in condition
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn($column, array $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }
    
    /**
     * Add where null condition
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $this->conditions[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not
        ];
        
        return $this;
    }
    
    /**
     * Add where not null condition
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        return $this->whereNull($column, $boolean, true);
    }
    
    /**
     * Add where between condition
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $this->conditions[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not
        ];
        
        return $this;
    }
    
    /**
     * Add where like condition
     * @param string $column
     * @param string $value
     * @param string $boolean
     * @return $this
     */
    public function whereLike($column, $value, $boolean = 'and')
    {
        $this->conditions[] = [
            'type' => 'like',
            'column' => $column,
            'value' => $value,
            'boolean' => $boolean
        ];
        
        return $this;
    }
    
    /**
     * Add select columns
     * @param mixed $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * Add join
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner')
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];
        
        return $this;
    }
    
    /**
     * Add left join
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }
    
    /**
     * Add right join
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }
    
    /**
     * Add order by
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'asc' ? 'asc' : 'desc'
        ];
        
        return $this;
    }
    
    /**
     * Add group by
     * @param mixed $columns
     * @return $this
     */
    public function groupBy($columns)
    {
        $this->groups = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * Add having condition
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function having($column, $operator, $value = null, $boolean = 'and')
    {
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        
        return $this;
    }
    
    /**
     * Set limit
     * @param int $limit
     * @return $this
     */
    public function take($limit)
    {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * Set offset
     * @param int $offset
     * @return $this
     */
    public function skip($offset)
    {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * Add eager load relations
     * @param mixed $relations
     * @return $this
     */
    public function with($relations)
    {
        $this->with = is_array($relations) ? $relations : func_get_args();
        return $this;
    }
    
    /**
     * Execute query and get results
     * @return array
     */
    public function get()
    {
        return $this->model->all();
    }
    
    /**
     * Execute query and get first result
     * @return mixed
     */
    public function first()
    {
        $this->take(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Get count
     * @param string $column
     * @return int
     */
    public function count($column = '*')
    {
        return $this->model->count();
    }
    
    /**
     * Get paginated results
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function paginate($perPage = 15, $page = null)
    {
        return $this->model->paginate($perPage, $page);
    }
}