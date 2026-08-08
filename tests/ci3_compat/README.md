# CodeIgniter 3 Compatibility Tests

Direktori ini berisi unit tests untuk memastikan semua API library Kodhe Framework kompatibel dengan CodeIgniter 3.

## Tujuan

Memastikan bahwa semua library dalam Kodhe Framework dapat digunakan dengan cara yang sama seperti di CodeIgniter 3, baik melalui:
1. **CI3 Style** - Menggunakan `$this->load->library()` dan mengakses properti/methods publik
2. **Modern PSR-4** - Menggunakan namespace dan dependency injection

## Struktur Test

Setiap library memiliki file test terpisah yang menguji:
- Inisialisasi (dengan dan tanpa konfigurasi)
- Semua methods publik
- Properti publik
- Perilaku default
- Edge cases

## Menjalankan Tests

### Jalankan Semua Tests CI3 Compatibility

```bash
cd /workspace
composer test -- --testsuite ci3-compat
```

### Jalankan Test Spesifik Library

```bash
# Email Library
vendor/bin/phpunit --testsuite ci3-compat --filter EmailTest

# Upload Library  
vendor/bin/phpunit --testsuite ci3-compat --filter UploadTest

# Database Library
vendor/bin/phpunit --testsuite ci3-compat --filter DatabaseTest
```

### Jalankan dengan Coverage

```bash
composer test:coverage -- --testsuite ci3-compat
```

## Libraries yang Di-test

- ✅ Agent
- ✅ Cache
- ✅ Calendar
- ✅ Cart
- ✅ Database
- ✅ Driver
- ✅ Email
- ✅ Encrypt
- ✅ Encryption
- ✅ FTP
- ✅ Image
- ✅ Javascript
- ✅ Migration
- ✅ Pagination
- ✅ Parser
- ✅ Profiler
- ✅ Session
- ✅ Table
- ✅ Trackback
- ✅ Typography
- ✅ Upload
- ✅ Validation
- ✅ View
- ✅ XML-RPC
- ✅ ZIP

## Contoh Test

```php
<?php

namespace Kodhe\Framework\Tests\CI3Compat;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Email\Email;

/**
 * Test Email library compatibility with CodeIgniter 3
 */
class EmailTest extends TestCase
{
    public function testDefaultProperties(): void
    {
        $email = new Email();
        
        // CI3 default properties
        $this->assertEquals('CodeIgniter', $email->useragent);
        $this->assertEquals('mail', $email->protocol);
        $this->assertEquals('text', $email->mailtype);
        $this->assertEquals('UTF-8', $email->charset);
        $this->assertEquals(3, $email->priority);
        $this->assertTrue($email->wordwrap);
        $this->assertEquals(76, $email->wrapchars);
    }
    
    public function testInitializeWithConfig(): void
    {
        $config = [
            'protocol' => 'smtp',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'mailtype' => 'html',
        ];
        
        $email = new Email($config);
        
        $this->assertEquals('smtp', $email->protocol);
        $this->assertEquals('smtp.example.com', $email->smtp_host);
        $this->assertEquals(587, $email->smtp_port);
        $this->assertEquals('html', $email->mailtype);
    }
    
    public function testFromMethod(): void
    {
        $email = new Email();
        $result = $email->from('test@example.com', 'Test User');
        
        // Should return $this for chaining (CI3 style)
        $this->assertSame($email, $result);
        $this->assertEquals('test@example.com', $email->from_email);
        $this->assertEquals('Test User', $email->from_name);
    }
}
```

## Menambahkan Test Baru

1. Buat file baru di `/workspace/tests/ci3_compat/` dengan format `{LibraryName}Test.php`
2. Extend `PHPUnit\Framework\TestCase`
3. Gunakan namespace `Kodhe\Framework\Tests\CI3Compat`
4. Test semua methods dan properti publik
5. Pastikan kompatibilitas dengan CI3 API

## Continuous Integration

Tests ini akan dijalankan otomatis di CI pipeline untuk memastikan tidak ada breaking changes pada API CodeIgniter 3.
