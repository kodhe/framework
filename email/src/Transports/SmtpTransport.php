<?php

namespace Kodhe\Framework\Email\Transports;

use Kodhe\Framework\Email\Contracts\TransportInterface;
use Kodhe\Framework\Email\Message\EmailMessage;
use Kodhe\Framework\Email\Traits\ConfigurableTrait;
use Kodhe\Framework\Email\Traits\DebugTrait;

/**
 * Transport menggunakan SMTP protocol
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class SmtpTransport implements TransportInterface
{
    use ConfigurableTrait, DebugTrait;

    /**
     * @var resource|null SMTP connection socket
     */
    private $connection = null;

    /**
     * @var bool Whether authenticated
     */
    private $authenticated = false;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Destructor - close connection
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function send(EmailMessage $message): bool
    {
        try {
            // Open connection if not already open
            if (!$this->connection || !$this->isConnectionAlive()) {
                $this->openConnection();
            }

            // Authenticate if needed
            if (!$this->authenticated && $this->getConfig('smtp_user')) {
                $this->authenticate();
            }

            // Send email
            return $this->sendEmail($message);
        } catch (\Exception $e) {
            $this->addDebugMessage("SMTP Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Open SMTP connection
     *
     * @return void
     * @throws \RuntimeException
     */
    private function openConnection(): void
    {
        $host = $this->getConfig('smtp_host', 'localhost');
        $port = $this->getConfig('smtp_port', 25);
        $timeout = $this->getConfig('smtp_timeout', 5);

        // Add ssl:// prefix if using SSL
        $crypto = $this->getConfig('smtp_crypto');
        if ($crypto === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $this->addDebugMessage("Connecting to {$host}:{$port}");

        $this->connection = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if (!$this->connection) {
            throw new \RuntimeException("SMTP Connection failed: {$errstr} ({$errno})");
        }

        // Set timeout
        stream_set_timeout($this->connection, $timeout);

        // Read greeting
        $this->readResponse();

        // Send EHLO
        $this->sendCommand('EHLO ' . $this->getConfig('smtp_host', 'localhost'));

        // Start TLS if configured
        if ($crypto === 'tls') {
            $this->sendCommand('STARTTLS');
            stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand('EHLO ' . $this->getConfig('smtp_host', 'localhost'));
        }
    }

    /**
     * Authenticate with SMTP server
     *
     * @return void
     * @throws \RuntimeException
     */
    private function authenticate(): void
    {
        $username = $this->getConfig('smtp_user');
        $password = $this->getConfig('smtp_pass');

        if (!$username || !$password) {
            return;
        }

        $this->addDebugMessage("Authenticating as {$username}");

        // Use LOGIN authentication
        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode($username));
        $response = $this->sendCommand(base64_encode($password));

        if (strpos($response, '235') !== 0) {
            throw new \RuntimeException("SMTP Authentication failed");
        }

        $this->authenticated = true;
    }

    /**
     * Send email message
     *
     * @param EmailMessage $message
     * @return bool
     */
    private function sendEmail(EmailMessage $message): bool
    {
        // MAIL FROM
        $from = $message->getFrom();
        $this->sendCommand('MAIL FROM:<' . $from . '>');

        // RCPT TO
        foreach ($message->getTo() as $to) {
            $this->sendCommand('RCPT TO:<' . $to . '>');
        }

        // RCPT CC
        foreach ($message->getCc() as $cc) {
            $this->sendCommand('RCPT TO:<' . $cc . '>');
        }

        // RCPT BCC
        foreach ($message->getBcc() as $bcc) {
            $this->sendCommand('RCPT TO:<' . $bcc . '>');
        }

        // DATA
        $this->sendCommand('DATA');

        // Build and send headers + body
        $data = $this->buildMessageData($message);
        fputs($this->connection, $data . "\r\n.\r\n");

        $response = $this->readResponse();
        $this->addDebugMessage("Send result: {$response}");

        return strpos($response, '250') === 0;
    }

    /**
     * Build complete message data with headers
     *
     * @param EmailMessage $message
     * @return string
     */
    private function buildMessageData(EmailMessage $message): string
    {
        $newline = $this->getConfig('newline', "\r\n");
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
        $headers[] = "To: " . implode(', ', $message->getTo());

        // Subject header
        $headers[] = "Subject: " . $message->getSubject();

        // Reply-To header
        $replyTo = $message->getReplyTo();
        if ($replyTo) {
            $headers[] = "Reply-To: {$replyTo}";
        }

        // CC header
        $cc = $message->getCc();
        if (!empty($cc)) {
            $headers[] = "Cc: " . implode(', ', $cc);
        }

        // MIME headers
        $headers[] = "MIME-Version: 1.0";

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
        $data = implode($newline, $headers) . $newline . $newline . $message->getBody();

        return $data;
    }

    /**
     * Send SMTP command and read response
     *
     * @param string $command
     * @return string
     */
    private function sendCommand(string $command): string
    {
        fputs($this->connection, $command . "\r\n");
        $this->addDebugMessage("C: {$command}");
        return $this->readResponse();
    }

    /**
     * Read SMTP server response
     *
     * @return string
     */
    private function readResponse(): string
    {
        $response = fgets($this->connection, 515);
        $this->addDebugMessage("S: " . trim($response));
        return trim($response);
    }

    /**
     * Check if connection is alive
     *
     * @return bool
     */
    private function isConnectionAlive(): bool
    {
        if (!$this->connection) {
            return false;
        }

        $meta = stream_get_meta_data($this->connection);
        return !$meta['timed_out'] && !feof($this->connection);
    }

    /**
     * Close SMTP connection
     *
     * @return void
     */
    private function closeConnection(): void
    {
        if ($this->connection) {
            $this->sendCommand('QUIT');
            fclose($this->connection);
            $this->connection = null;
            $this->authenticated = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage(array $include = []): string
    {
        return $this->getDebugString("\n");
    }
}
