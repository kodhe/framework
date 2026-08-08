<?php
/**
 * Template Helper - Utility functions
 *
 * @package CodeIgniter\Parser\Support
 */

namespace Kodhe\Parser\Support;

class TemplateHelper
{
    /**
     * Escape HTML special characters
     *
     * @param string $value
     * @return string
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Strip tags from value
     *
     * @param string $value
     * @return string
     */
    public static function stripTags(string $value): string
    {
        return strip_tags($value);
    }

    /**
     * Trim whitespace
     *
     * @param string $value
     * @return string
     */
    public static function trim(string $value): string
    {
        return trim($value);
    }

    /**
     * Convert to uppercase
     *
     * @param string $value
     * @return string
     */
    public static function upper(string $value): string
    {
        return strtoupper($value);
    }

    /**
     * Convert to lowercase
     *
     * @param string $value
     * @return string
     */
    public static function lower(string $value): string
    {
        return strtolower($value);
    }

    /**
     * Limit string length
     *
     * @param string $value
     * @param int    $length
     * @param string $suffix
     * @return string
     */
    public static function limit(string $value, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($value) <= $length) {
            return $value;
        }
        return substr($value, 0, $length) . $suffix;
    }

    /**
     * Check if value is empty
     *
     * @param mixed $value
     * @return bool
     */
    public static function isEmpty($value): bool
    {
        return empty($value);
    }

    /**
     * Get value or default
     *
     * @param mixed $value
     * @param mixed $default
     * @return mixed
     */
    public static function valueOr($value, $default)
    {
        return isset($value) && $value !== '' ? $value : $default;
    }

    /**
     * Join array values
     *
     * @param array  $array
     * @param string $glue
     * @return string
     */
    public static function join(array $array, string $glue = ', '): string
    {
        return implode($glue, $array);
    }

    /**
     * Count items in array
     *
     * @param array $array
     * @return int
     */
    public static function count(array $array): int
    {
        return count($array);
    }
}
