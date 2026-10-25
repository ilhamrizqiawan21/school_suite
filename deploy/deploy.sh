#!/usr/bin/env bash
# ==============================================================================
# Zero-Downtime Deployment Script — School Suite
# Usage: ./deploy/deploy.sh [branch]
# ==============================================================================

set -euo pipefail

# Configuration
APP_DIR="/var/www/school_suite"
SHARED_DIR="${APP_DIR}/shared"
RELEASES_DIR="${APP_DIR}/releases"
CURRENT_SYMLINK="${APP_DIR}/current"
BRANCH="${1:-main}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"
HEALTH_CHECK_URL="http://127.0.0.1/up"

echo "=== Memulai Deployment School Suite [${TIMESTAMP}] ==="

# 1. Pastikan direktori dasar ada
mkdir -p "${SHARED_DIR}/storage" "${SHARED_DIR}/backups" "${RELEASES_DIR}"

# 2. Clone/Salin release baru
echo "1/7 Menyiapkan rilis baru di ${RELEASE_DIR}..."
if [ -d ".git" ]; then
    git archive "${BRANCH}" | tar -x -C "${RELEASE_DIR}" || {
        # Fallback jika dijalankan dari repo checkout langsung
        mkdir -p "${RELEASE_DIR}"
        cp -a . "${RELEASE_DIR}"
        rm -rf "${RELEASE_DIR}/.git"
    }
else
    mkdir -p "${RELEASE_DIR}"
    cp -a . "${RELEASE_DIR}"
fi

cd "${RELEASE_DIR}"

# 3. Hubungkan Shared .env dan Shared Storage
echo "2/7 Menghubungkan shared .env dan shared storage..."
if [ -f "${SHARED_DIR}/.env" ]; then
    ln -sfn "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
else
    echo "Peringatan: ${SHARED_DIR}/.env tidak ditemukan. Menggunakan .env lokal."
fi

rm -rf "${RELEASE_DIR}/storage"
ln -sfn "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"

# 4. Install PHP Dependencies (No Dev)
echo "3/7 Menginstall dependensi Composer (production)..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet

# 5. Build Frontend Assets
echo "4/7 Mengompilasi aset Vue & Tailwind CSS (Vite production build)..."
if command -v npm &> /dev/null; then
    npm ci --silent
    npm run build --silent
fi

# 6. Database Migrations
echo "5/7 Menjalankan migrasi basis data..."
php artisan migrate --force --isolated

# 7. Optimasi Caching Framework
echo "6/7 Mengoptimalkan cache konfigurasi, routes, dan views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Atomic Symlink Switch
echo "7/7 Mengalihkan symlink live secara atomik..."
PREVIOUS_RELEASE=""
if [ -L "${CURRENT_SYMLINK}" ]; then
    PREVIOUS_RELEASE=$(readlink "${CURRENT_SYMLINK}")
fi

ln -sfn "${RELEASE_DIR}" "${CURRENT_SYMLINK}"

# 9. Verifikasi Health Check
echo "Memverifikasi kesehatan rilis..."
if command -v curl &> /dev/null; then
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${HEALTH_CHECK_URL}" || echo "000")
    if [ "${HTTP_STATUS}" != "200" ] && [ "${HTTP_STATUS}" != "302" ]; then
        echo "PERINGATAN: Health check gagal dengan status HTTP ${HTTP_STATUS}."
        if [ -n "${PREVIOUS_RELEASE}" ] && [ -d "${PREVIOUS_RELEASE}" ]; then
            echo "Melakukan rollback otomatis ke rilis sebelumnya: ${PREVIOUS_RELEASE}..."
            ln -sfn "${PREVIOUS_RELEASE}" "${CURRENT_SYMLINK}"
            echo "Rollback selesai."
            exit 1
        fi
    fi
fi

# 10. Restart Background Queue Worker & Reload PHP-FPM
if command -v systemctl &> /dev/null; then
    echo "Memuat ulang worker dan PHP-FPM..."
    sudo systemctl restart school-suite-worker || true
    sudo systemctl reload php8.2-fpm || sudo systemctl reload php-fpm || true
fi

# 11. Bersihkan Rilis Lama (Pertahankan 5 rilis terakhir)
cd "${RELEASES_DIR}"
ls -1dt "${RELEASES_DIR}"/* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true

echo "=== Deployment Berhasil Selesai! Versi aktif: ${RELEASE_DIR} ==="
