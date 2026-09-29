<?php

/**
 * Storage path helper.
 *
 * The runtime storage directory (cache/, logs/, sessions/, uploads/) is
 * published as the STORAGEPATH constant by the application front controller
 * (kodhe/public/index.php) and by the console bootstrap. Because it is a
 * constant, every consumer used to concatenate it directly:
 *
 *     $cache_path = ($path === '') ? STORAGEPATH.'cache/' : $path;
 *
 * That breaks in two very common situations:
 *
 *   1. STORAGEPATH is undefined (library used standalone, package tests,
 *      CLI tooling) -> PHP 8 "Undefined constant" fatal.
 *   2. config 'cache_path' is not set -> item() returns NULL, the loose
 *      `$path === ''` check misses it, and rtrim(NULL) throws a TypeError
 *      under declare(strict_types=1).
 *
 * Both failures happen while the router boots or while a resolved route is
 * executed, so the request dies before any output and the symptom users
 * report is "my closure route is 404 / not found".
 *
 * This file exposes the same behaviour for every package through two plain
 * global functions, guarded with function_exists() so it can be required
 * from several places (composer "files", legacy common.php, per-class
 * fallbacks) without redeclaration errors.
 */

if ( ! function_exists('kodhe_storage_path')) {
    /**
     * Resolve the writable storage base directory.
     *
     * Priority: explicit argument > STORAGEPATH > BASEPATH/storage >
     * APPPATH/../storage > system temp dir.
     *
     * @param string|null $fallback Optional override.
     * @return string Path WITHOUT a trailing separator.
     */
    function kodhe_storage_path($fallback = null)
    {
        if (is_string($fallback) && trim($fallback) !== '') {
            return rtrim($fallback, '/\\');
        }

        if (defined('STORAGEPATH')) {
            $candidate = STORAGEPATH;
        } elseif (defined('BASEPATH')) {
            $candidate = rtrim(BASEPATH, '/\\') . DIRECTORY_SEPARATOR . 'storage';
        } elseif (defined('APPPATH')) {
            $candidate = rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage';
        } else {
            $candidate = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kodhe-storage';
        }

        return rtrim((string) $candidate, '/\\');
    }
}

if ( ! function_exists('kodhe_cache_path')) {
    /**
     * Resolve the cache directory from an optional configured value.
     *
     * Accepts NULL / '' / garbage coming out of Config::item('cache_path')
     * and always returns a usable, non-empty absolute-ish path with no
     * trailing separator - never NULL, which is what previously blew up
     * inside rtrim().
     *
     * @param mixed $configured Value read from the config container.
     * @return string
     */
    function kodhe_cache_path($configured = null)
    {
        if (is_string($configured) && trim($configured) !== '') {
            return rtrim($configured, '/\\');
        }

        return kodhe_storage_path() . DIRECTORY_SEPARATOR . 'cache';
    }
}
