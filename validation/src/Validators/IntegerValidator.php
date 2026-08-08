<?php

declare(strict_types=1);

namespace Kodhe\Validation\Validators;

/**
 * Integer Validator
 */
class IntegerValidator extends BaseValidator
{
    protected string $name = 'integer';
    protected string $message = 'The {field} field must contain an integer';
    
    public function validate(mixed $value): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        return (bool) preg_match('/^[\-+]?[0-9]+$/', (string) $value);
    }
}
