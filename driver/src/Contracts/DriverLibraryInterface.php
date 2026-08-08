<?php

namespace Kodhe\Framework\Driver\Contracts;

/**
 * Interface DriverLibraryInterface
 *
 * Contract untuk library parent dalam sistem multi-driver.
 * Library parent bertanggung jawab untuk loading dan managing drivers.
 *
 * @package     Kodhe\Driver\Contracts
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface DriverLibraryInterface
{
    /**
     * Load driver berdasarkan nama
     *
     * @param string $driverName Nama driver yang akan di-load
     * @return DriverInterface Instance dari driver yang di-load
     * @throws \RuntimeException Jika driver tidak ditemukan
     */
    public function load_driver(string $driverName): DriverInterface;

    /**
     * Cek apakah driver valid untuk library ini
     *
     * @param string $driverName Nama driver
     * @return bool True jika valid, false jika tidak
     */
    public function isValidDriver(string $driverName): bool;

    /**
     * Get nama library
     *
     * @return string Nama library
     */
    public function getLibName(): string;
}
