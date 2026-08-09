<?php

declare(strict_types=1);

namespace Kodhe\Framework\Table\Builder;

use Kodhe\Framework\Table\Support\ColumnNormalizer;

/**
 * Builder for table rows
 */
class RowBuilder
{
    /**
     * @var array The rows data
     */
    private array $rows = [];

    /**
     * @var ColumnNormalizer The column normalizer
     */
    private ColumnNormalizer $normalizer;

    /**
     * Constructor
     *
     * @param ColumnNormalizer|null $normalizer
     */
    public function __construct(?ColumnNormalizer $normalizer = null)
    {
        $this->normalizer = $normalizer ?? new ColumnNormalizer();
    }

    /**
     * Add a row
     *
     * @param mixed $args
     * @return self
     */
    public function addRow($args = null): self
    {
        if ($args === null) {
            return $this;
        }

        $arguments = func_get_args();
        $row = $this->normalizer->prepArgs($arguments);
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Set rows from array
     *
     * @param array $rows
     * @return self
     */
    public function setRows(array $rows): self
    {
        $this->rows = [];
        foreach ($rows as $row) {
            $this->rows[] = $this->normalizer->prepArgs(is_array($row) ? $row : [$row]);
        }
        return $this;
    }

    /**
     * Get all rows
     *
     * @return array
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Check if rows are empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->rows);
    }

    /**
     * Count rows
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->rows);
    }

    /**
     * Clear all rows
     *
     * @return self
     */
    public function clear(): self
    {
        $this->rows = [];
        return $this;
    }

    /**
     * Make columns from one-dimensional array
     *
     * @param array $array
     * @param int $col_limit
     * @return array|false
     */
    public function makeColumns(array $array, int $col_limit = 0)
    {
        return $this->normalizer->makeColumns($array, $col_limit);
    }
}
