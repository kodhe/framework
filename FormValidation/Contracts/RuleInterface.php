<?php

namespace Kodhe\FormValidation\Contracts;

interface RuleInterface
{
    public function validate($value, $field, $data);
    public function getName(): string;
}
