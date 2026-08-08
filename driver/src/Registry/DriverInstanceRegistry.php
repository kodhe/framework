<?php

namespace Kodhe\Framework\Driver\Registry;

use Kodhe\Framework\Driver\Contracts\DriverInterface;

/**
 * Class DriverInstanceRegistry
 *
 * Registry pattern untuk menyimpan dan mengelola instance driver yang sudah dibuat.
 * Mencegah instansiasi ulang driver yang sama dalam satu request.
 *
 * @package     Kodhe\Driver\Registry
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class DriverInstanceRegistry
{
    /**
     * Storage untuk instance driver
     * Format: [libName => [driverName => instance]]
     *
     * @var array<string, array<string, DriverInterface>>
     */
    private $instances = [];

    /**
     * Get instance driver dari registry
     *
     * @param string $libName Nama library
     * @param string $driverName Nama driver
     * @return DriverInterface|null Instance driver atau null jika tidak ada
     */
    public function get(string $libName, string $driverName): ?DriverInterface
    {
        if (!isset($this->instances[$libName])) {
            return null;
        }

        if (!isset($this->instances[$libName][$driverName])) {
            return null;
        }

        return $this->instances[$libName][$driverName];
    }

    /**
     * Set instance driver ke registry
     *
     * @param string $libName Nama library
     * @param string $driverName Nama driver
     * @param DriverInterface $instance Instance driver
     * @return self
     */
    public function set(string $libName, string $driverName, DriverInterface $instance): self
    {
        if (!isset($this->instances[$libName])) {
            $this->instances[$libName] = [];
        }

        $this->instances[$libName][$driverName] = $instance;

        return $this;
    }

    /**
     * Cek apakah instance driver ada di registry
     *
     * @param string $libName Nama library
     * @param string $driverName Nama driver
     * @return bool True jika ada, false jika tidak
     */
    public function has(string $libName, string $driverName): bool
    {
        if (!isset($this->instances[$libName])) {
            return false;
        }

        return isset($this->instances[$libName][$driverName]);
    }

    /**
     * Remove instance driver dari registry
     *
     * @param string $libName Nama library
     * @param string $driverName Nama driver
     * @return bool True jika berhasil dihapus, false jika tidak ada
     */
    public function remove(string $libName, string $driverName): bool
    {
        if (!isset($this->instances[$libName])) {
            return false;
        }

        if (!isset($this->instances[$libName][$driverName])) {
            return false;
        }

        unset($this->instances[$libName][$driverName]);

        // Clean up empty library entry
        if (empty($this->instances[$libName])) {
            unset($this->instances[$libName]);
        }

        return true;
    }

    /**
     * Clear semua instance untuk library tertentu
     *
     * @param string $libName Nama library
     * @return self
     */
    public function clear(string $libName): self
    {
        if (isset($this->instances[$libName])) {
            unset($this->instances[$libName]);
        }

        return $this;
    }

    /**
     * Clear semua instance dari semua library
     *
     * @return self
     */
    public function clearAll(): self
    {
        $this->instances = [];
        return $this;
    }

    /**
     * Get semua instance untuk library tertentu
     *
     * @param string $libName Nama library
     * @return array<string, DriverInterface> Array of driver instances
     */
    public function getAll(string $libName): array
    {
        return $this->instances[$libName] ?? [];
    }

    /**
     * Get jumlah instance untuk library tertentu
     *
     * @param string $libName Nama library
     * @return int Jumlah instance
     */
    public function count(string $libName): int
    {
        return count($this->instances[$libName] ?? []);
    }

    /**
     * Get semua library yang terdaftar
     *
     * @return array<string> Array of library names
     */
    public function getLibraries(): array
    {
        return array_keys($this->instances);
    }
}
