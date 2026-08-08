<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc\Factory;

use Kodhe\Framework\Xmlrpc\Decoder\XmlRpcDecoder;
use Kodhe\Framework\Xmlrpc\Contracts\DecoderInterface;

/**
 * Factory for creating decoder instances
 */
class DecoderFactory
{
    /**
     * @var array
     */
    private static $decoders = [];

    /**
     * Get or create a decoder instance
     *
     * @param string $type
     * @return DecoderInterface
     */
    public static function create(string $type = 'xmlrpc'): DecoderInterface
    {
        if (!isset(self::$decoders[$type])) {
            switch ($type) {
                case 'xmlrpc':
                default:
                    self::$decoders[$type] = new XmlRpcDecoder();
                    break;
            }
        }

        return self::$decoders[$type];
    }

    /**
     * Register a custom decoder
     *
     * @param string $type
     * @param DecoderInterface $decoder
     * @return void
     */
    public static function register(string $type, DecoderInterface $decoder): void
    {
        self::$decoders[$type] = $decoder;
    }

    /**
     * Clear cached decoders
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$decoders = [];
    }
}
