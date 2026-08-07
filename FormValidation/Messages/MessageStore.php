<?php

namespace Kodhe\FormValidation\Messages;

class MessageStore
{
    private $messages = [
        'required' => 'The {field} field is required.',
        'isset' => 'The {field} field must have a value.',
        'valid_email' => 'The {field} field must contain a valid email address.',
        'valid_emails' => 'The {field} field must contain all valid email addresses.',
        'min_length' => 'The {field} field must be at least {param} characters in length.',
        'max_length' => 'The {field} field can not exceed {param} characters in length.',
        'exact_length' => 'The {field} field must be exactly {param} characters in length.',
        'greater_than' => 'The {field} field must be greater than {param}.',
        'greater_than_equal_to' => 'The {field} field must be greater than or equal to {param}.',
        'less_than' => 'The {field} field must be less than {param}.',
        'less_than_equal_to' => 'The {field} field must be less than or equal to {param}.',
        'numeric' => 'The {field} field must contain only numbers.',
        'integer' => 'The {field} field must contain an integer.',
        'decimal' => 'The {field} field must contain a decimal number.',
        'is_natural' => 'The {field} field must only contain natural numbers.',
        'is_natural_no_zero' => 'The {field} field must not contain zero.',
        'alpha' => 'The {field} field may only contain alphabetical characters.',
        'alpha_numeric' => 'The {field} field may only contain alpha-numeric characters.',
        'alpha_numeric_space' => 'The {field} field may only contain alpha-numeric characters and spaces.',
        'alpha_dash' => 'The {field} field may only contain alpha-numeric characters, underscores, and dashes.',
        'special_chars' => 'The {field} field contains disallowed characters.',
        'regex_match' => 'The {field} field is not in the correct format.',
        'matches' => 'The {field} field does not match the {param} field.',
        'differs' => 'The {field} field must differ from the {param} field.',
        'is_unique' => 'The {field} field must be unique.',
        'is_not_unique' => 'The {field} field must not be unique.',
        'in_list' => 'The {field} field must be one of: {param}.',
        'valid_url' => 'The {field} field must contain a valid URL.',
        'valid_ip' => 'The {field} field must contain a valid IP.',
        'valid_ipv4' => 'The {field} field must contain a valid IPv4 address.',
        'valid_ipv6' => 'The {field} field must contain a valid IPv6 address.',
        'file_type' => 'The {field} field must contain a file type of {param}.',
        'file_size' => 'The {field} field must be less than {param} KB.',
        'file_exists' => 'The {field} field must contain a valid file path.',
        'check_boxes' => 'The {field} field must have at least one option selected.',
    ];

    public function get($rule)
    {
        return $this->messages[$rule] ?? null;
    }

    public function set($rule, $message)
    {
        $this->messages[$rule] = $message;
    }

    public function all(): array
    {
        return $this->messages;
    }

    public function replacePlaceholders($message, $field, $params = [])
    {
        $replacements = [
            '{field}' => $field,
            '{param}' => is_array($params) ? implode(', ', $params) : $params,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}
