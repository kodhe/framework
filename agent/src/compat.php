<?php

/**
 * CI3 compatibility aliases for agent.
 */
if (!class_exists('CI_User_agent', false) && class_exists('Kodhe\\Framework\\Agent\\UserAgent', true)) {
    class_alias('Kodhe\\Framework\\Agent\\UserAgent', 'CI_User_agent');
}
