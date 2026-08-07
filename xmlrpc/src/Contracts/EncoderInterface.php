<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpc\Contracts;

/**
 * Interface for XML-RPC encoder implementations
 */
interface EncoderInterface
{
    /**
     * Encode a value to XML-RPC format
     *
     * @param mixed $value
     * @return string
     */
    public function encode($value): string;
}
