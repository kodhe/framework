<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpc\Exceptions;

/**
 * Exception for XML-RPC fault responses
 */
class FaultException extends XmlRpcException
{
    /**
     * @var int
     */
    private $faultCode;

    public function __construct(int $faultCode, string $faultString, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($faultString, $code, $previous);
        $this->faultCode = $faultCode;
    }

    public function getFaultCode(): int
    {
        return $this->faultCode;
    }

    public function getFaultString(): string
    {
        return $this->getMessage();
    }
}
