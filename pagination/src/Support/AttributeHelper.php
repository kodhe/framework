<?php
namespace Kodhe\Pagination\Support;

class AttributeHelper
{
    /**
     * Normalize attributes from CI3 string format to array format
     * Example: 'class="active" id="foo"' => ['class' => 'active', 'id' => 'foo']
     */
    public static function normalize($attributes): array
    {
        if (is_array($attributes)) {
            return $attributes;
        }

        if (is_string($attributes) && !empty($attributes)) {
            $result = [];
            // Regex to parse key="value" or key='value'
            preg_match_all('/([\w-]+)\s*=\s*["\']([^"\']+)["\']/i', $attributes, $matches);
            
            if (!empty($matches[1]) && !empty($matches[2])) {
                foreach ($matches[1] as $index => $key) {
                    $result[$key] = $matches[2][$index];
                }
            } else {
                // Fallback if string doesn't match pattern, treat as class
                $result['class'] = $attributes;
            }
            return $result;
        }

        return [];
    }

    /**
     * Convert array attributes back to string for HTML
     */
    public static function toString(array $attributes): string
    {
        $str = '';
        foreach ($attributes as $key => $value) {
            $str .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        return $str;
    }
}