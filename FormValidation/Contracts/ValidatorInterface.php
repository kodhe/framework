<?php

namespace Kodhe\FormValidation\Contracts;

interface ValidatorInterface
{
    public function validate($value, $params = []);
    public function getMessage(): string;
}
