<?php

/**
 * PHPUnit Bootstrap File for Kodhe Routing Component
 * 
 * This file sets up the testing environment including:
 * - Autoloading via Composer
 * - Mock CodeIgniter 3 environment
 * - Test helpers and utilities
 */

// Load Composer autoloader
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new RuntimeException('Please run "composer install" to install dependencies');
}

require_once __DIR__ . '/../vendor/autoload.php';

// Define constants for testing
if (!defined('APPPATH')) {
    define('APPPATH', __DIR__ . '/Fixtures/');
}

if (!defined('STORAGEPATH')) {
    define('STORAGEPATH', __DIR__ . '/Fixtures/storage/');
}

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Create storage directory if not exists
if (!is_dir(STORAGEPATH)) {
    mkdir(STORAGEPATH, 0755, true);
}

if (!is_dir(STORAGEPATH . 'cache/')) {
    mkdir(STORAGEPATH . 'cache/', 0755, true);
}

// Mock CodeIgniter superglobal
class MockCodeIgniter {
    public $config;
    
    public function __construct() {
        $this->config = new MockConfig();
    }
    
    public function config_item($key) {
        return $this->config->item($key);
    }
}

class MockConfig {
    protected $items = [
        'cache_path' => '',
        'base_url' => 'http://localhost/',
        'index_page' => 'index.php',
    ];
    
    public function item($key) {
        return $this->items[$key] ?? null;
    }
    
    public function set_item($key, $value) {
        $this->items[$key] = $value;
    }
}

// Global app() helper for testing
function app() {
    static $instance = null;
    if ($instance === null) {
        $instance = new MockCodeIgniter();
    }
    return $instance;
}

// Mock site_url function
if (!function_exists('site_url')) {
    function site_url($uri = '') {
        $base = 'http://localhost/';
        return $base . ltrim($uri, '/');
    }
}

// Mock redirect function
if (!function_exists('redirect')) {
    function redirect($uri = '', $status = 302) {
        return new MockRedirectResponse($uri, $status);
    }
}

class MockRedirectResponse {
    public $uri;
    public $status;
    
    public function __construct($uri, $status) {
        $this->uri = $uri;
        $this->status = $status;
    }
}

// Mock view function
if (!function_exists('view')) {
    function view($name, $data = []) {
        return "[View: {$name}]";
    }
}

// Mock url function
if (!function_exists('url')) {
    function url($uri = '') {
        return 'http://localhost/' . ltrim($uri, '/');
    }
}

// Mock log_message function
if (!function_exists('log_message')) {
    function log_message($level, $message) {
        // Silent in tests
    }
}

// Mock config function
if (!function_exists('config')) {
    function config($key, $default = null) {
        return $default;
    }
}

// Mock is_cli function
if (!function_exists('is_cli')) {
    function is_cli() {
        return true;
    }
}

// Mock resolve_path function  
if (!function_exists('resolve_path')) {
    function resolve_path(...$paths) {
        return implode('/', array_map(function($path) {
            return rtrim($path, '/\\');
        }, $paths));
    }
}
