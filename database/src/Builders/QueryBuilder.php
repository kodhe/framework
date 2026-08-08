<?php

declare(strict_types=1);

namespace Kodhe\Database\Builders;

use Kodhe\Database\Contracts\BuilderInterface;
use Kodhe\Database\Traits\BuildsQueriesTrait;
use Kodhe\Database\Traits\ManagesConnectionTrait;

/**
 * Query Builder Implementation
 */
class QueryBuilder implements BuilderInterface
{
    use BuildsQueriesTrait, ManagesConnectionTrait;

    /**
     * @var string Table name
     */
    protected $table;

    /**
     * Constructor
     * @param string|null $table
     * @param mixed $connection
     */
    public function __construct(?string $table = null, $connection = null)
    {
        if ($table) {
            $this->from($table);
        }

        if ($connection) {
            $this->setConnection($connection);
        }
    }

    /**
     * Set table name
     * @param string $table
     * @return self
     */
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Get table name
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * Execute and get all results
     * @return array
     */
    public function get(): array
    {
        $db = $this->getConnection();
        $query = $this->buildQuery($db);
        $result = $db->query($query);

        return $result->result_array();
    }

    /**
     * Get first result
     * @return mixed|null
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Insert data
     * @param array $data
     * @return bool|int
     */
    public function insert(array $data)
    {
        $db = $this->getConnection();
        $db->insert($this->table, $data);
        return $db->insert_id();
    }

    /**
     * Update data
     * @param array $data
     * @return int
     */
    public function update(array $data): int
    {
        $db = $this->getConnection();
        $db->where($this->buildWhereArray());
        $db->update($this->table, $data);
        return $db->affected_rows();
    }

    /**
     * Delete records
     * @return int
     */
    public function delete(): int
    {
        $db = $this->getConnection();
        $db->where($this->buildWhereArray());
        $db->delete($this->table);
        return $db->affected_rows();
    }

    /**
     * Count results
     * @return int
     */
    public function count(): int
    {
        $db = $this->getConnection();
        $query = $this->buildCountQuery($db);
        $result = $db->query($query)->row_array();
        return (int) ($result['numrows'] ?? 0);
    }

    /**
     * Build SQL query
     * @return string
     */
    public function toSql(): string
    {
        $db = $this->getConnection();
        return $this->buildQuery($db);
    }

    /**
     * Build SELECT query
     * @param mixed $db
     * @return string
     */
    protected function buildQuery($db): string
    {
        $select = implode(', ', $this->selects);
        $sql = "SELECT {$select} FROM {$this->table}";

        // Add joins
        foreach ($this->joins as $join) {
            $on = $join['first'];
            if ($join['operator'] && $join['second']) {
                $on .= " {$join['operator']} {$join['second']}";
            }
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$on}";
        }

        // Add where conditions
        $whereClause = $this->buildWhereClause($db);
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }

        // Add group by
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // Add having
        if (!empty($this->havings)) {
            $havingParts = [];
            foreach ($this->havings as $having) {
                $value = $db->escape($having['value']);
                $havingParts[] = "{$having['column']} {$having['operator']} {$value}";
            }
            $sql .= ' HAVING ' . implode(' AND ', $havingParts);
        }

        // Add order by
        if (!empty($this->orders)) {
            $orderParts = [];
            foreach ($this->orders as $order) {
                $orderParts[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }

        // Add limit and offset
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset > 0) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $sql;
    }

    /**
     * Build COUNT query
     * @param mixed $db
     * @return string
     */
    protected function buildCountQuery($db): string
    {
        $sql = "SELECT COUNT(*) AS numrows FROM {$this->table}";

        $whereClause = $this->buildWhereClause($db);
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }

        return $sql;
    }

    /**
     * Build WHERE clause
     * @param mixed $db
     * @return string
     */
    protected function buildWhereClause($db): string
    {
        $conditions = [];

        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 || $where['type'] === 'where' ? '' : 'OR ';

            switch ($where['type']) {
                case 'where':
                case 'orWhere':
                    if (is_array($where['column'])) {
                        foreach ($where['column'] as $col => $val) {
                            $escapedValue = $db ? $db->escape($val) : "'" . addslashes($val) . "'";
                            $conditions[] = "{$prefix}{$col} = {$escapedValue}";
                        }
                    } else {
                        $operator = $where['operator'] ?? '=';
                        $escapedValue = $db ? $db->escape($where['value']) : "'" . addslashes($where['value']) . "'";
                        $conditions[] = "{$prefix}{$where['column']} {$operator} {$escapedValue}";
                    }
                    break;

                case 'whereIn':
                    $escapedValues = array_map(fn($v) => $db ? $db->escape($v) : "'" . addslashes($v) . "'", $where['values']);
                    $conditions[] = "{$prefix}{$where['column']} IN (" . implode(', ', $escapedValues) . ")";
                    break;

                case 'whereNotIn':
                    $escapedValues = array_map(fn($v) => $db ? $db->escape($v) : "'" . addslashes($v) . "'", $where['values']);
                    $conditions[] = "{$prefix}{$where['column']} NOT IN (" . implode(', ', $escapedValues) . ")";
                    break;

                case 'like':
                    $side = $where['side'] ?? 'both';
                    $pattern = match($side) {
                        'before' => "%{$where['value']}",
                        'after' => "{$where['value']}%",
                        default => "%{$where['value']}%"
                    };
                    $escapedPattern = $db ? $db->escape($pattern) : "'%" . addslashes($where['value']) . "%'";
                    $conditions[] = "{$prefix}{$where['column']} LIKE {$escapedPattern}";
                    break;
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Build WHERE array for CI DB
     * @return array
     */
    protected function buildWhereArray(): array
    {
        $where = [];

        foreach ($this->wheres as $w) {
            if ($w['type'] === 'where' && !is_array($w['column'])) {
                $where[$w['column']] = $w['value'];
            } elseif (is_array($w['column'])) {
                $where = array_merge($where, $w['column']);
            }
        }

        return $where;
    }

    /**
     * Create new instance for table
     * @param string $table
     * @return self
     */
    public static function table(string $table): self
    {
        return new self($table);
    }
}
