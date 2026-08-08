<?php

declare(strict_types=0);

namespace Kodhe\Framework\Zip\Support;

/**
 * Utility class for byte-safe string operations and DOS time conversion
 */
class ByteUtils
{
    private static ?bool $funcOverload = null;

    /**
     * Initialize mbstring overload detection
     */
    public static function init(): void
    {
        if (self::$funcOverload === null) {
            self::$funcOverload = (extension_loaded('mbstring') && ini_get('mbstring.func_overload'));
        }
    }

    /**
     * Byte-safe strlen()
     */
    public static function strlen(string $str): int
    {
        self::init();
        return self::$funcOverload
            ? mb_strlen($str, '8bit')
            : strlen($str);
    }

    /**
     * Byte-safe substr()
     */
    public static function substr(string $str, int $start, ?int $length = null): string
    {
        self::init();
        if (self::$funcOverload) {
            // mb_substr($str, $start, null, '8bit') returns an empty
            // string on PHP 5.3
            if ($length === null) {
                $length = ($start >= 0 ? self::strlen($str) - $start : -$start);
            }
            return mb_substr($str, $start, $length, '8bit');
        }

        return $length !== null
            ? substr($str, $start, $length)
            : substr($str, $start);
    }

    /**
     * Convert Unix timestamp to DOS time format
     */
    public static function unixToDosTime(int $timestamp): array
    {
        $date = getdate($timestamp);

        return [
            'time' => ($date['hours'] << 11) + ($date['minutes'] << 5) + (int)($date['seconds'] / 2),
            'date' => (($date['year'] - 1980) << 9) + ($date['mon'] << 5) + $date['mday']
        ];
    }

    /**
     * Normalize path separators to forward slashes
     */
    public static function normalizePath(string $path): string
    {
        return str_replace(['\\', '/'], '/', $path);
    }

    /**
     * Pack little-endian unsigned short (16-bit)
     */
    public static function packU16(int $value): string
    {
        return pack('v', $value & 0xFFFF);
    }

    /**
     * Pack little-endian unsigned long (32-bit)
     */
    public static function packU32(int $value): string
    {
        return pack('V', $value & 0xFFFFFFFF);
    }
}
