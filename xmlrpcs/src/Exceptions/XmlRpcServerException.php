<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\Exceptions;

/**
 * Base exception for XML-RPC Server
 */
class XmlRpcServerException extends \Exception
{
    protected int $faultCode;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->faultCode = $code;
        parent::__construct($message, $code, $previous);
    }

    public function getFaultCode(): int
    {
        return $this->faultCode;
    }
}
