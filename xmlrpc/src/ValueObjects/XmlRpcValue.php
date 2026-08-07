<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpc\ValueObjects;

/**
 * Represents an XML-RPC value with type information
 */
class XmlRpcValue
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $type;

    /**
     * Type constants
     */
    public const TYPE_I4 = 'i4';
    public const TYPE_INT = 'int';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_STRING = 'string';
    public const TYPE_DOUBLE = 'double';
    public const TYPE_DATETIME = 'dateTime.iso8601';
    public const TYPE_BASE64 = 'base64';
    public const TYPE_ARRAY = 'array';
    public const TYPE_STRUCT = 'struct';

    /**
     * @param mixed $value
     * @param string $type
     */
    public function __construct($value, string $type = self::TYPE_STRING)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isScalar(): bool
    {
        return in_array($this->type, [
            self::TYPE_I4,
            self::TYPE_INT,
            self::TYPE_BOOLEAN,
            self::TYPE_STRING,
            self::TYPE_DOUBLE,
            self::TYPE_DATETIME,
            self::TYPE_BASE64,
        ], true);
    }

    public function isArray(): bool
    {
        return $this->type === self::TYPE_ARRAY;
    }

    public function isStruct(): bool
    {
        return $this->type === self::TYPE_STRUCT;
    }
}
