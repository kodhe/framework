<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\Validators;

/**
 * Numeric Validator
 */
class NumericValidator extends BaseValidator
{
    protected string $name = 'numeric';
    protected string $message = 'The {field} field must contain only numbers';
    
    public function validate(mixed $value): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', (string) $value);
    }
}
