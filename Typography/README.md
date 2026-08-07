# Typography Library - CodeIgniter 3 Modular Refactoring

Library Typography untuk CodeIgniter 3 yang telah direfactor menjadi modular, PSR-4/PSR-12 compliant, dengan tetap mempertahankan 100% backward compatibility.

## 📂 Struktur

```
Typography/
├── Typography.php                # Main class (API CI3 compatible)
├── Contracts/
│   ├── FormatterInterface.php    # Interface untuk formatter
│   └── ParserInterface.php       # Interface untuk parser
├── Formatters/
│   ├── SmartQuoteFormatter.php   # Smart quotes (" → &ldquo;)
│   ├── CharacterFormatter.php    # Karakter khusus (--, ..., dll)
│   └── ParagraphFormatter.php    # Paragraf dan line breaks
├── Parsers/
│   ├── HtmlParser.php            # Parser HTML dengan proteksi tag
│   └── TextParser.php            # Parser teks biasa
├── Factory/
│   └── TypographyFactory.php     # Factory pattern
├── Support/
│   ├── RegexCache.php            # Cache regex untuk performa
│   └── TagProtector.php          # Proteksi tag dan braced quotes
├── ValueObjects/
│   └── TypographyConfig.php      # Configuration value object
├── tests/
│   └── TypographyTest.php        # PHPUnit test cases
├── composer.json                 # PSR-4 autoloading
├── phpunit.xml                   # PHPUnit configuration
└── README.md                     # Dokumentasi
```

## 🔌 API (100% CI3 Compatible)

### Method yang Tersedia

```php
// Auto-format teks menjadi typography yang benar
$this->typography->auto_typography($str, $reduce_linebreaks = FALSE);

// Format karakter khusus saja
$this->typography->format_characters($str);

// Ubah newline ke <br> kecuali di dalam <pre>
$this->typography->nl2br_except_pre($str);

// Lindungi tanda kutip dalam kurung kurawal
$this->typography->protect_braced_quotes($str, $temp_swap = []);

// Set custom delimiters
$this->typography->set_delimiters($l = '{', $r = '}');
```

## 🎨 Design Patterns

1. **Strategy Pattern**: Formatter dan Parser dapat diganti melalui interface
2. **Pipeline Pattern**: Proses formatting berjalan sebagai pipeline
3. **Factory Pattern**: TypographyFactory untuk membuat instance
4. **Dependency Injection**: Constructor injection untuk testing mudah
5. **Value Object**: TypographyConfig untuk konfigurasi immutable

## ⚡ Fitur Performa

- **Cache Regex**: Pola regex dikompilasi sekali dan digunakan kembali
- **Lazy Formatting**: Formatter hanya diinisialisasi saat dibutuhkan
- **Tag Protection**: Mekanisme token swap yang efisien untuk melindungi tag HTML

## 🧪 Testing

Jalankan test dengan PHPUnit:

```bash
cd Typography
composer install
./vendor/bin/phpunit
```

### Test Coverage

- ✅ Auto Typography (basic, nested structures)
- ✅ Smart Quotes (double & single)
- ✅ Character Formatting (em-dash, ellipsis, copyright, trademark)
- ✅ Paragraph Formatting (dengan/tanpa reduce line breaks)
- ✅ HTML Preservation (tag tidak rusak)
- ✅ Braced Quotes Protection
- ✅ Custom Delimiters
- ✅ Empty String Handling
- ✅ Backward Compatibility

## 🚀 Cara Penggunaan

### 1. Penggunaan Standar (CI3 Style)

```php
$this->load->library('typography');

$text = "Hello \"world\".\n\nNew paragraph.";
echo $this->typography->auto_typography($text);
```

### 2. Penggunaan Modular (Direct Instantiation)

```php
use Kodhe\Typography\Typography;
use Kodhe\Typography\Factory\TypographyFactory;

// Via Factory dengan config
$typography = TypographyFactory::makeWithConfig([
    'reduce_linebreaks' => true
]);

// Via Factory default
$typography = TypographyFactory::make();

// Via Dependency Injection (untuk custom/testing)
$typography = new Typography(
    new \Kodhe\Typography\Parsers\HtmlParser(),
    new \Kodhe\Typography\Formatters\SmartQuoteFormatter(),
    new \Kodhe\Typography\Formatters\CharacterFormatter(),
    new \Kodhe\Typography\Formatters\ParagraphFormatter(true),
    new \Kodhe\Typography\Support\TagProtector()
);

echo $typography->auto_typography($text);
```

### 3. Custom Formatter

```php
use Kodhe\Typography\Contracts\FormatterInterface;

class CustomFormatter implements FormatterInterface
{
    public function format(string $text): string
    {
        // Custom formatting logic
        return strtoupper($text);
    }
}

$typography = TypographyFactory::makeWithComponents(
    new HtmlParser(),
    new SmartQuoteFormatter(),
    new CharacterFormatter(),
    new CustomFormatter(),
    new TagProtector()
);
```

## 📦 Instalasi via Composer

```json
{
    "autoload": {
        "psr-4": {
            "Kodhe\\Typography\\": "path/to/Typography/"
        }
    }
}
```

Kemudian jalankan:
```bash
composer dump-autoload
```

## ✨ Fitur Template

- **Smart Quotes**: `"quote"` → `&ldquo;quote&rdquo;`
- **Em-dash**: `--` atau `---` → `&#8212;`
- **Ellipsis**: `...` → `&#8230;`
- **Copyright**: `(c)` → `&#169;`
- **Registered**: `(r)` → `&#174;`
- **Trademark**: `(tm)` → `&#8482;`
- **Single Quotes**: `'s` → `&rsquo;s`

## 📝 Lisensi

MIT License
