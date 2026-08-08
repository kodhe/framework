<?php

declare(strict_types=1);

namespace Kodhe\Database\Traits;

/**
 * Trait untuk Query Building
 */
trait BuildsQueriesTrait
{
    /**
     * @var array Select columns
     */
    protected $selects = ['*'];

    /**
     * @var array Where conditions
     */
    protected $wheres = [];

    /**
     * @var array Joins
     */
    protected $joins = [];

    /**
     * @var array Order by clauses
     */
    protected $orders = [];

    /**
     * @var array Group by clauses
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
     * Add select columns
     * @param mixed $columns
     * @return self
     */
    public function select($columns = '*'): self
    {
        if (is_array($columns)) {
            $this->selects = $columns;
        } else {
            $this->selects = [$columns];
        }

        return $this;
    }

    /**
     * Add where condition
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function where($column, $operator = null, $value = null): self
    {
        $this->wheres[] = [
            'type' => 'where',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add orWhere condition
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        $this->wheres[] = [
            'type' => 'orWhere',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add whereIn condition
     * @param string $column
     * @param array $values
     * @return self
     */
    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'whereIn',
            'column' => $column,
            'values' => $values,
        ];

        return $this;
    }

    /**
     * Add whereNotIn condition
     * @param string $column
     * @param array $values
     * @return self
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'whereNotIn',
            'column' => $column,
            'values' => $values,
        ];

        return $this;
    }

    /**
     * Add like condition
     * @param string $column
     * @param string $value
     * @param string $side
     * @return self
     */
    public function like(string $column, string $value, string $side = 'both'): self
    {
        $this->wheres[] = [
            'type' => 'like',
            'column' => $column,
            'value' => $value,
            'side' => $side,
        ];

        return $this;
    }

    /**
     * Add join clause
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return self
     */
    public function join(
        string $table,
        string $first,
        string $operator = null,
        string $second = null,
        string $type = 'inner'
    ): self {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * Add leftJoin clause
     * @param string $table
     * @param string $first
     * @param string $second
     * @return self
     */
    public function leftJoin(string $table, string $first, string $second = null): self
    {
        return $this->join($table, $first, '=', $second, 'left');
    }

    /**
     * Add orderBy clause
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    /**
     * Add groupBy clause
     * @param string|array $columns
     * @return self
     */
    public function groupBy($columns): self
    {
        if (is_array($columns)) {
            $this->groups = array_merge($this->groups, $columns);
        } else {
            $this->groups[] = $columns;
        }

        return $this;
    }

    /**
     * Add having clause
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function having(string $column, $operator = null, $value = null): self
    {
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Set limit
     * @param int $limit
     * @param int $offset
     * @return self
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set offset
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Reset query builder state
     * @return self
     */
    protected function resetQuery(): self
    {
        $this->selects = ['*'];
        $this->wheres = [];
        $this->joins = [];
        $this->orders = [];
        $this->groups = [];
        $this->havings = [];
        $this->limit = null;
        $this->offset = 0;

        return $this;
    }
}
