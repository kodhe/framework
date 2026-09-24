<?php

declare(strict_types=1);

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

    /**
     * @param string          $message  Error description
     * @param int             $code     Optional SPL exception code
     * @param \Throwable|null $previous Chained previous exception
     * @param string          $url      Request URL that failed (empty when unknown)
     */
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, string $url = '')
    {
        parent::__construct($message, $code, $previous);
        $this->url = $url;
    }

    /**
     * @return string The URL whose request triggered this failure ('' when unavailable)
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
