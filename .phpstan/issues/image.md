**Paket:** `image` · **Level di manifest:** `5`

[ ] `composer install`
[ ] `bash scripts/phpstan-package.sh image` → salin jumlah error ke komentar issue
[ ] Klasifikasi error: docblock hilang/salah (perbaiki inline) · dependensi eksternal tak ter-resolve (tambah scanDirectories atau tandai `skipped` di `.phpstan/packages.txt` dengan alasan) · bug nyata kode legacy (buat issue terpisah + tautkan)
[ ] Sesuaikan level di `.phpstan/packages.txt` sampai realistis
[ ] PR perbaikan + update checklist

**Kriteria selesai:** paket hijau pada level tercatat di manifest, atau `skipped` dengan justifikasi tertulis.
