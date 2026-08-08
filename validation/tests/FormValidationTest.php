<?php

declare(strict_types=1);

namespace Kodhe\Framework\Validation\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Validation\FormValidation;
use Kodhe\Framework\Validation\Validators\RequiredValidator;
use Kodhe\Framework\Validation\Validators\NumericValidator;
use Kodhe\Framework\Validation\Validators\IntegerValidator;
use Kodhe\Framework\Validation\Validators\EmailValidator;
use Kodhe\Framework\Validation\Validators\UrlValidator;
use Kodhe\Framework\Validation\Validators\RegexValidator;
use Kodhe\Framework\Validation\Contracts\RuleInterface;

/**
 * Form Validation Tests
 */
class FormValidationTest extends TestCase
{
    protected FormValidation $validation;

    protected function setUp(): void
    {
        parent::setUp();
        // Note: In a real environment, you'd need to mock the CodeIgniter instance
        // For now we test the validators directly
    }

    /**
     * Test Required Validator
     */
    public function testRequiredValidator(): void
    {
        $validator = new RequiredValidator();
        
        $this->assertTrue($validator->validate('test'));
        $this->assertTrue($validator->validate('123'));
        $this->assertTrue($validator->validate([1, 2]));
        $this->assertFalse($validator->validate(''));
        $this->assertFalse($validator->validate(null));
        $this->assertFalse($validator->validate([]));
    }

    /**
     * Test Numeric Validator
     */
    public function testNumericValidator(): void
    {
        $validator = new NumericValidator();
        
        $this->assertTrue($validator->validate('123'));
        $this->assertTrue($validator->validate('123.45'));
        $this->assertTrue($validator->validate('-123'));
        $this->assertTrue($validator->validate('+123'));
        $this->assertTrue($validator->validate(''));
        $this->assertFalse($validator->validate('abc'));
        $this->assertFalse($validator->validate('123abc'));
    }

    /**
     * Test Integer Validator
     */
    public function testIntegerValidator(): void
    {
        $validator = new IntegerValidator();
        
        $this->assertTrue($validator->validate('123'));
        $this->assertTrue($validator->validate('-123'));
        $this->assertTrue($validator->validate('+123'));
        $this->assertTrue($validator->validate(''));
        $this->assertFalse($validator->validate('123.45'));
        $this->assertFalse($validator->validate('abc'));
    }

    /**
     * Test Email Validator
     */
    public function testEmailValidator(): void
    {
        $validator = new EmailValidator();
        
        $this->assertTrue($validator->validate('test@example.com'));
        $this->assertTrue($validator->validate('user.name@domain.co.uk'));
        $this->assertTrue($validator->validate(''));
        $this->assertFalse($validator->validate('invalid'));
        $this->assertFalse($validator->validate('test@'));
        $this->assertFalse($validator->validate('@example.com'));
    }

    /**
     * Test URL Validator
     */
    public function testUrlValidator(): void
    {
        $validator = new UrlValidator();
        
        $this->assertTrue($validator->validate('http://example.com'));
        $this->assertTrue($validator->validate('https://example.com'));
        $this->assertTrue($validator->validate('example.com'));
        $this->assertTrue($validator->validate(''));
        $this->assertFalse($validator->validate('not-a-url'));
        $this->assertFalse($validator->validate('ftp://example.com'));
    }

    /**
     * Test Regex Validator
     */
    public function testRegexValidator(): void
    {
        $validator = new RegexValidator();
        $validator->setParameter('/^[A-Z]+$/');
        
        $this->assertTrue($validator->validate('ABC'));
        $this->assertTrue($validator->validate(''));
        $this->assertFalse($validator->validate('abc'));
        $this->assertFalse($validator->validate('123'));
    }

    /**
     * Test Callback Validator
     */
    public function testCallbackValidator(): void
    {
        $callback = function($value) {
            return strlen($value) > 3;
        };
        
        $validator = new \Kodhe\Framework\Validation\Validators\CallableValidator($callback, 'custom_callback');
        
        $this->assertTrue($validator->validate('test'));
        $this->assertTrue($validator->validate('testing'));
        $this->assertFalse($validator->validate('ab'));
    }

    /**
     * Test Custom Rule Implementation
     */
    public function testCustomRule(): void
    {
        // Create a custom rule implementing RuleInterface
        $customRule = new class implements RuleInterface {
            protected string $name = 'custom';
            protected mixed $parameter = null;
            
            public function validate(mixed $value): bool
            {
                return $value === 'valid';
            }
            
            public function getMessage(string $field, string $label): string
            {
                return "The {$label} field must be 'valid'";
            }
            
            public function setParameter(mixed $param): self
            {
                $this->parameter = $param;
                return $this;
            }
            
            public function getName(): string
            {
                return $this->name;
            }
        };
        
        $this->assertTrue($customRule->validate('valid'));
        $this->assertFalse($customRule->validate('invalid'));
        $this->assertEquals("The Test field must be 'valid'", $customRule->getMessage('test', 'Test'));
    }

    /**
     * Test Error Message Formatting
     */
    public function testErrorMessageFormatting(): void
    {
        $messageManager = new \Kodhe\Framework\Validation\Messages\MessageManager();
        
        // Test {field} and {param} replacement
        $message = $messageManager->get('required', 'username');
        $formatted = $messageManager->format($message, 'Username');
        $this->assertStringContainsString('Username', $formatted);
        
        // Test legacy %s format
        $legacyMessage = 'The %s field is required';
        $formatted = $messageManager->format($legacyMessage, 'Username', '');
        $this->assertStringContainsString('Username', $formatted);
    }

    /**
     * Test Rule Parser
     */
    public function testRuleParser(): void
    {
        // Test string parsing
        $rules = \Kodhe\Framework\Validation\Support\RuleParser::parse('required|numeric|min_length[5]');
        $this->assertEquals(['required', 'numeric', 'min_length[5]'], $rules);
        
        // Test array passthrough
        $rules = \Kodhe\Framework\Validation\Support\RuleParser::parse(['required', 'numeric']);
        $this->assertEquals(['required', 'numeric'], $rules);
        
        // Test rule extraction
        [$name, $param] = \Kodhe\Framework\Validation\Support\RuleParser::extractRuleAndParam('min_length[5]');
        $this->assertEquals('min_length', $name);
        $this->assertEquals('5', $param);
    }

    /**
     * Test Validator Factory
     */
    public function testValidatorFactory(): void
    {
        // Register a validator
        \Kodhe\Framework\Validation\Factory\ValidatorFactory::register(
            'test_rule',
            \Kodhe\Framework\Validation\Validators\RequiredValidator::class
        );
        
        $this->assertTrue(\Kodhe\Framework\Validation\Factory\ValidatorFactory::has('test_rule'));
        
        $validator = \Kodhe\Framework\Validation\Factory\ValidatorFactory::make('test_rule');
        $this->assertInstanceOf(\Kodhe\Framework\Validation\Contracts\ValidatorInterface::class, $validator);
    }

    /**
     * Test Rule Cache
     */
    public function testRuleCache(): void
    {
        \Kodhe\Framework\Validation\Support\RuleCache::clear();
        
        $key = 'test_key';
        $value = ['required', 'numeric'];
        
        \Kodhe\Framework\Validation\Support\RuleCache::set($key, $value);
        
        $this->assertTrue(\Kodhe\Framework\Validation\Support\RuleCache::has($key));
        $this->assertEquals($value, \Kodhe\Framework\Validation\Support\RuleCache::get($key));
        
        // Test cache stats
        $stats = \Kodhe\Framework\Validation\Support\RuleCache::getStats();
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
    }
}
