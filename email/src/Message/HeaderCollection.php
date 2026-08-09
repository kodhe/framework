<?php

declare(strict_types=1);

namespace Kodhe\Framework\Email\Message;

/**
 * Collection class for managing email headers
 *
 * @package     Kodhe\Email
 * @author      CodeIgniter Team
 * @version     2.0.0
 * @license     MIT
 */
class HeaderCollection
{
    /**
     * @var array<string, string|array> Stored headers
     */
    private array $headers = [];

    /**
     * @var string Newline character
     */
    private string $newline = "\n";

    /**
     * Constructor
     *
     * @param string $newline
     */
    public function __construct(string $newline = "\n")
    {
        $this->newline = $newline;
    }

    /**
     * Set a header value
     *
     * @param string $name
     * @param string|array $value
     * @return self
     */
    public function set(string $name, $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get a header value
     *
     * @param string $name
     * @return string|array|null
     */
    public function get(string $name)
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Check if a header exists
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * Remove a header
     *
     * @param string $name
     * @return self
     */
    public function remove(string $name): self
    {
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * Append to an existing header value
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function append(string $name, string $value): self
    {
        if (!isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        } elseif (is_array($this->headers[$name])) {
            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] .= ', ' . $value;
        }
        return $this;
    }

    /**
     * Get all headers
     *
     * @return array<string, string|array>
     */
    public function all(): array
    {
        return $this->headers;
    }

    /**
     * Clear all headers
     *
     * @return self
     */
    public function clear(): self
    {
        $this->headers = [];
        return $this;
    }

    /**
     * Convert headers to string format for sending
     *
     * @return string
     */
    public function toString(): string
    {
        $output = '';
        
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $output .= $name . ': ' . $item . $this->newline;
                }
            } else {
                $output .= $name . ': ' . $value . $this->newline;
            }
        }

        return $output;
    }

    /**
     * Get header count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->headers);
    }

    /**
     * Build standard email headers
     *
     * @param string $from
     * @param string $subject
     * @param string $date
     * @param string $messageId
     * @return self
     */
    public function buildStandardHeaders(
        string $from,
        string $subject,
        string $date,
        string $messageId
    ): self {
        $this->set('Date', $date)
             ->set('From', $from)
             ->set('Message-ID', $messageId)
             ->set('Subject', $subject);
        
        return $this;
    }

    /**
     * Build MIME headers for multipart messages
     *
     * @param string $boundary
     * @param string $charset
     * @param string $mailType
     * @return self
     */
    public function buildMimeHeaders(string $boundary, string $charset, string $mailType): self
    {
        $this->set('MIME-Version', '1.0')
             ->set('Content-Type', "multipart/mixed; boundary=\"{$boundary}\"");
        
        return $this;
    }

    /**
     * Build content transfer encoding header
     *
     * @param string $encoding
     * @return self
     */
    public function setContentTransferEncoding(string $encoding): self
    {
        $this->set('Content-Transfer-Encoding', $encoding);
        return $this;
    }
}
