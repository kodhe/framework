<?php

namespace Kodhe\FormValidation\Filters;

use Kodhe\FormValidation\Contracts\FilterInterface;

class XssCleanFilter implements FilterInterface
{
    public function filter($value, $params = [])
    {
        if (!is_string($value)) {
            return $value;
        }
        
        // Basic XSS cleaning - in real CI3 this would use the Security library
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
