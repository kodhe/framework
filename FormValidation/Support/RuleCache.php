<?php

namespace Kodhe\FormValidation\Support;

class RuleCache
{
    private static $compiledRules = [];

    public static function get($key)
    {
        return self::$compiledRules[$key] ?? null;
    }

    public static function set($key, $value)
    {
        self::$compiledRules[$key] = $value;
    }

    public static function has($key)
    {
        return isset(self::$compiledRules[$key]);
    }

    public static function clear()
    {
        self::$compiledRules = [];
    }
}
