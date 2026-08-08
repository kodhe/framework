<?php

declare(strict_types=1);

namespace Kodhe\Validation\Validators;

/**
 * Callable Validator (for callbacks and custom callables)
 */
class CallableValidator extends BaseValidator
{
    protected string $name = 'callback';
    protected string $message = 'The {field} field does not pass validation';
    
    public function __construct(
        protected callable $callback,
        ?string $name = null
    ) {
        if ($name !== null) {
            $this->name = $name;
        }
    }
    
    public function validate(mixed $value): bool
    {
        return (bool) call_user_func($this->callback, $value, $this->parameter);
    }
    
    public function getMessage(string $field, string $label): string
    {
        return str_replace(
            ['{field}', '{param}'],
            [$label, (string) $this->parameter],
            $this->message
        );
    }
}
