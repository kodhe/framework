<?php

/**
 * CI3 compatibility aliases for table.
 */
if (!class_exists('CI_Table', false) && class_exists('Kodhe\Framework\Table\Table', true)) {
    class_alias('Kodhe\Framework\Table\Table', 'CI_Table');
}

// Also provide lowercase variant for case-sensitive systems (Linux)
if (!class_exists('CI_table', false) && class_exists('Kodhe\Framework\Table\Table', true)) {
    class_alias('Kodhe\Framework\Table\Table', 'CI_table');
}
