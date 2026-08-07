<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs\Exceptions;

/**
 * Exception for unknown method errors
 */
class UnknownMethodException extends XmlRpcServerException
{
    public function __construct(string $message = 'Unknown method', int $code = 1)
    {
        parent::__construct($message, $code);
    }
}
