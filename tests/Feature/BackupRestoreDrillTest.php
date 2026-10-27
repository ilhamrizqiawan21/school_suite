<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;
use ZipArchive;

class BackupRestoreDrillTest extends TestCase
{
    use RefreshDatabase;

    private string $backupTestDir;

    private string $drillDbPath;

    private string $drillFilesDir;

    protected function setUp(): void
    {
        parent::setUp();
        config(['backup.password' => str_repeat('test-backup-secret-', 3)]);

        $this->backupTestDir = storage_path('app/testing_backups');
        $this->drillDbPath = storage_path('app/testing_drill_db.sqlite');
        $this->drillFilesDir = storage_path('app/testing_drill_files');

        File::makeDirectory($this->backupTestDir, 0755, true, true);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->backupTestDir)) {
            File::deleteDirectory($this->backupTestDir);
        }
        if (File::exists($this->drillDbPath)) {
            File::delete($this->drillDbPath);
        }
        if (File::isDirectory($this->drillFilesDir)) {
            File::deleteDirectory($this->drillFilesDir);
        }

        parent::tearDown();
    }

    public function test_backup_command_creates_valid_zip_archive_with_manifest(): void
    {
        $school = School::create([
            'npsn' => '88889999',
            'name' => 'SMP Pilot Percontohan',
            'status' => 'active',
        ]);

        $storage = new PrivateStorageService;
        $storage->put('sample_document.txt', 'ISI_DOKUMEN_SENSITIF_SEKOLAH');

        $this->artisan('school:backup', [
            '--destination' => $this->backupTestDir,
        ])->assertSuccessful();

        // Periksa file zip yang dihasilkan
        $files = File::files($this->backupTestDir);
        $this->assertCount(1, $files);

        $zipPath = $files[0]->getPathname();
        $this->assertStringEndsWith('.zip', $zipPath);

        // Buka zip dan verifikasi manifest serta komponennya
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertNotFalse($zip->locateName('database_dump.sql'));
        $this->assertNotFalse($zip->locateName('private_files/sample_document.txt'));

        $this->assertFalse(@$zip->getFromName('database_dump.sql'));
        $zip->setPassword((string) config('backup.password'));
        $manifestContent = $zip->getFromName('manifest.json');
        $manifest = json_decode((string) $manifestContent, true);
        $this->assertArrayHasKey('created_at', $manifest);
        $this->assertArrayHasKey('db_checksum', $manifest);

        $zip->close();
    }

    public function test_restore_drill_into_isolated_database_restores_data_completely(): void
    {
        $school = School::create([
            'npsn' => '77776666',
            'name' => 'SMP Laboratorium Prestasi',
            'status' => 'active',
        ]);

        $storage = new PrivateStorageService;
        $storage->put('student_file.pdf', 'KONTEN_BERKAS_SISWA');

        // 1. Eksekusi Backup
        $this->artisan('school:backup', [
            '--destination' => $this->backupTestDir,
        ])->assertSuccessful();

        $zipPath = File::files($this->backupTestDir)[0]->getPathname();

        // 2. Jalankan Drill Restore ke target terpisah (isolated target-db dan target-files)
        $this->artisan('school:restore', [
            'file' => $zipPath,
            '--target-db' => $this->drillDbPath,
            '--target-files' => $this->drillFilesDir,
            '--force' => true,
        ])->assertSuccessful();

        // 3. Verifikasi berkas privat berhasil dipulihkan
        $restoredFile = "{$this->drillFilesDir}/student_file.pdf";
        $this->assertTrue(File::exists($restoredFile));
        $this->assertSame('KONTEN_BERKAS_SISWA', File::get($restoredFile));

        // 4. Verifikasi database terpisah berhasil dipulihkan (query langsung ke drillDbPath via PDO)
        $this->assertTrue(File::exists($this->drillDbPath));
        $pdo = new PDO("sqlite:{$this->drillDbPath}");
        $stmt = $pdo->query("SELECT name, npsn FROM schools WHERE npsn = '77776666'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        $this->assertSame('SMP Laboratorium Prestasi', $row['name']);
        $this->assertSame('77776666', $row['npsn']);
    }
}
