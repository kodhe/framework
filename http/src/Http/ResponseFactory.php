<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

use Kodhe\Framework\Http\Contracts\ResponseInterface;
use Kodhe\Framework\Http\Contracts\ResponseFactoryInterface;

/**
 * Response Factory - Create response instances
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * The application instance
     *
     * @var mixed
     */
    protected $app;

    /**
     * Default response headers
     *
     * @var array
     */
    protected $defaultHeaders = [
        'Content-Type' => 'text/html; charset=UTF-8',
    ];

    /**
     * Create a new response factory instance
     *
     * @param mixed $app
     */
    public function __construct($app = null)
    {
        $this->app = $app;
    }

    /**
     * Create a new response
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return ResponseInterface
     */
    public function createResponse(
        string $content = '',
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        $response = new Response($this->app);
        
        return $response->setBody($content)
            ->setStatusCode($status)
            ->withHeaders($this->mergeHeaders($headers));
    }

    /**
     * Create a JSON response
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return ResponseInterface
     */
    public function createJsonResponse(
        $data = [],
        int $status = 200,
        array $headers = [],
        int $options = 0
    ): ResponseInterface {
        $response = new JsonResponse($this->app, $data, $status, $headers, $options);
        
        return $response;
    }

    /**
     * Create a redirect response
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return ResponseInterface
     */
    public function createRedirectResponse(
        string $url,
        int $status = 302,
        array $headers = []
    ): ResponseInterface {
        $response = new RedirectResponse($this->app, $url, $status, $headers);
        
        return $response;
    }

    /**
     * Create an HTML response
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return ResponseInterface
     */
    public function createHtmlResponse(
        string $content = '',
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        $headers = $this->mergeHeaders(array_merge(
            ['Content-Type' => 'text/html; charset=UTF-8'],
            $headers
        ));
        
        return $this->createResponse($content, $status, $headers);
    }

    /**
     * Create a plain text response
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return ResponseInterface
     */
    public function createTextResponse(
        string $content = '',
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        $headers = $this->mergeHeaders(array_merge(
            ['Content-Type' => 'text/plain; charset=UTF-8'],
            $headers
        ));
        
        return $this->createResponse($content, $status, $headers);
    }

    /**
     * Create a file download response
     *
     * @param string $content
     * @param string $filename
     * @param string $mimeType
     * @param int $status
     * @return ResponseInterface
     */
    public function createDownloadResponse(
        string $content,
        string $filename,
        string $mimeType = 'application/octet-stream',
        int $status = 200
    ): ResponseInterface {
        $headers = $this->mergeHeaders([
            'Content-Type' => $mimeType,
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Content-Length' => strlen($content),
        ]);
        
        return $this->createResponse($content, $status, $headers);
    }

    /**
     * Create an error response
     *
     * @param string $message
     * @param int $status
     * @param array $headers
     * @return ResponseInterface
     */
    public function createErrorResponse(
        string $message = 'Error',
        int $status = 400,
        array $headers = []
    ): ResponseInterface {
        return $this->createResponse($message, $status, $headers);
    }

    /**
     * Create a 404 Not Found response
     *
     * @param string $message
     * @param array $headers
     * @return ResponseInterface
     */
    public function createNotFoundResponse(
        string $message = 'Not Found',
        array $headers = []
    ): ResponseInterface {
        return $this->createErrorResponse($message, 404, $headers);
    }

    /**
     * Create a 500 Internal Server Error response
     *
     * @param string $message
     * @param array $headers
     * @return ResponseInterface
     */
    public function createInternalServerErrorResponse(
        string $message = 'Internal Server Error',
        array $headers = []
    ): ResponseInterface {
        return $this->createErrorResponse($message, 500, $headers);
    }

    /**
     * Merge default headers with provided headers
     *
     * @param array $headers
     * @return array
     */
    protected function mergeHeaders(array $headers): array
    {
        return array_merge($this->defaultHeaders, $headers);
    }

    /**
     * Set default headers
     *
     * @param array $headers
     * @return $this
     */
    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;
        return $this;
    }

    /**
     * Get default headers
     *
     * @return array
     */
    public function getDefaultHeaders(): array
    {
        return $this->defaultHeaders;
    }
}
