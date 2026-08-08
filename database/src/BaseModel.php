<?php

declare(strict_types=1);

namespace Kodhe\Database;

use Kodhe\Database\Contracts\ModelInterface;
use Kodhe\Database\Contracts\BuilderInterface;
use Kodhe\Database\Builders\QueryBuilder;
use Kodhe\Database\Traits\ManagesConnectionTrait;
use Exception;

/**
 * Base Model Implementation
 * Compatible dengan CodeIgniter 3
 */
abstract class BaseModel implements ModelInterface
{
    use ManagesConnectionTrait;

    /**
     * @var string Table name
     */
    protected $table = '';

    /**
     * @var string Primary key
     */
    protected $primaryKey = 'id';

    /**
     * @var array Allowed fields untuk mass assignment
     */
    protected $allowedFields = [];

    /**
     * @var bool Gunakan timestamps
     */
    protected $useTimestamps = false;

    /**
     * @var string Created field
     */
    protected $createdField = 'created_at';

    /**
     * @var string Updated field
     */
    protected $updatedField = 'updated_at';

    /**
     * @var bool Gunakan soft deletes
     */
    protected $useSoftDeletes = false;

    /**
     * @var string Deleted field
     */
    protected $deletedField = 'deleted_at';

    /**
     * @var array Validation rules
     */
    protected $validationRules = [];

    /**
     * @var bool Skip validation
     */
    protected $skipValidation = false;

    /**
     * @var QueryBuilder|null
     */
    protected $queryBuilder = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Auto-connect to database
        $this->connect();
    }

    /**
     * Get new query builder instance
     * @return BuilderInterface
     */
    public function newQuery(): BuilderInterface
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new QueryBuilder($this->table, $this->getConnection());
        }

        return $this->queryBuilder;
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
     * Find or fail
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
     * Get all records
     * @return array
     */
    public function all(): array
    {
        return $this->newQuery()->get();
    }

    /**
     * Create record
     * @param array $data
     * @return mixed Insert ID or false
     */
    public function create(array $data)
    {
        // Filter allowed fields
        if (!empty($this->allowedFields)) {
            $data = array_intersect_key($data, array_flip($this->allowedFields));
        }

        // Add timestamps
        if ($this->useTimestamps) {
            $data[$this->createdField] = date('Y-m-d H:i:s');
            $data[$this->updatedField] = date('Y-m-d H:i:s');
        }

        // Add soft delete field
        if ($this->useSoftDeletes) {
            $data[$this->deletedField] = null;
        }

        return $this->newQuery()->insert($data);
    }

    /**
     * Update record
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool
    {
        // Filter allowed fields
        if (!empty($this->allowedFields)) {
            $data = array_intersect_key($data, array_flip($this->allowedFields));
        }

        // Add timestamps
        if ($this->useTimestamps) {
            $data[$this->updatedField] = date('Y-m-d H:i:s');
        }

        $affected = $this->where($this->primaryKey, $id)->newQuery()->update($data);
        return $affected > 0;
    }

    /**
     * Delete record (soft or hard)
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool
    {
        if ($this->useSoftDeletes) {
            // Soft delete
            $data = [$this->deletedField => date('Y-m-d H:i:s')];
            $affected = $this->where($this->primaryKey, $id)->newQuery()->update($data);
            return $affected > 0;
        } else {
            // Hard delete
            $affected = $this->where($this->primaryKey, $id)->newQuery()->delete();
            return $affected > 0;
        }
    }

    /**
     * Find or create
     * @param array $attributes
     * @param array $values
     * @return mixed
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        $record = $this->where($attributes)->first();

        if ($record) {
            return $record;
        }

        $data = array_merge($attributes, $values);
        $id = $this->create($data);
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
        $record = $this->where($attributes)->first();

        if ($record) {
            return $record;
        }

        return (object) array_merge($attributes, $values);
    }

    /**
     * Update or create
     * @param array $attributes
     * @param array $values
     * @return mixed
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        $record = $this->where($attributes)->first();

        if ($record) {
            $this->update($record->{$this->primaryKey}, $values);
            return $this->find($record->{$this->primaryKey});
        }

        $data = array_merge($attributes, $values);
        $id = $this->create($data);
        return $this->find($id);
    }

    /**
     * Get first record
     * @return mixed|null
     */
    public function first()
    {
        return $this->newQuery()->first();
    }

    /**
     * Get count
     * @return int
     */
    public function count(): int
    {
        return $this->newQuery()->count();
    }

    /**
     * Add where condition
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null): self
    {
        $this->newQuery()->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add orWhere condition
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        $this->newQuery()->orWhere($column, $operator, $value);
        return $this;
    }

    /**
     * Add select columns
     * @param mixed $columns
     * @return $this
     */
    public function select($columns = '*'): self
    {
        $this->newQuery()->select($columns);
        return $this;
    }

    /**
     * Set limit
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->newQuery()->limit($limit, $offset);
        return $this;
    }

    /**
     * Add orderBy
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->newQuery()->orderBy($column, $direction);
        return $this;
    }

    /**
     * Reset query builder
     * @return $this
     */
    public function resetQuery(): self
    {
        $this->queryBuilder = null;
        return $this;
    }

    /**
     * Get table name
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set table name
     * @param string $table
     * @return $this
     */
    public function setTable(string $table): self
    {
        $this->table = $table;
        $this->queryBuilder = null;
        return $this;
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->trans_begin();
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getConnection()->trans_commit();
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->getConnection()->trans_rollback();
    }
}
