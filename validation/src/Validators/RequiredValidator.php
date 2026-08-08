<?php

declare(strict_types=1);

namespace Kodhe\Validation\Validators;

/**
 * Required Validator
 */
class RequiredValidator extends BaseValidator
{
    protected string $name = 'required';
    protected string $message = 'The {field} field is required';
    
    public function validate(mixed $value): bool
    {
        if (is_array($value)) {
            return !empty($value);
        }
        
        return trim((string) $value) !== '';
    }
}
