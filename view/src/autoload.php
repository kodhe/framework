<?php
/**
 * Simple PSR-4 Autoloader for Kodhe Framework View
 * 
 * This is a fallback autoloader when composer is not available
 */

spl_autoload_register(function ($class) {
    // Base namespace
    $basePrefix = 'Kodhe\\Framework\\View\\';
    
    // Check if the class uses the base namespace
    $len = strlen($basePrefix);
    if (strncmp($basePrefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load helper files
require_once __DIR__ . '/helpers/template.php';
require_once __DIR__ . '/helpers/template_asset.php';
