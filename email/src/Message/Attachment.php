<?php

declare(strict_types=1);

namespace Kodhe\Framework\Email\Message;

/**
 * Value Object for Email Attachments
 *
 * @package     Kodhe\Email
 * @author      CodeIgniter Team
 * @version     2.0.0
 * @license     MIT
 */
class Attachment
{
    /**
     * @var string Path to the file
     */
    private string $filename;

    /**
     * @var string Content disposition ('attachment' or 'inline')
     */
    private string $disposition;

    /**
     * @var string|null Custom filename for the attachment
     */
    private ?string $newname;

    /**
     * @var string MIME type of the file
     */
    private string $mime;

    /**
     * @var string File content (if provided directly)
     */
    private string $content = '';

    /**
     * Constructor
     *
     * @param string $filename
     * @param string $disposition
     * @param string|null $newname
     * @param string $mime
     */
    public function __construct(
        string $filename,
        string $disposition = 'attachment',
        ?string $newname = null,
        string $mime = ''
    ) {
        $this->filename = $filename;
        $this->disposition = $disposition;
        $this->newname = $newname;
        $this->mime = $mime;
    }

    /**
     * Get the file path
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Get the content disposition
     *
     * @return string
     */
    public function getDisposition(): string
    {
        return $this->disposition;
    }

    /**
     * Get the custom filename
     *
     * @return string|null
     */
    public function getNewname(): ?string
    {
        return $this->newname;
    }

    /**
     * Get the MIME type
     *
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * Set the file content directly
     *
     * @param string $content
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the file content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Check if content is set
     *
     * @return bool
     */
    public function hasContent(): bool
    {
        return $this->content !== '';
    }

    /**
     * Check if this is an inline attachment
     *
     * @return bool
     */
    public function isInline(): bool
    {
        return $this->disposition === 'inline';
    }

    /**
     * Convert to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'disposition' => $this->disposition,
            'newname' => $this->newname,
            'mime' => $this->mime,
            'content' => $this->content,
        ];
    }
}
