<?php

declare(strict_types=1);

namespace Kodhe\Framework\Routing\Exceptions;

/**
 * RouteRegistrationException - Exception for route registration errors
 */
class RouteRegistrationException extends RoutingException
{
    public static function duplicate(string $method, string $uri): self
    {
        return new self("Duplicate route: [{$method}] {$uri}");
    }

    public static function invalidCallback(): self
    {
        return new self("Invalid route group callback. Expected callable.");
    }
}
