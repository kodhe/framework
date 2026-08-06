<?php

/**
 * CI3 compatibility aliases for Session package.
 * Loaded automatically via Composer "files" autoload.
 */
if (!class_exists('CI_Session', false) && class_exists(\Kodhe\Framework\Session\Session::class, true)) {
    class_alias(\Kodhe\Framework\Session\Session::class, 'CI_Session');
}

if (!class_exists('Session', false) && class_exists(\Kodhe\Framework\Session\Session::class, true)) {
    class_alias(\Kodhe\Framework\Session\Session::class, 'Session');
}
