<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller Interface
 */
interface ControllerInterface
{
    /**
     * Execute the controller action
     */
    public function execute(ServerRequestInterface $request): ResponseInterface;

    /**
     * Get the controller name
     */
    public function getName(): string;

    /**
     * Get the current action
     */
    public function getAction(): ?string;

    /**
     * Set the current action
     */
    public function setAction(string $action): self;

    /**
     * Call a controller method
     */
    public function callMethod(string $method, array $parameters = []): mixed;

    /**
     * Check if controller has a method
     */
    public function hasMethod(string $method): bool;

    /**
     * Get controller constructor parameters
     */
    public function getConstructorParameters(): array;

    /**
     * Initialize the controller
     */
    public function initialize(ServerRequestInterface $request, ResponseInterface $response): void;

    /**
     * Get request instance
     */
    public function getRequest(): ?ServerRequestInterface;

    /**
     * Get response instance
     */
    public function getResponse(): ?ResponseInterface;

    /**
     * Set request instance
     */
    public function setRequest(ServerRequestInterface $request): self;

    /**
     * Set response instance
     */
    public function setResponse(ResponseInterface $response): self;

    /**
     * Load a model
     */
    public function loadModel(string $model, ?string $alias = null): object;

    /**
     * Load a helper
     */
    public function loadHelper(string $helper): self;

    /**
     * Load a library
     */
    public function loadLibrary(string $library, ?array $params = null, ?string $alias = null): object;

    /**
     * Render a view
     */
    public function renderView(string $view, array $data = [], bool $return = false): string;

    /**
     * Get validation instance
     */
    public function getValidation(): object;

    /**
     * Validate request data
     */
    public function validate(array $rules): bool;

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array;

    /**
     * Redirect to a URL
     */
    public function redirect(string $uri, int $status = 302): ResponseInterface;

    /**
     * Return JSON response
     */
    public function json(array $data, int $status = 200): ResponseInterface;

    /**
     * Return XML response
     */
    public function xml($data, int $status = 200): ResponseInterface;

    /**
     * Download a file
     */
    public function download(string $filename, string $content, ?string $mimeType = null): ResponseInterface;
}
