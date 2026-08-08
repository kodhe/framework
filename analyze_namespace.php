<?php
/**
 * Script untuk menganalisis konsistensi namespace Kodhe di semua file PHP
 */

$files = [];
exec('find /workspace -type f -name "*.php"', $files);

$results = [
    'namespace_declarations' => [],
    'use_statements' => [],
    'fully_qualified_calls' => [],
    'string_references' => [],
    'inconsistencies' => []
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $relativePath = str_replace('/workspace/', '', $file);
    
    // 1. Cek namespace declaration
    if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
        $ns = trim($matches[1]);
        if (stripos($ns, 'kodhe') === 0) {
            $results['namespace_declarations'][$relativePath] = $ns;
        }
    }
    
    // 2. Cek use statements dengan Kodhe
    if (preg_match_all('/^\s*use\s+([^;]+);/m', $content, $matches)) {
        foreach ($matches[1] as $useStmt) {
            $useStmt = trim($useStmt);
            if (stripos($useStmt, 'Kodhe\\') === 0 || stripos($useStmt, '\\Kodhe\\') === 0) {
                if (!isset($results['use_statements'][$relativePath])) {
                    $results['use_statements'][$relativePath] = [];
                }
                $results['use_statements'][$relativePath][] = $useStmt;
            }
        }
    }
    
    // 3. Cek fully qualified calls (\Kodhe\...)
    if (preg_match_all('/\\\\Kodhe\\\\[a-zA-Z0-9_\\\\]+/i', $content, $matches)) {
        foreach ($matches[0] as $call) {
            if (!isset($results['fully_qualified_calls'][$relativePath])) {
                $results['fully_qualified_calls'][$relativePath] = [];
            }
            if (!in_array($call, $results['fully_qualified_calls'][$relativePath])) {
                $results['fully_qualified_calls'][$relativePath][] = $call;
            }
        }
    }
    
    // 4. Cek string references ('Kodhe\...' atau "Kodhe\...")
    if (preg_match_all('/[\'"]Kodhe\\\\[^\'"]*[\'"]/i', $content, $matches)) {
        foreach ($matches[0] as $strRef) {
            if (!isset($results['string_references'][$relativePath])) {
                $results['string_references'][$relativePath] = [];
            }
            if (!in_array($strRef, $results['string_references'][$relativePath])) {
                $results['string_references'][$relativePath][] = $strRef;
            }
        }
    }
}

// Analisis inkonsistensi
echo "=== ANALISIS KONSISTENSI NAMESPACE KODHE ===\n\n";

echo "1. FILE DENGAN NAMESPACE DECLARATION:\n";
echo "   Total: " . count($results['namespace_declarations']) . " file\n\n";

echo "2. FILE DENGAN USE STATEMENTS (Kodhe):\n";
echo "   Total: " . count($results['use_statements']) . " file\n\n";

echo "3. FILE DENGAN FULLY QUALIFIED CALLS (\\Kodhe\\...):\n";
echo "   Total: " . count($results['fully_qualified_calls']) . " file\n";
foreach (array_keys($results['fully_qualified_calls']) as $file) {
    echo "   - $file\n";
}
echo "\n";

echo "4. FILE DENGAN STRING REFERENCES ('Kodhe\\...'):\n";
echo "   Total: " . count($results['string_references']) . " file\n";
foreach (array_keys($results['string_references']) as $file) {
    echo "   - $file\n";
}
echo "\n";

// Deteksi potensi inkonsistensi
echo "5. POTENSI INKONSISTENSI:\n";
foreach ($results['fully_qualified_calls'] as $file => $calls) {
    // Cek apakah file ini sudah punya use statement yang sesuai
    if (isset($results['use_statements'][$file])) {
        foreach ($calls as $call) {
            $callShort = substr($call, 1); // Hilangkan backslash awal
            $foundUse = false;
            foreach ($results['use_statements'][$file] as $useStmt) {
                if (strpos($useStmt, $callShort) === 0 || strpos($useStmt, class_basename($callShort)) !== false) {
                    $foundUse = true;
                    break;
                }
            }
            if (!$foundUse) {
                echo "   [WARNING] $file menggunakan $call tanpa use statement\n";
            }
        }
    }
}

echo "\n=== SELESAI ===\n";

function class_basename($class) {
    $parts = explode('\\', $class);
    return end($parts);
}
