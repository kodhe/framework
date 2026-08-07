<?php

namespace Kodhe\Email\Transports;

use Kodhe\Email\Contracts\TransportInterface;
use Kodhe\Email\Message\EmailMessage;

/**
 * Transport menggunakan Sendmail binary
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class SendmailTransport implements TransportInterface
{
    /**
     * @var array Configuration
     */
    protected $config = [];

    /**
     * @var string Debug message
     */
    protected $debugMessage = '';

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function send(EmailMessage $message): bool
    {
        $sendmailPath = $this->config['sendmail_path'] ?? '/usr/sbin/sendmail';
        $sendmailArgs = $this->config['sendmail_args'] ?? '-bs';

        // Build email content
        $content = $this->buildContent($message);

        // Execute sendmail
        $command = "{$sendmailPath} {$sendmailArgs}";
        $process = popen($command, 'w');

        if ($process === false) {
            $this->debugMessage .= "Failed to open sendmail process\n";
            return false;
        }

        fwrite($process, $content);
        $result = pclose($process) === 0;

        if ($result) {
            $this->debugMessage .= "Email sent successfully using sendmail\n";
        } else {
            $this->debugMessage .= "Failed to send email using sendmail\n";
        }

        return $result;
    }

    /**
     * Build complete email content with headers
     *
     * @param EmailMessage $message
     * @return string
     */
    protected function buildContent(EmailMessage $message): string
    {
        $newline = $this->config['newline'] ?? "\r\n";
        $headers = [];

        // From header
        $from = $message->getFrom();
        $fromName = $message->getFromName();
        if ($fromName) {
            $headers[] = "From: {$fromName} <{$from}>";
        } else {
            $headers[] = "From: {$from}";
        }

        // To header
        $to = implode(', ', $message->getTo());
        $headers[] = "To: {$to}";

        // Subject header
        $subject = $message->getSubject();
        $headers[] = "Subject: {$subject}";

        // Reply-To header
        $replyTo = $message->getReplyTo();
        if ($replyTo) {
            $replyToName = $message->getReplyToName();
            if ($replyToName) {
                $headers[] = "Reply-To: {$replyToName} <{$replyTo}>";
            } else {
                $headers[] = "Reply-To: {$replyTo}";
            }
        }

        // CC header
        $cc = $message->getCc();
        if (!empty($cc)) {
            $headers[] = "Cc: " . implode(', ', $cc);
        }

        // Content-Type header
        $mailType = $message->getMailType();
        $charset = $message->getCharset();
        if ($mailType === 'html') {
            $headers[] = "Content-Type: text/html; charset={$charset}";
        } else {
            $headers[] = "Content-Type: text/plain; charset={$charset}";
        }

        // X-Mailer header
        $headers[] = "X-Mailer: Kodhe Email Library";

        // Combine headers and body
        $content = implode($newline, $headers) . $newline . $newline . $message->getBody();

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage(array $include = []): string
    {
        return $this->debugMessage;
    }
}
