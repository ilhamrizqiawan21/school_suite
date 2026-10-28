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
HEALTH_CHECK_URL="${HEALTH_CHECK_URL:-}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
SCHEDULER_TIMER_SERVICE="${SCHEDULER_TIMER_SERVICE:-school-suite-scheduler.timer}"
DEPLOY_SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

if [ -z "${HEALTH_CHECK_URL}" ]; then
    echo "Kesalahan: HEALTH_CHECK_URL wajib diisi, misalnya https://<domain-aplikasi>/up." >&2
    exit 1
fi
case "${HEALTH_CHECK_URL}" in
    https://*) ;;
    *) echo "Kesalahan: HEALTH_CHECK_URL harus menggunakan HTTPS." >&2; exit 1 ;;
esac

for executable in php composer npm curl; do
    command -v "$executable" >/dev/null || { echo "Executable wajib tidak tersedia: $executable" >&2; exit 1; }
done
php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'

if [ ! -f "${SHARED_DIR}/.env" ]; then
    echo "Kesalahan: ${SHARED_DIR}/.env wajib tersedia." >&2
    exit 1
fi
php "${DEPLOY_SCRIPT_DIR}/validate-production-env.php" "${SHARED_DIR}/.env"

echo "=== Memulai Deployment School Suite [${TIMESTAMP}] ==="

# 1. Pastikan direktori dasar ada
mkdir -p "${SHARED_DIR}/storage/app/private" "${SHARED_DIR}/storage/framework/cache/data" "${SHARED_DIR}/storage/framework/sessions" "${SHARED_DIR}/storage/framework/views" "${SHARED_DIR}/storage/logs" "${SHARED_DIR}/backups" "${RELEASES_DIR}"

# 2. Clone/Salin release baru
echo "1/7 Menyiapkan rilis baru di ${RELEASE_DIR}..."
mkdir -p "${RELEASE_DIR}"
if [ -d ".git" ]; then
    if ! git archive "${BRANCH}" | tar -x -C "${RELEASE_DIR}"; then
        echo "Kesalahan: gagal membuat rilis dari branch/commit '${BRANCH}'." >&2
        rm -rf "${RELEASE_DIR}"
        exit 1
    fi
else
    cp -a . "${RELEASE_DIR}"
fi

cd "${RELEASE_DIR}"

# 3. Hubungkan Shared .env dan Shared Storage
echo "2/7 Menghubungkan shared .env dan shared storage..."
if [ -f "${SHARED_DIR}/.env" ]; then
    ln -sfn "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
else
    echo "Kesalahan: ${SHARED_DIR}/.env wajib tersedia." >&2
    exit 1
fi

rm -rf "${RELEASE_DIR}/storage"
ln -sfn "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"

# 4. Install PHP Dependencies (No Dev)
echo "3/7 Menginstall dependensi Composer (production)..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet

# 5. Build Frontend Assets
echo "4/7 Mengompilasi aset Vue & Tailwind CSS (Vite production build)..."
npm ci --silent
npm run build --silent

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

# 9. Restart processes before probing so the request executes the new PHP release.
reject_release() {
    echo "Rilis gagal diverifikasi." >&2
    if [ -n "${PREVIOUS_RELEASE}" ] && [ -d "${PREVIOUS_RELEASE}" ]; then
        ln -sfn "${PREVIOUS_RELEASE}" "${CURRENT_SYMLINK}"
        sudo systemctl restart school-suite-worker || echo "Restart worker rollback gagal." >&2
        sudo systemctl reload "${PHP_FPM_SERVICE}" || echo "Reload FPM rollback gagal." >&2
    else
        if [ -L "${CURRENT_SYMLINK}" ] && [ "$(readlink "${CURRENT_SYMLINK}")" = "${RELEASE_DIR}" ]; then
            rm -f "${CURRENT_SYMLINK}"
        fi
        sudo systemctl stop school-suite-worker || echo "Stop worker setelah rilis pertama gagal." >&2
        sudo systemctl reload "${PHP_FPM_SERVICE}" || echo "Reload FPM setelah rilis pertama gagal." >&2
    fi
    exit 1
}
if ! sudo systemctl restart school-suite-worker || ! sudo systemctl reload "${PHP_FPM_SERVICE}"; then
    reject_release
fi
if ! sudo systemctl enable --now "${SCHEDULER_TIMER_SERVICE}"; then
    echo "Kesalahan: scheduler timer ${SCHEDULER_TIMER_SERVICE} gagal diaktifkan." >&2
    reject_release
fi

# 10. Require HTTP 200 and the application's healthy JSON payload, including first install.
if ! bash "${RELEASE_DIR}/deploy/health-check.sh" "${HEALTH_CHECK_URL}"; then
    reject_release
fi

# 11. Bersihkan Rilis Lama (Pertahankan 5 rilis terakhir)
cd "${RELEASES_DIR}"
ls -1dt "${RELEASES_DIR}"/* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true

echo "=== Deployment Berhasil Selesai! Versi aktif: ${RELEASE_DIR} ==="

