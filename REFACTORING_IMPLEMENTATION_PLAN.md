# Comprehensive Refactoring Implementation Plan

## Target Goals
✅ Modular Architecture  
✅ PSR-4 Autoloading  
✅ PSR-12 Coding Standards  
✅ PHPUnit Ready  
✅ 100% Backward Compatible with CodeIgniter 3  

## Current Status Assessment

### ✅ Already Completed
- Framework split into modular packages (email, calendar, parser, encryption, http, etc.)
- PSR-4 autoloading configured in root composer.json
- PHP 8.1+ requirement established
- Some packages have proper test suites (parser, calendar, encryption)
- Modern namespace structure (Kodhe\Framework\*, Kodhe\Email\*, etc.)

### 🔄 In Progress / Needs Work
1. **Email Module** - Critical priority
   - Email.php: 2458 lines - needs decomposition
   - Missing tests directory
   - Missing phpunit.xml
   - Public properties should be typed
   
2. **Standardization Across Modules**
   - Consistent namespace patterns
   - Uniform test structure
   - Common phpunit.xml configuration
   - README templates

3. **PSR-12 Compliance**
   - Add strict_types=1 to all files
   - Convert public properties to typed properties
   - Standardize naming conventions
   - Fix spacing/indentation

4. **Backward Compatibility Layer**
   - Ensure compat.php files exist where needed
   - Maintain CI3 function signatures
   - Preserve legacy access patterns

## Implementation Phases

### Phase 1: Email Module Refactoring (CRITICAL)
**Priority: HIGH** - Largest technical debt

#### 1.1 Decompose Email.php
Break down the monolithic 2458-line class into focused components:

```
src/
├── Email.php (main facade, backward compatible)
├── Contracts/
│   ├── EmailInterface.php ✅
│   └── TransportInterface.php ✅
├── Message/
│   ├── EmailMessage.php ✅
│   ├── Attachment.php (NEW)
│   └── HeaderCollection.php (NEW)
├── Transports/
│   ├── MailTransport.php ✅
│   ├── SendmailTransport.php ✅
│   └── SmtpTransport.php ✅
├── Encoding/
│   ├── EncoderInterface.php ✅
│   ├── Base64Encoder.php ✅
│   └── QuotedPrintableEncoder.php ✅
├── Validation/
│   └── EmailAddressValidator.php ✅
├── Traits/
│   ├── ConfigurableTrait.php ✅
│   └── DebugTrait.php ✅
└── helpers/
    └── email.php ✅
```

**New Classes to Create:**
- `Attachment.php` - Handle attachment data
- `HeaderCollection.php` - Manage email headers
- `EmailBuilder.php` - Fluent interface for building emails

#### 1.2 Add Test Suite
Create `/workspace/email/tests/`:
- EmailTest.php
- Message/EmailMessageTest.php
- Transports/MailTransportTest.php
- Transports/SmtpTransportTest.php
- Validation/EmailAddressValidatorTest.php

#### 1.3 Add phpunit.xml
Configure PHPUnit for email module

### Phase 2: Standardize All Modules

#### 2.1 Composer.json Standardization
Ensure all modules have:
```json
{
  "require": { "php": ">=8.1" },
  "require-dev": { "phpunit/phpunit": "^9.0|^10.0" },
  "autoload": { "psr-4": { "Kodhe\\Module\\": "src/" } },
  "autoload-dev": { "psr-4": { "Kodhe\\Module\\Tests\\": "tests/" } },
  "scripts": {
    "test": "phpunit",
    "test:coverage": "phpunit --coverage-html coverage"
  }
}
```

#### 2.2 PHPUnit Configuration
Create phpunit.xml for each module:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Module Test Suite">
            <directory suffix="Test.php">tests</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>vendor</directory>
            <directory>tests</directory>
        </exclude>
    </coverage>
</phpunit>
```

#### 2.3 Module Checklist
- [x] agent
- [x] cache
- [x] calendar
- [ ] cart - needs tests
- [ ] database - needs tests
- [ ] driver - needs tests
- [ ] email - CRITICAL (needs decomposition + tests)
- [ ] encrypt - needs tests
- [x] encryption (has tests)
- [x] framework (has tests)
- [ ] ftp - needs tests
- [x] http (has tests)
- [ ] image - needs tests
- [ ] javascript - needs tests
- [ ] migration - needs tests
- [ ] pagination - needs tests
- [x] parser (has tests)
- [ ] profiler - needs tests
- [ ] session - needs tests
- [ ] table - needs tests
- [ ] test - needs tests
- [ ] trackback - needs tests
- [ ] typography - needs tests
- [ ] upload - needs tests
- [ ] validation - needs tests
- [ ] view - needs tests
- [ ] xmlrpc - needs tests
- [ ] xmlrpcs - needs tests
- [ ] zip - needs tests

### Phase 3: PSR-12 Compliance Audit

#### 3.1 File Structure Requirements
All PHP files must have:
```php
<?php

declare(strict_types=1);

namespace Kodhe\Framework\Module;

// Imports sorted alphabetically
use ...

// Class implementation
```

#### 3.2 Property Declarations
Convert old-style:
```php
public $property = 'value';
```

To typed properties:
```php
public string $property = 'value';
```

#### 3.3 Method Signatures
Add type hints:
```php
public function methodName(string $param, int $count): bool
```

### Phase 4: Backward Compatibility

#### 4.1 Compatibility Files
Each module needs `src/compat.php`:
```php
<?php
/**
 * Backward compatibility layer for CodeIgniter 3
 */

if (!class_exists('CI_Email', false)) {
    class_alias(\Kodhe\Email\Email::class, 'CI_Email');
}

// Legacy function aliases
if (!function_exists('email_send')) {
    function email_send(...) {
        return \Kodhe\Email\email_send(...);
    }
}
```

#### 4.2 Legacy Loader Support
Ensure modules work with CI3's `$this->load->library()`:
```php
// In CI3 controller
$this->load->library('email');
$this->email->send(); // Should work
```

### Phase 5: Documentation

#### 5.1 README Template
Each module needs consistent README:
```markdown
# Kodhe/Module

[![PHPUnit](https://img.shields.io/badge/phpunit-ready-green)]()
[![PSR-4](https://img.shields.io/badge/PSR--4-compliant-blue)]()
[![PSR-12](https://img.shields.io/badge/PSR--12-compliant-blue)]()
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1+-purple)]()
[![CI3 Compatible](https://img.shields.io/badge/CodeIgniter-3-orange)]()

Modern, modular implementation of CodeIgniter 3 library.

## Installation

```bash
composer require kodhe/module
```

## Usage

### Modern Usage
```php
use Kodhe\Module\ClassName;
$instance = new ClassName();
```

### CodeIgniter 3 Usage
```php
$this->load->library('module');
```

## Testing
```bash
composer test
composer test:coverage
```

## Backward Compatibility
100% compatible with CodeIgniter 3 API.
```

## Immediate Next Steps

1. **Refactor Email.php** - Break into smaller classes
2. **Create Email tests** - Full test coverage
3. **Add phpunit.xml to all modules**
4. **Audit and fix PSR-12 violations**
5. **Create compat.php for all modules**
6. **Update documentation**

## Success Criteria

- [ ] All modules have phpunit.xml
- [ ] All modules have tests/ directory
- [ ] Email.php decomposed (<500 lines main class)
- [ ] 100% PSR-12 compliance (verified by PHP_CodeSniffer)
- [ ] All tests passing
- [ ] Backward compatibility verified with CI3 apps
- [ ] Documentation complete
