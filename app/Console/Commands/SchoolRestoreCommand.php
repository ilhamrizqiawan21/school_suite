<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class SchoolRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:restore
                            {file : Path atau nama berkas zip backup yang akan dipulihkan}
                            {--target-db= : Path berkas sqlite atau koneksi database target pemulihan (drill restore)}
                            {--target-files= : Direktori tujuan ekstraksi berkas privat}
                            {--force : Jalankan pemulihan tanpa konfirmasi interaktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulihkan basis data dan berkas privat dari arsip cadangan School Suite';

    public function handle(): int
    {
        $this->warn('=== Prosedur Pemulihan Cadangan (Restore / Drill) School Suite ===');

        $archiveInput = (string) $this->argument('file');
        $archivePath = File::exists($archiveInput)
            ? $archiveInput
            : storage_path("app/backups/{$archiveInput}");

        if (! File::exists($archivePath)) {
            $this->error("Berkas arsip cadangan tidak ditemukan: {$archivePath}");

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm('PERINGATAN: Pemulihan akan menimpa data yang ada. Lanjutkan prosedur restore?', false)) {
                $this->info('Operasi pemulihan dibatalkan oleh pengguna.');

                return self::SUCCESS;
            }
        }

        $tempExtractDir = storage_path('app/restore_temp_'.now()->format('Ymd_His'));
        File::makeDirectory($tempExtractDir, 0755, true);

        try {
            $this->info('1/3 Mengekstrak arsip cadangan...');
            $zip = new ZipArchive;
            if ($zip->open($archivePath) !== true) {
                $this->error('Gagal membuka berkas zip cadangan.');

                return self::FAILURE;
            }
            $zip->extractTo($tempExtractDir);
            $zip->close();

            // Baca manifest jika ada
            $manifestFile = "{$tempExtractDir}/manifest.json";
            if (File::exists($manifestFile)) {
                $manifest = json_decode((string) File::get($manifestFile), true);
                $this->line("Informasi Arsip: Waktu pembuatan {$manifest['created_at']}, DB Checksum: {$manifest['db_checksum']}");
            }

            // 2. Pemulihan Database
            $this->info('2/3 Memulihkan basis data...');
            $dbDumpFile = "{$tempExtractDir}/database_dump.sql";
            if (File::exists($dbDumpFile)) {
                $this->restoreDatabase($dbDumpFile);
            }

            // 3. Pemulihan Berkas Privat
            $extractedFilesDir = "{$tempExtractDir}/private_files";
            if (File::isDirectory($extractedFilesDir)) {
                $targetFilesDir = $this->option('target-files')
                    ? (string) $this->option('target-files')
                    : storage_path('app/private');

                $this->info("3/3 Memulihkan berkas privat ke {$targetFilesDir}...");
                if (! File::isDirectory($targetFilesDir)) {
                    File::makeDirectory($targetFilesDir, 0755, true);
                }
                File::copyDirectory($extractedFilesDir, $targetFilesDir);
            }

            $this->info('✓ Pemulihan cadangan selesai dengan sukses!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Restore failed: '.$e->getMessage()."\n".$e->getTraceAsString());
            $this->error('Terjadi kesalahan selama prosedur restore: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            if (File::isDirectory($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
        }
    }

    /**
     * Pulihkan database dari file dump.
     */
    protected function restoreDatabase(string $dumpFile): void
    {
        $targetDbOption = $this->option('target-db');

        // Jika target database path SQLite ditentukan (misal saat drill restore)
        if ($targetDbOption && is_string($targetDbOption)) {
            $targetDir = dirname($targetDbOption);
            if (! File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            // Jika dump file adalah binary sqlite file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $dumpFile);
            finfo_close($finfo);

            if ($mime === 'application/x-sqlite3') {
                File::copy($dumpFile, $targetDbOption);

                return;
            }

            // Jika dump file berisi teks query SQL
            $pdo = new \PDO("sqlite:{$targetDbOption}");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sqlContent = (string) File::get($dumpFile);
            $pdo->exec($sqlContent);

            return;
        }

        // Default: Pulihkan ke koneksi aktif aplikasi
        $sqlContent = (string) File::get($dumpFile);
        if (trim($sqlContent) !== '') {
            DB::unprepared($sqlContent);
        }
    }
}
