<?php

declare(strict_types=1);

namespace Kodhe\Table\ValueObjects;

/**
 * Value object for a table row
 */
class TableRow
{
    /**
     * @var TableCell[] The cells in this row
     */
    private array $cells = [];

    /**
     * Constructor
     *
     * @param array $cells
     */
    public function __construct(array $cells = [])
    {
        foreach ($cells as $cell) {
            $this->addCell($cell);
        }
    }

    /**
     * Add a cell to the row
     *
     * @param mixed $cell
     * @return self
     */
    public function addCell($cell): self
    {
        if ($cell instanceof TableCell) {
            $this->cells[] = $cell;
        } else {
            $this->cells[] = TableCell::fromValue($cell);
        }
        return $this;
    }

    /**
     * Get all cells
     *
     * @return TableCell[]
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    /**
     * Get cell at index
     *
     * @param int $index
     * @return TableCell|null
     */
    public function getCell(int $index): ?TableCell
    {
        return $this->cells[$index] ?? null;
    }

    /**
     * Check if row is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->cells);
    }

    /**
     * Count cells
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->cells);
    }

    /**
     * Convert to array format
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_map(fn(TableCell $cell) => $cell->toArray(), $this->cells);
    }
}
