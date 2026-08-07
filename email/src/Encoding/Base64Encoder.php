<?php

namespace Kodhe\Email\Encoding;

/**
 * Base64 Encoder
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class Base64Encoder implements EncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(string $data): string
    {
        return base64_decode($data);
    }

    /**
     * Encode with line wrapping (76 chars max)
     *
     * @param string $data
     * @return string
     */
    public function encodeWrapped(string $data): string
    {
        $encoded = $this->encode($data);
        return chunk_split($encoded, 76, "\r\n");
    }
}
