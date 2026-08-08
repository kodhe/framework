<?php

declare(strict_types=0);

namespace Kodhe\Framework\Email;

use Kodhe\Framework\Email\Contracts\EmailInterface;
use Kodhe\Framework\Email\Message\EmailMessage;
use Kodhe\Framework\Email\Message\Attachment;
use Kodhe\Framework\Email\Message\HeaderCollection;
use Kodhe\Framework\Email\Validation\EmailAddressValidator;
use Kodhe\Framework\Email\Contracts\TransportInterface;
use Kodhe\Framework\Email\Transports\MailTransport;
use Kodhe\Framework\Email\Transports\SendmailTransport;
use Kodhe\Framework\Email\Transports\SmtpTransport;
use Kodhe\Framework\Email\Traits\ConfigurableTrait;
use Kodhe\Framework\Email\Traits\DebugTrait;

/**
 * Email Facade Class
 * 
 * Main entry point for email functionality, maintaining backward compatibility
 * with CodeIgniter 3 while delegating to modern modular components.
 *
 * @package     Kodhe\Email
 * @author      CodeIgniter Team
 * @version     2.0.0
 * @license     MIT
 */
class Email implements EmailInterface
{
    use ConfigurableTrait;
    use DebugTrait;

    /**
     * User-Agent value
     *
     * @var string
     */
    public string $useragent = 'CodeIgniter';

    /**
     * Path to Sendmail binary
     *
     * @var string
     */
    public string $mailpath = '/usr/sbin/sendmail';

    /**
     * Email protocol: 'mail', 'sendmail', or 'smtp'
     *
     * @var string
     */
    public string $protocol = 'mail';

    /**
     * SMTP host
     *
     * @var string
     */
    public string $smtp_host = '';

    /**
     * SMTP username
     *
     * @var string
     */
    public string $smtp_user = '';

    /**
     * SMTP password
     *
     * @var string
     */
    public string $smtp_pass = '';

    /**
     * SMTP port
     *
     * @var int
     */
    public int $smtp_port = 25;

    /**
     * SMTP timeout in seconds
     *
     * @var int
     */
    public int $smtp_timeout = 5;

    /**
     * SMTP keepalive
     *
     * @var bool
     */
    public bool $smtp_keepalive = false;

    /**
     * SMTP encryption: '', 'tls', or 'ssl'
     *
     * @var string
     */
    public string $smtp_crypto = '';

    /**
     * Enable word wrapping
     *
     * @var bool
     */
    public bool $wordwrap = true;

    /**
     * Characters per line for wrapping
     *
     * @var int
     */
    public int $wrapchars = 76;

    /**
     * Mail type: 'text' or 'html'
     *
     * @var string
     */
    public string $mailtype = 'text';

    /**
     * Character set
     *
     * @var string
     */
    public string $charset = 'UTF-8';

    /**
     * Alternative message for HTML emails
     *
     * @var string
     */
    public string $alt_message = '';

    /**
     * Validate email addresses
     *
     * @var bool
     */
    public bool $validate = false;

    /**
     * Email priority (1-5)
     *
     * @var int
     */
    public int $priority = 3;

    /**
     * Newline character
     *
     * @var string
     */
    public string $newline = "\n";

    /**
     * CRLF character
     *
     * @var string
     */
    public string $crlf = "\n";

    /**
     * Delivery Status Notification
     *
     * @var bool
     */
    public bool $dsn = false;

    /**
     * Send multipart messages
     *
     * @var bool
     */
    public bool $send_multipart = true;

    /**
     * BCC batch mode
     *
     * @var bool
     */
    public bool $bcc_batch_mode = false;

    /**
     * BCC batch size
     *
     * @var int
     */
    public int $bcc_batch_size = 200;

    /**
     * @var EmailMessage|null Message object
     */
    protected ?EmailMessage $message = null;

    /**
     * @var HeaderCollection|null Headers collection
     */
    protected ?HeaderCollection $headers = null;

    /**
     * @var TransportInterface|null Transport handler
     */
    protected ?TransportInterface $transport = null;

    /**
     * @var EmailAddressValidator Validator instance
     */
    protected EmailAddressValidator $validator;

    /**
     * @var array Configuration
     */
    protected array $config = [];

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->validator = new EmailAddressValidator();
        $this->headers = new HeaderCollection($this->newline);
        
        if (!empty($config)) {
            $this->initialize($config);
        }
    }

    /**
     * Initialize email preferences
     *
     * @param array $config
     * @return EmailInterface
     */
    public function initialize(array $config = []): EmailInterface
    {
        $this->config = $config;
        $this->setConfig($config);
        
        // Reinitialize headers with correct newline
        $this->headers = new HeaderCollection($this->newline);
        
        return $this;
    }

    /**
     * Set from address
     *
     * @param string $from
     * @param string $name
     * @return EmailInterface
     */
    public function from(string $from, string $name = ''): EmailInterface
    {
        if ($this->validate && !$this->validator->isValid($from)) {
            $this->setErrorMessage('Invalid from email address: ' . $from);
            return $this;
        }

        $this->getMessage()->setFrom($from, $name);
        return $this;
    }

    /**
     * Set reply-to address
     *
     * @param string $replyto
     * @param string $name
     * @return EmailInterface
     */
    public function replyTo(string $replyto, string $name = ''): EmailInterface
    {
        if ($this->validate && !$this->validator->isValid($replyto)) {
            $this->setErrorMessage('Invalid reply-to email address: ' . $replyto);
            return $this;
        }

        $this->getMessage()->setReplyTo($replyto, $name);
        return $this;
    }

    /**
     * Set recipient addresses
     *
     * @param mixed $to
     * @return EmailInterface
     */
    public function to($to): EmailInterface
    {
        $addresses = $this->validator->parseAddresses($to);
        
        if ($this->validate && !$this->validator->isValidList($addresses)) {
            $this->setErrorMessage('Invalid recipient email address');
            return $this;
        }

        $this->getMessage()->setTo($addresses);
        return $this;
    }

    /**
     * Set CC addresses
     *
     * @param mixed $cc
     * @return EmailInterface
     */
    public function cc($cc): EmailInterface
    {
        $addresses = $this->validator->parseAddresses($cc);
        
        if ($this->validate && !$this->validator->isValidList($addresses)) {
            $this->setErrorMessage('Invalid CC email address');
            return $this;
        }

        $this->getMessage()->setCc($addresses);
        return $this;
    }

    /**
     * Set BCC addresses
     *
     * @param mixed $bcc
     * @param int $limit
     * @return EmailInterface
     */
    public function bcc($bcc, int $limit = 0): EmailInterface
    {
        $addresses = $this->validator->parseAddresses($bcc);
        
        if ($this->validate && !$this->validator->isValidList($addresses)) {
            $this->setErrorMessage('Invalid BCC email address');
            return $this;
        }

        $this->getMessage()->setBcc($addresses);
        return $this;
    }

    /**
     * Set email subject
     *
     * @param string $subject
     * @return EmailInterface
     */
    public function subject(string $subject): EmailInterface
    {
        $this->getMessage()->setSubject($subject);
        return $this;
    }

    /**
     * Set email message body
     *
     * @param string $body
     * @return EmailInterface
     */
    public function message(string $body): EmailInterface
    {
        $this->getMessage()->setBody($body);
        return $this;
    }

    /**
     * Add attachment
     *
     * @param string $filename
     * @param string $disposition
     * @param string|null $newname
     * @param string $mime
     * @return EmailInterface
     */
    public function attach(string $filename, string $disposition = '', ?string $newname = null, string $mime = ''): EmailInterface
    {
        $attachment = new Attachment($filename, $disposition ?: 'attachment', $newname, $mime);
        $this->getMessage()->addAttachmentObject($attachment);
        return $this;
    }

    /**
     * Send email
     *
     * @param bool $autoClear
     * @return bool
     */
    public function send(bool $autoClear = true): bool
    {
        try {
            $transport = $this->getTransport();
            
            // Build the message
            $emailMessage = $this->getMessage();
            
            // Send via transport
            $result = $transport->send($emailMessage);
            
            if ($result && $autoClear) {
                $this->clear();
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }

    /**
     * Clear email data
     *
     * @param bool $clearAttachments
     * @return EmailInterface
     */
    public function clear(bool $clearAttachments = false): EmailInterface
    {
        $this->message = null;
        $this->headers = new HeaderCollection($this->newline);
        
        if ($clearAttachments && $this->message) {
            // Clear attachments handled by EmailMessage
        }
        
        return $this;
    }

    /**
     * Print debug information
     *
     * @param array $include
     * @return string
     */
    public function printDebugger(array $include = ['headers', 'subject', 'body']): string
    {
        return $this->getDebugMessage();
    }

    /**
     * Get EmailMessage instance
     *
     * @return EmailMessage
     */
    protected function getMessage(): EmailMessage
    {
        if ($this->message === null) {
            $this->message = new EmailMessage();
            $this->message->setMailType($this->mailtype);
            $this->message->setCharset($this->charset);
        }
        
        return $this->message;
    }

    /**
     * Get transport instance
     *
     * @return TransportInterface
     * @throws \InvalidArgumentException
     */
    protected function getTransport(): TransportInterface
    {
        if ($this->transport !== null) {
            return $this->transport;
        }

        switch ($this->protocol) {
            case 'smtp':
                $this->transport = new SmtpTransport([
                    'host' => $this->smtp_host,
                    'port' => $this->smtp_port,
                    'username' => $this->smtp_user,
                    'password' => $this->smtp_pass,
                    'crypto' => $this->smtp_crypto,
                    'timeout' => $this->smtp_timeout,
                    'keepalive' => $this->smtp_keepalive,
                ]);
                break;
                
            case 'sendmail':
                $this->transport = new SendmailTransport($this->mailpath);
                break;
                
            case 'mail':
            default:
                $this->transport = new MailTransport();
                break;
        }

        return $this->transport;
    }

    /**
     * Set configuration values
     *
     * @param array $config
     * @return void
     */
    protected function setConfig(array $config): void
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Set error message
     *
     * @param string $message
     * @return void
     */
    protected function setErrorMessage(string $message): void
    {
        $this->addDebugMessage($message);
    }
}
