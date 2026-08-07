<?php

namespace Kodhe\Driver;

/**
 * Class NullDriver
 *
 * Null Object Pattern implementation untuk driver.
 * Driver fallback yang aman ketika driver yang diminta tidak ditemukan.
 *
 * @package     Kodhe\Driver
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class NullDriver extends AbstractDriver
{
    /**
     * Cek apakah driver ini didukung
     * Selalu return false karena ini adalah null driver
     *
     * @return bool False
     */
    public function isSupported(): bool
    {
        return false;
    }

    /**
     * Magic caller yang selalu return null atau default value
     *
     * @param string $method Method name
     * @param array $args Method arguments
     * @return mixed Null atau default value
     */
    public function __call(string $method, array $args)
    {
        // Return null untuk semua method call
        return null;
    }

    /**
     * Magic getter yang selalu return null
     *
     * @param string $key Property name
     * @return mixed Null
     */
    public function __get(string $key)
    {
        return null;
    }

    /**
     * Decorate dengan parent (no-op)
     *
     * @param Contracts\DriverLibraryInterface $parent Parent library
     * @return void
     */
    public function decorate(Contracts\DriverLibraryInterface $parent): void
    {
        $this->parent = $parent;
    }
}
