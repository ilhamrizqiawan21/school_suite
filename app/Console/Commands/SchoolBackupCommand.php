<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class SchoolBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:backup
                            {--destination= : Path direktori tujuan penyimpanan backup}
                            {--database-only : Hanya mem-backup basis data tanpa berkas privat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cadangkan basis data dan seluruh berkas privat sekolah ke arsip aman terenkripsi/terstempel';

    public function handle(): int
    {
        $this->info('=== Memulai Prosedur Cadangan Mandiri School Suite ===');

        $backupDir = $this->option('destination')
            ? (string) $this->option('destination')
            : storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $tempDir = storage_path("app/backup_temp_{$timestamp}");
        File::makeDirectory($tempDir, 0755, true);

        try {
            // 1. Cadangkan Database
            $this->info('1/3 Mencadangkan struktur & data database...');
            $dbDumpFile = "{$tempDir}/database_dump.sql";
            $this->dumpDatabase($dbDumpFile);

            // 2. Cadangkan Berkas Privat
            if (! $this->option('database-only')) {
                $this->info('2/3 Menyalin berkas privat sekolah (storage/app/private)...');
                $privateStorageDir = storage_path('app/private');
                $targetFilesDir = "{$tempDir}/private_files";

                if (File::isDirectory($privateStorageDir)) {
                    File::copyDirectory($privateStorageDir, $targetFilesDir);
                } else {
                    File::makeDirectory($targetFilesDir, 0755, true);
                }
            }

            // 3. Buat Manifest Metadata
            $manifest = [
                'app_name' => config('app.name', 'School Suite'),
                'created_at' => now()->toIso8601String(),
                'php_version' => PHP_VERSION,
                'db_driver' => DB::getDriverName(),
                'db_checksum' => hash_file('sha256', $dbDumpFile),
                'includes_files' => ! $this->option('database-only'),
            ];
            File::put("{$tempDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

            // 4. Kompresi ke format ZIP
            $this->info('3/3 Mengompresi seluruh paket cadangan...');
            $archiveFilename = "backup_school_suite_{$timestamp}.zip";
            $archivePath = "{$backupDir}/{$archiveFilename}";

            $zip = new ZipArchive;
            if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->error('Gagal membuat berkas arsip zip backup.');

                return self::FAILURE;
            }

            $filesToZip = File::allFiles($tempDir);
            foreach ($filesToZip as $file) {
                $relativePath = substr($file->getPathname(), strlen($tempDir) + 1);
                $zip->addFile($file->getPathname(), $relativePath);
            }
            $zip->close();

            $fileSizeMb = round(filesize($archivePath) / (1024 * 1024), 2);
            $this->info("✓ Cadangan selesai dengan sukses: {$archivePath} ({$fileSizeMb} MB)");
            $this->line('Checksum SHA-256: '.hash_file('sha256', $archivePath));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Terjadi kesalahan saat proses backup: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            // Bersihkan direktori temporary
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Dump database secara fleksibel (SQLite & MySQL kompatibel).
     */
    protected function dumpDatabase(string $targetFile): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $databasePath = $connection->getDatabaseName();
            if ($databasePath === ':memory:') {
                // Untuk in-memory DB saat testing, dump SQL query schema & data
                $this->dumpGenericTablesToSql($targetFile);
            } else {
                File::copy($databasePath, $targetFile);
            }

            return;
        }

        // Untuk MySQL / database server lain
        $this->dumpGenericTablesToSql($targetFile);
    }

    /**
     * Serialisasi skema dan data seluruh tabel ke format script SQL portabel.
     */
    protected function dumpGenericTablesToSql(string $targetFile): void
    {
        $sql = "-- School Suite Database Dump\n-- Generated: ".now()->toIso8601String()."\n\n";

        // 1. Ekstrak DDL Skema (CREATE TABLE & INDEX) jika di SQLite
        if (DB::getDriverName() === 'sqlite') {
            $schemaObjects = DB::select("SELECT sql FROM sqlite_master WHERE sql IS NOT NULL AND type IN ('table', 'index') AND name NOT LIKE 'sqlite_%' ORDER BY type DESC");
            foreach ($schemaObjects as $obj) {
                if (isset($obj->sql)) {
                    $sql .= "{$obj->sql};\n";
                }
            }
            $sql .= "\n-- DATA INSERTIONS --\n\n";
        }

        $rawTables = Schema::getTableListing();

        foreach ($rawTables as $rawTable) {
            $table = str_contains($rawTable, '.') ? explode('.', $rawTable)[1] : $rawTable;

            // Abaikan tabel internal sqlite / cache
            if (str_starts_with($table, 'sqlite_')) {
                continue;
            }

            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) {
                continue;
            }

            $sql .= "-- Data for table: {$table}\n";
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $columns = implode(', ', array_map(fn ($col) => "\"{$col}\"", array_keys($rowArray)));
                $values = implode(', ', array_map(function ($val) {
                    if ($val === null) {
                        return 'NULL';
                    }
                    if (is_numeric($val) && ! is_string($val)) {
                        return (string) $val;
                    }

                    return "'".addslashes((string) $val)."'";
                }, array_values($rowArray)));

                $sql .= "INSERT INTO \"{$table}\" ({$columns}) VALUES ({$values});\n";
            }
            $sql .= "\n";
        }

        File::put($targetFile, $sql);
    }
}
