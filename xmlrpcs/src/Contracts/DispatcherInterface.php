<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs\Contracts;

/**
 * Interface for request dispatcher
 */
interface DispatcherInterface
{
    /**
     * Dispatch a method call
     *
     * @param string $methodName
     * @param array $params
     * @return mixed
     */
    public function dispatch(string $methodName, array $params): mixed;

    /**
     * Set the object context for method calls
     *
     * @param object|null $object
     * @return void
     */
    public function setObjectContext(?object $object): void;

    /**
     * Get the object context
     *
     * @return object|null
     */
    public function getObjectContext(): ?object;
}
