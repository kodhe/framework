<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Contracts;

use Psr\Http\Message\ResponseInterface;

/**
 * Response Factory Interface
 */
interface ResponseFactoryInterface
{
    /**
     * Create a new response
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface;

    /**
     * Create a JSON response
     */
    public function createJsonResponse(array $data, int $code = 200, array $headers = [], int $encodingOptions = JSON_UNESCAPED_UNICODE): ResponseInterface;

    /**
     * Create a redirect response
     */
    public function createRedirectResponse(string $uri, int $status = 302): ResponseInterface;

    /**
     * Create a download response
     */
    public function createDownloadResponse(string $filename, string $content, ?string $mimeType = null): ResponseInterface;

    /**
     * Create an HTML response
     */
    public function createHtmlResponse(string $html, int $code = 200, array $headers = []): ResponseInterface;

    /**
     * Create an XML response
     */
    public function createXmlResponse($data, int $code = 200, array $headers = []): ResponseInterface;

    /**
     * Create a file response
     */
    public function createFileResponse(string $filePath, bool $download = false): ResponseInterface;

    /**
     * Create a view response
     */
    public function createViewResponse(string $view, array $data = [], int $code = 200, array $headers = []): ResponseInterface;

    /**
     * Create an error response
     */
    public function createErrorResponse(string $message, int $code = 400, array $headers = []): ResponseInterface;

    /**
     * Create a stream response
     */
    public function createStreamResponse($stream, int $code = 200, array $headers = []): ResponseInterface;
}
