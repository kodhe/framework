<?php

declare(strict_types=0);

namespace Kodhe\Framework\Validation\Validators;

/**
 * Email Validator
 */
class EmailValidator extends BaseValidator
{
    protected string $name = 'valid_email';
    protected string $message = 'The {field} field must contain a valid email address';
    
    public function validate(mixed $value): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $str = (string) $value;
        
        // Handle IDN domains
        if (function_exists('idn_to_ascii') && preg_match('#\A([^@]+)@(.+)\z#', $str, $matches)) {
            $domain = defined('INTL_IDNA_VARIANT_UTS46')
                ? idn_to_ascii($matches[2], 0, INTL_IDNA_VARIANT_UTS46)
                : idn_to_ascii($matches[2]);
            
            if ($domain !== false) {
                $str = $matches[1] . '@' . $domain;
            }
        }
        
        return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
    }
}
