<?php

declare(strict_types=1);

namespace Kodhe\Validation\Messages;

/**
 * Error Message Manager
 * 
 * Handles custom error messages and message formatting
 */
class MessageManager
{
    /**
     * Default error messages
     */
    protected array $defaultMessages = [
        'required' => 'The {field} field is required',
        'isset' => 'The {field} field must be set',
        'matches' => 'The {field} field does not match the {param} field',
        'differs' => 'The {field} field must differ from the {param} field',
        'is_unique' => 'The {field} field must be unique',
        'min_length' => 'The {field} field must be at least {param} characters in length',
        'max_length' => 'The {field} field cannot exceed {param} characters in length',
        'exact_length' => 'The {field} field must be exactly {param} characters in length',
        'greater_than' => 'The {field} field must be greater than {param}',
        'greater_than_equal_to' => 'The {field} field must be greater than or equal to {param}',
        'less_than' => 'The {field} field must be less than {param}',
        'less_than_equal_to' => 'The {field} field must be less than or equal to {param}',
        'in_list' => 'The {field} field must be one of: {param}',
        'numeric' => 'The {field} field must contain only numbers',
        'integer' => 'The {field} field must contain an integer',
        'decimal' => 'The {field} field must contain a decimal number',
        'is_natural' => 'The {field} field must contain only natural numbers',
        'is_natural_no_zero' => 'The {field} field must contain only natural numbers excluding zero',
        'valid_email' => 'The {field} field must contain a valid email address',
        'valid_emails' => 'The {field} field must contain all valid email addresses',
        'valid_url' => 'The {field} field must contain a valid URL',
        'valid_ip' => 'The {field} field must contain a valid IP address',
        'alpha' => 'The {field} field may only contain alphabetical characters',
        'alpha_numeric' => 'The {field} field may only contain alpha-numeric characters',
        'alpha_numeric_spaces' => 'The {field} field may only contain alpha-numeric characters and spaces',
        'alpha_dash' => 'The {field} field may only contain alpha-numeric characters, underscores, and dashes',
        'regex_match' => 'The {field} field does not match the required format',
        'valid_base64' => 'The {field} field must contain only valid base64 characters',
    ];
    
    /**
     * Custom messages set by user
     */
    protected array $customMessages = [];
    
    /**
     * Field-specific messages
     */
    protected array $fieldMessages = [];
    
    /**
     * Set custom error messages
     * 
     * @param array|string $messages
     * @param string $value Optional value if $messages is string
     * @return self
     */
    public function set(array|string $messages, string $value = ''): self
    {
        if (!is_array($messages)) {
            $messages = [$messages => $value];
        }
        
        $this->customMessages = array_merge($this->customMessages, $messages);
        
        return $this;
    }
    
    /**
     * Set field-specific error messages
     * 
     * @param string $field
     * @param array $messages
     * @return self
     */
    public function setFieldMessages(string $field, array $messages): self
    {
        $this->fieldMessages[$field] = $messages;
        
        return $this;
    }
    
    /**
     * Get error message for a rule
     * 
     * @param string $rule
     * @param string $field
     * @param array $fieldErrors Field-specific errors
     * @return string
     */
    public function get(string $rule, string $field, array $fieldErrors = []): string
    {
        // Check field-specific errors first
        if (isset($fieldErrors[$rule])) {
            return $fieldErrors[$rule];
        }
        
        // Check custom messages
        if (isset($this->customMessages[$rule])) {
            return $this->customMessages[$rule];
        }
        
        // Check default messages
        if (isset($this->defaultMessages[$rule])) {
            return $this->defaultMessages[$rule];
        }
        
        // Fallback
        return 'The {field} field does not pass validation';
    }
    
    /**
     * Format error message with field label and parameter
     * 
     * @param string $message
     * @param string $label
     * @param mixed $param
     * @return string
     */
    public function format(string $message, string $label, mixed $param = ''): string
    {
        // Handle legacy %s format
        if (strpos($message, '%s') !== false) {
            return sprintf($message, $label, $param);
        }
        
        return str_replace(
            ['{field}', '{param}'],
            [$label, (string) $param],
            $message
        );
    }
    
    /**
     * Translate field name (handle lang: prefix)
     * 
     * @param string $fieldname
     * @param callable $translator Optional translator callback
     * @return string
     */
    public function translateFieldname(string $fieldname, ?callable $translator = null): string
    {
        // Check for lang: prefix
        if (sscanf($fieldname, 'lang:%s', $line) === 1) {
            if ($translator !== null) {
                $translated = $translator($line);
                if ($translated !== false && $translated !== null) {
                    return $translated;
                }
            }
            return $line;
        }
        
        return $fieldname;
    }
    
    /**
     * Get all default messages
     * 
     * @return array
     */
    public function getDefaultMessages(): array
    {
        return $this->defaultMessages;
    }
    
    /**
     * Clear custom messages
     * 
     * @return self
     */
    public function clearCustomMessages(): self
    {
        $this->customMessages = [];
        return $this;
    }
}
