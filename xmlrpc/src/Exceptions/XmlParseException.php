<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpc\Exceptions;

/**
 * Exception for XML parsing errors
 */
class XmlParseException extends XmlRpcException
{
    /**
     * @var int
     */
    private $errorCode;

    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, int $errorCode = 0)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
