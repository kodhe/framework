<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Contracts;

use Closure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface PipelineInterface
 * 
 * Pipeline for processing requests through middleware
 */
interface PipelineInterface
{
    /**
     * Set the pipeline slices (middleware)
     */
    public function send(RequestInterface $request): self;

    /**
     * Set the destination handler
     */
    public function to(callable $destination): self;

    /**
     * Set the pipeline slices (middleware)
     */
    public function through(array $slices): self;

    /**
     * Add a slice to the pipeline
     */
    public function addSlice($slice): self;

    /**
     * Execute the pipeline
     */
    public function then(?Closure $callback = null): ResponseInterface;

    /**
     * Set the method to carry
     */
    public function via(string $method): self;

    /**
     * Get the current request
     */
    public function getRequest(): RequestInterface;

    /**
     * Set the request
     */
    public function setRequest(RequestInterface $request): self;

    /**
     * Clear the pipeline
     */
    public function clear(): self;

    /**
     * Check if pipeline has slices
     */
    public function hasSlices(): bool;

    /**
     * Get all slices
     */
    public function getSlices(): array;
}
