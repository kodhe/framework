<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Contracts;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * Interface ResponseInterface
 * 
 * Extended PSR-7 ResponseInterface with CodeIgniter 3 compatibility
 */
interface ResponseInterface extends PsrResponseInterface
{
    /**
     * Get all headers
     */
    public function getHeaders(): array;

    /**
     * Check if header exists
     */
    public function hasHeader(string $name): bool;

    /**
     * Get a specific header
     */
    public function getHeader(string $name): array;

    /**
     * Get header line
     */
    public function getHeaderLine(string $name): string;

    /**
     * Get status code
     */
    public function getStatusCode(): int;

    /**
     * Get reason phrase
     */
    public function getReasonPhrase(): string;

    /**
     * Get response body
     */
    public function getBody();

    /**
     * Get protocol version
     */
    public function getProtocolVersion(): string;

    /**
     * Set cookie
     */
    public function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $sameSite = ''
    ): self;

    /**
     * Get cookie
     */
    public function getCookie(?string $name = null);

    /**
     * Delete cookie
     */
    public function deleteCookie(
        string $name,
        string $path = '',
        string $domain = ''
    ): self;

    /**
     * Has cookie
     */
    public function hasCookie(string $name): bool;

    /**
     * Set header
     */
    public function setHeader(string $name, $value): self;

    /**
     * Remove header
     */
    public function removeHeader(string $name): self;

    /**
     * Append header
     */
    public function appendHeader(string $name, $value): self;

    /**
     * Prepend header
     */
    public function prependHeader(string $name, $value): self;

    /**
     * Set content type
     */
    public function setContentType(string $type, ?string $charset = null): self;

    /**
     * Set cache control
     */
    public function setCache(array $options = []): self;

    /**
     * No cache
     */
    public function noCache(): self;

    /**
     * Download file
     */
    public function download(
        string $filename,
        ?string $data = null,
        bool $setMime = true
    ): self;

    /**
     * Send response
     */
    public function send(int $statusCode = 200): void;

    /**
     * Send headers
     */
    public function sendHeaders(): void;

    /**
     * Send body
     */
    public function sendBody(): void;

    /**
     * Redirect to URL
     */
    public function redirect(
        ?string $uri = null,
        string $method = 'auto',
        ?int $code = null
    ): self;

    /**
     * Get redirect URI
     */
    public function getRedirectUri(): ?string;

    /**
     * Is redirect
     */
    public function isRedirect(): bool;

    /**
     * Set JSON response
     */
    public function setJSON($body, int $status = 200): self;

    /**
     * Get JSON response
     */
    public function getJSON(bool $assoc = false, int $depth = 512, int $options = 0);

    /**
     * Set XML response
     */
    public function setXML($body, int $status = 200): self;

    /**
     * Get XML response
     */
    public function getXML();

    /**
     * Set status
     */
    public function setStatus(int $code, string $reason = ''): self;

    /**
     * Is OK (200)
     */
    public function isOK(): bool;

    /**
     * Is client error (4xx)
     */
    public function isClientError(): bool;

    /**
     * Is server error (5xx)
     */
    public function isServerError(): bool;

    /**
     * Is successful (2xx)
     */
    public function isSuccessful(): bool;

    /**
     * Is informational (1xx)
     */
    public function isInformational(): bool;

    /**
     * Is redirect (3xx)
     */
    public function isRedirected(): bool;

    /**
     * Get body content as string
     */
    public function getBodyAsString(): string;
}
