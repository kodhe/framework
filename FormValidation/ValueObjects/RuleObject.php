<?php

namespace Kodhe\FormValidation\ValueObjects;

class RuleObject
{
    private $field;
    private $label;
    private $rules = [];
    private $compiledRules = [];

    public function __construct($field, $label, array $rules)
    {
        $this->field = $field;
        $this->label = $label;
        $this->setRules($rules);
    }

    public function getField()
    {
        return $this->field;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function setRules(array $rules)
    {
        $this->rules = $rules;
        $this->compiledRules = []; // Reset cache
    }

    public function addRule($rule)
    {
        $this->rules[] = $rule;
        $this->compiledRules = []; // Reset cache
    }

    public function getCompiledRules(): array
    {
        if (empty($this->compiledRules)) {
            $this->compiledRules = $this->compileRules();
        }
        return $this->compiledRules;
    }

    private function compileRules(): array
    {
        $compiled = [];
        
        foreach ($this->rules as $rule) {
            if (is_callable($rule)) {
                $compiled[] = ['type' => 'callback', 'callback' => $rule];
                continue;
            }

            if (strpos($rule, '[') !== false) {
                preg_match('/([a-zA-Z_]+)\[(.*)\]/', $rule, $matches);
                if (isset($matches[1], $matches[2])) {
                    $compiled[] = [
                        'type' => $matches[1],
                        'params' => explode(',', $matches[2])
                    ];
                }
            } else {
                $compiled[] = ['type' => $rule];
            }
        }

        return $compiled;
    }
}
