<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs\Handlers;

use Kodhe\Xmlrpcs\Contracts\MethodHandlerInterface;

/**
 * Handler for object method-based methods
 */
class ObjectMethodHandler implements MethodHandlerInterface
{
    /**
     * Object instance
     *
     * @var object|null
     */
    protected ?object $object;

    /**
     * Method name
     *
     * @var string
     */
    protected string $methodName;

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
     * Constructor
     *
     * @param object|null $object
     * @param string $methodName
     * @param array|null $signature
     * @param string $docstring
     */
    public function __construct(?object $object, string $methodName, ?array $signature = null, string $docstring = '')
    {
        $this->object = $object;
        $this->methodName = $methodName;
        $this->signature = $signature;
        $this->docstring = $docstring;
    }

    /**
     * Execute the method with given parameters
     *
     * @param array $params
     * @return mixed
     */
    public function execute(array $params): mixed
    {
        if ($this->object !== null) {
            return call_user_func_array([$this->object, $this->methodName], $params);
        }

        // Static method call or CI super object
        return call_user_func_array([$this->methodName], $params);
    }

    /**
     * Get method signature
     *
     * @return array|null
     */
    public function getSignature(): ?array
    {
        return $this->signature;
    }

    /**
     * Get method docstring
     *
     * @return string
     */
    public function getDocstring(): string
    {
        return $this->docstring;
    }

    /**
     * Set the object context
     *
     * @param object|null $object
     * @return void
     */
    public function setObject(?object $object): void
    {
        $this->object = $object;
    }

    /**
     * Get the object context
     *
     * @return object|null
     */
    public function getObject(): ?object
    {
        return $this->object;
    }
}
