<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class UrlValidator implements ValidatorInterface
{
    public function validate($value, $params = [])
    {
        if ($value === '' || $value === null) {
            return true;
        }
        return (bool) filter_var($value, FILTER_VALIDATE_URL);
    }

    public function getMessage(): string
    {
        return 'The {field} field must contain a valid URL.';
    }
}
