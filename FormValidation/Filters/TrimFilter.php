<?php

namespace Kodhe\FormValidation\Filters;

use Kodhe\FormValidation\Contracts\FilterInterface;

class TrimFilter implements FilterInterface
{
    public function filter($value, $params = [])
    {
        if (!is_string($value)) {
            return $value;
        }
        return trim($value);
    }
}
