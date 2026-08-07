<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Request Interface
 * 
 * Represents an HTTP request, compatible with PSR-7
 */
interface RequestInterface extends ServerRequestInterface
{
    /**
     * Get the request method (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string;

    /**
     * Set the request method
     */
    public function setMethod(string $method): self;

    /**
     * Get the request URI
     */
    public function getUri(): \Psr\Http\Message\UriInterface;

    /**
     * Get all headers
     */
    public function getHeaders(): array;

    /**
     * Get a specific header value
     */
    public function getHeader(string $name): array;

    /**
     * Get a single header value as string
     */
    public function getHeaderLine(string $name): string;

    /**
     * Check if header exists
     */
    public function hasHeader(string $name): bool;

    /**
     * Get all query parameters ($_GET)
     */
    public function getQueryParams(): array;

    /**
     * Get a specific query parameter
     */
    public function getQueryParam(string $key, $default = null);

    /**
     * Get all parsed body parameters ($_POST)
     */
    public function getParsedBody(): array;

    /**
     * Get a specific post parameter
     */
    public function getPostField(string $key, $default = null);

    /**
     * Get all cookies
     */
    public function getCookieParams(): array;

    /**
     * Get a specific cookie value
     */
    public function getCookie(string $key, $default = null);

    /**
     * Get uploaded files
     */
    public function getUploadedFiles(): array;

    /**
     * Get a specific uploaded file
     */
    public function getFile(string $key): ?UploadedFileInterface;

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool;

    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecure(): bool;

    /**
     * Get client IP address
     */
    public function getClientIp(): ?string;

    /**
     * Get user agent
     */
    public function getUserAgent(): ?string;

    /**
     * Get the request content type
     */
    public function getContentType(): ?string;

    /**
     * Check if request expects JSON response
     */
    public function wantsJson(): bool;

    /**
     * Get session data
     */
    public function getSessionData(string $key, $default = null);

    /**
     * Get old input (for form validation)
     */
    public function getOldInput(string $key = null, $default = null);

    /**
     * Get all input data (query + post)
     */
    public function getAllInput(): array;

    /**
     * Get only specified keys from input
     */
    public function only(array $keys): array;

    /**
     * Get input except specified keys
     */
    public function except(array $keys): array;

    /**
     * Check if input key exists
     */
    public function hasInput(string $key): bool;

    /**
     * Validate input against rules
     */
    public function validate(array $rules): bool;

    /**
     * Get validation errors
     */
    public function validationErrors(): array;
}
