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
| [app-components](concepts/app-components.md) | **Standar komponen aplikasi Kodhe**: controller (`App\Controllers`), model (`Kodhe\Framework\Database\Model`), library & view/template multi-engine — plus cara migrasi tiap komponen dari CI3 |
| [migrasi-dari-ci3](concepts/migrating-from-ci3.md) | **Panduan langkah demi langkah memindahkan aplikasi CI3** (strategi, bootstrap, config, checklist verifikasi, rollback) |
| [struktur-folder-routing](concepts/folder-structure-and-routing.md) | ⚠️ Perbedaan struktur folder Kodhe (`Controllers/`, tanpa `_`) vs CI3, router ganda modern+legacy, strategi koeksistensi |
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
