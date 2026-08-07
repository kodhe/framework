<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Pipeline Interface
 */
interface PipelineInterface
{
    /**
     * Set the request to be processed
     */
    public function setRequest(ServerRequestInterface $request): self;

    /**
     * Get the current request
     */
    public function getRequest(): ?ServerRequestInterface;

    /**
     * Add middleware to the pipeline
     */
    public function addMiddleware($middleware): self;

    /**
     * Add multiple middlewares to the pipeline
     */
    public function addMiddlewares(array $middlewares): self;

    /**
     * Add middleware by alias
     */
    public function addMiddlewareByAlias(string $alias): self;

    /**
     * Add middleware group by name
     */
    public function addMiddlewareGroup(string $groupName): self;

    /**
     * Insert middleware at specific position
     */
    public function insertMiddlewareAt(int $position, $middleware): self;

    /**
     * Remove middleware from pipeline
     */
    public function removeMiddleware($middleware): self;

    /**
     * Clear all middlewares
     */
    public function clearMiddlewares(): self;

    /**
     * Set the final handler (destination)
     */
    public function setHandler(callable $handler): self;

    /**
     * Get the final handler
     */
    public function getHandler(): ?callable;

    /**
     * Process the pipeline
     */
    public function process(): ResponseInterface;

    /**
     * Send the request through the pipeline and return response
     */
    public function send(ServerRequestInterface $request, callable $handler): ResponseInterface;

    /**
     * Get all middlewares in the pipeline
     */
    public function getMiddlewares(): array;

    /**
     * Check if pipeline has middlewares
     */
    public function hasMiddlewares(): bool;

    /**
     * Get middleware count
     */
    public function getMiddlewareCount(): int;

    /**
     * Set pipeline priority
     */
    public function setPriority(int $priority): self;

    /**
     * Get pipeline priority
     */
    public function getPriority(): int;
}
