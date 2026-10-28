<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\Backup\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class SchoolRestoreCommand extends Command
{
    protected $signature = 'school:restore
                            {file : Path atau nama arsip backup}
                            {--target-db= : Path SQLite atau nama koneksi MySQL terisolasi}
                            {--target-files= : Direktori pemulihan berkas privat}
                            {--force : Lewati konfirmasi pemulihan}';

    protected $description = 'Verifikasi lalu pulihkan backup terenkripsi database dan berkas privat';

    public function handle(DatabaseBackupService $database): int
    {
        $temporary = storage_path('app/restore_temp_'.bin2hex(random_bytes(12)));
        $stagedFiles = null;
        try {
            $target = $this->option('target-db');
            if (app()->isProduction() && ! app()->isDownForMaintenance()) {
                throw new RuntimeException('Aktifkan maintenance dan hentikan worker sebelum restore target operasional.');
            }
            if (! $this->option('force') && ! $this->confirm('Pemulihan akan menimpa data target. Lanjutkan?', false)) {
                return self::SUCCESS;
            }
            $password = (string) config('backup.password');
            if (strlen($password) < 32) {
                throw new RuntimeException('BACKUP_PASSWORD wajib diisi minimal 32 karakter.');
            }
            $input = (string) $this->argument('file');
            $archive = File::exists($input) ? $input : storage_path('app/backups/'.$input);
            File::ensureDirectoryExists($temporary, 0700);
            $zip = new ZipArchive;
            if ($zip->open($archive) !== true) {
                throw new RuntimeException('Arsip backup tidak dapat dibuka.');
            }
            try {
                $zip->setPassword($password);
                // Never extract unvalidated archive paths or symlinks.
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $name = $zip->getNameIndex($index);
                    $stat = $zip->statIndex($index);
                    if (! is_string($name) || $name === '' || str_contains($name, '\\') || str_starts_with($name, '/') || preg_match('~(^|/)\.\.?(/|$)|^[A-Za-z]:~', $name)) {
                        throw new RuntimeException('Path arsip tidak valid.');
                    }
                    if (($stat['encryption_method'] ?? 0) !== ZipArchive::EM_AES_256) {
                        throw new RuntimeException('Backup harus menggunakan enkripsi AES-256 pada seluruh komponen.');
                    }
                    $zip->getExternalAttributesIndex($index, $system, $attributes);
                    if ((($attributes >> 16) & 0170000) === 0120000) {
                        throw new RuntimeException('Symlink tidak diperbolehkan dalam backup.');
                    }
                    if (! $zip->extractTo($temporary, [$name])) {
                        throw new RuntimeException('Dekripsi/ekstraksi gagal; periksa password dan integritas arsip.');
                    }
                }
            } finally {
                $zip->close();
            }
            $manifestPath = $temporary.'/manifest.json';
            if (! File::exists($manifestPath)) {
                throw new RuntimeException('Manifest backup wajib tersedia.');
            }
            $manifest = json_decode((string) File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            if (($manifest['version'] ?? null) !== 2 || ! is_array($manifest['files'] ?? null) || ! isset($manifest['files']['database_dump.sql'])) {
                throw new RuntimeException('Manifest backup tidak didukung atau tidak lengkap.');
            }
            $actualFiles = [];
            foreach (File::allFiles($temporary) as $file) {
                $name = $file->getRelativePathname();
                if ($name === 'manifest.json') {
                    continue;
                }
                $actualFiles[] = $name;
                if (! isset($manifest['files'][$name]) || ! is_string($manifest['files'][$name]) || ! hash_equals($manifest['files'][$name], hash_file('sha256', $file->getPathname()))) {
                    throw new RuntimeException('Checksum komponen backup tidak cocok.');
                }
            }
            if (array_diff(array_keys($manifest['files']), $actualFiles) !== []) {
                throw new RuntimeException('Komponen backup tidak lengkap.');
            }

            $filesTarget = null;
            if (File::isDirectory($temporary.'/private_files')) {
                $filesTarget = (string) ($this->option('target-files') ?: storage_path('app/private'));
                $stagedFiles = $this->stagePrivateFiles($temporary.'/private_files', $filesTarget);
            }

            $database->restore($temporary.'/database_dump.sql', (string) ($manifest['db_format'] ?? ''), $target ? (string) $target : null);
            if ($stagedFiles !== null && $filesTarget !== null) {
                $this->activatePrivateFiles($stagedFiles, $filesTarget);
                $stagedFiles = null;
            }
            $this->info('Verifikasi dan pemulihan backup selesai.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Restore gagal: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($stagedFiles !== null) {
                File::deleteDirectory($stagedFiles);
            }
            File::deleteDirectory($temporary);
        }
    }

    private function stagePrivateFiles(string $source, string $target): string
    {
        if ($target === '' || is_link($target)) {
            throw new RuntimeException('Target berkas privat tidak valid.');
        }

        $parent = dirname($target);
        File::ensureDirectoryExists($parent, 0700);
        $staged = $parent.'/.'.basename($target).'.restore-'.bin2hex(random_bytes(8));

        if (! File::copyDirectory($source, $staged)) {
            File::deleteDirectory($staged);
            throw new RuntimeException('Persiapan berkas privat untuk pemulihan gagal.');
        }

        chmod($staged, 0700);

        return $staged;
    }

    private function activatePrivateFiles(string $staged, string $target): void
    {
        $previous = null;
        if (is_link($target)) {
            throw new RuntimeException('Target berkas privat tidak valid.');
        }

        if (is_dir($target)) {
            $previous = dirname($target).'/.'.basename($target).'.previous-'.bin2hex(random_bytes(8));
            if (! rename($target, $previous)) {
                throw new RuntimeException('Direktori berkas privat lama tidak dapat diamankan.');
            }
        }

        try {
            if (! rename($staged, $target)) {
                throw new RuntimeException('Direktori berkas privat baru tidak dapat diaktifkan.');
            }
        } catch (\Throwable $exception) {
            if (is_dir($target)) {
                File::deleteDirectory($target);
            }
            if ($previous !== null && ! rename($previous, $target)) {
                throw new RuntimeException('Pemulihan direktori berkas privat lama gagal.', 0, $exception);
            }
            throw $exception;
        }

        if ($previous !== null) {
            File::deleteDirectory($previous);
        }
    }
}
