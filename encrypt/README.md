# Kodhe Encrypt - Modernized CI3 Encrypt Library

Library enkripsi modern untuk CodeIgniter 3 dengan dukungan OpenSSL, menggantikan library `mcrypt` yang sudah deprecated.

## ⚠️ DEPRECATED NOTICE

Library ini **DEPRECATED** dan dipertahankan hanya untuk backward compatibility. Untuk proyek baru, gunakan library **Encryption** resmi CodeIgniter 3 yang berbasis OpenSSL.

## 🚀 Fitur Utama

- ✅ **OpenSSL Support**: AES-256-CBC + HMAC-SHA256 untuk keamanan maksimal
- ✅ **Backward Compatible**: API sama persis dengan CI3 Encrypt lama
- ✅ **Legacy Data Support**: Bisa membaca data enkripsi lama (mcrypt)
- ✅ **Tamper Proof**: Deteksi otomatis jika data di-tamper
- ✅ **PSR-4 Compliant**: Struktur modular dan modern
- ✅ **Performance Optimized**: Caching key derivation, batch operations
- ✅ **Migration Path**: Method `migrate()` untuk migrasi data lama ke format baru

## 📦 Instalasi

```bash
composer require kodhe/ci3-encrypt
```

Atau tambahkan ke `composer.json`:

```json
{
    "require": {
        "kodhe/ci3-encrypt": "^2.0"
    }
}
```

## 🔧 Konfigurasi

Tambahkan di `application/config/config.php`:

```php
$config['encryption_key'] = 'your-secret-key-min-32-chars-long';
```

**PENTING**: Gunakan key minimal 32 karakter untuk keamanan optimal!

## 💡 Cara Penggunaan

### Gaya CI3 Tradisional

```php
$this->load->library('encrypt');

// Enkripsi
$encrypted = $this->encrypt->encode('data rahasia');

// Dekripsi
$decrypted = $this->encrypt->decode($encrypted);

// Dengan key custom
$encrypted = $this->encrypt->encode('data', 'custom-key');
```

### Gaya Modern PSR-4

```php
use Kodhe\Encrypt\Encrypt;

$encrypt = new Encrypt([
    'encryption_key' => 'your-secret-key-min-32-chars-long',
    'algorithm' => 'aes-256-cbc', // optional
]);

// Enkripsi
$token = $encrypt->encode('user_id_123');

// Dekripsi
$user_id = $encrypt->decode($token);

// Batch operations (lebih cepat untuk banyak data)
$encrypted_array = $encrypt->encode_many(['data1', 'data2', 'data3']);
$decrypted_array = $encrypt->decode_many($encrypted_array);
```

## 🔐 Keamanan

### Format Data Baru (OpenSSL)
```
Base64(IV[16 bytes] + HMAC-SHA256[32 bytes] + Ciphertext)
```

- **IV (Initialization Vector)**: Random setiap enkripsi
- **HMAC**: Mencegah tampering/data manipulation
- **AES-256-CBC**: Standard industri untuk enkripsi simetris

### Deteksi Tampering

```php
$data = $encrypt->decode($encrypted);

if ($data === false) {
    // Data corrupted, kunci salah, atau di-tamper!
    log_message('error', 'Dekripsi gagal - data mungkin di-tamper');
}
```

## 🔄 Migrasi dari Legacy (mcrypt)

Library ini otomatis mendeteksi dan membaca data legacy CI3:

```php
// Otomatis detect format legacy dan decode
$old_data = $this->encrypt->decode($legacy_encrypted);

// Migrasi manual ke format baru
$new_encrypted = $this->encrypt->migrate($legacy_encrypted);

// Cek apakah data masih format legacy
if ($this->encrypt->is_legacy_format($encrypted)) {
    // Perlu migrasi
    $this->encrypt->migrate($encrypted);
}
```

### Script Migrasi Database

```php
// Migrasi semua data terenkripsi di database
$users = $this->db->get('users')->result();

foreach ($users as $user) {
    $migrated = $this->encrypt->migrate($user->encrypted_data);
    
    if ($migrated !== false) {
        $this->db->where('id', $user->id)
                 ->update('users', ['encrypted_data' => $migrated]);
    }
}
```

## 📚 API Reference

| Method | Deskripsi | Return |
|--------|-----------|--------|
| `encode($string, $key = '')` | Enkripsi string | string (base64) |
| `decode($string, $key = '')` | Dekripsi string | string\|false |
| `set_cipher($cipher)` | Set algoritma cipher | self |
| `set_mode($mode)` | Set mode operasi | self |
| `sha1($str)` | Generate SHA1 hash | string |
| `set_key($key)` | Set encryption key | self |
| `is_legacy_format($string)` | Cek format legacy | bool |
| `migrate($old_encoded)` | Migrasi ke format baru | string\|false |
| `encode_many($strings, $key)` | Batch enkripsi | array |
| `decode_many($encoded, $key)` | Batch dekripsi | array |

## 🧪 Testing

```bash
composer test
```

## 📊 Perbandingan dengan CI3 Asli

| Fitur | CI3 Encrypt (mcrypt) | Kodhe Encrypt (OpenSSL) |
|-------|---------------------|-------------------------|
| Engine | Mcrypt ❌ (removed PHP 7.2+) | OpenSSL ✅ |
| Integritas | ❌ Tidak ada | ✅ HMAC-SHA256 |
| Tamper Proof | ❌ Silent corruption | ✅ Detect & reject |
| IV Generation | Weak random | ✅ `random_bytes()` |
| Key Derivation | Simple substr | ✅ HKDF/PBKDF2 |
| Legacy Support | N/A | ✅ Auto-detect |
| Batch Ops | ❌ | ✅ `encode_many()` |

## ⚠️ Security Best Practices

1. **Gunakan Key Panjang**: Minimal 32 karakter
2. **Simpan Key Aman**: Jangan commit ke Git, gunakan environment variables
3. **Rotate Key Berkala**: Ganti encryption key secara periodik
4. **HTTPS Always**: Kirim data terenkripsi hanya via HTTPS
5. **Migrasi Data Lama**: Segera migrate data legacy ke format baru

## 📝 Changelog

### v2.0.0 (2024)
- ✅ Migrasi penuh dari mcrypt ke OpenSSL
- ✅ Tambah HMAC untuk integritas data
- ✅ Support auto-detect legacy format
- ✅ Batch operations untuk performa
- ✅ PSR-4 autoloading

### v1.0.0 (Legacy)
- Original CI3 Encrypt library compatibility

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions welcome! Please read our contributing guidelines before submitting PRs.

## 📞 Support

- Issues: https://github.com/yourname/ci3-encrypt/issues
- Documentation: https://github.com/yourname/ci3-encrypt/wiki

---

**Note**: Library ini untuk CodeIgniter 3. Untuk CodeIgniter 4, gunakan built-in Encryption service.
