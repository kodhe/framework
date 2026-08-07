<?php

declare(strict_types=1);

namespace CodeIgniter\Http\Support;

class ServerBag extends ParameterBag
{
    public function getHeaders(): array
    {
        $headers = [];
        
        foreach ($this->parameters as $key => $value) {
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
}
