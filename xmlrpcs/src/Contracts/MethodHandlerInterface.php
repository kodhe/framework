<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs\Contracts;

/**
 * Interface for method handlers
 */
interface MethodHandlerInterface
{
    /**
     * Execute the method with given parameters
     *
     * @param array $params
     * @return mixed
     */
    public function execute(array $params): mixed;

    /**
     * Get method signature
     *
     * @return array|null
     */
    public function getSignature(): ?array;

    /**
     * Get method docstring
     *
     * @return string
     */
    public function getDocstring(): string;
}
