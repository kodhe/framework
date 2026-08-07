<?php

declare(strict_types=1);

namespace Kodhe\Table\ValueObjects;

/**
 * Value object for a table cell
 */
class TableCell
{
    /**
     * @var mixed The cell data
     */
    private $data;

    /**
     * @var array Additional attributes
     */
    private array $attributes = [];

    /**
     * Constructor
     *
     * @param mixed $data
     * @param array $attributes
     */
    public function __construct($data = '', array $attributes = [])
    {
        $this->data = $data;
        $this->attributes = $attributes;
    }

    /**
     * Create from array format
     *
     * @param array|mixed $value
     * @return self
     */
    public static function fromValue($value): self
    {
        if (is_array($value) && isset($value['data'])) {
            $data = $value['data'];
            $attributes = array_filter($value, fn($key) => $key !== 'data', ARRAY_FILTER_USE_KEY);
            return new self($data, $attributes);
        }

        return new self($value);
    }

    /**
     * Get the cell data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the cell attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Check if cell is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->data === '' || $this->data === null;
    }

    /**
     * Convert to array format
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = ['data' => $this->data];
        foreach ($this->attributes as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }
}
