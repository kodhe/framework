<?php

declare(strict_types=1);

namespace Kodhe\Framework\Routing\Exceptions;

/**
 * MethodNotAllowedException - Exception when HTTP method is not allowed
 */
class MethodNotAllowedException extends RoutingException
{
    public static function create(string $method, string $uri): self
    {
        return new self("Method [{$method}] not allowed for URI [{$uri}]");
    }
}
