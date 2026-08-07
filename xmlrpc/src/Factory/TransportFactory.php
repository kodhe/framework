<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpc\Factory;

use Kodhe\Xmlrpc\Transport\SocketTransport;
use Kodhe\Xmlrpc\Contracts\TransportInterface;

/**
 * Factory for creating transport instances
 */
class TransportFactory
{
    /**
     * @var array
     */
    private static $transports = [];

    /**
     * Get or create a transport instance
     *
     * @param string $type
     * @return TransportInterface
     */
    public static function create(string $type = 'socket'): TransportInterface
    {
        if (!isset(self::$transports[$type])) {
            switch ($type) {
                case 'socket':
                default:
                    self::$transports[$type] = new SocketTransport();
                    break;
            }
        }

        return self::$transports[$type];
    }

    /**
     * Register a custom transport
     *
     * @param string $type
     * @param TransportInterface $transport
     * @return void
     */
    public static function register(string $type, TransportInterface $transport): void
    {
        self::$transports[$type] = $transport;
    }

    /**
     * Clear cached transports
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$transports = [];
    }
}
