<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpc\Exceptions;

/**
 * Exception for transport errors
 */
class TransportException extends XmlRpcException
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, string $url = '')
    {
        parent::__construct($message, $code, $previous);
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
