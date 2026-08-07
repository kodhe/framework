<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

use Kodhe\Framework\Http\Contracts\RequestInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Uri;

/**
 * Class Request
 * 
 * PSR-7 compatible Request implementation with CodeIgniter 3 compatibility
 */
class Request extends Psr7Request implements RequestInterface
{
    protected array $globals = [];
    protected ?string $ipAddress = null;
    protected bool $isAjax = false;
    protected array $segments = [];
    
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $globals = []
    ) {
        parent::__construct($method, $uri, $headers, $body, $version);
        $this->globals = $globals;
        $this->parseGlobals();
    }
    
    /**
     * Parse global arrays (_GET, _POST, _COOKIE, _SERVER)
     */
    protected function parseGlobals(): void
    {
        // Extract IP address
        $this->ipAddress = $this->extractIpAddress();
        
        // Check if AJAX request
        $this->isAjax = isset($this->globals['HTTP_X_REQUESTED_WITH']) 
            && strtolower($this->globals['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Extract URI segments
        if (isset($this->globals['REQUEST_URI'])) {
            $this->segments = explode('/', trim(parse_url($this->globals['REQUEST_URI'], PHP_URL_PATH), '/'));
            $this->segments = array_filter($this->segments, fn($s) => $s !== '');
            $this->segments = array_values($this->segments);
        }
    }
    
    /**
     * Extract IP address from server variables
     */
    protected function extractIpAddress(): ?string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($this->globals[$key]) && !empty($this->globals[$key])) {
                $ips = explode(',', $this->globals[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return null;
    }
    
    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }
    
    public function isAjax(): bool
    {
        return $this->isAjax;
    }
    
    public function isSecure(): bool
    {
        return (isset($this->globals['HTTPS']) && $this->globals['HTTPS'] === 'on')
            || (isset($this->globals['HTTP_X_FORWARDED_PROTO']) && $this->globals['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($this->globals['REQUEST_SCHEME']) && $this->globals['REQUEST_SCHEME'] === 'https');
    }
    
    public function userAgent(): ?string
    {
        return $this->globals['HTTP_USER_AGENT'] ?? null;
    }
    
    public function getValidIp(): ?string
    {
        return $this->ipAddress;
    }
    
    public function setIpAddress(?string $ip): self
    {
        $this->ipAddress = $ip;
        return $this;
    }
    
    public function getData(?string $key = null, $default = null)
    {
        $data = array_merge(
            $this->globals['_GET'] ?? [],
            $this->globals['_POST'] ?? [],
            $this->globals['_REQUEST'] ?? []
        );
        
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? $default;
    }
    
    public function getGet(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->globals['_GET'] ?? [];
        }
        
        return ($this->globals['_GET'][$key] ?? $default);
    }
    
    public function getPost(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->globals['_POST'] ?? [];
        }
        
        return ($this->globals['_POST'][$key] ?? $default);
    }
    
    public function getCookie(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->globals['_COOKIE'] ?? [];
        }
        
        return ($this->globals['_COOKIE'][$key] ?? $default);
    }
    
    public function getServer(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->globals;
        }
        
        return ($this->globals[$key] ?? $default);
    }
    
    public function isPost(): bool
    {
        return strtoupper($this->getMethod()) === 'POST';
    }
    
    public function isGet(): bool
    {
        return strtoupper($this->getMethod()) === 'GET';
    }
    
    public function isPut(): bool
    {
        return strtoupper($this->getMethod()) === 'PUT';
    }
    
    public function isDelete(): bool
    {
        return strtoupper($this->getMethod()) === 'DELETE';
    }
    
    public function isPatch(): bool
    {
        return strtoupper($this->getMethod()) === 'PATCH';
    }
    
    public function isHead(): bool
    {
        return strtoupper($this->getMethod()) === 'HEAD';
    }
    
    public function isOptions(): bool
    {
        return strtoupper($this->getMethod()) === 'OPTIONS';
    }
    
    public function wantsJson(): bool
    {
        $accept = $this->getHeaderLine('Accept');
        return strpos($accept, 'application/json') !== false;
    }
    
    public function acceptsJson(): bool
    {
        return $this->wantsJson();
    }
    
    public function getSegment(int $index, $default = '')
    {
        $index = $index - 1; // CI uses 1-based index
        return $this->segments[$index] ?? $default;
    }
    
    public function getSegments(): array
    {
        return $this->segments;
    }
    
    public function getTotalSegments(): int
    {
        return count($this->segments);
    }
    
    public function getReferrer(): ?string
    {
        return $this->globals['HTTP_REFERER'] ?? null;
    }
    
    public function getClientIp(): ?string
    {
        return $this->ipAddress;
    }
    
    public static function createFromGlobals(array $server = [], array $get = [], array $post = [], array $cookie = []): self
    {
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri = new Uri(self::getUriFromGlobals($server));
        
        $headers = self::extractHeaders($server);
        
        $globals = array_merge($server, [
            '_GET' => $get,
            '_POST' => $post,
            '_COOKIE' => $cookie,
            '_REQUEST' => array_merge($get, $post)
        ]);
        
        return new self($method, $uri, $headers, null, '1.1', $globals);
    }
    
    protected static function getUriFromGlobals(array $server): string
    {
        $scheme = isset($server['HTTPS']) && $server['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? 'localhost';
        $path = $server['REQUEST_URI'] ?? '/';
        $query = isset($server['QUERY_STRING']) && $server['QUERY_STRING'] !== '' ? '?' . $server['QUERY_STRING'] : '';
        
        return "{$scheme}://{$host}{$path}{$query}";
    }
    
    protected static function extractHeaders(array $server): array
    {
        $headers = [];
        
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = [$value];
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headerName = str_replace('_', '-', $key);
                $headers[$headerName] = [$value];
            }
        }
        
        return $headers;
    }
}
