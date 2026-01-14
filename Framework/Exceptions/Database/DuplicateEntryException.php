<?php

namespace Kodhe\Framework\Exceptions\Database;

class DuplicateEntryException extends DatabaseException
{
    protected string $errorCode = 'DUPLICATE_ENTRY';
    protected int $httpStatusCode = 409;
    protected string $logLevel = 'warning';

    /**
     * @var string|null Duplicate field name
     */
    protected ?string $field = null;

    /**
     * @var mixed Duplicate value
     */
    protected $value = null;

    public function __construct(string $field = '', $value = null, string $message = '')
    {
        if (!$message) {
            $message = 'Duplicate entry';
            if ($field) {
                $message .= " for field: {$field}";
                if ($value !== null) {
                    $message .= " = {$value}";
                }
            }
        }
        
        parent::__construct($message);
        
        $data = [];
        if ($field) {
            $data['field'] = $field;
            $this->field = $field;
        }
        if ($value !== null) {
            $data['value'] = $value;
            $this->value = $value;
        }
        
        $this->withData($data);
    }

    /**
     * Get duplicate field
     *
     * @return string|null
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * Get duplicate value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public static function forUniqueConstraint(string $constraint, string $field, $value): self
    {
        return (new self($field, $value, "Unique constraint violation: {$constraint}"))
            ->withData(['constraint' => $constraint]);
    }
}