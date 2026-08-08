<?php
/**
 * Bootstrap file for CI3 Compatibility Tests
 * 
 * This bootstrap defines necessary constants and initializes
 * the environment to mimic CodeIgniter 3 behavior
 */

// Define CodeIgniter-style constants
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../framework/src/');
}

if (!defined('APPPATH')) {
    define('APPPATH', __DIR__ . '/../../application/');
}

if (!defined('VIEWPATH')) {
    define('VIEWPATH', __DIR__ . '/../../application/views/');
}

if (!defined('LOGPATH')) {
    define('LOGPATH', sys_get_temp_dir() . '/logs/');
}

if (!defined('CACHEPATH')) {
    define('CACHEPATH', sys_get_temp_dir() . '/cache/');
}

if (!defined('FCPATH')) {
    define('FCPATH', __DIR__ . '/../../');
}

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Create necessary directories
@mkdir(LOGPATH, 0777, true);
@mkdir(CACHEPATH, 0777, true);
@mkdir(APPPATH, 0777, true);
@mkdir(VIEWPATH, 0777, true);

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Load CI3 compatibility helpers if they exist
$compatFiles = [
    __DIR__ . '/../../framework/src/Support/Legacy/common.php',
    __DIR__ . '/../../framework/src/Support/Helpers.php',
];

foreach ($compatFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '0');

// Set timezone
date_default_timezone_set('UTC');
