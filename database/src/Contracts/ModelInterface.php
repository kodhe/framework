<?php

declare(strict_types=1);

namespace Kodhe\Database\Contracts;

/**
 * Model Interface
 */
interface ModelInterface
{
    /**
     * Find by primary key
     * @param mixed $id
     * @return mixed|null
     */
    public function find($id);

    /**
     * Find or fail
     * @param mixed $id
     * @return mixed
     * @throws \Exception
     */
    public function findOrFail($id);

    /**
     * Get all records
     * @return array
     */
    public function all(): array;

    /**
     * Insert record
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update record
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool;

    /**
     * Delete record
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool;

    /**
     * Get query builder instance
     * @return BuilderInterface
     */
    public function newQuery(): BuilderInterface;
}
