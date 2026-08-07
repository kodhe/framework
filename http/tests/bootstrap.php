<?php

/**
 * PHPUnit Bootstrap File
 * 
 * This file is responsible for setting up the testing environment
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define constants
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__) . DS);
defined('SRC_PATH') or define('SRC_PATH', ROOT_PATH . 'src' . DS);
defined('TESTS_PATH') or define('TESTS_PATH', ROOT_PATH . 'tests' . DS);

// Load Composer autoloader
if (file_exists(ROOT_PATH . 'vendor/autoload.php')) {
    require_once ROOT_PATH . 'vendor/autoload.php';
} else {
    throw new RuntimeException('Composer autoload not found. Run "composer install" first.');
}

// Register test namespaces
spl_autoload_register(function ($class) {
    $prefix = 'Kodhe\\Framework\\Http\\Tests\\';
    $base_dir = TESTS_PATH;
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Create necessary directories
$dirs = [
    TESTS_PATH . 'Fixtures/Controllers',
    TESTS_PATH . 'Fixtures/Routes',
    ROOT_PATH . 'build/coverage'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

echo "PHPUnit bootstrap loaded successfully.\n";
