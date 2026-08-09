<?php

declare(strict_types=0);

namespace Kodhe\Framework\Validation\ValueObjects;

/**
 * Field Configuration Value Object
 */
class FieldConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly array $rules = [],
        public readonly array $errors = [],
        public readonly bool $isArray = false,
        public readonly array $keys = []
    ) {
    }
    
    public function toArray(): array
    {
        return [
            'field' => $this->name,
            'label' => $this->label,
            'rules' => $this->rules,
            'errors' => $this->errors,
            'is_array' => $this->isArray,
            'keys' => $this->keys,
            'postdata' => null,
            'error' => ''
        ];
    }
}
