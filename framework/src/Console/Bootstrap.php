<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console;

/**
 * Console Bootstrap — padanan public/index.php untuk dunia CLI.
 *
 * Sesuai arsitektur project:
 *   - Konstanta path (SYSPATH/APPPATH/VIEWPATH/STORAGEPATH/ENVIRONMENT)
 *     didefinisikan oleh ENTRY POINT (bin/console), sama seperti index.php.
 *   - Persiapan ringan yang dibutuhkan command (autoloader app, konstanta
 *     config, error handler, helper global kodhe()/app()/get_instance())
 *     dilakukan di sini.
 *   - bootstrap/app.php TIDAK dimuat penuh, karena file tersebut adalah
 *     boot web: ia menjalankan $app->run($request) dan mengirim response —
 *     tidak boleh dieksekusi saat `php bin/console migrate`.
 */
final class Bootstrap
{
    private static bool $booted = false;

    /**
     * Siapkan environment CLI. Aman dipanggil berkali-kali (idempotent).
     *
     * @param string|null $projectRoot root project; default dari env
     *                                 KODHE_PROJECT_ROOT lalu SYSPATH/APPPATH.
     */
    public static function boot(?string $projectRoot = null): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        // ----------------------------------------------------------
        // 1. Root project
        // ----------------------------------------------------------
        if ($projectRoot === null || $projectRoot === '') {
            $projectRoot = getenv('KODHE_PROJECT_ROOT') ?: null;
        }
        if (($projectRoot === null || $projectRoot === '') && defined('APPPATH')) {
            // APPPATH = <project>/application/
            $projectRoot = dirname(rtrim(APPPATH, '/\\'));
        }
        if ($projectRoot !== null && $projectRoot !== '') {
            $projectRoot = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR;
            putenv('KODHE_PROJECT_ROOT=' . $projectRoot);
            $_ENV['KODHE_PROJECT_ROOT']    = $projectRoot;
            $_SERVER['KODHE_PROJECT_ROOT'] = $projectRoot;
        }

        // ----------------------------------------------------------
        // 2. Autoloader "App\..." (prefix -> APPPATH), sama seperti
        //    yang dilakukan bootstrap/app.php pada boot web.
        // ----------------------------------------------------------
        if (defined('APPPATH')
            && class_exists(\Kodhe\Framework\Support\Autoloader::class)) {
            \Kodhe\Framework\Support\Autoloader::getInstance()
                ->addPrefix('App', APPPATH)
                ->register();
        }

        // ----------------------------------------------------------
        // 3. Konstanta dari application/config/constants.php
        // ----------------------------------------------------------
        if (defined('APPPATH')) {
            $constantsFile = APPPATH . 'config' . DIRECTORY_SEPARATOR . 'constants.php';
            if (is_file($constantsFile)) {
                $constants = require $constantsFile;
                if (is_array($constants)) {
                    foreach ($constants as $k => $v) {
                        defined($k) || define($k, $v);
                    }
                }
            }
        }

        // ----------------------------------------------------------
        // 4. Helper global kodhe() / app() / get_instance().
        //    Definisi aslinya ada di bootstrap/app.php milik project,
        //    tetapi file itu sekaligus me-run request web — jadi di CLI
        //    kita daftarkan padanannya di sini (hanya bila belum ada).
        // ----------------------------------------------------------
        if (!function_exists('get_instance')) {
            require_once __DIR__ . '/Functions.php';
        }
    }

    /**
     * Root project hasil boot (null bila tidak terdeteksi).
     */
    public static function projectRoot(): ?string
    {
        $root = getenv('KODHE_PROJECT_ROOT');
        return ($root === false || $root === '') ? null : $root;
    }
}
