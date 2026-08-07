<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;

/**
 * Interface MiddlewareInterface
 * 
 * PSR-15 Middleware Interface with CodeIgniter 3 compatibility
 */
interface MiddlewareInterface extends PsrMiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(RequestInterface $request, callable $handler): ResponseInterface;

    /**
     * Get middleware name
     */
    public function getName(): string;

    /**
     * Set middleware priority
     */
    public function setPriority(int $priority): self;

    /**
     * Get middleware priority
     */
    public function getPriority(): int;
}
