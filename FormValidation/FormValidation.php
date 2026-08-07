<?php

namespace Kodhe\FormValidation;

use Kodhe\FormValidation\Contracts\ValidatorInterface;
use Kodhe\FormValidation\Messages\MessageStore;
use Kodhe\FormValidation\Rules\RuleChain;
use Kodhe\FormValidation\Factory\ValidatorFactory;
use Kodhe\FormValidation\Support\RuleCache;
use Kodhe\FormValidation\Exceptions\FormValidationException;
use Kodhe\FormValidation\ValueObjects\RuleObject;

class FormValidation
{
    protected $CI;
    
    private $rules = [];
    private $messages = [];
    private $errors = [];
    private $errorPrefix = '<p>';
    private $errorSuffix = '</p>';
    private $validationData = [];
    private $fieldData = [];
    private $customMessages = [];
    private $callbackClass = null;
    private $ruleChains = [];
    private $messageStore;

    public function __construct($config = [])
    {
        $this->messageStore = new MessageStore();
        
        if (!empty($config)) {
            $this->initialize($config);
        }
    }

    public function initialize($config = [])
    {
        if (isset($config['error_prefix'])) {
            $this->errorPrefix = $config['error_prefix'];
        }
        if (isset($config['error_suffix'])) {
            $this->errorSuffix = $config['error_suffix'];
        }
        
        return $this;
    }

    public function set_rules($field, $label = '', $rules = '')
    {
        if (!is_array($field)) {
            $field = [$field => $label];
            $rules = is_string($rules) ? explode('|', $rules) : (array)$rules;
        }

        foreach ($field as $f => $l) {
            $ruleObject = new RuleObject($f, $l, is_array($l) ? $l : []);
            $this->rules[$f] = $ruleObject;
            
            if (is_array($l)) {
                $this->rules[$f]->setRules($l);
            }
        }

        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($field as $f => $l) {
            if (!is_array($l)) {
                $this->rules[$f]->setRules((array)$rules);
            }
        }

        return $this;
    }

    public function run($data = null, $group = null, $dbGroup = null)
    {
        $this->resetValidation();
        
        if ($data === null) {
            $data = $_POST ?? [];
        }

        $this->validationData = $data;
        $this->errors = [];

        foreach ($this->rules as $field => $ruleObject) {
            $value = $data[$field] ?? null;
            $label = $ruleObject->getLabel() ?: $field;
            
            $compiledRules = $ruleObject->getCompiledRules();
            $chainKey = md5(serialize($compiledRules));
            
            if (!isset($this->ruleChains[$chainKey])) {
                $this->ruleChains[$chainKey] = $this->buildRuleChain($compiledRules);
            }
            
            $chain = $this->ruleChains[$chainKey];
            $fieldErrors = $chain->validate($value, $label, $data);
            
            if (!empty($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    $customError = $this->getCustomMessage($field, $error);
                    $this->errors[$field][] = $customError ?: $error;
                }
            }
        }

        return empty($this->errors);
    }

    private function buildRuleChain(array $compiledRules): RuleChain
    {
        $chain = new RuleChain($this->messageStore);

        foreach ($compiledRules as $rule) {
            $type = $rule['type'];
            $params = $rule['params'] ?? [];

            if ($type === 'callback') {
                continue; // Handle callbacks separately
            }

            $validator = ValidatorFactory::make($type, $params);
            if ($validator) {
                $chain->addValidator($validator);
            }
        }

        return $chain;
    }

    private function getCustomMessage($field, $defaultMessage)
    {
        $key = "{$field}_" . md5($defaultMessage);
        return $this->customMessages[$key] ?? null;
    }

    public function reset_validation()
    {
        $this->rules = [];
        $this->errors = [];
        $this->validationData = [];
        $this->ruleChains = [];
        RuleCache::clear();
        
        return $this;
    }

    public function set_message($rule, $message)
    {
        if (is_array($rule)) {
            foreach ($rule as $r => $msg) {
                $this->customMessages[$r] = $msg;
            }
        } else {
            $this->customMessages[$rule] = $message;
        }
        
        return $this;
    }

    public function set_error_delimiters($prefix = '<p>', $suffix = '</p>')
    {
        $this->errorPrefix = $prefix;
        $this->errorSuffix = $suffix;
        
        return $this;
    }

    public function error($field)
    {
        if (!isset($this->errors[$field])) {
            return '';
        }
        
        return $this->errorPrefix . $this->errors[$field][0] . $this->errorSuffix;
    }

    public function error_array()
    {
        $result = [];
        
        foreach ($this->errors as $field => $errors) {
            $result[$field] = $this->errorPrefix . $errors[0] . $this->errorSuffix;
        }
        
        return $result;
    }

    public function validation_errors($prefix = '', $suffix = '')
    {
        if (empty($this->errors)) {
            return '';
        }

        $prefix = $prefix ?: $this->errorPrefix;
        $suffix = $suffix ?: $this->errorSuffix;
        
        $output = '';
        foreach ($this->errors as $field => $errors) {
            foreach ($errors as $error) {
                $output .= $prefix . $error . $suffix;
            }
        }
        
        return $output;
    }

    public function data($field = null)
    {
        if ($field !== null) {
            return $this->validationData[$field] ?? null;
        }
        
        return $this->validationData;
    }

    public function has_error($field = null)
    {
        if ($field !== null) {
            return isset($this->errors[$field]);
        }
        
        return !empty($this->errors);
    }

    public function get_errors()
    {
        return $this->errors;
    }

    public function set_callback_class($class)
    {
        $this->callbackClass = $class;
        return $this;
    }
}
