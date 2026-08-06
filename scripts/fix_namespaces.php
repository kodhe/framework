<?php declare(strict_types=1);

/**
 * Script to fix namespace declarations to PSR modern standard
 * Converts: <?php namespace Kodhe\...
 * To:       <?php declare(strict_types=1);\n\nnamespace Kodhe\...
 */

$rootDir = __DIR__ . '/..';
$phpFiles = array_merge(
    glob($rootDir . '/Framework/**/*.php'),
    glob($rootDir . '/Library/**/*.php'),
    glob($rootDir . '/Controllers/**/*.php')
);

$fixedCount = 0;
$errorCount = 0;

foreach ($phpFiles as $file) {
    if (strpos($file, '/vendor/') !== false) {
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Skip if already has strict_types
    if (strpos($content, 'declare(strict_types=1)') !== false) {
        continue;
    }
    
    // Fix <?php namespace pattern
    if (preg_match('/^<\?php namespace/', $content)) {
        $content = preg_replace(
            '/^<\?php namespace/',
            "<?php declare(strict_types=1);\n\nnamespace",
            $content
        );
        $fixedCount++;
    } elseif (preg_match('/^<\?php\s+namespace/', $content)) {
        $content = preg_replace(
            '/^<\?php\s+namespace/',
            "<?php declare(strict_types=1);\n\nnamespace",
            $content
        );
        $fixedCount++;
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
    }
}

echo "\nCompleted: Fixed $fixedCount files\n";
