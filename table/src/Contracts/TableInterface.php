<?php

declare(strict_types=0);

namespace Kodhe\Framework\Table\Contracts;

/**
 * Interface for Table class
 */
interface TableInterface
{
    /**
     * Set the table heading
     *
     * @param mixed $args
     * @return self
     */
    public function set_heading($args = array()): self;

    /**
     * Set columns with a column limit
     *
     * @param array $array
     * @param int $col_limit
     * @return array|false
     */
    public function make_columns(array $array = [], int $col_limit = 0);

    /**
     * Set empty cells value
     *
     * @param mixed $value
     * @return self
     */
    public function set_empty($value): self;

    /**
     * Add a table row
     *
     * @param mixed $args
     * @return self
     */
    public function add_row($args = array()): self;

    /**
     * Set table caption
     *
     * @param string $caption
     * @return self
     */
    public function set_caption(string $caption): self;

    /**
     * Generate the table HTML
     *
     * @param mixed $table_data
     * @return string
     */
    public function generate($table_data = null): string;

    /**
     * Clear table data
     *
     * @return self
     */
    public function clear(): self;

    /**
     * Set the template
     *
     * @param array $template
     * @return bool
     */
    public function set_template(array $template): bool;
}
