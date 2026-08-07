<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpc\Support;

/**
 * Lazy XML encoding helper
 */
class LazyEncoder
{
    /**
     * @var array
     */
    private static $cache = [];

    /**
     * Lazily encode a value to XML-RPC format
     *
     * @param mixed $value
     * @param string $type
     * @return string
     */
    public static function encode($value, string $type = 'string'): string
    {
        $key = md5(serialize([$value, $type]));

        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = self::doEncode($value, $type);
        }

        return self::$cache[$key];
    }

    /**
     * Perform actual encoding
     *
     * @param mixed $value
     * @param string $type
     * @return string
     */
    private static function doEncode($value, string $type): string
    {
        switch ($type) {
            case 'base64':
                return '<base64>'.base64_encode((string) $value).'</base64>';
            case 'boolean':
                return '<boolean>'.((bool) $value ? '1' : '0').'</boolean>';
            case 'string':
                return '<string>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_NOQUOTES, 'UTF-8').'</string>';
            case 'i4':
            case 'int':
                return '<int>'.(int) $value.'</int>';
            case 'double':
                return '<double>'.(float) $value.'</double>';
            case 'dateTime.iso8601':
                return '<dateTime.iso8601>'.gmstrftime('%Y%m%dT%H:%i:%s', (int) $value).'</dateTime.iso8601>';
            default:
                return '<string>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_NOQUOTES, 'UTF-8').'</string>';
        }
    }

    /**
     * Clear the encoding cache
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = [];
    }
}
