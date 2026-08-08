<?php

declare(strict_types=0);

namespace Kodhe\Framework\Validation\Validators;

/**
 * Regex Validator
 */
class RegexValidator extends BaseValidator
{
    protected string $name = 'regex_match';
    protected string $message = 'The {field} field does not match the required format';
    
    public function validate(mixed $value): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        if ($this->parameter === null) {
            return false;
        }
        
        return (bool) preg_match((string) $this->parameter, (string) $value);
    }
}
