<?php

namespace Kodhe\Migration\Exceptions;

/**
 * Exception untuk migration file tidak valid
 *
 * @package Kodhe\Migration\Exceptions
 */
class InvalidMigrationFileException extends \InvalidArgumentException
{
    /**
     * @param string $file
     */
    public function __construct(string $file)
    {
        parent::__construct("Invalid migration file: {$file}");
    }
}
