# Troubleshooting

Kumpulan pesan error yang paling sering muncul di pengembangan, beserta penyebab
dan solusinya. Semua sudah punya fix di framework — update repo Anda terlebih
dahulu (`git pull` + `composer dump-autoload`).

## 1. `Class "Kodhe\UserAgent\UserAgent" not found` (atau Kodhe\<X>\<Y> lain)

**Penyebab**: kode/config memakai namespace legacy tanpa prefix `Framework` yang
tidak terdaftar di autoload.
**Solusi**: ganti ke FQCN aktif — lihat [peta migrasi](../concepts/namespaces-migration.md).
Contoh: `Kodhe\UserAgent\UserAgent` → `Kodhe\Framework\Agent\UserAgent`.
Loader `$CI->load->library('user_agent')` sudah memperbaiki pemetaannya sejak PR #194.

## 2. `Unknown column 'data' in 'SELECT'` (session database)

**Penyebab**: tabel session tidak memakai skema CI3 modern.
**Solusi**: buat tabel sesuai README paket session:

```sql
CREATE TABLE ci_sessions (
  id VARCHAR(128) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  timestamp INT UNSIGNED DEFAULT 0 NOT NULL,
  data BLOB NOT NULL,
  PRIMARY KEY (id),
  KEY ci_sessions_timestamp (timestamp)
);
```

lalu isi `$config['sess_save_path'] = 'ci_sessions';`. Driver kini memvalidasi
skema dan memberi pesan jelas bila kolom kurang (PR #192). Kolom warisan lama
`lastactivity` tetap didukung untuk garbage collection.

## 3. `Unable to inspect session table ''` / `strcspn(): Argument #1 ... null given`

**Penyebab**: `sess_save_path` kosong/null saat `sess_driver = database`.
**Solusi**: isi nama tabel pada config (lihat #2). Framework kini fail-fast dengan
pesan eksplisit alih-alih TypeError membingungkan (PR #192/#196). Untuk dev cepat,
pakai `'sess_driver' => 'files', 'sess_save_path' => sys_get_temp_dir()`.

## 4. Error PHPStan/CI "Cannot resolve class ..." di paket

**Penyebab**: biasanya artefak environment (fungsi global CI3 belum distub) atau
manifest level terlalu tinggi.
**Solusi**: jalankan `bash scripts/phpstan-package.sh <paket>`; hasil nyata per
paket tercatat di `.phpstan/packages.txt` dan issue #148–#175.

## 5. Workflow statis tidak berjalan

File contoh ada di `ci/static-analysis.yml.example`; salin isinya ke
`.github/workflows/static-analysis.yml` (PAT tanpa scope `workflow` tidak boleh
mengirim file workflows — harus via UI/repo admin).

## 6. `index.html` menutupi aplikasi

Root repo/app kadang berisi `index.html` bawaan; arahkan DocumentRoot ke folder
`public/` atau hapus file tersebut.
