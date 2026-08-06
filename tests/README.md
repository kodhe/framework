# PHPUnit Tests for Kodhe Framework

This directory contains unit tests for the Kodhe framework components.

## Test Files

### ParserTest.php
Tests for the Parser library (`/workspace/parser/src/Parser.php`):
- Simple variable replacement
- Tag pair (loop) functionality
- Nested tag pairs
- Custom delimiters
- Empty template handling
- Multiple variables
- Special characters in data
- Non-existent variables
- Null values in data

### UnitTestTest.php
Tests for the UnitTest class (`/workspace/test/src/UnitTest.php`):
- Instantiation and property defaults
- Boolean, string, integer, and array comparisons
- Type checking functions (is_string, is_int, is_array, etc.)
- Strict vs loose comparison modes
- Active/inactive test states
- Template customization
- Result reporting
- Helper functions (is_true, is_false)

### CalendarTest.php
Tests for the Calendar library (`/workspace/calendar/src/Calendar.php`):
- Calendar generation
- Month and day name retrieval
- Preferences initialization
- Days in month calculation
- Leap year handling
- Custom templates

### ValidationTest.php
Tests for validation helper functions:
- Email validation (valid_email, valid_emails)
- Length validation (min_length, max_length, exact_length)
- Numeric validation (numeric, integer, decimal, greater_than, less_than)
- String format validation (alpha, alpha_numeric, alpha_dash)
- Natural number validation (is_natural, is_natural_no_zero)
- IP address validation (valid_ip, valid_ipv4, valid_ipv6)
- URL validation (valid_url)
- Required field validation
- Field comparison (matches, differs)
- List validation (in_list)
- Regex matching
- Timezone validation
- Base64 validation
- XSS and PHP tag encoding

## Running Tests

To run the tests, you need PHPUnit installed. Install dependencies first:

```bash
composer install
```

Then run PHPUnit:

```bash
./vendor/bin/phpunit tests/
```

Or with a specific test file:

```bash
./vendor/bin/phpunit tests/ParserTest.php
```

## Test Structure

Each test file follows the PHPUnit best practices:
- Tests extend `PHPUnit\Framework\TestCase`
- Test methods are prefixed with `test`
- Assertions use descriptive messages
- Setup and teardown methods are used when needed
- Edge cases and error conditions are tested

## Adding New Tests

When adding new tests:
1. Create a new test class following the naming convention `*Test.php`
2. Place it in the `/workspace/tests/` directory
3. Use appropriate namespaces (`Kodhe\Tests`)
4. Follow existing test patterns
5. Include both positive and negative test cases
6. Test edge cases and boundary conditions
