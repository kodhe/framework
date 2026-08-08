<?php

namespace Kodhe\Framework\Email\Contracts;

/**
 * Interface untuk Email Library
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface EmailInterface
{
    /**
     * Initialize email preferences
     *
     * @param array $config
     * @return EmailInterface
     */
    public function initialize(array $config = []): EmailInterface;

    /**
     * Set from address
     *
     * @param string $from
     * @param string $name
     * @return EmailInterface
     */
    public function from(string $from, string $name = ''): EmailInterface;

    /**
     * Set reply-to address
     *
     * @param string $replyto
     * @param string $name
     * @return EmailInterface
     */
    public function replyTo(string $replyto, string $name = ''): EmailInterface;

    /**
     * Set recipient addresses
     *
     * @param mixed $to
     * @return EmailInterface
     */
    public function to($to): EmailInterface;

    /**
     * Set CC addresses
     *
     * @param mixed $cc
     * @return EmailInterface
     */
    public function cc($cc): EmailInterface;

    /**
     * Set BCC addresses
     *
     * @param mixed $bcc
     * @param int $limit
     * @return EmailInterface
     */
    public function bcc($bcc, int $limit = 0): EmailInterface;

    /**
     * Set email subject
     *
     * @param string $subject
     * @return EmailInterface
     */
    public function subject(string $subject): EmailInterface;

    /**
     * Set email message body
     *
     * @param string $body
     * @return EmailInterface
     */
    public function message(string $body): EmailInterface;

    /**
     * Add attachment
     *
     * @param string $filename
     * @param string $disposition
     * @param string|null $newname
     * @param string $mime
     * @return EmailInterface
     */
    public function attach(string $filename, string $disposition = '', ?string $newname = null, string $mime = ''): EmailInterface;

    /**
     * Send email
     *
     * @param bool $autoClear
     * @return bool
     */
    public function send(bool $autoClear = true): bool;

    /**
     * Clear email data
     *
     * @param bool $clearAttachments
     * @return EmailInterface
     */
    public function clear(bool $clearAttachments = false): EmailInterface;

    /**
     * Print debug information
     *
     * @param array $include
     * @return string
     */
    public function printDebugger(array $include = ['headers', 'subject', 'body']): string;
}
