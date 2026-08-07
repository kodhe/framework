<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Contracts;

use Psr\Http\Message\ResponseInterface;

/**
 * Response Interface
 * 
 * Represents an HTTP response, compatible with PSR-7
 */
interface ResponseInterface extends ResponseInterface
{
    /**
     * Get the status code
     */
    public function getStatusCode(): int;

    /**
     * Set the status code
     */
    public function setStatusCode(int $code, string $reasonPhrase = ''): self;

    /**
     * Get the reason phrase
     */
    public function getReasonPhrase(): string;

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
     * Set a header
     */
    public function setHeader(string $name, $value): self;

    /**
     * Add a header value
     */
    public function appendHeader(string $name, $value): self;

    /**
     * Remove a header
     */
    public function removeHeader(string $name): self;

    /**
     * Get the response body content
     */
    public function getBodyContent(): string;

    /**
     * Set the response body
     */
    public function setBody($body): self;

    /**
     * Set content type header
     */
    public function setContentType(string $type, string $charset = 'UTF-8'): self;

    /**
     * Get content type
     */
    public function getContentType(): ?string;

    /**
     * Set cache control headers
     */
    public function setCache(array $options): self;

    /**
     * Disable caching
     */
    public function noCache(): self;

    /**
     * Set a cookie
     */
    public function setCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, string $sameSite = 'Lax'): self;

    /**
     * Get all cookies
     */
    public function getCookies(): array;

    /**
     * Delete a cookie
     */
    public function deleteCookie(string $name, string $path = '/', string $domain = ''): self;

    /**
     * Check if response has been sent
     */
    public function isSent(): bool;

    /**
     * Mark response as sent
     */
    public function markAsSent(): void;

    /**
     * Send the response to the client
     */
    public function send(): void;

    /**
     * Send headers only
     */
    public function sendHeaders(): void;

    /**
     * Send body only
     */
    public function sendBody(): void;

    /**
     * Get response as string (headers + body)
     */
    public function __toString(): string;
}
