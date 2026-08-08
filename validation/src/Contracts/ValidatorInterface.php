<?php

declare(strict_types=1);

namespace Kodhe\Validation\Contracts;

/**
 * Validator Interface
 * 
 * Defines the contract for individual validation rules
 */
interface ValidatorInterface
{
    /**
     * Validate a value against the rule
     * 
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool;
    
    /**
     * Get the error message when validation fails
     * 
     * @param string $field
     * @param string $label
     * @return string
     */
    public function getMessage(string $field, string $label): string;
}
