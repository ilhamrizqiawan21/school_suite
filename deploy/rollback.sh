#!/usr/bin/env bash
# ==============================================================================
# Emergency Rollback Script — School Suite
# Usage: ./deploy/rollback.sh
# ==============================================================================

set -euo pipefail

APP_DIR="/var/www/school_suite"
RELEASES_DIR="${APP_DIR}/releases"
CURRENT_SYMLINK="${APP_DIR}/current"

echo "=== Memulai Prosedur Rollback Darurat School Suite ==="

if [ ! -L "${CURRENT_SYMLINK}" ]; then
    echo "Kesalahan: Symlink ${CURRENT_SYMLINK} tidak ditemukan."
    exit 1
fi

CURRENT_RELEASE=$(readlink "${CURRENT_SYMLINK}")
echo "Rilis aktif saat ini: ${CURRENT_RELEASE}"

# Cari rilis sebelumnya dari daftar rilis terurut
PREVIOUS_RELEASE=$(ls -1dt "${RELEASES_DIR}"/* 2>/dev/null | grep -v "^${CURRENT_RELEASE}$" | head -n 1 || true)

if [ -z "${PREVIOUS_RELEASE}" ] || [ ! -d "${PREVIOUS_RELEASE}" ]; then
    echo "Kesalahan: Tidak ditemukan rilis sebelumnya yang valid untuk di-rollback."
    exit 1
fi

echo "Mengalihkan rilis aktif ke versi sebelumnya: ${PREVIOUS_RELEASE}..."
ln -sfn "${PREVIOUS_RELEASE}" "${CURRENT_SYMLINK}"

# Refresh Cache pada rilis sebelumnya
cd "${PREVIOUS_RELEASE}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart Worker & Reload PHP-FPM
if command -v systemctl &> /dev/null; then
    echo "Merestart worker dan memuat ulang PHP-FPM..."
    sudo systemctl restart school-suite-worker || true
    sudo systemctl reload php8.2-fpm || sudo systemctl reload php-fpm || true
fi

echo "✓ Rollback darurat berhasil selesai! Rilis aktif sekarang: ${PREVIOUS_RELEASE}"
