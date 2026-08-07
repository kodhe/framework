<?php

namespace Kodhe\FormValidation\Validators;

use Kodhe\FormValidation\Contracts\ValidatorInterface;

class MatchesValidator implements ValidatorInterface
{
    private $matchField;

    public function __construct($matchField)
    {
        $this->matchField = $matchField;
    }

    public function validate($value, $params = [])
    {
        if ($value === '' || $value === null) {
            return true;
        }
        
        $data = $params['data'] ?? [];
        $matchValue = $data[$this->matchField] ?? null;
        
        return $value === $matchValue;
    }

    public function getMessage(): string
    {
        return "The {field} field does not match the {$this->matchField} field.";
    }
}
