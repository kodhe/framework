<?php

declare(strict_types=1);

namespace Kodhe\Encryption\Key;

/**
 * Class KeyDeriver
 *
 * Implements HKDF (HMAC-based Extract-and-Expand Key Derivation Function)
 * as per RFC 5869
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 * @link        https://tools.ietf.org/rfc/rfc5869.txt
 */
class KeyDeriver
{
    /**
     * Supported HMAC digest algorithms with their output sizes in bytes
     *
     * @var array<string, int>
     */
    private array $digests = [
        'sha224' => 28,
        'sha256' => 32,
        'sha384' => 48,
        'sha512' => 64,
    ];

    /**
     * HKDF - HMAC-based Extract-and-Expand Key Derivation Function
     *
     * @param string $key Input key (IKM - Input Keying Material)
     * @param string $digest A SHA-2 hashing algorithm (e.g., sha256, sha512)
     * @param string|null $salt Optional salt value (if not provided, a string of zeros is used)
     * @param int|null $length Output length in bytes (defaults to the selected digest size)
     * @param string $info Optional context/application-specific info
     * @return string|false A pseudo-random key or FALSE on failure
     */
    public function hkdf(
        string $key,
        string $digest = 'sha512',
        ?string $salt = null,
        ?int $length = null,
        string $info = ''
    ) {
        // Validate digest algorithm
        if (!isset($this->digests[$digest])) {
            return false;
        }

        // Set default length to digest size if not specified
        if ($length === null || !is_int($length)) {
            $length = $this->digests[$digest];
        }

        // Maximum output length is 255 * hash length
        if ($length > (255 * $this->digests[$digest])) {
            return false;
        }

        // If no salt is provided, use a string of zeros
        if ($salt === null || strlen($salt) === 0) {
            $salt = str_repeat("\0", $this->digests[$digest]);
        }

        // Step 1: Extract - create a fixed-length pseudorandom key (PRK)
        $prk = hash_hmac($digest, $key, $salt, true);

        // Step 2: Expand - expand the PRK to the desired length
        $key = '';
        $keyBlock = '';
        $blockIndex = 1;

        while (strlen($key) < $length) {
            $keyBlock = hash_hmac($digest, $keyBlock . $info . chr($blockIndex), $prk, true);
            $key .= $keyBlock;
            $blockIndex++;
        }

        // Return only the requested number of bytes
        return substr($key, 0, $length);
    }

    /**
     * Get supported digest algorithms
     *
     * @return array<string, int> Array of digest name => output size pairs
     */
    public function getSupportedDigests(): array
    {
        return $this->digests;
    }

    /**
     * Check if a digest algorithm is supported
     *
     * @param string $digest Digest algorithm name
     * @return bool True if supported
     */
    public function isDigestSupported(string $digest): bool
    {
        return isset($this->digests[$digest]);
    }

    /**
     * Get the output size for a digest algorithm
     *
     * @param string $digest Digest algorithm name
     * @return int|null Output size in bytes or null if not supported
     */
    public function getDigestSize(string $digest): ?int
    {
        return $this->digests[$digest] ?? null;
    }
}
