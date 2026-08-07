<?php

namespace Kodhe\Driver;

use Kodhe\Driver\Contracts\DriverLibraryInterface;
use Kodhe\Driver\Contracts\DriverInterface;
use Kodhe\Driver\Resolvers\DriverFileResolver;
use Kodhe\Driver\Registry\DriverInstanceRegistry;

/**
 * Class DriverLibrary
 *
 * Base class untuk library parent dalam sistem multi-driver CodeIgniter 3.
 * Bertindak sebagai facade dan registry untuk mengelola multiple drivers.
 *
 * @package     Kodhe\Driver
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
abstract class DriverLibrary implements DriverLibraryInterface
{
    /**
     * Nama library
     *
     * @var string
     */
    public $lib_name;

    /**
     * Daftar driver yang valid untuk library ini
     *
     * @var array
     */
    protected $valid_drivers = [];

    /**
     * Registry untuk menyimpan instance driver
     *
     * @var DriverInstanceRegistry
     */
    protected $registry;

    /**
     * Resolver untuk loading file driver
     *
     * @var DriverFileResolver
     */
    protected $resolver;

    /**
     * Constructor
     *
     * @param DriverInstanceRegistry|null $registry Optional registry instance
     * @param DriverFileResolver|null $resolver Optional resolver instance
     */
    public function __construct(
        ?DriverInstanceRegistry $registry = null,
        ?DriverFileResolver $resolver = null
    ) {
        $this->registry = $registry ?? new DriverInstanceRegistry();
        $this->resolver = $resolver ?? new DriverFileResolver();

        // Auto-detect lib_name jika tidak diset
        if (empty($this->lib_name)) {
            $className = get_class($this);
            // Remove namespace prefix jika ada
            if (($pos = strrpos($className, '\\')) !== false) {
                $className = substr($className, $pos + 1);
            }
            // Remove CI_ prefix jika ada (untuk backward compatibility)
            $this->lib_name = preg_replace('/^CI_/', '', $className);
        }
    }

    /**
     * Magic getter untuk mengakses driver
     * Lazy load driver saat pertama kali diakses
     *
     * @param string $driverName Nama driver
     * @return DriverInterface Instance driver
     */
    public function __get(string $driverName)
    {
        return $this->load_driver($driverName);
    }

    /**
     * Load driver berdasarkan nama
     *
     * @param string $driverName Nama driver yang akan di-load
     * @return DriverInterface Instance dari driver yang di-load
     * @throws \RuntimeException Jika driver tidak ditemukan atau tidak valid
     */
    public function load_driver(string $driverName): DriverInterface
    {
        // Cek registry dulu (cache instance)
        $cached = $this->registry->get($this->lib_name, $driverName);
        if ($cached !== null) {
            return $cached;
        }

        // Validasi driver name
        if (!$this->isValidDriver($driverName)) {
            throw new \RuntimeException(
                "Invalid driver requested: {$driverName} for library {$this->lib_name}"
            );
        }

        // Resolve class name dan load file
        $driverClass = $this->resolver->resolveClassName($this->lib_name, $driverName);
        $this->resolver->loadFile($this->lib_name, $driverName);

        // Cek apakah class sudah ada
        if (!class_exists($driverClass, false)) {
            throw new \RuntimeException(
                "Unable to load the requested driver: {$driverClass}"
            );
        }

        // Instantiate driver
        $driver = new $driverClass();

        // Decorate dengan parent library
        if ($driver instanceof DriverInterface) {
            $driver->decorate($this);
        }

        // Simpan ke registry
        $this->registry->set($this->lib_name, $driverName, $driver);

        return $driver;
    }

    /**
     * Cek apakah driver valid untuk library ini
     *
     * @param string $driverName Nama driver
     * @return bool True jika valid, false jika tidak
     */
    public function isValidDriver(string $driverName): bool
    {
        // Jika valid_drivers kosong, anggap semua driver valid (auto-discovery mode)
        if (empty($this->valid_drivers)) {
            return true;
        }

        // Cek apakah driver ada dalam daftar valid_drivers
        foreach ($this->valid_drivers as $valid) {
            // Support both 'driver' and 'lib_driver' formats
            if ($driverName === $valid || $driverName === str_replace($this->lib_name . '_', '', $valid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get nama library
     *
     * @return string Nama library
     */
    public function getLibName(): string
    {
        return $this->lib_name;
    }

    /**
     * Set valid drivers
     *
     * @param array $drivers Daftar driver yang valid
     * @return self
     */
    public function setValidDrivers(array $drivers): self
    {
        $this->valid_drivers = $drivers;
        return $this;
    }

    /**
     * Get valid drivers
     *
     * @return array Daftar driver yang valid
     */
    public function getValidDrivers(): array
    {
        return $this->valid_drivers;
    }

    /**
     * Close/cleanup semua driver
     *
     * @return void
     */
    public function __destruct()
    {
        $this->registry->clear($this->lib_name);
    }
}
