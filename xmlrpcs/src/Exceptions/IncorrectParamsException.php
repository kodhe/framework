<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs\Exceptions;

/**
 * Exception for incorrect parameters
 */
class IncorrectParamsException extends XmlRpcServerException
{
    public function __construct(string $message = 'Incorrect parameters', int $code = 3)
    {
        parent::__construct($message, $code);
    }
}
