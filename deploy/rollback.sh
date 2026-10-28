#!/usr/bin/env bash
# ==============================================================================
# Emergency Rollback Script — School Suite
# Usage: HEALTH_CHECK_URL=https://<domain>/up ./deploy/rollback.sh
# ==============================================================================

set -euo pipefail

APP_DIR="/var/www/school_suite"
RELEASES_DIR="${APP_DIR}/releases"
CURRENT_SYMLINK="${APP_DIR}/current"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
HEALTH_CHECK_URL="${HEALTH_CHECK_URL:-}"
DEPLOY_SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

if [ -z "${HEALTH_CHECK_URL}" ]; then
    echo "Kesalahan: HEALTH_CHECK_URL wajib diisi, misalnya https://<domain-aplikasi>/up." >&2
    exit 1
fi
case "${HEALTH_CHECK_URL}" in
    https://*) ;;
    *) echo "Kesalahan: HEALTH_CHECK_URL harus menggunakan HTTPS." >&2; exit 1 ;;
esac

echo "=== Memulai Prosedur Rollback Darurat School Suite ==="

if [ ! -L "${CURRENT_SYMLINK}" ]; then
    echo "Kesalahan: Symlink ${CURRENT_SYMLINK} tidak ditemukan." >&2
    exit 1
fi

CURRENT_RELEASE=$(readlink "${CURRENT_SYMLINK}")
echo "Rilis aktif saat ini: ${CURRENT_RELEASE}"

# Cari rilis sebelumnya dari daftar rilis terurut.
PREVIOUS_RELEASE=$(ls -1dt "${RELEASES_DIR}"/* 2>/dev/null | grep -v "^${CURRENT_RELEASE}$" | head -n 1 || true)

if [ -z "${PREVIOUS_RELEASE}" ] || [ ! -d "${PREVIOUS_RELEASE}" ]; then
    echo "Kesalahan: Tidak ditemukan rilis sebelumnya yang valid untuk di-rollback." >&2
    exit 1
fi

echo "Mengalihkan rilis aktif ke versi sebelumnya: ${PREVIOUS_RELEASE}..."
ln -sfn "${PREVIOUS_RELEASE}" "${CURRENT_SYMLINK}"

# Refresh cache pada rilis sebelumnya.
cd "${PREVIOUS_RELEASE}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart worker & reload PHP-FPM.
if command -v systemctl >/dev/null 2>&1; then
    echo "Merestart worker dan memuat ulang PHP-FPM..."
    sudo systemctl restart school-suite-worker
    sudo systemctl reload "${PHP_FPM_SERVICE}"
fi

# Never report a rollback as successful until the active release answers healthy.
if ! bash "${DEPLOY_SCRIPT_DIR}/health-check.sh" "${HEALTH_CHECK_URL}"; then
    echo "Kesalahan: health check pasca-rollback gagal; rilis sebelumnya tidak terverifikasi." >&2
    echo "Memulihkan symlink ke rilis yang aktif sebelum rollback..." >&2
    ln -sfn "${CURRENT_RELEASE}" "${CURRENT_SYMLINK}"

    if command -v systemctl >/dev/null 2>&1; then
        sudo systemctl restart school-suite-worker || echo "Restart worker setelah rollback gagal." >&2
        sudo systemctl reload "${PHP_FPM_SERVICE}" || echo "Reload FPM setelah rollback gagal." >&2
    fi

    echo "Rollback dibatalkan: health check gagal dan rilis awal telah dipulihkan." >&2
    exit 1
fi

echo "✓ Rollback darurat berhasil diverifikasi! Rilis aktif sekarang: ${PREVIOUS_RELEASE}"
