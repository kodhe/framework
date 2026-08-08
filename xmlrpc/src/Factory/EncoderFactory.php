<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc\Factory;

use Kodhe\Framework\Xmlrpc\Encoder\XmlRpcEncoder;
use Kodhe\Framework\Xmlrpc\Contracts\EncoderInterface;

/**
 * Factory for creating encoder instances
 */
class EncoderFactory
{
    /**
     * @var array
     */
    private static $encoders = [];

    /**
     * Get or create an encoder instance
     *
     * @param string $type
     * @return EncoderInterface
     */
    public static function create(string $type = 'xmlrpc'): EncoderInterface
    {
        if (!isset(self::$encoders[$type])) {
            switch ($type) {
                case 'xmlrpc':
                default:
                    self::$encoders[$type] = new XmlRpcEncoder();
                    break;
            }
        }

        return self::$encoders[$type];
    }

    /**
     * Register a custom encoder
     *
     * @param string $type
     * @param EncoderInterface $encoder
     * @return void
     */
    public static function register(string $type, EncoderInterface $encoder): void
    {
        self::$encoders[$type] = $encoder;
    }

    /**
     * Clear cached encoders
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$encoders = [];
    }
}
