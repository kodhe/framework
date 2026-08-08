<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\Validators;

use Kodhe\Framework\Validation\Contracts\ValidatorInterface;

/**
 * Base Validator Class
 * 
 * Abstract base class for all validators implementing Strategy pattern
 */
abstract class BaseValidator implements ValidatorInterface
{
    protected string $name = '';
    protected mixed $parameter = null;
    protected string $message = 'The {field} field does not pass validation';
    
    /**
     * Set the rule parameter
     */
    public function setParameter(mixed $param): static
    {
        $this->parameter = $param;
        return $this;
    }
    
    /**
     * Get the rule name
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Set custom error message
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }
    
    /**
     * Get the error message
     */
    public function getMessage(string $field, string $label): string
    {
        return str_replace(
            ['{field}', '{param}'],
            [$label, (string) $this->parameter],
            $this->message
        );
    }
    
    /**
     * Check if value is empty
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [] || $value === false;
    }
}
