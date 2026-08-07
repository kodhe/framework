<?php

declare(strict_types=1);

namespace Kodhe\Typography\Factory;

use Kodhe\Typography\Contracts\FormatterInterface;
use Kodhe\Typography\Formatters\CharacterFormatter;
use Kodhe\Typography\Formatters\ParagraphFormatter;
use Kodhe\Typography\Exceptions/TypographyException;

/**
 * Formatter Factory
 * 
 * Creates and caches formatter instances.
 */
class FormatterFactory
{
    /**
     * @var array Cached formatter instances
     */
    private static $instances = [];

    /**
     * Get a formatter instance.
     *
     * @param string $type
     * @return FormatterInterface
     * @throws TypographyException
     */
    public static function create(string $type): FormatterInterface
    {
        if (!isset(self::$instances[$type])) {
            self::$instances[$type] = self::makeFormatter($type);
        }

        return self::$instances[$type];
    }

    /**
     * Make a formatter instance.
     *
     * @param string $type
     * @return FormatterInterface
     * @throws TypographyException
     */
    private static function makeFormatter(string $type): FormatterInterface
    {
        switch ($type) {
            case 'character':
                return new CharacterFormatter();
            case 'paragraph':
                return new ParagraphFormatter();
            default:
                throw new TypographyException("Unknown formatter type: {$type}");
        }
    }

    /**
     * Clear cached instances.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$instances = [];
    }

    /**
     * Check if a formatter type exists.
     *
     * @param string $type
     * @return bool
     */
    public static function has(string $type): bool
    {
        return in_array($type, ['character', 'paragraph']);
    }
}
