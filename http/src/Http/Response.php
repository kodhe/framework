<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use CodeIgniter\Http\Contracts\ResponseInterface as CiResponseInterface;

/**
 * HTTP Response Class
 */
class Response implements ResponseInterface, CiResponseInterface
{
    protected int $statusCode = 200;
    protected string $reasonPhrase = 'OK';
    protected string $protocolVersion = '1.1';
    protected array $headers = [];
    protected StreamInterface $body;
    protected bool $sent = false;
    protected array $cookies = [];

    protected static array $statusPhrases = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    public function __construct($body = null)
    {
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
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : (self::$statusPhrases[$code] ?? '');
        return $new;
    }

    public function setStatusCode(int $code, string $reasonPhrase = ''): self
    {
        return $this->withStatus($code, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
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
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $lowerName = strtolower($name);
        return $this->headers[$lowerName] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): self
    {
        $new = clone $this;
        $lowerName = strtolower($name);
        $new->headers[$lowerName] = is_array($value) ? $value : [$value];
        return $new;
    }

    public function setHeader(string $name, $value): self
    {
        return $this->withHeader($name, $value);
    }

    public function withAddedHeader(string $name, $value): self
    {
        $new = clone $this;
        $lowerName = strtolower($name);
        
        if (!isset($new->headers[$lowerName])) {
            $new->headers[$lowerName] = [];
        }
        
        $values = is_array($value) ? $value : [$value];
        $new->headers[$lowerName] = array_merge($new->headers[$lowerName], $values);
        
        return $new;
    }

    public function appendHeader(string $name, $value): self
    {
        return $this->withAddedHeader($name, $value);
    }

    public function withoutHeader(string $name): self
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);
        return $new;
    }

    public function removeHeader(string $name): self
    {
        return $this->withoutHeader($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function getBodyContent(): string
    {
        $this->body->rewind();
        return $this->body->getContents();
    }

    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public function setBody($body): self
    {
        if (is_string($body)) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $body);
            rewind($stream);
            return $this->withBody(new \GuzzleHttp\Psr7\Stream($stream));
        }
        
        return $this->withBody($body);
    }

    public function setContentType(string $type, string $charset = 'UTF-8'): self
    {
        return $this->setHeader('Content-Type', $type . '; charset=' . $charset);
    }

    public function getContentType(): ?string
    {
        return $this->getHeaderLine('Content-Type') ?: null;
    }

    public function setCache(array $options): self
    {
        $cacheControl = [];
        
        if (isset($options['max-age'])) {
            $cacheControl[] = 'max-age=' . $options['max-age'];
        }
        
        if (isset($options['public'])) {
            $cacheControl[] = 'public';
        }
        
        if (isset($options['private'])) {
            $cacheControl[] = 'private';
        }
        
        if (isset($options['no-cache'])) {
            $cacheControl[] = 'no-cache';
        }
        
        if (isset($options['no-store'])) {
            $cacheControl[] = 'no-store';
        }
        
        if (!empty($cacheControl)) {
            $this->setHeader('Cache-Control', implode(', ', $cacheControl));
        }
        
        if (isset($options['expires'])) {
            $this->setHeader('Expires', $options['expires']);
        }
        
        if (isset($options['etag'])) {
            $this->setHeader('ETag', $options['etag']);
        }
        
        if (isset($options['last-modified'])) {
            $this->setHeader('Last-Modified', $options['last-modified']);
        }
        
        return $this;
    }

    public function noCache(): self
    {
        return $this->setCache([
            'no-cache' => true,
            'no-store' => true,
            'max-age' => 0,
        ]);
    }

    public function setCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, string $sameSite = 'Lax'): self
    {
        $this->cookies[$name] = compact('name', 'value', 'expire', 'path', 'domain', 'secure', 'httpOnly', 'sameSite');
        return $this;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function deleteCookie(string $name, string $path = '/', string $domain = ''): self
    {
        return $this->setCookie($name, '', time() - 3600, $path, $domain);
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function markAsSent(): void
    {
        $this->sent = true;
    }

    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        $this->sendHeaders();
        $this->sendBody();
        $this->markAsSent();
    }

    public function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false);
            }
        }

        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httpOnly']
            );
        }
    }

    public function sendBody(): void
    {
        echo $this->getBodyContent();
    }

    public function __toString(): string
    {
        $output = "HTTP/{$this->protocolVersion} {$this->statusCode} {$this->reasonPhrase}\r\n";
        
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $output .= ucfirst($name) . ': ' . $value . "\r\n";
            }
        }
        
        $output .= "\r\n" . $this->getBodyContent();
        
        return $output;
    }

    public function __clone()
    {
        // Body is not cloned to maintain stream reference
    }
}
