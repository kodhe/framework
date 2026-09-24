<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\Contracts;

/**
 * Filter Interface
 * 
 * Defines the contract for data filters/sanitizers
 */
interface FilterInterface
{
    /**
     * Filter/sanitize a value
     * 
     * @param mixed $value
     * @return mixed
     */
    public function filter(mixed $value): mixed;
}
