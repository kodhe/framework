<?php

/**
 * CI3 compatibility aliases for cache.
 */
if (!class_exists('CI_Cache', false) && class_exists('Kodhe\Framework\Cache\Cache', true)) {
    class_alias('Kodhe\Framework\Cache\Cache', 'CI_Cache');
}
