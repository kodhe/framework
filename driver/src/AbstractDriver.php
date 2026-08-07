<?php

namespace Kodhe\Driver;

use Kodhe\Driver\Contracts\DriverInterface;
use Kodhe\Driver\Contracts\DriverLibraryInterface;

/**
 * Class AbstractDriver
 *
 * Base abstract class untuk semua driver dalam sistem multi-driver.
 * Menyediakan mekanisme dekorasi parent library dan delegasi method/property.
 *
 * @package     Kodhe\Driver
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
abstract class AbstractDriver implements DriverInterface
{
    /**
     * Parent library instance
     *
     * @var DriverLibraryInterface
     */
    protected $parent;

    /**
     * Cache property parent untuk performa
     *
     * @var array|null
     */
    private static $parentPropertyCache = null;

    /**
     * Decorate driver dengan parent library
     *
     * @param DriverLibraryInterface $parent Parent library instance
     * @return void
     */
    public function decorate(DriverLibraryInterface $parent): void
    {
        $this->parent = $parent;

        // Copy properties dari parent ke driver (kecuali lib_name)
        if (self::$parentPropertyCache === null) {
            self::$parentPropertyCache = get_class_vars(get_class($parent));
        }

        foreach (self::$parentPropertyCache as $var => $val) {
            if ($var !== 'lib_name') {
                $this->{$var} = $val;
            }
        }
    }

    /**
     * Magic getter untuk mengakses property parent
     *
     * @param string $key Nama property
     * @return mixed Value dari property atau null jika tidak ada
     */
    public function __get(string $key)
    {
        if (property_exists($this->parent, $key)) {
            return $this->parent->{$key};
        }

        return null;
    }

    /**
     * Magic setter untuk menset property parent
     *
     * @param string $key Nama property
     * @param mixed $value Value yang akan diset
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->parent->{$key} = $value;
    }

    /**
     * Magic caller untuk memanggil method parent
     *
     * @param string $method Nama method
     * @param array $args Argument untuk method
     * @return mixed Hasil dari method call
     * @throws \BadMethodCallException Jika method tidak ada di parent
     */
    public function __call(string $method, array $args)
    {
        if (method_exists($this->parent, $method)) {
            return call_user_func_array([$this->parent, $method], $args);
        }

        throw new \BadMethodCallException(
            "Method {$method} does not exist in parent library"
        );
    }

    /**
     * Static magic caller untuk memanggil method parent secara statis
     *
     * @param string $method Nama method
     * @param array $args Argument untuk method
     * @return mixed Hasil dari method call
     * @throws \BadMethodCallException Jika method tidak ada
     */
    public static function __callStatic(string $method, array $args)
    {
        throw new \BadMethodCallException(
            "Static method {$method} cannot be called on driver"
        );
    }

    /**
     * Cek apakah driver ini didukung
     * Harus diimplementasikan oleh setiap driver concrete
     *
     * @return bool True jika didukung, false jika tidak
     */
    abstract public function isSupported(): bool;
}
