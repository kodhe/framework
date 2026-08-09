<?php

/**
 * CI3 compatibility aliases for email.
 */
if (!class_exists('CI_Email', false) && class_exists('Kodhe\Framework\Email\Email', true)) {
    class_alias('Kodhe\Framework\Email\Email', 'CI_Email');
}

// Also provide lowercase variant for case-sensitive systems (Linux)
if (!class_exists('CI_email', false) && class_exists('Kodhe\Framework\Email\Email', true)) {
    class_alias('Kodhe\Framework\Email\Email', 'CI_email');
}
