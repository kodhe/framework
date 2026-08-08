<?php

namespace Kodhe\Framework\Email\Contracts;

use Kodhe\Framework\Email\Message\EmailMessage;

/**
 * Interface untuk Transport Email
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface TransportInterface
{
    /**
     * Send email message
     *
     * @param EmailMessage $message
     * @return bool
     */
    public function send(EmailMessage $message): bool;

    /**
     * Get debug message
     *
     * @param array $include
     * @return string
     */
    public function getDebugMessage(array $include = []): string;
}
