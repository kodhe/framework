<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpc\Contracts;

/**
 * Interface for XML-RPC decoder implementations
 */
interface DecoderInterface
{
    /**
     * Decode XML-RPC response to PHP value
     *
     * @param string $xml
     * @return mixed
     */
    public function decode(string $xml);
}
