<?php

declare(strict_types=1);

namespace Kodhe\Validation\Validators;

/**
 * Custom Rule Validator (implements custom RuleInterface)
 */
class CustomRuleValidator extends BaseValidator
{
    public function __construct(
        protected \Kodhe\Validation\Contracts\RuleInterface $rule
    ) {
        $this->name = $rule->getName();
    }
    
    public function validate(mixed $value): bool
    {
        return $this->rule->validate($value);
    }
    
    public function getMessage(string $field, string $label): string
    {
        return $this->rule->getMessage($field, $label);
    }
}
