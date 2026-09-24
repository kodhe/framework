<?php

declare(strict_types=1);

namespace Kodhe\Framework\Exceptions\Routing;

/**
 * InvalidRouteException - Exception for invalid route configuration
 */
class InvalidRouteException extends RoutingException
{
    public static function invalidAction($action): self
    {
        $type = is_object($action) ? get_class($action) : gettype($action);
        return new self("Invalid route action: {$type}");
    }

    public static function invalidUri(string $uri): self
    {
        return new self("Invalid URI pattern: {$uri}");
    }
}
