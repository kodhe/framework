<?php

declare(strict_types=1);

namespace Kodhe\Framework\Encryption\Message;

/**
 * Class EncryptedPayload
 *
 * Value object representing an encrypted payload with IV, ciphertext, and optional tag/HMAC
 *
 * @package     Kodhe\Encryption
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
final class EncryptedPayload
{
    /**
     * Initialization Vector
     *
     * @var string
     */
    private string $iv;

    /**
     * Encrypted ciphertext
     *
     * @var string
     */
    private string $ciphertext;

    /**
     * Authentication tag (for GCM mode)
     *
     * @var string|null
     */
    private ?string $tag;

    /**
     * HMAC signature (for CBC+HMAC mode)
     *
     * @var string|null
     */
    private ?string $hmac;

    /**
     * Constructor
     *
     * @param string $iv Initialization Vector
     * @param string $ciphertext Encrypted ciphertext
     * @param string|null $tag Authentication tag (GCM mode)
     * @param string|null $hmac HMAC signature (CBC+HMAC mode)
     */
    public function __construct(
        string $iv,
        string $ciphertext,
        ?string $tag = null,
        ?string $hmac = null
    ) {
        $this->iv = $iv;
        $this->ciphertext = $ciphertext;
        $this->tag = $tag;
        $this->hmac = $hmac;
    }

    /**
     * Get the Initialization Vector
     *
     * @return string
     */
    public function getIv(): string
    {
        return $this->iv;
    }

    /**
     * Get the ciphertext
     *
     * @return string
     */
    public function getCiphertext(): string
    {
        return $this->ciphertext;
    }

    /**
     * Get the authentication tag (GCM mode)
     *
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * Get the HMAC signature (CBC+HMAC mode)
     *
     * @return string|null
     */
    public function getHmac(): ?string
    {
        return $this->hmac;
    }

    /**
     * Check if this payload uses GCM mode (has a tag)
     *
     * @return bool
     */
    public function isGcmMode(): bool
    {
        return $this->tag !== null;
    }

    /**
     * Check if this payload uses CBC+HMAC mode (has an HMAC)
     *
     * @return bool
     */
    public function isHmacMode(): bool
    {
        return $this->hmac !== null;
    }

    /**
     * Combine payload components into binary string
     * Structure: IV + (TAG or HMAC) + Ciphertext
     *
     * @return string Binary representation of the payload
     */
    public function toBinary(): string
    {
        if ($this->isGcmMode()) {
            return $this->iv . $this->tag . $this->ciphertext;
        }

        if ($this->isHmacMode()) {
            return $this->iv . $this->hmac . $this->ciphertext;
        }

        // No authentication: just IV + ciphertext
        return $this->iv . $this->ciphertext;
    }

    /**
     * Parse binary payload into EncryptedPayload object
     *
     * @param string $binary Binary payload data
     * @param int $ivLength Length of the IV
     * @param int $tagLength Length of the tag/HMAC (default 16 for GCM, variable for HMAC)
     * @param bool $isGcmMode Whether this is GCM mode (uses fixed 16-byte tag)
     * @return self
     */
    public static function fromBinary(
        string $binary,
        int $ivLength,
        int $tagLength = 16,
        bool $isGcmMode = false
    ): self {
        $iv = substr($binary, 0, $ivLength);

        if ($isGcmMode) {
            $tag = substr($binary, $ivLength, 16);
            $ciphertext = substr($binary, $ivLength + 16);
            return new self($iv, $ciphertext, $tag, null);
        }

        // For HMAC mode, tagLength should be provided
        $hmac = substr($binary, $ivLength, $tagLength);
        $ciphertext = substr($binary, $ivLength + $tagLength);
        return new self($iv, $ciphertext, null, $hmac);
    }
}
