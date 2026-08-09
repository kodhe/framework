<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\Exceptions;

/**
 * Exception for XML parsing errors
 */
class XmlParseException extends XmlRpcServerException
{
    public function __construct(string $message = 'XML parse error', int $code = 100)
    {
        parent::__construct($message, $code);
    }
}
