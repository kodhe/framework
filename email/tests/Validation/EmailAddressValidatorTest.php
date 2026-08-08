<?php

declare(strict_types=1);

namespace Kodhe\Framework\Email\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Email\Validation\EmailAddressValidator;

/**
 * Test class for EmailAddressValidator
 *
 * @package     Kodhe\Email\Tests
 * @covers      \Kodhe\Framework\Email\Validation\EmailAddressValidator
 */
class EmailAddressValidatorTest extends TestCase
{
    private EmailAddressValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new EmailAddressValidator();
    }

    public function testValidEmailAddresses(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.org',
            'user+tag@gmail.com',
            'admin@sub.domain.co.uk',
            'test123@test-domain.com',
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue($this->validator->isValid($email), "Email {$email} should be valid");
        }
    }

    public function testInvalidEmailAddresses(): void
    {
        $invalidEmails = [
            'invalid',
            '@example.com',
            'user@',
            'user@.com',
            'user name@example.com',
            '',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse($this->validator->isValid($email), "Email {$email} should be invalid");
        }
    }

    public function testIsValidList(): void
    {
        $validList = ['test1@example.com', 'test2@example.com'];
        $invalidList = ['test1@example.com', 'invalid'];

        $this->assertTrue($this->validator->isValidList($validList));
        $this->assertFalse($this->validator->isValidList($invalidList));
    }

    public function testClearCache(): void
    {
        $this->validator->isValid('test@example.com');
        $this->validator->clearCache();
        
        // After clearing cache, validation should still work
        $this->assertTrue($this->validator->isValid('test@example.com'));
    }

    public function testParseAddressesFromArray(): void
    {
        $input = ['test1@example.com', 'test2@example.com'];
        $result = $this->validator->parseAddresses($input);
        
        $this->assertEquals($input, $result);
    }

    public function testParseAddressesFromString(): void
    {
        $input = 'test1@example.com, test2@example.com, test3@example.com';
        $expected = ['test1@example.com', 'test2@example.com', 'test3@example.com'];
        $result = $this->validator->parseAddresses($input);
        
        $this->assertEquals($expected, $result);
    }

    public function testParseAddressesWithWhitespace(): void
    {
        $input = '  test1@example.com  ,  test2@example.com  ';
        $expected = ['test1@example.com', 'test2@example.com'];
        $result = $this->validator->parseAddresses($input);
        
        $this->assertEquals($expected, $result);
    }

    public function testParseAddressesWithInvalidInput(): void
    {
        $result = $this->validator->parseAddresses(null);
        $this->assertEquals([], $result);
        
        $result = $this->validator->parseAddresses(123);
        $this->assertEquals([], $result);
    }

    public function testValidationCaching(): void
    {
        // First validation
        $this->assertTrue($this->validator->isValid('cached@example.com'));
        
        // Second validation should use cache
        $this->assertTrue($this->validator->isValid('cached@example.com'));
    }
}
