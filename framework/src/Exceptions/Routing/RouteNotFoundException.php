<?php

declare(strict_types=1);

namespace Kodhe\Framework\Exceptions\Routing;

/**
 * RouteNotFoundException - Exception when route is not found
 */
class RouteNotFoundException extends RoutingException
{
    public static function notFound(string $uri): self
    {
        return new self("Route [{$uri}] not found");
    }

    public static function named(string $name): self
    {
        return new self("Named route [{$name}] not found");
    }
}
