<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

use Kodhe\Framework\Http\Contracts\ResponseInterface;

/**
 * Redirect Response - HTTP redirect response
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class RedirectResponse extends Response implements ResponseInterface
{
    /**
     * The target URL
     *
     * @var string
     */
    protected $targetUrl;

    /**
     * Create a new redirect response instance
     *
     * @param mixed $app
     * @param string $url
     * @param int $status
     * @param array $headers
     */
    public function __construct(
        $app = null,
        string $url = '',
        int $status = 302,
        array $headers = []
    ) {
        parent::__construct($app);

        $this->targetUrl = $url;

        // Set redirect headers
        $defaultHeaders = [
            'Location' => $url,
            'Content-Type' => 'text/html; charset=UTF-8',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        // Generate redirect HTML body
        $body = $this->generateRedirectBody($url);

        $this->setStatusCode($status)
            ->withHeaders($headers)
            ->setBody($body);
    }

    /**
     * Generate the HTML body for redirect
     *
     * @param string $url
     * @return string
     */
    protected function generateRedirectBody(string $url): string
    {
        return sprintf(
            '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=%s"/></head>' .
            '<body><p>Redirecting to <a href="%s">%s</a>...</p></body></html>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Get the target URL
     *
     * @return string
     */
    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    /**
     * Set the target URL
     *
     * @param string $url
     * @return $this
     */
    public function setTargetUrl(string $url): self
    {
        $this->targetUrl = $url;
        $this->withHeader('Location', $url);
        $this->setBody($this->generateRedirectBody($url));
        return $this;
    }

    /**
     * Create a redirect response
     *
     * @param mixed $app
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return RedirectResponse
     */
    public static function create(
        $app = null,
        string $url = '',
        int $status = 302,
        array $headers = []
    ): self {
        return new static($app, $url, $status, $headers);
    }

    /**
     * Create a permanent redirect (301)
     *
     * @param mixed $app
     * @param string $url
     * @param array $headers
     * @return RedirectResponse
     */
    public static function permanent(
        $app = null,
        string $url = '',
        array $headers = []
    ): self {
        return new static($app, $url, 301, $headers);
    }

    /**
     * Create a temporary redirect (302)
     *
     * @param mixed $app
     * @param string $url
     * @param array $headers
     * @return RedirectResponse
     */
    public static function temporary(
        $app = null,
        string $url = '',
        array $headers = []
    ): self {
        return new static($app, $url, 302, $headers);
    }

    /**
     * Create a "See Other" redirect (303)
     *
     * @param mixed $app
     * @param string $url
     * @param array $headers
     * @return RedirectResponse
     */
    public static function seeOther(
        $app = null,
        string $url = '',
        array $headers = []
    ): self {
        return new static($app, $url, 303, $headers);
    }

    /**
     * Check if this is a redirect response
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        $status = $this->getStatusCode();
        return $status >= 300 && $status < 400 && $this->hasHeader('Location');
    }

    /**
     * Get the redirect status code
     *
     * @return int
     */
    public function getRedirectStatusCode(): int
    {
        return $this->getStatusCode();
    }
}
