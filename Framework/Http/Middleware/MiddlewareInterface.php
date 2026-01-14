<?php namespace Kodhe\Framework\Http\Middleware;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;


interface MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(Request $request, Response $response, callable $next, array $params = []);
}