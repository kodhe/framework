<?php

declare(strict_types=1);

namespace Kodhe\Database\Contracts;

/**
 * Query Builder Interface
 */
interface BuilderInterface
{
    /**
     * Add select columns
     * @param array|string $columns
     * @return self
     */
    public function select($columns): self;

    /**
     * Add from table
     * @param string $table
     * @return self
     */
    public function from(string $table): self;

    /**
     * Add where condition
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function where($column, $operator = null, $value = null): self;

    /**
     * Add orWhere condition
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function orWhere($column, $operator = null, $value = null): self;

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
    ): self;

    /**
     * Add orderBy clause
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self;

    /**
     * Add groupBy clause
     * @param string|array $columns
     * @return self
     */
    public function groupBy($columns): self;

    /**
     * Add having clause
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function having(string $column, $operator = null, $value = null): self;

    /**
     * Set limit
     * @param int $limit
     * @param int $offset
     * @return self
     */
    public function limit(int $limit, int $offset = 0): self;

    /**
     * Execute and get all results
     * @return array
     */
    public function get(): array;

    /**
     * Get first result
     * @return mixed|null
     */
    public function first();

    /**
     * Insert data
     * @param array $data
     * @return bool|int
     */
    public function insert(array $data);

    /**
     * Update data
     * @param array $data
     * @return int
     */
    public function update(array $data): int;

    /**
     * Delete records
     * @return int
     */
    public function delete(): int;

    /**
     * Count results
     * @return int
     */
    public function count(): int;

    /**
     * Build SQL query
     * @return string
     */
    public function toSql(): string;
}
