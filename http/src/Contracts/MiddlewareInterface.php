<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware Interface
 */
interface MiddlewareInterface
{
    /**
     * Process an incoming server request and produce a response.
     */
    public function process(ServerRequestInterface $request, callable $handler): ResponseInterface;

    /**
     * Get middleware priority (lower number = higher priority)
     */
    public function getPriority(): int;

    /**
     * Get middleware name
     */
    public function getName(): string;
}
