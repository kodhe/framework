# Testing

Framework memakai PHPUnit (lihat `phpunit.xml` root). Untuk unit test aplikasi:

```php
use Kodhe\Framework\Validation\FormValidation;

final class RegisterTest extends \PHPUnit\Framework\TestCase
{
    public function testRejectsShortPassword(): void
    {
        $v = new FormValidation();
        $v->set_rules('password', 'Password', 'required|min_length[8]');
        $this->assertFalse($v->run(['password' => 'abc']));
    }
}
```

Prinsip pengujian paket ini: hampir semua kelas dapat di-`new` langsung dengan
array config (tanpa superobject CI) — dependency injection eksplisit membuat
testing jauh lebih mudah dibanding CI3. Suite statis per paket: PHPStan level 0–5
(lihat `.phpstan/packages.txt` + workflow static-analysis).
