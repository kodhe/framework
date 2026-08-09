<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\Validators;

/**
 * URL Validator
 */
class UrlValidator extends BaseValidator
{
    protected string $name = 'valid_url';
    protected string $message = 'The {field} field must contain a valid URL';
    
    public function validate(mixed $value): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $str = (string) $value;
        
        if (empty($str)) {
            return false;
        } elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches)) {
            if (empty($matches[2])) {
                return false;
            } elseif (!in_array(strtolower($matches[1]), ['http', 'https'], true)) {
                return false;
            }
            
            $str = $matches[2];
        }
        
        // Reject digit-only names
        if (ctype_digit($str)) {
            return false;
        }
        
        // Handle IPv6 addresses within square brackets
        if (preg_match('/^\[([^\]]+)\]/', $str, $matches) && !version_compare(PHP_VERSION, '7.0', '>=') && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $str = 'ipv6.host' . substr($str, strlen($matches[1]) + 2);
        }
        
        return filter_var('http://' . $str, FILTER_VALIDATE_URL) !== false;
    }
}
