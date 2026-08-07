<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap for CodeIgniter 3 HTTP Component
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Autoload
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new RuntimeException('Please run "composer install" to install dependencies.');
}

require_once __DIR__ . '/../vendor/autoload.php';

// Define constants for testing
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/Fixtures/');
}

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Load helper functions
require_once __DIR__ . '/../src/helpers/url.php';

// Create a simple test case base class
class CI_TestCase extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
