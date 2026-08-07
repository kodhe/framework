<?php

namespace Kodhe\Migration\Exceptions;

/**
 * Exception untuk duplicate version migration
 *
 * @package Kodhe\Migration\Exceptions
 */
class DuplicateVersionException extends \RuntimeException
{
    /**
     * @param int $version
     */
    public function __construct(int $version)
    {
        parent::__construct("Duplicate migration version: {$version}");
    }
}
