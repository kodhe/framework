<?php

namespace Kodhe\Driver\Contracts;

/**
 * Interface DriverInterface
 *
 * Contract untuk setiap driver individual dalam sistem multi-driver.
 * Setiap driver harus mengimplementasikan interface ini.
 *
 * @package     Kodhe\Driver\Contracts
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface DriverInterface
{
    /**
     * Decorate driver dengan parent library
     *
     * @param DriverLibraryInterface $parent Parent library instance
     * @return void
     */
    public function decorate(DriverLibraryInterface $parent): void;

    /**
     * Cek apakah driver ini didukung oleh sistem/server saat ini
     *
     * @return bool True jika didukung, false jika tidak
     */
    public function isSupported(): bool;
}
