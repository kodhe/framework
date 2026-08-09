<?php

/**
 * CI3 compatibility aliases for upload.
 */
if (!class_exists('CI_Upload', false) && class_exists('Kodhe\Framework\Upload\Upload', true)) {
    class_alias('Kodhe\Framework\Upload\Upload', 'CI_Upload');
}

// Also provide lowercase variant for case-sensitive systems (Linux)
if (!class_exists('CI_upload', false) && class_exists('Kodhe\Framework\Upload\Upload', true)) {
    class_alias('Kodhe\Framework\Upload\Upload', 'CI_upload');
}
