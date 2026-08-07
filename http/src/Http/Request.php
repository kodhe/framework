<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use CodeIgniter\Http\Contracts\RequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use CodeIgniter\Http\Support\HeaderBag;
use CodeIgniter\Http\Support\ParameterBag;
use CodeIgniter\Http\Support\CookieBag;
use CodeIgniter\Http\Support\ServerBag;

/**
 * HTTP Request Class
 * 
 * Represents an HTTP server request.
 */
class Request implements RequestInterface, ServerRequestInterface
{
    protected string $method = 'GET';
    protected UriInterface $uri;
    protected HeaderBag $headers;
    protected ParameterBag $queryParams;
    protected ParameterBag $parsedBody;
    protected CookieBag $cookies;
    protected ServerBag $serverParams;
    protected array $uploadedFiles = [];
    protected StreamInterface $body;
    protected string $protocolVersion = '1.1';
    protected bool $ajax = false;
    protected bool $secure = false;
    protected ?string $clientIp = null;
    protected ?string $userAgent = null;
    protected array $attributes = [];
    protected array $validationErrors = [];
    protected bool $validated = false;

    public function __construct(
        array $get = [],
        array $post = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $body = null
    ) {
        $this->queryParams = new ParameterBag($get);
        $this->parsedBody = new ParameterBag($post);
        $this->cookies = new CookieBag($cookies);
        $this->serverParams = new ServerBag($server);
        $this->headers = new HeaderBag($this->extractHeaders($server));
        
        if ($body === null) {
            $body = fopen('php://temp', 'r+');
        }
        
        if (is_string($body)) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $body);
            rewind($stream);
            $this->body = new \GuzzleHttp\Psr7\Stream($stream);
        } else {
            $this->body = $body;
        }

        $this->uri = $this->createUriFromServer($server);
        $this->method = $this->extractMethod($server, $post);
        $this->ajax = $this->extractAjax($server);
        $this->secure = $this->extractSecure($server);
        $this->clientIp = $this->extractClientIp($server);
        $this->userAgent = $server['HTTP_USER_AGENT'] ?? null;
    }

    protected function extractHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headerName = str_replace('_', '-', $key);
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    protected function createUriFromServer(array $server): UriInterface
    {
        $scheme = isset($server['HTTPS']) && $server['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? 'localhost';
        $port = null;
        
        if (strpos($host, ':') !== false) {
            [$host, $port] = explode(':', $host);
            $port = (int) $port;
        }
        
        $path = $server['REQUEST_URI'] ?? '/';
        $query = $server['QUERY_STRING'] ?? '';
        
        return new Uri($scheme, $host, $port, $path, $query);
    }

    protected function extractMethod(array $server, array $post): string
    {
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        
        if ($method === 'POST' && isset($post['_method'])) {
            $method = strtoupper($post['_method']);
        }
        
        if (isset($server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = strtoupper($server['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }
        
        return $method;
    }

    protected function extractAjax(array $server): bool
    {
        return isset($server['HTTP_X_REQUESTED_WITH']) && 
               $server['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    protected function extractSecure(array $server): bool
    {
        return (isset($server['HTTPS']) && $server['HTTPS'] !== 'off') ||
               (isset($server['HTTP_X_FORWARDED_PROTO']) && $server['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (isset($server['REQUEST_SCHEME']) && $server['REQUEST_SCHEME'] === 'https');
    }

    protected function extractClientIp(array $server): ?string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (!empty($server[$key])) {
                $ips = explode(',', $server[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $server['REMOTE_ADDR'] ?? null;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): self
    {
        $new = clone $this;
        $new->uri = $uri;
        
        if (!$preserveHost || !$this->hasHeader('Host')) {
            $host = $uri->getHost();
            if ($host !== '') {
                $port = $uri->getPort();
                if ($port !== null) {
                    $host .= ':' . $port;
                }
                $new->headers->set('Host', $host);
            }
        }
        
        return $new;
    }

    public function getRequestTarget(): string
    {
        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        
        $query = $this->uri->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }
        
        return $target;
    }

    public function withRequestTarget(string $requestTarget): self
    {
        $new = clone $this;
        // Parse and update URI based on request target
        return $new;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): self
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers->all();
    }

    public function hasHeader(string $name): bool
    {
        return $this->headers->has($name);
    }

    public function getHeader(string $name): array
    {
        return $this->headers->get($name);
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->headers->set($name, $value);
        return $new;
    }

    public function withAddedHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->headers->add($name, $value);
        return $new;
    }

    public function withoutHeader(string $name): self
    {
        $new = clone $this;
        $new->headers->remove($name);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams->all();
    }

    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->queryParams = new ParameterBag($query);
        return $new;
    }

    public function getQueryParam(string $key, $default = null)
    {
        return $this->queryParams->get($key, $default);
    }

    public function getParsedBody(): array
    {
        return $this->parsedBody->all();
    }

    public function withParsedBody($data): self
    {
        $new = clone $this;
        $new->parsedBody = is_array($data) ? new ParameterBag($data) : new ParameterBag([]);
        return $new;
    }

    public function getPostField(string $key, $default = null)
    {
        return $this->parsedBody->get($key, $default);
    }

    public function getCookieParams(): array
    {
        return $this->cookies->all();
    }

    public function withCookieParams(array $cookies): self
    {
        $new = clone $this;
        $new->cookies = new CookieBag($cookies);
        return $new;
    }

    public function getCookie(string $key, $default = null)
    {
        return $this->cookies->get($key, $default);
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    public function getFile(string $key): ?UploadedFileInterface
    {
        return $this->uploadedFiles[$key] ?? null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute(string $name): self
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }

    public function isAjax(): bool
    {
        return $this->ajax;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getContentType(): ?string
    {
        return $this->getHeaderLine('Content-Type') ?: null;
    }

    public function wantsJson(): bool
    {
        $accept = $this->getHeaderLine('Accept');
        return strpos($accept, 'application/json') !== false ||
               strpos($accept, '*/*') !== false;
    }

    public function getSessionData(string $key, $default = null)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return $default;
    }

    public function getOldInput(string $key = null, $default = null)
    {
        $oldInput = $_SESSION['_old_input'] ?? [];
        
        if ($key === null) {
            return $oldInput;
        }
        
        return $oldInput[$key] ?? $default;
    }

    public function getAllInput(): array
    {
        return array_merge($this->queryParams->all(), $this->parsedBody->all());
    }

    public function only(array $keys): array
    {
        $input = $this->getAllInput();
        return array_intersect_key($input, array_flip($keys));
    }

    public function except(array $keys): array
    {
        $input = $this->getAllInput();
        return array_diff_key($input, array_flip($keys));
    }

    public function hasInput(string $key): bool
    {
        return $this->queryParams->has($key) || $this->parsedBody->has($key);
    }

    public function validate(array $rules): bool
    {
        // Simple validation implementation
        // In production, integrate with CI3 validation library
        $this->validated = true;
        $this->validationErrors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $value = $this->getPostField($field) ?? $this->getQueryParam($field);
            
            if (is_string($ruleSet)) {
                $ruleSet = explode('|', $ruleSet);
            }
            
            foreach ($ruleSet as $rule) {
                if (!$this->applyRule($field, $value, $rule)) {
                    $this->validationErrors[$field][] = "Validation failed for {$field}";
                }
            }
        }
        
        return empty($this->validationErrors);
    }

    protected function applyRule(string $field, $value, string $rule): bool
    {
        // Simplified rule application
        switch ($rule) {
            case 'required':
                return !empty($value);
            default:
                return true;
        }
    }

    public function validationErrors(): array
    {
        return $this->validationErrors;
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->queryParams = clone $this->queryParams;
        $this->parsedBody = clone $this->parsedBody;
        $this->cookies = clone $this->cookies;
        $this->serverParams = clone $this->serverParams;
    }
}
