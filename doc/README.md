# doc/ — Arsip Dokumentasi Historis (Deprecated)

Folder ini berisi **salinan historis** dokumen README versi lama, disimpan hanya untuk jejak riwayat. Status: **deprecated — jangan diedit, jangan dijadikan rujukan.**

## Kebijakan

1. **Sumber kebenaran** dokumentasi setiap package adalah `<package>/README.md` di root repo, ditulis mengikuti [TEMPLATE_README.md](TEMPLATE_README.md).
2. File di folder ini **tidak disinkronkan otomatis**. Ketika sebuah README paket ditulis ulang, salinan lamanya dibiarkan di sini apa adanya (termasuk namespace lama `Kodhe\Library\*` yang sudah tidak dipakai).
3. Daftar isi dan status tiap paket ada di [README_INDEX.md](README_INDEX.md).
4. Menghapus file arsip hanya boleh dilakukan lewat keputusan eksplisit di review (mis. setelah cukup jauh dari transisi namespace), bukan sebagai efek samping PR dokumentasi biasa.

## Isi

| File | Keterangan |
|---|---|
| `README_INDEX.md` | Indeks & status README seluruh package (rujukan utama) |
| `TEMPLATE_README.md` | Template standar penulisan README package (rujukan utama) |
| `agent_README.md`, `cache_README.md`, `cart_README.md` | Arsip salinan lama — identik dengan README paketnya |
| `calendar_README` | Arsip salinan lama (pra-penulisan-ulang; tanpa ekstensi `.md` karena berasal dari ekstraksi test-file) |
| `database_README.md` | Arsip salinan lama — **salah konten**: isinya sebenarnya skrip test Parser, bukan Database |
