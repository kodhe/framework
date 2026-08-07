<?php

namespace Kodhe\Migration\Exceptions;

/**
 * Exception untuk migration tidak ditemukan
 *
 * @package Kodhe\Migration\Exceptions
 */
class MigrationNotFoundException extends \RuntimeException
{
    /**
     * @param string $version
     */
    public function __construct(string $version)
    {
        parent::__construct("Migration version {$version} not found.");
    }
}
