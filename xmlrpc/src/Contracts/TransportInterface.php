<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpc\Contracts;

/**
 * Interface for XML-RPC transport implementations
 */
interface TransportInterface
{
    /**
     * Send XML-RPC request and return response
     *
     * @param string $payload
     * @param string $url
     * @param int $port
     * @param int $timeout
     * @return array
     */
    public function send(string $payload, string $url, int $port = 80, int $timeout = 5): array;
}
