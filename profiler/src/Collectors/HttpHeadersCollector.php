<?php

declare(strict_types=0);

namespace Kodhe\Framework\Profiler\Collectors;

use Kodhe\Framework\Profiler\Contracts\CollectorInterface;

/**
 * HTTP Headers Collector
 * 
 * Collects HTTP header information from $_SERVER
 */
class HttpHeadersCollector implements CollectorInterface
{
    protected object $ci;
    protected ?array $headers = null;

    private array $headerKeys = [
        'HTTP_ACCEPT',
        'HTTP_USER_AGENT',
        'HTTP_CONNECTION',
        'SERVER_PORT',
        'SERVER_NAME',
        'REMOTE_ADDR',
        'SERVER_SOFTWARE',
        'HTTP_ACCEPT_LANGUAGE',
        'SCRIPT_NAME',
        'REQUEST_METHOD',
        'HTTP_HOST',
        'REMOTE_HOST',
        'CONTENT_TYPE',
        'SERVER_PROTOCOL',
        'QUERY_STRING',
        'HTTP_ACCEPT_ENCODING',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_DNT'
    ];

    public function setDependencies(object $ci): void
    {
        $this->ci = $ci;
    }

    public function collect(): array
    {
        if ($this->headers !== null) {
            return $this->headers;
        }

        $headers = [];
        foreach ($this->headerKeys as $header) {
            $headers[$header] = $_SERVER[$header] ?? '';
        }

        $this->headers = $headers;
        return $this->headers;
    }

    public function hasData(): bool
    {
        // Always has data (even if empty, we show the table)
        return true;
    }

    public function getSectionName(): string
    {
        return 'http_headers';
    }

    public function getHeaders(): array
    {
        if ($this->headers === null) {
            $this->collect();
        }
        return $this->headers;
    }

    public function getHeader(string $key): string
    {
        if ($this->headers === null) {
            $this->collect();
        }
        return $this->headers[$key] ?? '';
    }
}
