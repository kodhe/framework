<?php

declare(strict_types=1);

namespace Kodhe\Framework\Typography\Contracts;

/**
 * Formatter Interface
 * 
 * Defines the contract for typography formatters.
 */
interface FormatterInterface
{
    /**
     * Format the given text.
     *
     * @param string $text
     * @return string
     */
    public function format(string $text): string;
}
