<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class EmailValidator implements ValidatorInterface
{
    public function validate($value, $params = [])
    {
        if ($value === '' || $value === null) {
            return true;
        }
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function getMessage(): string
    {
        return 'The {field} field must contain a valid email address.';
    }
}
