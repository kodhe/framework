<?php

/**
 * CI3 compatibility aliases for upload.
 */
if (!class_exists('CI_Upload', false) && class_exists('Kodhe\Upload\Upload', true)) {
    class_alias('Kodhe\Upload\Upload', 'CI_Upload');
}
