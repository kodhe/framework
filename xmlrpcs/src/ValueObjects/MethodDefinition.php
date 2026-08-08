<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpcs\ValueObjects;

/**
 * Value object for method definitions
 */
class MethodDefinition
{
    /**
     * Method name
     *
     * @var string
     */
    protected string $name;

    /**
     * Function or callable
     *
     * @var mixed
     */
    protected $function;

    /**
     * Method signature
     *
     * @var array|null
     */
    protected ?array $signature;

    /**
     * Method docstring
     *
     * @var string
     */
    protected string $docstring;

    /**
     * Whether this is a system method
     *
     * @var bool
     */
    protected bool $isSystemMethod = false;

    /**
     * Constructor
     *
     * @param string $name
     * @param mixed $function
     * @param array|null $signature
     * @param string $docstring
     * @param bool $isSystemMethod
     */
    public function __construct(
        string $name,
        $function,
        ?array $signature = null,
        string $docstring = '',
        bool $isSystemMethod = false
    ) {
        $this->name = $name;
        $this->function = $function;
        $this->signature = $signature;
        $this->docstring = $docstring;
        $this->isSystemMethod = $isSystemMethod;
    }

    /**
     * Get method name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get function/callable
     *
     * @return mixed
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Get signature
     *
     * @return array|null
     */
    public function getSignature(): ?array
    {
        return $this->signature;
    }

    /**
     * Get docstring
     *
     * @return string
     */
    public function getDocstring(): string
    {
        return $this->docstring;
    }

    /**
     * Check if this is a system method
     *
     * @return bool
     */
    public function isSystemMethod(): bool
    {
        return $this->isSystemMethod;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'function' => $this->function,
            'signature' => $this->signature,
            'docstring' => $this->docstring,
            'isSystemMethod' => $this->isSystemMethod
        ];
    }
}
