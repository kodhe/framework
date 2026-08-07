<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Contracts;

use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Interface RequestInterface
 * 
 * Extended PSR-7 RequestInterface with CodeIgniter 3 compatibility
 */
interface RequestInterface extends PsrRequestInterface
{
    /**
     * Get the request URI
     */
    public function getUri(): UriInterface;

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
     * Get request method
     */
    public function getMethod(): string;

    /**
     * Get request body
     */
    public function getBody();

    /**
     * Get protocol version
     */
    public function getProtocolVersion(): string;

    /**
     * Get IP address
     */
    public function ipAddress(): ?string;

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool;

    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecure(): bool;

    /**
     * Get user agent
     */
    public function userAgent(): ?string;

    /**
     * Get valid IP address
     */
    public function getValidIp(): ?string;

    /**
     * Set IP address
     */
    public function setIpAddress(?string $ip): self;

    /**
     * Get request data (GET, POST, etc.)
     */
    public function getData(?string $key = null, $default = null);

    /**
     * Get GET data
     */
    public function getGet(?string $key = null, $default = null);

    /**
     * Get POST data
     */
    public function getPost(?string $key = null, $default = null);

    /**
     * Get cookie data
     */
    public function getCookie(?string $key = null, $default = null);

    /**
     * Get server data
     */
    public function getServer(?string $key = null, $default = null);

    /**
     * Check if method is POST
     */
    public function isPost(): bool;

    /**
     * Check if method is GET
     */
    public function isGet(): bool;

    /**
     * Check if method is PUT
     */
    public function isPut(): bool;

    /**
     * Check if method is DELETE
     */
    public function isDelete(): bool;

    /**
     * Check if method is PATCH
     */
    public function isPatch(): bool;

    /**
     * Check if method is HEAD
     */
    public function isHead(): bool;

    /**
     * Check if method is OPTIONS
     */
    public function isOptions(): bool;

    /**
     * Check if request expects JSON
     */
    public function wantsJson(): bool;

    /**
     * Check if request accepts JSON
     */
    public function acceptsJson(): bool;

    /**
     * Get segment from URI
     */
    public function getSegment(int $index, $default = '');

    /**
     * Get all segments
     */
    public function getSegments(): array;

    /**
     * Get total segments
     */
    public function getTotalSegments(): int;

    /**
     * Get referrer URL
     */
    public function getReferrer(): ?string;

    /**
     * Get client IP with proxy support
     */
    public function getClientIp(): ?string;
}
