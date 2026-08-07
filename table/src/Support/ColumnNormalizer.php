<?php

declare(strict_types=1);

namespace Kodhe\Table\Support;

/**
 * Column normalizer for table data
 */
class ColumnNormalizer
{
    /**
     * Prep args to ensure standard associative array format for all cell data
     *
     * @param array $args
     * @return array
     */
    public function prepArgs(array $args): array
    {
        // If there is no $args[0], skip this and treat as an associative array
        // This can happen if there is only a single key, for example this is passed to table->generate
        // array(array('foo'=>'bar'))
        if (isset($args[0]) && count($args) === 1 && is_array($args[0]) && !isset($args[0]['data'])) {
            $args = $args[0];
        }

        foreach ($args as $key => $val) {
            if (!is_array($val)) {
                $args[$key] = ['data' => $val];
            }
        }

        return $args;
    }

    /**
     * Make columns from a one-dimensional array
     *
     * @param array $array
     * @param int $col_limit
     * @return array|false
     */
    public function makeColumns(array $array, int $col_limit = 0)
    {
        if (empty($array) || $col_limit < 0) {
            return false;
        }

        if ($col_limit === 0) {
            return $array;
        }

        $new = [];
        do {
            $temp = array_splice($array, 0, $col_limit);

            if (count($temp) < $col_limit) {
                for ($i = count($temp); $i < $col_limit; $i++) {
                    $temp[] = '&nbsp;';
                }
            }

            $new[] = $temp;
        } while (count($array) > 0);

        return $new;
    }

    /**
     * Normalize heading data
     *
     * @param mixed $args
     * @return array
     */
    public function normalizeHeading($args): array
    {
        if (func_num_args() === 0) {
            return [];
        }

        $arguments = func_get_args();
        
        // Handle multiple arguments or single array argument
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return $this->prepArgs($arguments[0]);
        }

        return $this->prepArgs($arguments);
    }

    /**
     * Normalize row data
     *
     * @param mixed $args
     * @return array
     */
    public function normalizeRow($args): array
    {
        if (func_num_args() === 0) {
            return [];
        }

        $arguments = func_get_args();
        
        // Handle multiple arguments or single array argument
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return $this->prepArgs($arguments[0]);
        }

        return $this->prepArgs($arguments);
    }
}
