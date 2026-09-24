<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc\Exceptions;

/**
 * Exception for XML parsing errors
 */
class XmlParseException extends XmlRpcException
{
    /**
     * @var int
     */
    private $errorCode;

    /**
     * @param string          $message   Error description
     * @param int             $code      Optional SPL exception code
     * @param \Throwable|null $previous  Chained previous exception
     * @param int             $errorCode libxml/XML parse error code
     */
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, int $errorCode = 0)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    /**
     * @return int Underlying XML parse error code (0 when unspecified)
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
