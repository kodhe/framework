<?php

declare(strict_types=0);

namespace Kodhe\Framework\Test\Support;

/**
 * Utility class for comparing values
 */
class ValueComparator
{
    /**
     * Compare two values with loose equality
     *
     * @param mixed $test     Test value
     * @param mixed $expected Expected value
     * @return bool           Whether values are equal
     */
    public static function equals($test, $expected): bool
    {
        return $test == $expected;
    }

    /**
     * Compare two values with strict equality
     *
     * @param mixed $test     Test value
     * @param mixed $expected Expected value
     * @return bool           Whether values are identical
     */
    public static function strictEquals($test, $expected): bool
    {
        return $test === $expected;
    }

    /**
     * Check if values are not equal (loose)
     *
     * @param mixed $test     Test value
     * @param mixed $expected Expected value
     * @return bool           Whether values are not equal
     */
    public static function notEquals($test, $expected): bool
    {
        return $test != $expected;
    }

    /**
     * Check if values are not identical (strict)
     *
     * @param mixed $test     Test value
     * @param mixed $expected Expected value
     * @return bool           Whether values are not identical
     */
    public static function strictNotEquals($test, $expected): bool
    {
        return $test !== $expected;
    }

    /**
     * Compare types of two values
     *
     * @param mixed $test     Test value
     * @param mixed $expected Expected value
     * @return bool           Whether types match
     */
    public static function sameType($test, $expected): bool
    {
        return gettype($test) === gettype($expected);
    }
}
