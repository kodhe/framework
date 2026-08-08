<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Validation library
 */
class ValidationTest extends TestCase
{
    /**
     * @var \Kodhe\Framework\Validation\Validation
     */
    private $validation;

    protected function setUp(): void
    {
        parent::setUp();
        // The validation class may need dependencies
        // We'll test the helper functions and basic validation rules
    }

    /**
     * Test valid_email rule
     */
    public function testValidEmailRule(): void
    {
        $this->assertTrue(valid_email('test@example.com'));
        $this->assertFalse(valid_email('invalid-email'));
        $this->assertFalse(valid_email('test@'));
        $this->assertFalse(valid_email('@example.com'));
    }

    /**
     * Test valid_emails rule (multiple emails)
     */
    public function testValidEmailsRule(): void
    {
        $this->assertTrue(valid_emails('test@example.com,another@domain.org'));
        $this->assertFalse(valid_emails('invalid-email,another@domain.org'));
        $this->assertFalse(valid_emails('test@example.com,invalid'));
    }

    /**
     * Test min_length rule
     */
    public function testMinLengthRule(): void
    {
        $this->assertTrue(min_length('hello', 3));
        $this->assertTrue(min_length('hello', 5));
        $this->assertFalse(min_length('hi', 5));
        $this->assertTrue(min_length('', 0));
    }

    /**
     * Test max_length rule
     */
    public function testMaxLengthRule(): void
    {
        $this->assertTrue(max_length('hello', 10));
        $this->assertTrue(max_length('hello', 5));
        $this->assertFalse(max_length('hello world', 5));
    }

    /**
     * Test exact_length rule
     */
    public function testExactLengthRule(): void
    {
        $this->assertTrue(exact_length('hello', 5));
        $this->assertFalse(exact_length('hello', 3));
        $this->assertFalse(exact_length('hello', 10));
    }

    /**
     * Test greater_than rule
     */
    public function testGreaterThanRule(): void
    {
        $this->assertTrue(greater_than(10, 5));
        $this->assertFalse(greater_than(5, 5));
        $this->assertFalse(greater_than(3, 10));
    }

    /**
     * Test less_than rule
     */
    public function testLessThanRule(): void
    {
        $this->assertTrue(less_than(3, 5));
        $this->assertFalse(less_than(5, 5));
        $this->assertFalse(less_than(10, 5));
    }

    /**
     * Test alpha rule (only alphabetic characters)
     */
    public function testAlphaRule(): void
    {
        $this->assertTrue(alpha('hello'));
        $this->assertTrue(alpha('HelloWorld'));
        $this->assertFalse(alpha('hello123'));
        $this->assertFalse(alpha('hello world'));
        $this->assertFalse(alpha('hello-world'));
    }

    /**
     * Test alpha_numeric rule
     */
    public function testAlphaNumericRule(): void
    {
        $this->assertTrue(alpha_numeric('hello123'));
        $this->assertTrue(alpha_numeric('Hello123'));
        $this->assertFalse(alpha_numeric('hello-123'));
        $this->assertFalse(alpha_numeric('hello 123'));
    }

    /**
     * Test alpha_dash rule (alphanumeric, underscores, and dashes)
     */
    public function testAlphaDashRule(): void
    {
        $this->assertTrue(alpha_dash('hello-world'));
        $this->assertTrue(alpha_dash('hello_world'));
        $this->assertTrue(alpha_dash('hello123'));
        $this->assertFalse(alpha_dash('hello world'));
        $this->assertFalse(alpha_dash('hello@world'));
    }

    /**
     * Test numeric rule
     */
    public function testNumericRule(): void
    {
        $this->assertTrue(numeric('123'));
        $this->assertTrue(numeric('0'));
        $this->assertTrue(numeric('-123'));
        $this->assertTrue(numeric('12.34'));
        $this->assertFalse(numeric('12abc'));
        $this->assertFalse(numeric('abc'));
    }

    /**
     * Test integer rule
     */
    public function testIntegerRule(): void
    {
        $this->assertTrue(integer('123'));
        $this->assertTrue(integer('0'));
        $this->assertTrue(integer('-123'));
        $this->assertFalse(integer('12.34'));
        $this->assertFalse(integer('abc'));
    }

    /**
     * Test decimal rule
     */
    public function testDecimalRule(): void
    {
        $this->assertTrue(decimal('12.34'));
        $this->assertTrue(decimal('0.5'));
        $this->assertTrue(decimal('-12.34'));
        $this->assertTrue(decimal('123')); // integers are also decimals
        $this->assertFalse(decimal('abc'));
    }

    /**
     * Test is_unique rule (placeholder - would need database)
     */
    public function testIsUniqueRule(): void
    {
        // This rule requires database access
        // We just test that the function exists and returns a value
        $result = is_unique('test', 'table.field');
        $this->assertIsBool($result);
    }

    /**
     * Test is_natural rule (positive whole numbers including zero)
     */
    public function testIsNaturalRule(): void
    {
        $this->assertTrue(is_natural('0'));
        $this->assertTrue(is_natural('1'));
        $this->assertTrue(is_natural('100'));
        $this->assertFalse(is_natural('-1'));
        $this->assertFalse(is_natural('1.5'));
        $this->assertFalse(is_natural('abc'));
    }

    /**
     * Test is_natural_no_zero rule (positive whole numbers excluding zero)
     */
    public function testIsNaturalNoZeroRule(): void
    {
        $this->assertTrue(is_natural_no_zero('1'));
        $this->assertTrue(is_natural_no_zero('100'));
        $this->assertFalse(is_natural_no_zero('0'));
        $this->assertFalse(is_natural_no_zero('-1'));
        $this->assertFalse(is_natural_no_zero('1.5'));
    }

    /**
     * Test valid_ip rule
     */
    public function testValidIpRule(): void
    {
        $this->assertTrue(valid_ip('192.168.1.1'));
        $this->assertTrue(valid_ip('127.0.0.1'));
        $this->assertTrue(valid_ip('255.255.255.255'));
        $this->assertFalse(valid_ip('256.256.256.256'));
        $this->assertFalse(valid_ip('192.168.1'));
        $this->assertFalse(valid_ip('invalid'));
    }

    /**
     * Test valid_ipv4 rule
     */
    public function testValidIpv4Rule(): void
    {
        $this->assertTrue(valid_ipv4('192.168.1.1'));
        $this->assertTrue(valid_ipv4('127.0.0.1'));
        $this->assertFalse(valid_ipv4('2001:0db8:85a3::8a2e:0370:7334')); // IPv6
        $this->assertFalse(valid_ipv4('256.256.256.256'));
    }

    /**
     * Test valid_ipv6 rule
     */
    public function testValidIpv6Rule(): void
    {
        $this->assertTrue(valid_ipv6('2001:0db8:85a3::8a2e:0370:7334'));
        $this->assertTrue(valid_ipv6('::1'));
        $this->assertFalse(valid_ipv6('192.168.1.1')); // IPv4
        $this->assertFalse(valid_ipv6('invalid'));
    }

    /**
     * Test required rule
     */
    public function testRequiredRule(): void
    {
        $this->assertTrue(required('hello'));
        $this->assertTrue(required('0')); // String "0" is not empty
        $this->assertFalse(required(''));
        $this->assertFalse(required(null));
    }

    /**
     * Test matches rule
     */
    public function testMatchesRule(): void
    {
        $data = ['password' => 'secret123', 'confirm' => 'secret123'];
        $this->assertTrue(matches('secret123', 'password', $data));
        
        $data = ['password' => 'secret123', 'confirm' => 'different'];
        $this->assertFalse(matches('different', 'password', $data));
    }

    /**
     * Test differs rule
     */
    public function testDiffersRule(): void
    {
        $data = ['old' => 'value1', 'new' => 'value2'];
        $this->assertTrue(differs('value2', 'old', $data));
        
        $data = ['old' => 'same', 'new' => 'same'];
        $this->assertFalse(differs('same', 'old', $data));
    }

    /**
     * Test contains rule (string contains substring)
     */
    public function testContainsRule(): void
    {
        $this->assertTrue(contains('world', 'hello world'));
        $this->assertFalse(contains('foo', 'hello world'));
    }

    /**
     * Test in_list rule
     */
    public function testInListRule(): void
    {
        $this->assertTrue(in_list('apple', 'apple,banana,cherry'));
        $this->assertTrue(in_list('banana', 'apple,banana,cherry'));
        $this->assertFalse(in_list('grape', 'apple,banana,cherry'));
    }

    /**
     * Test regex_match rule
     */
    public function testRegexMatchRule(): void
    {
        $this->assertTrue(regex_match('hello', '/^[a-z]+$/'));
        $this->assertTrue(regex_match('123', '/^\d+$/'));
        $this->assertFalse(regex_match('hello123', '/^[a-z]+$/'));
    }

    /**
     * Test timezone rule
     */
    public function testTimezoneRule(): void
    {
        $this->assertTrue(timezone('UTC'));
        $this->assertTrue(timezone('America/New_York'));
        $this->assertTrue(timezone('Europe/London'));
        $this->assertFalse(timezone('Invalid/Timezone'));
    }

    /**
     * Test valid_url rule
     */
    public function testValidUrlRule(): void
    {
        $this->assertTrue(valid_url('http://example.com'));
        $this->assertTrue(valid_url('https://example.com/path'));
        $this->assertTrue(valid_url('http://www.example.com'));
        $this->assertFalse(valid_url('not-a-url'));
        $this->assertFalse(valid_url('ftp://example.com')); // Only http/https by default
    }

    /**
     * Test valid_base64 rule
     */
    public function testValidBase64Rule(): void
    {
        $validBase64 = base64_encode('hello world');
        $this->assertTrue(valid_base64($validBase64));
        $this->assertFalse(valid_base64('invalid base64!!!'));
    }

    /**
     * Test trim rule
     */
    public function testTrimRule(): void
    {
        $this->assertEquals('hello', trim('  hello  '));
        $this->assertEquals('hello world', trim('  hello world  '));
    }

    /**
     * Test strip_tags rule
     */
    public function testStripTagsRule(): void
    {
        $this->assertEquals('hello', strip_tags('<b>hello</b>'));
        $this->assertEquals('hello world', strip_tags('<p>hello <b>world</b></p>'));
    }

    /**
     * Test escape_str rule (basic test - actual implementation may vary)
     */
    public function testEscapeStrRule(): void
    {
        // Basic test - the actual implementation depends on database driver
        $result = escape_str("hello'world");
        $this->assertIsString($result);
    }

    /**
     * Test xss_clean rule (if available)
     */
    public function testXssCleanRule(): void
    {
        if (function_exists('xss_clean')) {
            $result = xss_clean('<script>alert("xss")</script>');
            $this->assertIsString($result);
        } else {
            // Skip if function doesn't exist
            $this->markTestSkipped('xss_clean function not available');
        }
    }

    /**
     * Test encode_php_tags rule
     */
    public function testEncodePhpTagsRule(): void
    {
        $result = encode_php_tags('<?php echo "hello"; ?>');
        $this->assertStringContainsString('&lt;?php', $result);
        $this->assertStringContainsString('?&gt;', $result);
    }
}
