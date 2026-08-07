<?php

namespace Kodhe\FormValidation\Rules;

use Kodhe\FormValidation\Contracts\ValidatorInterface;
use Kodhe\FormValidation\Messages\MessageStore;

class RuleChain
{
    private $validators = [];
    private $messageStore;

    public function __construct(MessageStore $messageStore)
    {
        $this->messageStore = $messageStore;
    }

    public function addValidator(ValidatorInterface $validator)
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function validate($value, $field, $data): array
    {
        $errors = [];

        foreach ($this->validators as $validator) {
            $params = ['data' => $data];
            
            if (!$validator->validate($value, $params)) {
                $message = $this->messageStore->replacePlaceholders(
                    $validator->getMessage(),
                    $field,
                    []
                );
                $errors[] = $message;
                
                // Stop on first error for required field
                if (get_class($validator) === \Kodhe\FormValidation\Validators\RequiredValidator::class) {
                    break;
                }
            }
        }

        return $errors;
    }

    public function getValidators(): array
    {
        return $this->validators;
    }
}
