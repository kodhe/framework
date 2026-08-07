<?php

namespace Kodhe\Email\Transports;

use Kodhe\Email\Contracts\TransportInterface;
use Kodhe\Email\Message\EmailMessage;

/**
 * Transport menggunakan PHP mail() function
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class MailTransport implements TransportInterface
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
        $to = implode(', ', $message->getTo());
        $subject = $message->getSubject();
        $body = $message->getBody();

        // Build headers
        $headers = $this->buildHeaders($message);

        // Send using PHP mail()
        $result = @mail($to, $subject, $body, $headers);

        if ($result) {
            $this->debugMessage .= "Email sent successfully using mail()\n";
        } else {
            $this->debugMessage .= "Failed to send email using mail()\n";
        }

        return $result;
    }

    /**
     * Build email headers
     *
     * @param EmailMessage $message
     * @return string
     */
    protected function buildHeaders(EmailMessage $message): string
    {
        $headers = [];
        $newline = $this->config['newline'] ?? "\r\n";

        // From header
        $from = $message->getFrom();
        $fromName = $message->getFromName();
        if ($fromName) {
            $headers[] = "From: {$fromName} <{$from}>";
        } else {
            $headers[] = "From: {$from}";
        }

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

        // BCC header (handled separately in mail())
        $bcc = $message->getBcc();

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

        return implode($newline, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage(array $include = []): string
    {
        return $this->debugMessage;
    }
}
