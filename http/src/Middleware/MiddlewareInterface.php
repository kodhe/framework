<?php

declare(strict_types=1);

namespace Kodhe\Http\Middleware;

use Kodhe\Http\Request;
use Kodhe\Http\Response;

interface MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(Request $request, Response $response, callable $next, array $params = []);
}
