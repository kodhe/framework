<?php

declare(strict_types=0);

namespace Kodhe\Framework\Table\Support;

/**
 * Table validator for validating table data
 */
class TableValidator
{
    /**
     * Validate heading data
     *
     * @param mixed $heading
     * @return bool
     */
    public function isValidHeading($heading): bool
    {
        return is_array($heading);
    }

    /**
     * Validate row data
     *
     * @param mixed $row
     * @return bool
     */
    public function isValidRow($row): bool
    {
        return is_array($row);
    }

    /**
     * Validate rows array
     *
     * @param array $rows
     * @return bool
     */
    public function isValidRows(array $rows): bool
    {
        foreach ($rows as $row) {
            if (!$this->isValidRow($row)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate table has data to display
     *
     * @param array $heading
     * @param array $rows
     * @return bool
     */
    public function hasData(array $heading, array $rows): bool
    {
        return !empty($heading) || !empty($rows);
    }

    /**
     * Validate callable function
     *
     * @param mixed $function
     * @return bool
     */
    public function isValidCallable($function): bool
    {
        return $function !== null && is_callable($function);
    }
}
