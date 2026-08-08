<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\Handlers;

use Kodhe\Framework\Xmlrpcs\Contracts\MethodHandlerInterface;

/**
 * Handler for system methods (built-in)
 */
class SystemMethodHandler implements MethodHandlerInterface
{
    /**
     * Server instance
     *
     * @var object
     */
    protected object $server;

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
     * @param object $server
     * @param string $methodName
     * @param array|null $signature
     * @param string $docstring
     */
    public function __construct(object $server, string $methodName, ?array $signature = null, string $docstring = '')
    {
        $this->server = $server;
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
        return call_user_func_array([$this->server, $this->methodName], $params);
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
