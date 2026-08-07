<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs\Exceptions;

/**
 * Exception for invalid return data
 */
class InvalidReturnException extends XmlRpcServerException
{
    public function __construct(string $message = 'Invalid return data', int $code = 2)
    {
        parent::__construct($message, $code);
    }
}
