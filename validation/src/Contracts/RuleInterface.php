<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\Contracts;

/**
 * Rule Interface
 * 
 * Defines the contract for validation rules with parameters
 */
interface RuleInterface extends ValidatorInterface
{
    /**
     * Set the rule parameter
     * 
     * @param mixed $param
     * @return self
     */
    public function setParameter(mixed $param): self;
    
    /**
     * Get the rule name
     * 
     * @return string
     */
    public function getName(): string;
}
