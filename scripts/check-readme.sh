#!/usr/bin/env bash
#
# check-readme.sh — Guard CI untuk kualitas dokumentasi package Kodhe.
#
# Memastikan setiap folder package (yang memiliki composer.json) punya README.md:
#   1. ada,
#   2. bukan placeholder ("TODO: README" / "Placeholder"),
#   3. cukup panjang (>= MIN_LINES baris),
#   4. contoh kode memakai namespace modern Kodhe\Framework\* (bukan Kodhe\Library\*),
#   5. tidak menyebut file/folder "(TODO)" yang tidak ada di src/.
#
# Pemakaian:  ./scripts/check-readme.sh
# Keluaran:   daftar pelanggaran + exit code != 0 bila ada.

set -uo pipefail

cd "$(dirname "$0")/.." || exit 1

MIN_LINES=${MIN_LINES:-30}
fail=0

report() {
    echo "❌ $1"
    fail=1
}

for dir in */; do
    pkg="${dir%/}"

    # Hanya folder package Composer (lewati doc/, tests/, vendor/, dll.)
    [ -f "${pkg}/composer.json" ] || continue

    readme="${pkg}/README.md"

    if [ ! -f "$readme" ]; then
        report "$readme: TIDAK ADA"
        continue
    fi

    # Placeholder hanya dideteksi dari penanda eksplisit "TODO: README"
    # (kata "placeholder" biasa, mis. placeholder %s, tidak boleh memicu gagal).
    if grep -qiE 'TODO: *README' "$readme"; then
        report "$readme: masih berstatus PLACEHOLDER (menemukan 'TODO: README')"
    fi

    lines=$(wc -l < "$readme")
    if [ "$lines" -lt "$MIN_LINES" ]; then
        report "$readme: terlalu pendek (${lines} baris < ${MIN_LINES})"
    fi

    # Namespace lama hanya boleh muncul di bagian "Catatan Migrasi".
    bad_ns=$(grep -n 'Kodhe\\Library' "$readme" | grep -iv 'migrasi\|lama\|legacy' || true)
    if [ -n "$bad_ns" ]; then
        report "$readme: contoh kode memakai namespace lama Kodhe\\Library:"
        echo "$bad_ns" | sed 's/^/     /'
    fi

    # Referensi "(TODO)" pada struktur direktori harus menunjuk file yang benar-benar ada.
    while IFS= read -r todo_line; do
        target=$(echo "$todo_line" | grep -oE '[A-Za-z0-9_]+\.php|[A-Za-z0-9_]+/' | head -1 | tr -d '/')
        if [ -n "$target" ] && ! find "${pkg}/src" -name "$target" 2>/dev/null | grep -q .; then
            report "$readme: menyebut '${target}' sebagai (TODO) padahal tidak ada di ${pkg}/src/"
        fi
    done < <(grep -iE '\(TODO\)' "$readme" || true)
done

if [ "$fail" -eq 0 ]; then
    echo "✅ Semua README package lolos pemeriksaan."
fi

exit "$fail"
