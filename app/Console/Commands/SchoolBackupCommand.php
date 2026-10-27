<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\Backup\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class SchoolBackupCommand extends Command
{
    protected $signature = 'school:backup
                            {--destination= : Direktori tujuan backup}
                            {--database-only : Hanya backup database}';

    protected $description = 'Cadangkan database dan berkas privat ke ZIP terenkripsi AES-256';

    public function handle(DatabaseBackupService $database): int
    {
        $temporary = storage_path('app/backup_temp_'.bin2hex(random_bytes(12)));
        $archivePath = null;
        $completed = false;
        $previousUmask = umask(0077);
        try {
            $password = (string) config('backup.password');
            if (strlen($password) < 32) {
                throw new RuntimeException('BACKUP_PASSWORD wajib diisi minimal 32 karakter.');
            }
            if (! ZipArchive::isEncryptionMethodSupported(ZipArchive::EM_AES_256)) {
                throw new RuntimeException('Ekstensi ZIP harus mendukung AES-256.');
            }
            $destination = (string) ($this->option('destination') ?: storage_path('app/backups'));
            File::ensureDirectoryExists($destination, 0700);
            File::ensureDirectoryExists($temporary, 0700);
            $dump = $temporary.'/database_dump.sql';
            $format = $database->dump($dump);
            chmod($dump, 0600);
            if (! $this->option('database-only') && File::isDirectory(storage_path('app/private'))) {
                if (! File::copyDirectory(storage_path('app/private'), $temporary.'/private_files')) {
                    throw new RuntimeException('Penyalinan berkas privat untuk backup gagal.');
                }
            }
            $checksums = [];
            foreach (File::allFiles($temporary) as $file) {
                $checksums[$file->getRelativePathname()] = hash_file('sha256', $file->getPathname());
            }
            $manifest = [
                'version' => 2,
                'created_at' => now()->toIso8601String(),
                'db_driver' => DB::getDriverName(),
                'db_format' => $format,
                'db_checksum' => $checksums['database_dump.sql'],
                'includes_files' => ! $this->option('database-only'),
                'files' => $checksums,
            ];
            File::put($temporary.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $archivePath = $destination.'/backup_school_suite_'.now()->format('Y-m-d_His').'_'.bin2hex(random_bytes(4)).'.zip';
            $zip = new ZipArchive;
            if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('Tidak dapat membuat arsip backup.');
            }
            try {
                $zip->setPassword($password);
                foreach (File::allFiles($temporary) as $file) {
                    $name = $file->getRelativePathname();
                    if (! $zip->addFile($file->getPathname(), $name) || ! $zip->setEncryptionName($name, ZipArchive::EM_AES_256)) {
                        throw new RuntimeException('Gagal mengenkripsi komponen backup.');
                    }
                }
            } finally {
                if (! $zip->close()) {
                    throw new RuntimeException('Gagal menyelesaikan arsip backup.');
                }
            }
            chmod($archivePath, 0600);
            $completed = true;
            $this->info('Backup terenkripsi selesai: '.$archivePath);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Backup gagal: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            umask($previousUmask);
            File::deleteDirectory($temporary);
            if (! $completed && $archivePath !== null) {
                File::delete($archivePath);
            }
        }
    }
}
