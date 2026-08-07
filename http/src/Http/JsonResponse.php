<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

/**
 * Class JsonResponse
 * 
 * JSON response with CodeIgniter 3 compatibility
 */
class JsonResponse extends Response
{
    public function __construct(
        $data = null,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = JSON_UNESCAPED_UNICODE
    ) {
        $json = json_encode($data, $encodingOptions);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('JSON encoding error: ' . json_last_error_msg());
        }
        
        $headers['Content-Type'] = 'application/json';
        
        parent::__construct($status, $headers, $json);
    }
    
    public function getData()
    {
        return json_decode($this->getBodyAsString(), true);
    }
}
