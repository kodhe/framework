<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class RequiredValidator implements ValidatorInterface
{
    public function validate($value, $params = [])
    {
        if (is_array($value)) {
            return !empty($value);
        }
        return trim((string)$value) !== '';
    }

    public function getMessage(): string
    {
        return 'The {field} field is required.';
    }
}
