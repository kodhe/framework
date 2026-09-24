<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\ValueObjects;

/**
 * Validation Error Value Object
 */
class ValidationError
{
    public function __construct(
        public readonly string $field,
        public readonly string $message,
        public readonly string $rule
    ) {
    }
    
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'message' => $this->message,
            'rule' => $this->rule
        ];
    }
}
