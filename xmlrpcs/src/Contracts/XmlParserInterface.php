<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpcs\Contracts;

/**
 * Interface for XML parsers
 */
interface XmlParserInterface
{
    /**
     * Parse XML request data
     *
     * @param string $data
     * @return array
     */
    public function parse(string $data): array;

    /**
     * Get parsing errors
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Check if parsing was successful
     *
     * @return bool
     */
    public function isValid(): bool;
}
