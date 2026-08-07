<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class MinLengthValidator implements ValidatorInterface
{
    private $min;

    public function __construct($min)
    {
        $this->min = (int)$min;
    }

    public function validate($value, $params = [])
    {
        if ($value === '' || $value === null) {
            return true;
        }
        return mb_strlen($value) >= $this->min;
    }

    public function getMessage(): string
    {
        return "The {field} field must be at least {$this->min} characters in length.";
    }
}
