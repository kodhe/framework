<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

use Kodhe\Framework\Http\Contracts\ResponseInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;

/**
 * Class Response
 * 
 * PSR-7 compatible Response implementation with CodeIgniter 3 compatibility
 */
class Response extends Psr7Response implements ResponseInterface
{
    protected array $cookies = [];
    protected ?string $redirectUri = null;
    protected bool $isRedirect = false;
    protected $bodyContent = '';
    
    public function __construct(
        int $status = 200,
        array $headers = [],
        $body = '',
        string $version = '1.1',
        ?string $reason = null
    ) {
        parent::__construct($status, $headers, $body, $version, $reason);
        $this->bodyContent = (string) $body;
    }
    
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->getHeaderLines() as $name => $value) {
            $headers[$name] = [$value];
        }
        return $headers;
    }
    
    public function hasHeader(string $name): bool
    {
        return $this->hasHeader($name);
    }
    
    public function getHeader(string $name): array
    {
        return $this->getHeader($name);
    }
    
    public function getHeaderLine(string $name): string
    {
        return $this->getHeaderLine($name);
    }
    
    public function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $sameSite = ''
    ): self {
        $this->cookies[$name] = compact('name', 'value', 'expire', 'path', 'domain', 'secure', 'httponly', 'sameSite');
        return $this;
    }
    
    public function getCookie(?string $name = null)
    {
        if ($name === null) {
            return $this->cookies;
        }
        return $this->cookies[$name] ?? null;
    }
    
    public function deleteCookie(
        string $name,
        string $path = '',
        string $domain = ''
    ): self {
        $this->setCookie($name, '', time() - 3600, $path, $domain);
        unset($this->cookies[$name]);
        return $this;
    }
    
    public function hasCookie(string $name): bool
    {
        return isset($this->cookies[$name]);
    }
    
    public function setHeader(string $name, $value): self
    {
        return $this->withHeader($name, $value);
    }
    
    public function removeHeader(string $name): self
    {
        return $this->withoutHeader($name);
    }
    
    public function appendHeader(string $name, $value): self
    {
        $existing = $this->getHeader($name);
        $existing[] = $value;
        return $this->withHeader($name, $existing);
    }
    
    public function prependHeader(string $name, $value): self
    {
        $existing = $this->getHeader($name);
        array_unshift($existing, $value);
        return $this->withHeader($name, $existing);
    }
    
    public function setContentType(string $type, ?string $charset = null): self
    {
        $contentType = $charset ? "{$type}; charset={$charset}" : $type;
        return $this->withHeader('Content-Type', $contentType);
    }
    
    public function setCache(array $options = []): self
    {
        $cacheControl = [];
        
        if (isset($options['max-age'])) {
            $cacheControl[] = 'max-age=' . $options['max-age'];
        }
        
        if (isset($options['public'])) {
            $cacheControl[] = 'public';
        } elseif (isset($options['private'])) {
            $cacheControl[] = 'private';
        }
        
        if (isset($options['no-cache'])) {
            $cacheControl[] = 'no-cache';
        }
        
        if (isset($options['no-store'])) {
            $cacheControl[] = 'no-store';
        }
        
        if (!empty($cacheControl)) {
            return $this->withHeader('Cache-Control', implode(', ', $cacheControl));
        }
        
        return $this;
    }
    
    public function noCache(): self
    {
        return $this->setCache([
            'no-cache' => true,
            'no-store' => true,
            'max-age' => 0
        ]);
    }
    
    public function download(
        string $filename,
        ?string $data = null,
        bool $setMime = true
    ): self {
        if ($data !== null) {
            $this->bodyContent = $data;
        }
        
        return $this
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Type', $setMime ? mime_content_type($filename) : 'application/octet-stream')
            ->withHeader('Content-Length', (string) strlen($this->bodyContent));
    }
    
    public function send(int $statusCode = 200): void
    {
        $this->sendHeaders();
        $this->sendBody();
    }
    
    public function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        
        http_response_code($this->getStatusCode());
        
        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }
        
        // Send cookies
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }
    }
    
    public function sendBody(): void
    {
        echo $this->getBodyAsString();
    }
    
    public function redirect(
        ?string $uri = null,
        string $method = 'auto',
        ?int $code = null
    ): self {
        if ($uri !== null) {
            $this->redirectUri = $uri;
        }
        
        $this->isRedirect = true;
        
        if ($code === null) {
            $code = $method === 'permanent' ? 301 : 302;
        }
        
        if ($this->redirectUri) {
            return $this->withStatus($code)->withHeader('Location', $this->redirectUri);
        }
        
        return $this->withStatus($code);
    }
    
    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }
    
    public function isRedirect(): bool
    {
        return $this->isRedirect || in_array($this->getStatusCode(), [301, 302, 303, 307, 308]);
    }
    
    public function setJSON($body, int $status = 200): self
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $this->bodyContent = $json;
        
        return $this
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($json));
    }
    
    public function getJSON(bool $assoc = false, int $depth = 512, int $options = 0)
    {
        return json_decode($this->getBodyAsString(), $assoc, $depth, $options);
    }
    
    public function setXML($body, int $status = 200): self
    {
        $xml = is_string($body) ? $body : xml_encode($body);
        $this->bodyContent = $xml;
        
        return $this
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/xml')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($xml));
    }
    
    public function getXML()
    {
        return simplexml_load_string($this->getBodyAsString());
    }
    
    public function setStatus(int $code, string $reason = ''): self
    {
        return $this->withStatus($code, $reason);
    }
    
    public function isOK(): bool
    {
        return $this->getStatusCode() === 200;
    }
    
    public function isClientError(): bool
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }
    
    public function isServerError(): bool
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }
    
    public function isSuccessful(): bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }
    
    public function isInformational(): bool
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }
    
    public function isRedirected(): bool
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }
    
    public function getBodyAsString(): string
    {
        return $this->bodyContent ?: (string) $this->getBody();
    }
}
