<?php

/**
 * CI3 compatibility aliases for agent.
 * 
 * This file provides backward compatibility with CodeIgniter 3's User_agent class
 * by creating class aliases to the new PSR-4 compatible Agent class.
 */

// Create CI3-style class alias if not already defined
if (!class_exists('CI_User_agent', false) && class_exists('Kodhe\\Agent\\Agent', true)) {
    class_alias('Kodhe\\Agent\\Agent', 'CI_User_agent');
}

// Also provide legacy UserAgent class alias for backward compatibility
if (!class_exists('Kodhe\\Framework\\Agent\\UserAgent', false) && class_exists('Kodhe\\Agent\\Agent', true)) {
    class_alias('Kodhe\\Agent\\Agent', 'Kodhe\\Framework\\Agent\\UserAgent');
}
