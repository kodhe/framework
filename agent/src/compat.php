<?php

/**
 * CI3 compatibility aliases for agent.
 */
if (!class_exists('CI_User_agent', false) && class_exists('Kodhe\UserAgent\UserAgent', true)) {
    class_alias('Kodhe\UserAgent\UserAgent', 'CI_User_agent');
}
