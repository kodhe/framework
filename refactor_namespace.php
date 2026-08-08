<?php

$excludeDir = '/framework/';
$files = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('/workspace')
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        if (strpos($path, $excludeDir) === false) {
            $files[] = $path;
        }
    }
}

echo "Found " . count($files) . " PHP files to process\n";

$processed = 0;
$modified = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Skip jika sudah ada Kodhe\Framework
    if (strpos($content, 'Kodhe\Framework\Framework') !== false) {
        continue;
    }
    
    // Pattern untuk namespace declaration
    $content = preg_replace(
        '/namespace\s+Kodhe\\\\(?!Framework\\\\)/',
        'namespace Kodhe\Framework\\Framework\\',
        $content
    );
    
    // Pattern untuk use statements
    $content = preg_replace(
        '/use\s+Kodhe\\\\(?!Framework\\\\)/',
        'use Kodhe\\Framework\\',
        $content
    );
    
    // Pattern untuk fully qualified calls (\Kodhe\Framework\...)
    $content = preg_replace(
        '/\\\\Kodhe\Framework\\\\(?!Framework\\\\)/',
        '\\Kodhe\Framework\\Framework\\',
        $content
    );
    
    // Pattern untuk string references 'Kodhe\...' atau "Kodhe\..."
    // Handle single quotes
    $content = preg_replace(
        "/'Kodhe\Framework\\\\\\\(?!Framework\\\\\\\\)/",
        "'Kodhe\\Framework\\",
        $content
    );
    
    // Handle double quotes
    $content = preg_replace(
        '/"Kodhe\Framework\\\\\\\(?!Framework\\\\\\\\)/',
        '"Kodhe\\Framework\\',
        $content
    );
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        $modified++;
        echo "Modified: $file\n";
    }
    
    $processed++;
    if ($processed % 100 === 0) {
        echo "Processed $processed files...\n";
    }
}

echo "\n=== Summary ===\n";
echo "Total files processed: $processed\n";
echo "Files modified: $modified\n";
echo "Files unchanged: " . ($processed - $modified) . "\n";
