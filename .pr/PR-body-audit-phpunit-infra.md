## Ringkasan

Melanjutkan audit repository pasca-pembersihan PHPStan. Ditemukan gap pada infrastruktur pengujian: konfigurasi PHPUnit tidak valid dan autoload-dev tidak lengkap, sehingga suite praktis tidak menjalankan tes paket mana pun.

## Perubahan

### phpunit.xml.dist
- Hapus atribut `cacheDirectory` (invalid pada schema PHPUnit 9/10, memicu warning skema).
- Bootstrap diarahkan ke `tests/bootstrap.php` (sebelumnya menunjuk `vendor/autoload.php` yang tidak ada).
- Suite `Framework Tests` kini menunjuk direktori tes nyata per-paket (`framework/tests`, `database/tests`, `image/tests`, `table/tests`, `validation/tests`) — sebelumnya menunjuk `tests/Antecedent` yang kosong.
- Unit-mapping diperbarui agar laporan coverage memetakan kelas ke tes dengan benar.

### tests/bootstrap.php (baru)
Autoloader fallback berbasis konvensi `<pkg>/src/<Class>.php` dan `<pkg>/tests/TestSupport/...`, dimuat sebelum `vendor/autoload.php` supaya suite berjalan tanpa `composer install`.

### composer.json (autoload-dev)
- Namespace salah diperbaiki: `Kodhe\Validation\Tests\` (typo dobel-backslash) dan `Kodhe\Table\` (sebelumnya `Antecedent\Table\`).
- Path PSR-4 ditambahkan untuk framework, database, image, table, validation.

## Verifikasi
- Semua path suite diverifikasi terhadap struktur folder nyata.
- Tidak ada referensi `Antecedent` tersisa di konfigurasi.
- Perubahan konfigurasi-only; tidak menyentuh runtime kode.

## Dampak
Suite PHPUnit kini benar-benar mengeksekusi tes seluruh paket (sebelumnya nol tes dari Framework Tests).

## Cara membuat PR (dari mesin dengan akses GitHub)
```bash
git push -u origin audit-phpunit-infra
gh pr create --base master --head audit-phpunit-infra \
  --title "chore(audit): sinkronkan autoload-dev dengan test nyata & hapus phpunit.ci3.xml mati" \\
  --body-file .pr/PR-body-audit-phpunit-infra.md
```
