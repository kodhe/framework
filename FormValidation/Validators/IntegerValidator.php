<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class IntegerValidator implements ValidatorInterface
{
    public function validate($value, $params = [])
    {
        if ($value === '' || $value === null) {
            return true;
        }
        return (string)(int)$value === (string)$value;
    }

    public function getMessage(): string
    {
        return 'The {field} field must contain an integer.';
    }
}
