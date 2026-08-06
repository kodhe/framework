<?php declare(strict_types=1);

/**
 * PHPUnit Bootstrap File for Kodhe Framework Tests
 * 
 * This file is responsible for:
 * - Loading composer autoloader
 * - Defining required constants
 * - Initializing test environment
 */

// Report all errors
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define path constants
if (!defined('APPPATH')) {
    define('APPPATH', dirname(__DIR__) . '/');
}

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/Framework/');
}

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

if (!defined('FCPATH')) {
    define('FCPATH', dirname(__DIR__) . '/');
}

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load helper functions that might be needed
$helpersPath = dirname(__DIR__) . '/Framework/Support/Helpers.php';
if (file_exists($helpersPath)) {
    require_once $helpersPath;
}

$commonPath = dirname(__DIR__) . '/Framework/Support/Legacy/common.php';
if (file_exists($commonPath)) {
    require_once $commonPath;
}

// Initialize any global state needed for tests
// This can be extended based on framework requirements

/**
 * Custom error handler for tests
 */
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return false;
    }
    
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Custom exception handler for tests
 */
set_exception_handler(function (\Throwable $exception) {
    echo "Uncaught exception: " . get_class($exception) . "\n";
    echo "Message: " . $exception->getMessage() . "\n";
    echo "Location: " . $exception->getFile() . ":" . $exception->getLine() . "\n";
    echo "\nStack trace:\n" . $exception->getTraceAsString() . "\n";
    exit(255);
});

// Log test start
echo "Kodhe Framework Test Suite Initialized\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Environment: " . ENVIRONMENT . "\n";
echo "Base Path: " . BASEPATH . "\n";
echo str_repeat("-", 50) . "\n";
