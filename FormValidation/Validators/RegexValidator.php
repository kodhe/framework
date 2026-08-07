<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class RegexValidator implements ValidatorInterface
{
    private $pattern;

    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }

    public function validate($value, $params = [])
    {
        if ($value === '' || $value === null) {
            return true;
        }
        return (bool) preg_match($this->pattern, $value);
    }

    public function getMessage(): string
    {
        return 'The {field} field is not in the correct format.';
    }
}
