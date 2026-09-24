# Rencana Perbaikan Framework Kodhe

## Executive Summary

Dokumen ini merencanakan perbaikan menyeluruh untuk framework Kodhe dengan fokus pada:
- Perbaikan bug kritis
- Peningkatan struktur folder dan file
- Peningkatan kualitas kode
- **100% mempertahankan backward compatibility dengan CodeIgniter 3**

---

## 🐛 Issue Bug yang Ditemukan

### 1. Strict Types Declaration Invalid (CRITICAL)
**Status**: ⚠️ Critical - 413 file terdampak

**Masalah**:
```php
declare(strict_types=0); // ❌ Tidak valid, akan diabaikan atau error
```

**Dampak**:
- Type checking tidak berjalan konsisten
- Potensi bug type-related yang sulit dilacak
- Inkonsistensi behavior di berbagai environment

**Solusi**:
```php
declare(strict_types=1); // ✅ Valid strict typing
```

**File Terdampak**: 413 file di seluruh codebase

**Action Plan**:
- [ ] Script find-and-replace untuk semua file
- [ ] Verifikasi manual sample file
- [ ] Test suite regression testing

---

### 2. Inkonsistensi Class Aliases CI3 (CRITICAL)
**Status**: ⚠️ Critical - Linux case-sensitive issue

**Masalah**:
CodeIgniter 3 menggunakan naming convention campuran:
- `CI_Upload` (PascalCase setelah prefix)
- `CI_upload` (lowercase setelah prefix)
- `CI_Session` vs `CI_session`

Di Linux (case-sensitive), jika hanya `CI_Upload` yang didefinisikan, maka `CI_upload` akan gagal.

**Contoh File yang Bermasalah**:
- `agent/src/compat.php` - Missing lowercase variants
- `database/src/compat.php` - Incomplete aliases
- `parser/src/compat.php` - Missing aliases

**Solusi**: Tambahkan semua variant di setiap `compat.php`:

```php
// Di setiap package compat.php
class_alias(\Kodhe\Framework\Agent\Agent::class, 'CI_agent', false);
class_alias(\Kodhe\Framework\Agent\Agent::class, 'CI_Agent', false);
class_alias(\Kodhe\Framework\Agent\Agent::class, 'CI_AGENT', false);
```

**Checklist Package yang Perlu Diperbaiki**:
- [ ] agent
- [ ] database
- [ ] parser
- [ ] pagination
- [ ] table
- [ ] typography
- [ ] unit_test
- [ ] security
- [ ] session
- [ ] upload
- [ ] email
- [ ] calendar
- [ ] cart
- [ ] trackback
- [ ] xmlrpc

---

### 3. Namespace Typo (HIGH)
**Status**: 🔴 High - Fatal error potential

**Lokasi**: `agent/src/compat.php`

**Masalah**:
```php
class_alias('Kodhe\\Framework\\Agent\\\\Agent', 'CI_agent', false);
//                                    ^^ Double backslash
```

**Solusi**:
```php
class_alias('Kodhe\\Framework\\Agent\\Agent', 'CI_agent', false);
//                                 ^ Single backslash
```

**Verifikasi Required**:
- [ ] Cek semua file `compat.php` di setiap package
- [ ] Gunakan regex untuk detect double/triple backslash
- [ ] Test autoload setelah fix

---

### 4. Autoload Path Salah di composer.json (HIGH)
**Status**: 🔴 High - Classes tidak ter-autoload

**Lokasi**: `parser/composer.json`

**Masalah**:
```json
{
    "autoload": {
        "psr-4": {
            "Kodhe\\\\Framework\\\\Parser\\\\": ""
            // Path kosong = classes tidak ditemukan
        }
    }
}
```

**Solusi**:
```json