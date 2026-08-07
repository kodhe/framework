<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class MaxLengthValidator implements ValidatorInterface
{
    private $max;

    public function __construct($max)
    {
        $this->max = (int)$max;
    }

    public function validate($value, $params = [])
    {
        if ($value === '' || $value === null) {
            return true;
        }
        return mb_strlen($value) <= $this->max;
    }

    public function getMessage(): string
    {
        return "The {field} field can not exceed {$this->max} characters in length.";
    }
}
