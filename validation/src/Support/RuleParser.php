<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\Support;

/**
 * Rule Parser
 * 
 * Parses rule strings and arrays into structured format
 */
class RuleParser
{
    /**
     * Parse rules from string or array format
     * 
     * @param string|array $rules
     * @return array
     */
    public static function parse(string|array $rules): array
    {
        if (is_array($rules)) {
            return $rules;
        }
        
        if (!is_string($rules)) {
            return [];
        }
        
        // Split by pipe, but not inside brackets
        $rules = preg_split('/\|(?![^\[]*\])/', $rules);
        
        return array_filter($rules, fn($r) => $r !== '');
    }
    
    /**
     * Extract rule name and parameter from a rule string
     * 
     * @param string $rule
     * @return array [name, parameter]
     */
    public static function extractRuleAndParam(string $rule): array
    {
        // Check for parameter in brackets
        if (preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
            return [$match[1], $match[2]];
        }
        
        return [$rule, null];
    }
    
    /**
     * Prepare rules in order of importance (Chain of Responsibility pattern)
     * 
     * Callbacks get highest priority, then required, then others
     * 
     * @param array $rules
     * @return array
     */
    public static function prepareRules(array $rules): array
    {
        $newRules = [];
        $callbacks = [];
        
        foreach ($rules as &$rule) {
            // Let 'required' always be the first (non-callback) rule
            if ($rule === 'required') {
                array_unshift($newRules, 'required');
            }
            // 'isset' is a kind of a weird alias for 'required'
            elseif ($rule === 'isset' && (empty($newRules) || $newRules[0] !== 'required')) {
                array_unshift($newRules, 'isset');
            }
            // The old/classic 'callback_'-prefixed rules
            elseif (is_string($rule) && strncmp('callback_', $rule, 9) === 0) {
                $callbacks[] = $rule;
            }
            // Proper callables
            elseif (is_callable($rule)) {
                $callbacks[] = $rule;
            }
            // "Named" callables; i.e. array('name' => $callable)
            elseif (is_array($rule) && isset($rule[0], $rule[1]) && is_callable($rule[1])) {
                $callbacks[] = $rule;
            }
            // Everything else goes at the end of the queue
            else {
                $newRules[] = $rule;
            }
        }
        
        return array_merge($callbacks, $newRules);
    }
    
    /**
     * Check if a rule is a callback
     * 
     * @param mixed $rule
     * @return bool
     */
    public static function isCallback(mixed $rule): bool
    {
        if (is_string($rule)) {
            return strpos($rule, 'callback_') === 0;
        }
        
        return is_callable($rule) || (is_array($rule) && isset($rule[0], $rule[1]) && is_callable($rule[1]));
    }
    
    /**
     * Get callback name from rule
     * 
     * @param mixed $rule
     * @return string|null
     */
    public static function getCallbackName(mixed $rule): ?string
    {
        if (is_string($rule) && strpos($rule, 'callback_') === 0) {
            return substr($rule, 9);
        }
        
        if (is_array($rule) && isset($rule[0]) && is_string($rule[0])) {
            return $rule[0];
        }
        
        return null;
    }
}
