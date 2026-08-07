<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

use Kodhe\Framework\Http\Contracts\ResponseInterface;

/**
 * JSON Response - HTTP response with JSON content
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class JsonResponse extends Response implements ResponseInterface
{
    /**
     * The JSON data
     *
     * @var mixed
     */
    protected $data;

    /**
     * JSON encoding options
     *
     * @var int
     */
    protected $encodingOptions;

    /**
     * Create a new JSON response instance
     *
     * @param mixed $app
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     */
    public function __construct(
        $app = null,
        $data = [],
        int $status = 200,
        array $headers = [],
        int $options = 0
    ) {
        parent::__construct($app);

        $this->data = $data;
        $this->encodingOptions = $options;

        // Set default JSON headers
        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=UTF-8',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        $this->setStatusCode($status)
            ->withHeaders($headers)
            ->setBody($this->encodeData());
    }

    /**
     * Encode the data to JSON
     *
     * @return string
     */
    protected function encodeData(): string
    {
        $json = json_encode($this->data, $this->encodingOptions);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                'JSON Encoding error: ' . json_last_error_msg()
            );
        }

        return $json;
    }

    /**
     * Get the JSON data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the JSON data
     *
     * @param mixed $data
     * @return $this
     */
    public function setData($data): self
    {
        $this->data = $data;
        $this->setBody($this->encodeData());
        return $this;
    }

    /**
     * Get the encoding options
     *
     * @return int
     */
    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    /**
     * Set the encoding options
     *
     * @param int $options
     * @return $this
     */
    public function setEncodingOptions(int $options): self
    {
        $this->encodingOptions = $options;
        $this->setBody($this->encodeData());
        return $this;
    }

    /**
     * Create a JSON response from data
     *
     * @param mixed $app
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return JsonResponse
     */
    public static function create(
        $app = null,
        $data = [],
        int $status = 200,
        array $headers = [],
        int $options = 0
    ): self {
        return new static($app, $data, $status, $headers, $options);
    }

    /**
     * Create a JSONP response
     *
     * @param mixed $app
     * @param mixed $data
     * @param string $callback
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return ResponseInterface
     */
    public static function jsonp(
        $app = null,
        $data = [],
        string $callback = 'callback',
        int $status = 200,
        array $headers = [],
        int $options = 0
    ): ResponseInterface {
        $response = new self($app, $data, $status, $headers, $options);
        
        $content = sprintf('%s(%s)', $callback, $response->getBody());
        
        return $response->setBody($content)
            ->withHeader('Content-Type', 'application/javascript; charset=UTF-8');
    }
}
