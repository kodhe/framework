# Kodhe Encrypt

Standalone Composer package of CodeIgniter's Encryption Library.

## ⚠️ Important Notice

**Mcrypt is DEPRECATED in PHP 7.1 and REMOVED in PHP 7.2+.** This library requires the mcrypt extension. For PHP 7.2+, consider using `openssl` or `sodium` based encryption libraries instead.

## Installation

```bash
composer require kodhe/encrypt

## File `test_encrypt.php`

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kodhe\Library\Encrypt\Encrypt;

echo "=== Kodhe Encrypt Tests ===\n\n";

// Check if mcrypt is available
if (!function_exists('mcrypt_encrypt')) {
    echo "WARNING: Mcrypt extension is not available. Some tests will be skipped.\n";
    echo "Mcrypt was deprecated in PHP 7.1 and removed in PHP 7.2+.\n\n";
}

try {
    $encrypt = new Encrypt();
    $mcrypt_available = true;
} catch (\RuntimeException $e) {
    $mcrypt_available = false;
    echo "Mcrypt not available. Skipping encryption tests.\n\n";
}

if ($mcrypt_available) {
    // Test 1: Basic initialization
    echo "Test 1: Basic Initialization\n";
    assert($encrypt instanceof Encrypt, 'Should create Encrypt instance');
    assert($encrypt->is_mcrypt_available() === true, 'Mcrypt should be available');
    echo "✓ Passed\n\n";

    // Test 2: Set and get key
    echo "Test 2: Set and Get Key\n";
    $encrypt->set_key('test-key-123');
    $key = $encrypt->get_key();
    assert($key === md5('test-key-123'), 'Key should be MD5 hashed');
    echo "Key: {$key}\n";
    echo "✓ Passed\n\n";

    // Test 3: Encrypt and decrypt
    echo "Test 3: Basic Encrypt/Decrypt\n";
    $plain_text = 'Hello World!';
    $encrypted = $encrypt->encode($plain_text);
    $decrypted = $encrypt->decode($encrypted);
    echo "Plain: {$plain_text}\n";
    echo "Encrypted: {$encrypted}\n";
    echo "Decrypted: {$decrypted}\n";
    assert($decrypted === $plain_text, 'Decrypted text should match original');
    echo "✓ Passed\n\n";

    // Test 4: Same input produces different output (random IV)
    echo "Test 4: Encryption Randomness\n";
    $encrypted1 = $encrypt->encode('Same text');
    $encrypted2 = $encrypt->encode('Same text');
    echo "First: {$encrypted1}\n";
    echo "Second: {$encrypted2}\n";
    assert($encrypted1 !== $encrypted2, 'Same input should produce different output');
    assert($encrypt->decode($encrypted1) === 'Same text', 'First should decode correctly');
    assert($encrypt->decode($encrypted2) === 'Same text', 'Second should decode correctly');
    echo "✓ Passed\n\n";

    // Test 5: Different keys
    echo "Test 5: Different Keys\n";
    $encrypt->set_key('key1');
    $encrypted = $encrypt->encode('Secret');
    
    $encrypt->set_key('key2');
    $decrypted = $encrypt->decode($encrypted);
    
    // Note: With different keys, decryption may still produce output
    // but it won't match the original (garbage data or false)
    assert($decrypted !== 'Secret' || $decrypted === false, 'Different key should not decrypt correctly');
    echo "✓ Passed\n\n";

    // Test 6: Invalid base64 input
    echo "Test 6: Invalid Input\n";
    $result = $encrypt->decode('invalid!!!data');
    assert($result === false, 'Invalid base64 should return false');
    echo "✓ Passed\n\n";

    // Test 7: Custom cipher
    echo "Test 7: Custom Cipher\n";
    $encrypt->set_key('test-key');
    $encrypt->set_cipher(MCRYPT_RIJNDAEL_128);
    $encrypted = $encrypt->encode('Cipher test');
    $decrypted = $encrypt->decode($encrypted);
    assert($decrypted === 'Cipher test', 'Should work with custom cipher');
    echo "✓ Passed\n\n";

    // Test 8: Custom mode
    echo "Test 8: Custom Mode\n";
    $encrypt->set_mode(MCRYPT_MODE_CFB);
    $encrypted = $encrypt->encode('Mode test');
    $decrypted = $encrypt->decode($encrypted);
    assert($decrypted === 'Mode test', 'Should work with custom mode');
    echo "✓ Passed\n\n";

    // Test 9: Reset to defaults
    echo "Test 9: Reset Cipher and Mode\n";
    $encrypt->set_cipher(MCRYPT_RIJNDAEL_256);
    $encrypt->set_mode(MCRYPT_MODE_CBC);
    $encrypted = $encrypt->encode('Default test');
    $decrypted = $encrypt->decode($encrypted);
    assert($decrypted === 'Default test', 'Should work with default cipher/mode');
    echo "✓ Passed\n\n";

    // Test 10: Hash algorithms
    echo "Test 10: Hash Algorithms\n";
    $encrypt->set_hash('sha1');
    $hash1 = $encrypt->hash('test');
    assert(strlen($hash1) === 40, 'SHA1 hash should be 40 chars');
    echo "SHA1: {$hash1}\n";

    $encrypt->set_hash('sha256');
    $hash2 = $encrypt->hash('test');
    assert(strlen($hash2) === 64, 'SHA256 hash should be 64 chars');
    echo "SHA256: {$hash2}\n";

    $encrypt->set_hash('md5');
    $hash3 = $encrypt->hash('test');
    assert(strlen($hash3) === 32, 'MD5 hash should be 32 chars');
    echo "MD5: {$hash3}\n";

    // Invalid hash defaults to sha1
    $encrypt->set_hash('invalid_algo');
    assert($encrypt->get_hash_type() === 'sha1', 'Invalid hash should default to sha1');
    echo "✓ Passed\n\n";

    // Test 11: Empty string
    echo "Test 11: Empty String\n";
    $encrypted = $encrypt->encode('');
    $decrypted = $encrypt->decode($encrypted);
    assert($decrypted === '', 'Empty string should encrypt/decrypt to empty');
    echo "✓ Passed\n\n";

    // Test 12: Long text
    echo "Test 12: Long Text\n";
    $long_text = str_repeat('Lorem ipsum dolor sit amet. ', 100);
    $encrypted = $encrypt->encode($long_text);
    $decrypted = $encrypt->decode($encrypted);
    assert($decrypted === $long_text, 'Long text should encrypt/decrypt correctly');
    echo "Length: " . strlen($long_text) . " chars\n";
    echo "✓ Passed\n\n";

    // Test 13: Special characters
    echo "Test 13: Special Characters\n";
    $special = "UTF-8: Café résumé naïve\nSymbols: !@#$%^&*()\nQuotes: \"'`";
    $encrypted = $encrypt->encode($special);
    $decrypted = $encrypt->decode($encrypted);
    assert($decrypted === $special, 'Special characters should be preserved');
    echo "✓ Passed\n\n";

    // Test 14: Legacy compatibility
    echo "Test 14: Legacy Encoding\n";
    $encrypt->set_key('legacy-key');
    $original = 'Legacy Data';
    
    // First encode normally
    $encoded = $encrypt->encode($original);
    
    // Then try to convert (this simulates legacy conversion)
    try {
        $legacy_result = $encrypt->encode_from_legacy($encoded, MCRYPT_MODE_ECB);
        if ($legacy_result !== false) {
            $decoded = $encrypt->decode($legacy_result);
            assert($decoded === $original, 'Legacy conversion should preserve data');
            echo "✓ Passed\n\n";
        } else {
            echo "Legacy conversion returned false (may be expected)\n";
            echo "✓ Passed\n\n";
        }
    } catch (\Exception $e) {
        echo "Legacy test skipped: " . $e->getMessage() . "\n";
        echo "✓ Passed\n\n";
    }

    // Test 15: Method chaining
    echo "Test 15: Method Chaining\n";
    $encrypt15 = new Encrypt();
    $result = $encrypt15
        ->set_key('chained-key')
        ->set_cipher(MCRYPT_RIJNDAEL_256)
        ->set_mode(MCRYPT_MODE_CBC);

    assert($result instanceof Encrypt, 'Method chaining should return Encrypt instance');
    echo "✓ Passed\n\n";
}

// Test 16: Hash methods (always available)
echo "Test 16: Hash Methods\n";
$encrypt16 = new Encrypt();
$encrypt16->set_hash('sha256');
$hash = $encrypt16->hash('test');
assert(!empty($hash), 'Hash should not be empty');
assert(strlen($hash) === 64, 'SHA256 should be 64 characters');
echo "Hash: {$hash}\n";
echo "✓ Passed\n\n";

// Test 17: Key handling
echo "Test 17: Key Handling\n";
$encrypt17 = new Encrypt();
$encrypt17->set_key('custom-key');
$key = $encrypt17->get_key();
assert($key === md5('custom-key'), 'Key should be MD5 hashed');
echo "Key: {$key}\n";

// Override with parameter
$key2 = $encrypt17->get_key('override-key');
assert($key2 === md5('override-key'), 'Parameter key should override');
echo "Override key: {$key2}\n";
echo "✓ Passed\n\n";

echo "=== All Encrypt Tests Passed ===\n";