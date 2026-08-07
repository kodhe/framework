<?php

namespace Kodhe\FormValidation\Exceptions;

class FormValidationException extends \RuntimeException
{
    public static function invalidRule($rule)
    {
        return new self("Invalid rule: {$rule}");
    }

    public static function callbackMethodNotFound($method)
    {
        return new self("Callback method not found: {$method}");
    }

    public static function fieldNotFound($field)
    {
        return new self("Field not found: {$field}");
    }
}
