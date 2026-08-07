<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class NumericValidator implements ValidatorInterface
{
    public function validate($value, $params = [])
    {
        if ($value === '' || $value === null) {
            return true;
        }
        return is_numeric($value);
    }

    public function getMessage(): string
    {
        return 'The {field} field must contain only numbers.';
    }
}
