<?php

namespace Kodhe\FormValidation\Contracts;

interface FilterInterface
{
    public function filter($value, $params = []);
}
