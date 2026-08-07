<?php

namespace Kodhe\Email\Encoding;

/**
 * Quoted-Printable Encoder
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class QuotedPrintableEncoder implements EncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode(string $data): string
    {
        return quoted_printable_encode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(string $data): string
    {
        return quoted_printable_decode($data);
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
        
        // Split into lines of max 76 characters
        $lines = explode("\r\n", $encoded);
        $result = [];
        
        foreach ($lines as $line) {
            while (strlen($line) > 76) {
                // Find a safe breaking point
                $breakAt = 75;
                while ($breakAt > 0 && substr($line, $breakAt, 1) !== '=') {
                    $breakAt--;
                }
                
                if ($breakAt === 0) {
                    $breakAt = 75;
                }
                
                $result[] = substr($line, 0, $breakAt) . "=\r\n";
                $line = substr($line, $breakAt);
            }
            
            if (!empty($line)) {
                $result[] = $line;
            }
        }
        
        return implode("\r\n", $result);
    }
}
