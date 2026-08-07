<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Controllers;

use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;

/**
 * REST Controller - Controller for RESTful APIs
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class RESTController extends BaseController
{
    /**
     * The format for the response
     *
     * @var string
     */
    protected $format = 'json';

    /**
     * Supported formats
     *
     * @var array
     */
    protected $supportedFormats = ['json', 'xml', 'html'];

    /**
     * Create a new REST controller instance
     *
     * @param mixed $app
     */
    public function __construct($app = null)
    {
        parent::__construct($app);
        
        // Detect requested format
        if ($this->request) {
            $this->format = $this->detectFormat();
        }
    }

    /**
     * Handle GET request
     *
     * @param mixed $id
     * @return ResponseInterface
     */
    public function index($id = null)
    {
        return $this->methodNotAllowed();
    }

    /**
     * Handle GET request for single resource
     *
     * @param mixed $id
     * @return ResponseInterface
     */
    public function show($id)
    {
        return $this->methodNotAllowed();
    }

    /**
     * Handle POST request
     *
     * @return ResponseInterface
     */
    public function store()
    {
        return $this->methodNotAllowed();
    }

    /**
     * Handle PUT/PATCH request
     *
     * @param mixed $id
     * @return ResponseInterface
     */
    public function update($id)
    {
        return $this->methodNotAllowed();
    }

    /**
     * Handle DELETE request
     *
     * @param mixed $id
     * @return ResponseInterface
     */
    public function destroy($id)
    {
        return $this->methodNotAllowed();
    }

    /**
     * Handle OPTIONS request
     *
     * @return ResponseInterface
     */
    public function options()
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        
        return $this->response->withStatus(204)
            ->withHeader('Allow', implode(', ', $methods))
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $methods))
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * Return a successful response
     *
     * @param mixed $data
     * @param int $status
     * @return ResponseInterface
     */
    protected function success($data, int $status = 200): ResponseInterface
    {
        return $this->respond($data, $status);
    }

    /**
     * Return a created response
     *
     * @param mixed $data
     * @param string|null $location
     * @return ResponseInterface
     */
    protected function created($data, ?string $location = null): ResponseInterface
    {
        $response = $this->respond($data, 201);
        
        if ($location) {
            $response = $response->withHeader('Location', $location);
        }

        return $response;
    }

    /**
     * Return a no content response
     *
     * @return ResponseInterface
     */
    protected function noContent(): ResponseInterface
    {
        return $this->response->withStatus(204);
    }

    /**
     * Return a not found response
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function notFound(string $message = 'Resource not found'): ResponseInterface
    {
        return $this->error($message, 404);
    }

    /**
     * Return a bad request response
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function badRequest(string $message = 'Bad request'): ResponseInterface
    {
        return $this->error($message, 400);
    }

    /**
     * Return an unauthorized response
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function unauthorized(string $message = 'Unauthorized'): ResponseInterface
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden response
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function forbidden(string $message = 'Forbidden'): ResponseInterface
    {
        return $this->error($message, 403);
    }

    /**
     * Return a method not allowed response
     *
     * @param string $message
     * @return ResponseInterface
     */
    protected function methodNotAllowed(string $message = 'Method not allowed'): ResponseInterface
    {
        return $this->error($message, 405);
    }

    /**
     * Return an error response
     *
     * @param string $message
     * @param int $status
     * @return ResponseInterface
     */
    protected function error(string $message, int $status = 400): ResponseInterface
    {
        return $this->respond([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status,
            ],
        ], $status);
    }

    /**
     * Respond with data in the appropriate format
     *
     * @param mixed $data
     * @param int $status
     * @return ResponseInterface
     */
    protected function respond($data, int $status = 200): ResponseInterface
    {
        switch ($this->format) {
            case 'json':
                return $this->json($data, $status);
            
            case 'xml':
                return $this->xml($data, $status);
            
            default:
                return $this->response->setBody((string) $data)
                    ->withStatus($status);
        }
    }

    /**
     * Detect the requested format
     *
     * @return string
     */
    protected function detectFormat(): string
    {
        if (!$this->request) {
            return 'json';
        }

        // Check URL extension
        $uri = $this->request->getUri();
        $path = $uri->getPath();
        
        if (preg_match('/\.(\w+)$/', $path, $matches)) {
            $ext = strtolower($matches[1]);
            if (in_array($ext, $this->supportedFormats)) {
                return $ext;
            }
        }

        // Check Accept header
        $accept = $this->request->getHeaderLine('Accept');
        
        if (strpos($accept, 'application/json') !== false) {
            return 'json';
        }
        
        if (strpos($accept, 'application/xml') !== false || strpos($accept, 'text/xml') !== false) {
            return 'xml';
        }

        return 'json';
    }

    /**
     * Return XML response
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return ResponseInterface
     */
    protected function xml($data, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers = array_merge([
            'Content-Type' => 'application/xml; charset=UTF-8',
        ], $headers);

        $xml = $this->arrayToXml($data);

        return $this->response->setBody($xml)
            ->withStatus($status)
            ->withHeaders($headers);
    }

    /**
     * Convert array to XML
     *
     * @param mixed $data
     * @param \SimpleXMLElement|null $xmlData
     * @param string|null $parentNode
     * @return string
     */
    protected function arrayToXml($data, &$xmlData = null, $parentNode = 'root'): string
    {
        if ($xmlData === null) {
            $xmlData = new \SimpleXMLElement("<?xml version=\"1.0\"?><{$parentNode}/>");
        }

        foreach ($data as $key => $value) {
            $key = preg_replace('/[^a-z0-9\-\_]/i', '', $key);
            
            if (is_array($value)) {
                $this->arrayToXml($value, $xmlData, $key);
            } else {
                $xmlData->addChild($key, htmlspecialchars((string) $value));
            }
        }

        return $xmlData->asXML();
    }
}
