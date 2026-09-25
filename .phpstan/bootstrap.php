<?php

/**
 * Stub bootstrap untuk analisis statis PHPStan (TIDAK dimuat saat runtime).
 *
 * Mayoritas paket legacy Kodhe Framework memanggil service-locator global
 * warisan CodeIgniter 3 yang didefinisikan oleh aplikasi host, bukan oleh
 * library ini — sehingga PHPStan melaporkannya sebagai "Function ... not
 * found" / "Constant ... not found" (ribuan false positive environment).
 * File ini mendeklarasikan simbol-simbol tersebut agar analisis fokus pada
 * masalah kode nyata.
 */

declare(strict_types=1);

if (!defined('APPPATH')) {
    define('APPPATH', 'APPPATH');
}
if (!defined('FCPATH')) {
    define('FCPATH', 'FCPATH');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', 'BASEPATH');
}
if (!defined('STORAGEPATH')) {
    define('STORAGEPATH', 'STORAGEPATH');
}
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
}

if (!function_exists('get_instance')) {
    /**
     * Service locator CodeIgniter 3.
     *
     * @return object Instance CI_Controller dengan properti magic ($load, $db, dst.)
     */
    function get_instance()
    {
        return new stdClass();
    }
}

if (!function_exists('app')) {
    /**
     * Resolver container framework (kodeh-app).
     *
     * @return object Layanan yang di-resolve.
     */
    function app(string $key = '', array $parameters = [])
    {
        return new stdClass();
    }
}

if (!function_exists('kodhe')) {
    /**
     * Helper global framework.
     *
     * @return mixed
     */
    function kodhe(?string $key = null, $default = null)
    {
        return $default;
    }
}

if (!function_exists('log_message')) {
    /**
     * Logger CI3 (dipakai beberapa driver legacy).
     *
     * @return void
     */
    function log_message($level, $message)
    {
    }
}

if (!function_exists('config_item')) {
    /**
     * @return mixed
     */
    function config_item($item)
    {
        return null;
    }
}

if (!function_exists('show_error')) {
    /**
     * @return never
     */
    function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
    {
        throw new RuntimeException((string) $message);
    }
}

if (!class_exists('CI_Controller')) {
    /** @see stub-only */
    class CI_Controller
    {
    }
}

if (!class_exists('CI_DB_result')) {
    /**
     * Stub hasil query CodeIgniter 3 untuk analisis statis (table/src memakai
     * instanceof CI_DB_result; kelas nyata dipasok DB driver CI saat runtime).
     */
    class CI_DB_result implements IteratorAggregate
    {
        /** @return \Traversable */
        public function getIterator(): \Traversable { return new \ArrayIterator([]); }

        /** @return array<int, object> */
        public function result_array() { return []; }

        /** @return int */
        public function num_rows() { return 0; }
    }
}

if (!class_exists('CI_Model')) {
    /** @see stub-only */
    class CI_Model
    {
    }
}

if (!function_exists('highlight_code')) {
    /**
     * Stub helper CI3 (system/helpers/text_helper.php) — dipakai Profiler untuk
     * menyorot SQL. Saat runtime dipasok framework; stub ini hanya untuk analisis statis.
     */
    function highlight_code($str)
    {
        return (string) $str;
    }
}

if (!extension_loaded('redis') && !class_exists('Redis')) {
    /** Stub kelas Redis php-redis (session driver) untuk analisis statis. */
    class Redis
    {
    }
}

if (!extension_loaded('memcached') && !class_exists('Memcached')) {
    /** Stub kelas Memcached php-memcached (session driver) untuk analisis statis. */
    class Memcached
    {
        public const OPT_BINARY_PROTOCOL = 5;
        public const RES_SUCCESS = 0;
        public const RES_NOTFOUND = 16;
    }
}
