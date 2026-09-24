<?php

declare(strict_types=1);

namespace Kodhe\Framework\Upload\Contracts;

/**
 * Validator Interface
 * 
 * Defines the contract for file validators
 * 
 * @package Kodhe\Upload\Contracts
 */
interface ValidatorInterface
{
    /**
     * Validate a file
     *
     * @param string $filePath
     * @param array $rules
     * @return bool
     */
    public function validate(string $filePath, array $rules = []): bool;

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function isValid(): bool;
}
