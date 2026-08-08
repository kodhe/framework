<?php

namespace Kodhe\Framework\Email\Validation;

/**
 * Validator untuk Email Address
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class EmailAddressValidator
{
    /**
     * @var array Cache untuk validasi email
     */
    private static $validationCache = [];

    /**
     * Validate single email address
     *
     * @param string $email
     * @return bool
     */
    public function isValid(string $email): bool
    {
        // Check cache first
        if (isset(self::$validationCache[$email])) {
            return self::$validationCache[$email];
        }

        // Validate email format
        $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        // Cache result
        self::$validationCache[$email] = $isValid;

        return $isValid;
    }

    /**
     * Validate multiple email addresses
     *
     * @param array $emails
     * @return bool
     */
    public function isValidList(array $emails): bool
    {
        foreach ($emails as $email) {
            if (!$this->isValid($email)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear validation cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        self::$validationCache = [];
    }

    /**
     * Parse email addresses from string or array
     *
     * @param mixed $input
     * @return array
     */
    public function parseAddresses($input): array
    {
        if (is_array($input)) {
            return $input;
        }

        // Handle comma-separated string
        if (is_string($input)) {
            $emails = explode(',', $input);
            return array_map('trim', $emails);
        }

        return [];
    }
}
