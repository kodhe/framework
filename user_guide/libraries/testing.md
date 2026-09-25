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
testing jauh lebih mudah dibanding CI3. PHPUnit adalah satu-satunya tester resmi
proyek ini (statis analyzer seperti PHPStan sudah dihapus agar tidak dobel).
