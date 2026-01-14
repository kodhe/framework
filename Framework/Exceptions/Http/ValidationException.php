<?php

namespace Kodhe\Framework\Exceptions\Http;

class ValidationException extends BadRequestException
{
    protected string $errorCode = 'VALIDATION_ERROR';
    protected int $httpStatusCode = 422;
    protected string $logLevel = 'info';
    
    /**
     * @var array Validation errors
     */
    protected array $errors = [];

    public function __construct(array $errors = [], string $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->errors = $errors;
        $this->withData(['errors' => $errors]);
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add error to collection
     *
     * @param string $field
     * @param string|array $messages
     * @return self
     */
    public function addError(string $field, $messages): self
    {
        if (is_string($messages)) {
            $messages = [$messages];
        }
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field] = array_merge($this->errors[$field], $messages);
        $this->withData(['errors' => $this->errors]);
        
        return $this;
    }

    /**
     * Check if field has errors
     *
     * @param string $field
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Get errors for specific field
     *
     * @param string $field
     * @return array
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public static function singleField(string $field, string $message): self
    {
        return new self([$field => [$message]]);
    }

    public static function fromValidator($validator): self
    {
        if (method_exists($validator, 'errors')) {
            $errors = $validator->errors();
            if (is_array($errors)) {
                return new self($errors);
            }
        }
        
        return new self(['general' => ['Validation failed']]);
    }
}