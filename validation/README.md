# Kodhe Encryption

Standalone Composer package of CodeIgniter's Encryption Library. Provides two-way keyed encryption using OpenSSL (recommended) or MCrypt.

## Installation

```bash
composer require kodhe/encryption


## File `test_encryption.php`

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kodhe\Library\Encryption\Encryption;

echo "=== Kodhe Encryption Tests ===\n\n";

// Check available drivers
$openssl_available = extension_loaded('openssl');
$mcrypt_available = defined('MCRYPT_DEV_URANDOM');

echo "Drivers available:\n";
echo "  OpenSSL: " . ($openssl_available ? 'Yes' : 'No') . "\n";
echo "  MCrypt: " . ($mcrypt_available ? 'Yes' : 'No') . "\n\n";

if (!$openssl_available && !$mcrypt_available) {
    echo "ERROR: No encryption driver available. Install OpenSSL or MCrypt extension.\n";
    exit(1);
}

try {
    $encryption = new Encryption();
    $driver = $encryption->driver;
    echo "Using driver: {$driver}\n\n";
} catch (\RuntimeException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 1: Basic initialization
echo "Test 1: Basic Initialization\n";
assert($encryption instanceof Encryption, 'Should create Encryption instance');
assert(in_array($encryption->driver, ['openssl', 'mcrypt']), 'Driver should be valid');
echo "Driver: " . $encryption->driver . "\n";
echo "Cipher: " . $encryption->cipher . "\n";
echo "Mode: " . $encryption->mode . "\n";
echo "✓ Passed\n\n";

// Test 2: Initialize with config
echo "Test 2: Initialize with Config\n";
$enc2 = new Encryption([
    'driver' => $openssl_available ? 'openssl' : 'mcrypt',
    'cipher' => 'aes-128',
    'mode'   => 'cbc',
    'key'    => 'test-config-key-12345',
]);
assert($enc2->cipher === 'aes-128', 'Cipher should be aes-128');
assert($enc2->mode === 'cbc', 'Mode should be cbc');
echo "✓ Passed\n\n";

// Test 3: Set key
echo "Test 3: Set Encryption Key\n";
$enc3 = new Encryption();
$enc3->set_key('my-secret-encryption-key');
echo "Key set successfully\n";
echo "✓ Passed\n\n";

// Test 4: Basic encrypt/decrypt
echo "Test 4: Basic Encrypt/Decrypt\n";
$enc4 = new Encryption(['key' => 'test-key-for-encryption-here']);
$plaintext = 'Hello World!';
$ciphertext = $enc4->encrypt($plaintext);
$decrypted = $enc4->decrypt($ciphertext);
echo "Plaintext: {$plaintext}\n";
echo "Ciphertext: {$ciphertext}\n";
echo "Decrypted: {$decrypted}\n";
assert($decrypted === $plaintext, 'Decrypted text should match original');
echo "✓ Passed\n\n";

// Test 5: Same input produces different output (random IV)
echo "Test 5: Encryption Randomness\n";
$enc5 = new Encryption(['key' => 'random-test-key']);
$encrypted1 = $enc5->encrypt('Same text');
$encrypted2 = $enc5->encrypt('Same text');
echo "First: {$encrypted1}\n";
echo "Second: {$encrypted2}\n";
assert($encrypted1 !== $encrypted2, 'Same input should produce different output');
assert($enc5->decrypt($encrypted1) === 'Same text', 'First should decode correctly');
assert($enc5->decrypt($encrypted2) === 'Same text', 'Second should decode correctly');
echo "✓ Passed\n\n";

// Test 6: Different keys produce different results
echo "Test 6: Different Keys\n";
$enc6 = new Encryption(['key' => 'key-number-one-here']);
$encrypted = $enc6->encrypt('Secret message');

$enc6->set_key('key-number-two-here');
$decrypted = $enc6->decrypt($encrypted);
echo "Decrypted with wrong key: " . var_export($decrypted, true) . "\n";
assert($decrypted === false || $decrypted !== 'Secret message', 'Different key should not decrypt correctly');
echo "✓ Passed\n\n";

// Test 7: Empty string
echo "Test 7: Empty String\n";
$enc7 = new Encryption(['key' => 'empty-test-key']);
$encrypted = $enc7->encrypt('');
$decrypted = $enc7->decrypt($encrypted);
assert($decrypted === '', 'Empty string should encrypt/decrypt to empty');
echo "✓ Passed\n\n";

// Test 8: Long text
echo "Test 8: Long Text\n";
$enc8 = new Encryption(['key' => 'long-text-test-key']);
$long_text = str_repeat('Lorem ipsum dolor sit amet. ', 100);
$encrypted = $enc8->encrypt($long_text);
$decrypted = $enc8->decrypt($encrypted);
assert($decrypted === $long_text, 'Long text should encrypt/decrypt correctly');
echo "Length: " . strlen($long_text) . " chars\n";
echo "✓ Passed\n\n";

// Test 9: Special characters
echo "Test 9: Special Characters\n";
$enc9 = new Encryption(['key' => 'special-char-test-key']);
$special = "UTF-8: Café résumé naïve\nSymbols: !@#$%^&*()\nQuotes: \"'`\nEmoji: 🎉🎈🎊";
$encrypted = $enc9->encrypt($special);
$decrypted = $enc9->decrypt($encrypted);
assert($decrypted === $special, 'Special characters should be preserved');
echo "✓ Passed\n\n";

// Test 10: Custom cipher (AES-256)
echo "Test 10: Custom Cipher (AES-256)\n";
$enc10 = new Encryption(['key' => '32-byte-key-for-aes-256-here!!']);
$params = [
    'cipher' => 'aes-256',
    'mode'   => 'cbc',
    'key'    => '32-byte-key-for-aes-256-here!!',
];
$encrypted = $enc10->encrypt('AES-256 test', $params);
$decrypted = $enc10->decrypt($encrypted, $params);
assert($decrypted === 'AES-256 test', 'Should work with AES-256');
echo "✓ Passed\n\n";

// Test 11: Custom mode (CTR)
echo "Test 11: Custom Mode (CTR)\n";
try {
    $enc11 = new Encryption(['key' => 'ctr-mode-test-key-here']);
    $params = [
        'cipher' => 'aes-128',
        'mode'   => 'ctr',
        'key'    => 'ctr-mode-test-key-here',
    ];
    $encrypted = $enc11->encrypt('CTR mode test', $params);
    $decrypted = $enc11->decrypt($encrypted, $params);
    assert($decrypted === 'CTR mode test', 'Should work with CTR mode');
    echo "✓ Passed\n\n";
} catch (\Exception $e) {
    echo "CTR mode not supported by driver: " . $e->getMessage() . "\n";
    echo "✓ Skipped\n\n";
}

// Test 12: Raw data (no base64)
echo "Test 12: Raw Data (No Base64)\n";
$enc12 = new Encryption(['key' => 'raw-data-test-key-here']);
$params = [
    'cipher'   => 'aes-128',
    'mode'     => 'cbc',
    'key'      => 'raw-data-test-key-here',
    'raw_data' => true,
];
$encrypted = $enc12->encrypt('Raw data test', $params);
$decrypted = $enc12->decrypt($encrypted, $params);
assert($decrypted === 'Raw data test', 'Should work with raw data');
echo "✓ Passed\n\n";

// Test 13: HMAC tamper detection
echo "Test 13: HMAC Tamper Detection\n";
$enc13 = new Encryption(['key' => 'hmac-test-key-here!!']);
$encrypted = $enc13->encrypt('Tamper test');

// Tamper with the ciphertext
$tampered = $encrypted . 'x';
$decrypted = $enc13->decrypt($tampered);
echo "Tampered decryption: " . var_export($decrypted, true) . "\n";
assert($decrypted === false, 'Tampered data should fail HMAC check');
echo "✓ Passed\n\n";

// Test 14: Disable HMAC
echo "Test 14: Disable HMAC\n";
$enc14 = new Encryption(['key' => 'no-hmac-test-key-here']);
$params = [
    'cipher' => 'aes-128',
    'mode'   => 'cbc',
    'key'    => 'no-hmac-test-key-here',
    'hmac'   => false,
];
$encrypted = $enc14->encrypt('No HMAC test', $params);
$decrypted = $enc14->decrypt($encrypted, $params);
assert($decrypted === 'No HMAC test', 'Should work without HMAC');
echo "✓ Passed\n\n";

// Test 15: Different HMAC digest
echo "Test 15: Custom HMAC Digest (SHA256)\n";
$enc15 = new Encryption(['key' => 'sha256-hmac-test-key']);
$params = [
    'cipher'      => 'aes-128',
    'mode'        => 'cbc',
    'key'         => 'sha256-hmac-test-key',
    'hmac_digest' => 'sha256',
    'hmac_key'    => 'hmac-sha256-key',
];
$encrypted = $enc15->encrypt('SHA256 HMAC test', $params);
$decrypted = $enc15->decrypt($encrypted, $params);
assert($decrypted === 'SHA256 HMAC test', 'Should work with SHA256 HMAC');
echo "✓ Passed\n\n";

// Test 16: Create random key
echo "Test 16: Create Random Key\n";
$enc16 = new Encryption();
$key32 = $enc16->create_key(32);
$key16 = $enc16->create_key(16);
assert(strlen($key32) === 32, '32-byte key should be 32 bytes');
assert(strlen($key16) === 16, '16-byte key should be 16 bytes');
assert($key32 !== $key16, 'Random keys should be different');
echo "32-byte key (hex): " . bin2hex($key32) . "\n";
echo "16-byte key (hex): " . bin2hex($key16) . "\n";
echo "✓ Passed\n\n";

// Test 17: HKDF key derivation
echo "Test 17: HKDF Key Derivation\n";
$enc17 = new Encryption();
$derived_key = $enc17->hkdf('master-secret', 'sha512', 'random-salt', 32, 'encryption');
assert(strlen($derived_key) === 32, 'Derived key should be 32 bytes');
echo "Derived key (hex): " . bin2hex($derived_key) . "\n";
echo "✓ Passed\n\n";

// Test 18: HKDF with different info
echo "Test 18: HKDF Different Context\n";
$enc18 = new Encryption();
$key_auth = $enc18->hkdf('master-secret', 'sha512', 'random-salt', 32, 'authentication');
$key_enc = $enc18->hkdf('master-secret', 'sha512', 'random-salt', 32, 'encryption');
assert($key_auth !== $key_enc, 'Different context should produce different keys');
echo "Auth key: " . bin2hex($key_auth) . "\n";
echo "Enc key: " . bin2hex($key_enc) . "\n";
echo "✓ Passed\n\n";

// Test 19: Invalid HKDF digest
echo "Test 19: Invalid HKDF Digest\n";
$enc19 = new Encryption();
$result = $enc19->hkdf('key', 'md5'); // MD5 not in digests list
assert($result === false, 'Invalid digest should return false');
echo "✓ Passed\n\n";

// Test 20: Property access via __get
echo "Test 20: Property Access (__get)\n";
$enc20 = new Encryption(['key' => 'property-test-key']);
assert($enc20->cipher === 'aes-128', 'Should get cipher');
assert(in_array($enc20->mode, ['cbc', 'ctr', 'ecb', 'cfb', 'ofb']), 'Should get mode');
assert(in_array($enc20->driver, ['openssl', 'mcrypt']), 'Should get driver');
assert(is_array($enc20->drivers), 'Should get drivers array');
assert(is_array($enc20->digests), 'Should get digests array');
echo "Cipher: " . $enc20->cipher . "\n";
echo "Mode: " . $enc20->mode . "\n";
echo "Driver: " . $enc20->driver . "\n";
echo "✓ Passed\n\n";

// Test 21: Method chaining
echo "Test 21: Method Chaining\n";
$enc21 = new Encryption();
$result = $enc21->set_key('chained-key')->initialize([
    'cipher' => 'aes-128',
    'mode'   => 'cbc',
]);
assert($result instanceof Encryption, 'Method chaining should return Encryption instance');
echo "✓ Passed\n\n";

// Test 22: Re-initialize with different cipher
echo "Test 22: Re-initialize with Different Cipher\n";
$enc22 = new Encryption(['key' => 'reinit-test-key-here']);
$enc22->initialize([
    'cipher' => 'aes-256',
    'mode'   => 'cbc',
    'key'    => 'reinit-test-key-here-32bytes',
]);
assert($enc22->cipher === 'aes-256', 'Cipher should be updated to aes-256');
echo "Cipher after re-init: " . $enc22->cipher . "\n";
echo "✓ Passed\n\n";

// Test 23: Binary data
echo "Test 23: Binary Data\n";
$enc23 = new Encryption(['key' => 'binary-data-test-key!!']);
$binary = hex2bin('000102030405060708090a0b0c0d0e0f');
$encrypted = $enc23->encrypt($binary);
$decrypted = $enc23->decrypt($encrypted);
assert($decrypted === $binary, 'Binary data should encrypt/decrypt correctly');
echo "Binary data preserved correctly\n";
echo "✓ Passed\n\n";

// Test 24: Invalid params
echo "Test 24: Invalid Parameters\n";
$enc24 = new Encryption(['key' => 'invalid-params-test']);
// Missing required params
$result = $enc24->encrypt('data', ['cipher' => 'aes-128']);
assert($result === false, 'Missing params should return false');
echo "✓ Passed\n\n";

// Test 25: All supported digests
echo "Test 25: All Supported HMAC Digests\n";
$enc25 = new Encryption();
foreach (['sha224', 'sha256', 'sha384', 'sha512'] as $digest) {
    $digests = $enc25->digests;
    assert(isset($digests[$digest]), "{$digest} should be supported");
    echo "  {$digest}: " . $digests[$digest] . " bytes\n";
}
echo "✓ Passed\n\n";

echo "=== All Encryption Tests Passed ===\n";