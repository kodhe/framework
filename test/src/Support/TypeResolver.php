<?php

declare(strict_types=1);

namespace Kodhe\Framework\Test\Support;

/**
 * Utility class for resolving types
 */
class TypeResolver
{
    /**
     * List of type checking functions supported by the library
     *
     * @var array
     */
    private static $typeFunctions = [
        'is_object',
        'is_string',
        'is_bool',
        'is_true',
        'is_false',
        'is_int',
        'is_numeric',
        'is_float',
        'is_double',
        'is_array',
        'is_null',
        'is_resource',
    ];

    /**
     * Check if a value is a type function name
     *
     * @param mixed $value Value to check
     * @return bool        Whether it's a type function
     */
    public static function isTypeFunction($value): bool
    {
        return is_string($value) && in_array($value, self::$typeFunctions, true);
    }

    /**
     * Execute a type function on a test value
     *
     * @param string $function Type function name
     * @param mixed  $test     Test value
     * @return bool            Result of type check
     * @throws \BadFunctionCallException If function doesn't exist
     */
    public static function executeTypeFunction(string $function, $test): bool
    {
        if (!self::isTypeFunction($function)) {
            throw new \BadFunctionCallException(
                sprintf('Invalid type function: %s', $function)
            );
        }

        // Handle special cases
        if ($function === 'is_true') {
            return $test === true;
        }

        if ($function === 'is_false') {
            return $test === false;
        }

        // Call the actual function
        return call_user_func($function, $test);
    }

    /**
     * Get the display type for a type function
     *
     * @param string $function Type function name
     * @return string          Display type name
     */
    public static function getDisplayType(string $function): string
    {
        if (in_array($function, ['is_true', 'is_false'], true)) {
            return 'bool';
        }

        return str_replace('is_', '', $function);
    }

    /**
     * Get all supported type functions
     *
     * @return array
     */
    public static function getSupportedTypeFunctions(): array
    {
        return self::$typeFunctions;
    }
}
