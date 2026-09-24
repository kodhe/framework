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

declare(strict_types=0);

if (!defined('APPPATH')) {
    define('APPPATH', 'APPPATH');
}
if (!defined('FCPATH')) {
    define('FCPATH', 'FCPATH');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', 'BASEPATH');
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

if (!class_exists('CI_Model')) {
    /** @see stub-only */
    class CI_Model
    {
    }
}
