<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs\Handlers;

use Kodhe\Xmlrpcs\Contracts\MethodHandlerInterface;

/**
 * Handler for function-based methods
 */
class FunctionHandler implements MethodHandlerInterface
{
    /**
     * Function name or callable
     *
     * @var callable
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
     * Constructor
     *
     * @param callable $function
     * @param array|null $signature
     * @param string $docstring
     */
    public function __construct(callable $function, ?array $signature = null, string $docstring = '')
    {
        $this->function = $function;
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
        return call_user_func($this->function, ...$params);
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
}
