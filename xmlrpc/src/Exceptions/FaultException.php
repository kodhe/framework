<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc\Exceptions;

/**
 * Exception for XML-RPC fault responses
 */
class FaultException extends XmlRpcException
{
    /**
     * @var int
     */
    private $faultCode;

    /**
     * @param int             $faultCode   XML-RPC fault <value> code returned by the server
     * @param string          $faultString Human-readable fault message (becomes the exception message)
     * @param int             $code        Optional SPL exception code
     * @param \Throwable|null $previous    Chained previous exception
     */
    public function __construct(int $faultCode, string $faultString, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($faultString, $code, $previous);
        $this->faultCode = $faultCode;
    }

    /**
     * @return int The XML-RPC fault code reported by the server
     */
    public function getFaultCode(): int
    {
        return $this->faultCode;
    }

    /**
     * @return string The fault message (alias of getMessage())
     */
    public function getFaultString(): string
    {
        return $this->getMessage();
    }
}
