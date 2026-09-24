#!/usr/bin/env bash
# Jalankan PHPStan untuk SATU paket: scripts/phpstan-package.sh <paket> [level]
# - Level default dibaca dari .phpstan/packages.txt (format "<paket>: <level>" atau "<paket>: skipped").
# - Config dibuat on-the-fly (tidak perlu file neon per paket).
# - Keluaran ringkas; exit 0 = bersih / skipped, exit != 0 = ada error.
set -u

pkg="${1:-}"
level="${2:-}"

if [ -z "$pkg" ]; then
    echo "usage: $0 <package-folder> [level]" >&2
    exit 2
fi

root="$(cd "$(dirname "$0")/.." && pwd)"
manifest="$root/.phpstan/packages.txt"

if [ ! -d "$root/$pkg/src" ]; then
    echo "SKIP: $pkg — folder src/ tidak ditemukan"
    exit 0
fi

# Baca level dari manifest bila tidak diberikan eksplisit.
if [ -z "$level" ] && [ -f "$manifest" ]; then
    entry="$(grep -E "^${pkg}[[:space:]]*:" "$manifest" | head -n1 || true)"
    level="$(printf '%s' "$entry" | sed -E 's/^[^:]+:[[:space:]]*//; s/[[:space:]]*$//')"
fi
level="${level:-5}"

if [ "$level" = "skipped" ]; then
    echo "SKIP: $pkg (skipped di .phpstan/packages.txt)"
    exit 0
fi

BIN="$root/vendor/bin/phpstan"
if [ ! -x "$BIN" ]; then
    echo "ERROR: vendor/bin/phpstan tidak ada — jalankan 'composer install' dulu." >&2
    exit 3
fi

tmpconf="$(mktemp "${TMPDIR:-/tmp}/phpstan-${pkg}.XXXXXX.neon")"
trap 'rm -f "$tmpconf"' EXIT

bootstrap=""
if [ -f "$root/.phpstan/bootstrap.php" ]; then
    bootstrap="
    bootstrapFiles:
        - ${root}/.phpstan/bootstrap.php"
fi

cat > "$tmpconf" <<EOF
parameters:
    level: ${level}${bootstrap}
    paths:
        - ${root}/${pkg}/src
EOF

echo "== phpstan level ${level}: ${pkg}/src =="
"$BIN" analyse -c "$tmpconf" --no-progress --error-format=table
rc=$?
if [ $rc -eq 0 ]; then
    echo "OK: $pkg bersih pada level ${level}."
else
    echo "FAIL: $pkg memiliki error PHPStan (level ${level}, exit ${rc})."
fi
exit $rc
