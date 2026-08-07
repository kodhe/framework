<?php

namespace Kodhe\Email\Encoding;

/**
 * Interface untuk Encoder
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface EncoderInterface
{
    /**
     * Encode string
     *
     * @param string $data
     * @return string
     */
    public function encode(string $data): string;

    /**
     * Decode string
     *
     * @param string $data
     * @return string
     */
    public function decode(string $data): string;
}
