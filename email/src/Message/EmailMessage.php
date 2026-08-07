<?php

namespace Kodhe\Email\Message;

/**
 * Value Object untuk Email Message
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class EmailMessage
{
    /**
     * @var string From address
     */
    private $from = '';

    /**
     * @var string From name
     */
    private $fromName = '';

    /**
     * @var array Recipient addresses
     */
    private $to = [];

    /**
     * @var array CC addresses
     */
    private $cc = [];

    /**
     * @var array BCC addresses
     */
    private $bcc = [];

    /**
     * @var string Reply-to address
     */
    private $replyTo = '';

    /**
     * @var string Reply-to name
     */
    private $replyToName = '';

    /**
     * @var string Subject
     */
    private $subject = '';

    /**
     * @var string Message body
     */
    private $body = '';

    /**
     * @var string Alternative message body (for text emails)
     */
    private $altBody = '';

    /**
     * @var array Attachments
     */
    private $attachments = [];

    /**
     * @var string Email type (text, html)
     */
    private $mailType = 'text';

    /**
     * @var string Character set
     */
    private $charset = 'utf-8';

    /**
     * Set from address
     *
     * @param string $email
     * @param string $name
     * @return self
     */
    public function setFrom(string $email, string $name = ''): self
    {
        $this->from = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * Set recipient addresses
     *
     * @param array|string $addresses
     * @return self
     */
    public function setTo($addresses): self
    {
        $this->to = is_array($addresses) ? $addresses : [$addresses];
        return $this;
    }

    /**
     * Set CC addresses
     *
     * @param array|string $addresses
     * @return self
     */
    public function setCc($addresses): self
    {
        $this->cc = is_array($addresses) ? $addresses : [$addresses];
        return $this;
    }

    /**
     * Set BCC addresses
     *
     * @param array|string $addresses
     * @return self
     */
    public function setBcc($addresses): self
    {
        $this->bcc = is_array($addresses) ? $addresses : [$addresses];
        return $this;
    }

    /**
     * Set reply-to address
     *
     * @param string $email
     * @param string $name
     * @return self
     */
    public function setReplyTo(string $email, string $name = ''): self
    {
        $this->replyTo = $email;
        $this->replyToName = $name;
        return $this;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return self
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set message body
     *
     * @param string $body
     * @return self
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set alternative message body
     *
     * @param string $altBody
     * @return self
     */
    public function setAltBody(string $altBody): self
    {
        $this->altBody = $altBody;
        return $this;
    }

    /**
     * Add attachment
     *
     * @param string $filename
     * @param string $disposition
     * @param string|null $newname
     * @param string $mime
     * @return self
     */
    public function addAttachment(string $filename, string $disposition = '', ?string $newname = null, string $mime = ''): self
    {
        $this->attachments[] = [
            'filename' => $filename,
            'disposition' => $disposition ?: 'attachment',
            'newname' => $newname,
            'mime' => $mime,
        ];
        return $this;
    }

    /**
     * Get from address
     *
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Get from name
     *
     * @return string
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * Get recipient addresses
     *
     * @return array
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * Get CC addresses
     *
     * @return array
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * Get BCC addresses
     *
     * @return array
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    /**
     * Get reply-to address
     *
     * @return string
     */
    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get message body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get alternative body
     *
     * @return string
     */
    public function getAltBody(): string
    {
        return $this->altBody;
    }

    /**
     * Get attachments
     *
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Set mail type
     *
     * @param string $type
     * @return self
     */
    public function setMailType(string $type): self
    {
        $this->mailType = $type;
        return $this;
    }

    /**
     * Get mail type
     *
     * @return string
     */
    public function getMailType(): string
    {
        return $this->mailType;
    }

    /**
     * Set charset
     *
     * @param string $charset
     * @return self
     */
    public function setCharset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Get charset
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Check if has attachments
     *
     * @return bool
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }
}
