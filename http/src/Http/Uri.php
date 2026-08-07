<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Http;

use Psr\Http\Message\UriInterface;

/**
 * URI Class
 * 
 * Represents a URI (Uniform Resource Identifier).
 */
class Uri implements UriInterface
{
    protected string $scheme = '';
    protected string $userInfo = '';
    protected string $host = '';
    protected ?int $port = null;
    protected string $path = '';
    protected string $query = '';
    protected string $fragment = '';

    public function __construct(
        string $scheme = '',
        string $host = '',
        ?int $port = null,
        string $path = '/',
        string $query = ''
    ) {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function withScheme(string $scheme): self
    {
        $new = clone $this;
        $new->scheme = $scheme;
        return $new;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function withUserInfo(string $user, ?string $password = null): self
    {
        $new = clone $this;
        $new->userInfo = $user . ($password !== null ? ':' . $password : '');
        return $new;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function withHost(string $host): self
    {
        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function withPort(?int $port): self
    {
        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function withPath(string $path): self
    {
        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function withQuery(string $query): self
    {
        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withFragment(string $fragment): self
    {
        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        if ($this->host !== '') {
            $uri .= '//';

            if ($this->userInfo !== '') {
                $uri .= $this->userInfo . '@';
            }

            $uri .= $this->host;

            if ($this->port !== null) {
                $uri .= ':' . $this->port;
            }
        }

        if ($this->path !== '') {
            if ($this->host !== '' && strpos($this->path, '/') !== 0) {
                $uri .= '/';
            }
            $uri .= $this->path;
        }

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Get the base URL
     */
    public function getBaseUrl(): string
    {
        $baseUrl = $this->scheme . '://' . $this->host;
        
        if ($this->port !== null && 
            !(($this->scheme === 'http' && $this->port === 80) || 
              ($this->scheme === 'https' && $this->port === 443))) {
            $baseUrl .= ':' . $this->port;
        }
        
        return $baseUrl;
    }

    /**
     * Get the full URL
     */
    public function getFullUrl(): string
    {
        return (string) $this;
    }

    /**
     * Check if URI is absolute
     */
    public function isAbsolute(): bool
    {
        return $this->scheme !== '' && $this->host !== '';
    }

    /**
     * Check if URI is relative
     */
    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    /**
     * Get URI segments as array
     */
    public function getSegments(): array
    {
        return array_values(array_filter(explode('/', $this->path)));
    }

    /**
     * Get total segment count
     */
    public function getTotalSegments(): int
    {
        return count($this->getSegments());
    }

    /**
     * Get a specific segment
     */
    public function getSegment(int $index, string $default = ''): string
    {
        $segments = $this->getSegments();
        return $segments[$index - 1] ?? $default;
    }
}
