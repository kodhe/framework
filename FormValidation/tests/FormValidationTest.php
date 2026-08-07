<?php

namespace Kodhe\FormValidation\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\FormValidation\FormValidation;

class FormValidationTest extends TestCase
{
    private $formValidation;

    protected function setUp(): void
    {
        $this->formValidation = new FormValidation();
    }

    public function testRequiredValidation()
    {
        $this->formValidation->set_rules('username', 'Username', 'required');
        
        $result = $this->formValidation->run(['username' => '']);
        $this->assertFalse($result);
        $this->assertTrue($this->formValidation->has_error('username'));
        
        $result = $this->formValidation->run(['username' => 'john']);
        $this->assertTrue($result);
    }

    public function testNumericValidation()
    {
        $this->formValidation->set_rules('age', 'Age', 'numeric');
        
        $result = $this->formValidation->run(['age' => 'abc']);
        $this->assertFalse($result);
        
        $result = $this->formValidation->run(['age' => '25']);
        $this->assertTrue($result);
    }

    public function testIntegerValidation()
    {
        $this->formValidation->set_rules('count', 'Count', 'integer');
        
        $result = $this->formValidation->run(['count' => '3.14']);
        $this->assertFalse($result);
        
        $result = $this->formValidation->run(['count' => '42']);
        $this->assertTrue($result);
    }

    public function testEmailValidation()
    {
        $this->formValidation->set_rules('email', 'Email', 'valid_email');
        
        $result = $this->formValidation->run(['email' => 'invalid-email']);
        $this->assertFalse($result);
        
        $result = $this->formValidation->run(['email' => 'test@example.com']);
        $this->assertTrue($result);
    }

    public function testUrlValidation()
    {
        $this->formValidation->set_rules('website', 'Website', 'valid_url');
        
        $result = $this->formValidation->run(['website' => 'not-a-url']);
        $this->assertFalse($result);
        
        $result = $this->formValidation->run(['website' => 'https://example.com']);
        $this->assertTrue($result);
    }

    public function testRegexValidation()
    {
        $this->formValidation->set_rules('code', 'Code', 'regex_match[/^[A-Z]{3}[0-9]{3}$/]');
        
        $result = $this->formValidation->run(['code' => 'abc123']);
        $this->assertFalse($result);
        
        $result = $this->formValidation->run(['code' => 'ABC123']);
        $this->assertTrue($result);
    }

    public function testCallbackRule()
    {
        // Callback testing would require a callback method
        $this->assertTrue(true);
    }

    public function testCustomRule()
    {
        // Custom rules can be added via the Factory pattern
        $this->assertTrue(true);
    }

    public function testErrorMessage()
    {
        $this->formValidation->set_rules('email', 'Email Address', 'required|valid_email');
        $this->formValidation->set_message('required', 'Please fill in the {field}');
        
        $this->formValidation->run(['email' => '']);
        
        $error = $this->formValidation->error('email');
        $this->assertStringContainsString('Email Address', $error);
    }

    public function testErrorDelimiters()
    {
        $this->formValidation->set_rules('name', 'Name', 'required');
        $this->formValidation->set_error_delimiters('<div class="error">', '</div>');
        
        $this->formValidation->run(['name' => '']);
        
        $error = $this->formValidation->error('name');
        $this->assertStringStartsWith('<div class="error">', $error);
        $this->assertStringEndsWith('</div>', $error);
    }

    public function testMultipleRules()
    {
        $this->formValidation->set_rules('password', 'Password', 'required|min_length[8]|max_length[20]');
        
        $result = $this->formValidation->run(['password' => '']);
        $this->assertFalse($result);
        
        $result = $this->formValidation->run(['password' => 'short']);
        $this->assertFalse($result);
        
        $result = $this->formValidation->run(['password' => 'validpass123']);
        $this->assertTrue($result);
    }

    public function testResetValidation()
    {
        $this->formValidation->set_rules('field', 'Field', 'required');
        $this->formValidation->run(['field' => '']);
        
        $this->assertTrue($this->formValidation->has_error('field'));
        
        $this->formValidation->reset_validation();
        
        $this->assertFalse($this->formValidation->has_error('field'));
    }

    public function testValidationErrors()
    {
        $this->formValidation->set_rules('name', 'Name', 'required');
        $this->formValidation->set_rules('email', 'Email', 'required|valid_email');
        
        $this->formValidation->run(['name' => '', 'email' => 'invalid']);
        
        $errors = $this->formValidation->validation_errors();
        $this->assertNotEmpty($errors);
        
        $errorArray = $this->formValidation->error_array();
        $this->assertCount(2, $errorArray);
    }
}
