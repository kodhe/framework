<?php

declare(strict_types=1);

/**
 * Helper global CLI (padanan blok helper di bootstrap/app.php web).
 *
 * Dimuat oleh Kodhe\Framework\Console\Bootstrap::boot() dan HANYA bila
 * belum ada definisinya — supaya tidak bentrok dengan project yang
 * mendefinisikan helper-nya sendiri.
 */

use Kodhe\Framework\Support\Facades\Facade;

if (!function_exists('get_instance')) {
    /**
     * Akses instance aplikasi (facade container), kompatibel gaya CI3.
     */
    function &get_instance()
    {
        $facade = \Kodhe\Framework\Support\Facades\Facade::getInstance();
        return $facade;
    }
}

if (!function_exists('app')) {
    /**
     * Return facade application, atau resolve dependency dari DI container.
     */
    function &app($dep = null)
    {
        $facade =& get_instance();

        if ($dep !== null && isset($facade->di)) {
            $made = call_user_func_array(array($facade->di, 'make'), func_get_args());
            return $made;
        }

        return $facade;
    }
}

if (!function_exists('kodhe')) {
    /**
     * Alias app().
     */
    function &kodhe($dep = null)
    {
        $app =& app(...func_get_args());
        return $app;
    }
}
