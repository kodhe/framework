<?php

/**
 * CI3 compatibility aliases for email.
 */
if (!class_exists('CI_Email', false) && class_exists('Kodhe\Email\Email', true)) {
    class_alias('Kodhe\Email\Email', 'CI_Email');
}
