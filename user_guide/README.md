# Panduan Pengguna Kodhe Framework (User Guide)

Dokumentasi untuk **pengembangan aplikasi** di atas Kodhe Framework, disusun mengikuti
struktur [Panduan Pengguna CodeIgniter 3](https://www.codeigniter.com/user_guide/) —
berisi *general topics*, *library reference* per paket, dan halaman `index` bertautan
di setiap sub-folder.

> Perbedaan utama dengan CI3: setiap library adalah **paket Composer terpisah** dengan
> namespace `Kodhe\Framework\<Paket>\*`. Anda cukup memasang paket yang dibutuhkan;
> tidak ada lagi `$this->load->library()` wajib untuk semua fitur.

## Daftar Isi

### Konsep Inti (`concepts/`)
| Dokumen | Isi |
|---|---|
| [arsitektur](concepts/architecture.md) | Model modular paket, loader dual-mode, service container |
| [instalasi](concepts/installation.md) | Composer per-paket vs root, autoload PSR-4, requirement PHP |
| [namespace-migrasi](concepts/namespaces-migration.md) | Peta lengkap `CI_*` / `Kodhe\Library\*` → `Kodhe\Framework\*` |

### Topik Umum (`general/`)
| Dokumen | Isi |
|---|---|
| [menjalankan-aplikasi](general/running.md) | Bootstrap framework, konfigurasi, menjalankan di server dev |
| [troubleshooting](general/troubleshooting.md) | Pesan error umum & penyebabnya (class not found, schema tabel, dsb.) |

### Referensi Library (`libraries/`)
Satu halaman per paket, format: tujuan → instalasi → Quick Start → API utama →
catatan migrasi dari CI3 → tautan ke README paket (sumber kebenaran teknis).

[Daftar lengkap & indeks →](libraries/index.md)

## Hierarki Sumber Dokumentasi

1. `user_guide/` (folder ini) — narasi pembelajaran/pengembangan aplikasi.
2. `<paket>/README.md` — referensi API kanonik per paket (selalu paling mutakhir).
3. `doc/` — arsip historis, deprecated, jangan dijadikan rujukan.
