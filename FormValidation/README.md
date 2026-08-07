# Form Validation Library - CodeIgniter 3 Modular Refactor

Modular, PSR-4 compliant Form Validation library refactored from CodeIgniter 3.

## Struktur Direktori

```
FormValidation/
├── FormValidation.php          # Main class (API kompatibel CI3)
├── Contracts/                  # Interfaces
│   ├── RuleInterface.php
│   ├── ValidatorInterface.php
│   └── FilterInterface.php
├── Rules/                      # Rule Chain implementation
│   └── RuleChain.php
├── Validators/                 # Concrete validators
│   ├── RequiredValidator.php
│   ├── NumericValidator.php
│   ├── IntegerValidator.php
│   ├── EmailValidator.php
│   ├── UrlValidator.php
│   ├── RegexValidator.php
│   ├── MinLengthValidator.php
│   ├── MaxLengthValidator.php
│   └── MatchesValidator.php
├── Filters/                    # Input filters
│   ├── TrimFilter.php
│   └── XssCleanFilter.php
├── Factory/                    # Factory pattern
│   └── ValidatorFactory.php
├── Support/                    # Helper classes
│   └── RuleCache.php
├── Messages/                   # Message handling
│   └── MessageStore.php
├── ValueObjects/               # Value objects
│   └── RuleObject.php
├── Exceptions/                 # Custom exceptions
│   └── FormValidationException.php
├── tests/                      # PHPUnit tests
│   └── FormValidationTest.php
├── composer.json
└── phpunit.xml
```

## API Kompatibel CodeIgniter 3

### Methods:
- `set_rules($field, $label, $rules)` - Set validation rules
- `run($data = null)` - Execute validation
- `reset_validation()` - Reset validation state
- `set_message($rule, $message)` - Set custom error message
- `set_error_delimiters($prefix, $suffix)` - Set error delimiters
- `error($field)` - Get single field error
- `error_array()` - Get all errors as array
- `validation_errors($prefix = '', $suffix = '')` - Get formatted error string

## Pola Desain

1. **Chain of Responsibility** - RuleChain untuk validasi berantai
2. **Strategy** - ValidatorInterface dengan berbagai implementasi
3. **Factory** - ValidatorFactory untuk membuat validator instances
4. **Rule Object** - RuleObject untuk encapsulasi rule configuration
5. **Dependency Injection** - Inject dependencies via constructor

## Optimasi Performa

- **Cache Compiled Rules** - RuleCache untuk caching aturan terkompilasi
- **Lazy Validation** - Validasi hanya saat run() dipanggil
- **Reuse Validator Instances** - Factory menyimpan instance validator

## Instalasi

```bash
composer install
```

## Testing

```bash
vendor/bin/phpunit
```

## Contoh Penggunaan

```php
use Kodhe\FormValidation\FormValidation;

$form = new FormValidation();

// Set rules (CI3 style)
$form->set_rules('username', 'Username', 'required|min_length[3]');
$form->set_rules('email', 'Email', 'required|valid_email');
$form->set_rules('password', 'Password', 'required|min_length[8]|max_length[20]');

// Run validation
if ($form->run($_POST)) {
    // Validation passed
} else {
    // Get errors
    echo $form->validation_errors();
    // Or
    echo $form->error('username');
}

// Custom error messages
$form->set_message('required', '{field} tidak boleh kosong');
$form->set_message('valid_email', '{field} harus email yang valid');

// Custom delimiters
$form->set_error_delimiters('<div class="error">', '</div>');
```

## Kompatibilitas

- ✅ 100% Backward Compatible dengan CodeIgniter 3 API
- ✅ PSR-4 Autoloading
- ✅ PSR-12 Coding Standards
- ✅ PHPUnit Ready
- ✅ PHP 7.2+

## Menambah Validator Custom

```php
use Kodhe\FormValidation\Contracts\ValidatorInterface;

class CustomValidator implements ValidatorInterface
{
    public function validate($value, $params = [])
    {
        // Your validation logic
        return true;
    }

    public function getMessage(): string
    {
        return 'Custom error message';
    }
}

// Register di ValidatorFactory
```
