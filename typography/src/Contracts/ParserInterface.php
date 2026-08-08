<?php

declare(strict_types=1);

namespace Kodhe\Framework\Typography\Contracts;

/**
 * Parser Interface
 * 
 * Defines the contract for typography parsers.
 */
interface ParserInterface
{
    /**
     * Parse the given text.
     *
     * @param string $text
     * @return string
     */
    public function parse(string $text): string;
}
