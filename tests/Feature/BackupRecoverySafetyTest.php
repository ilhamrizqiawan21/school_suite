<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;
use ZipArchive;

class BackupRecoverySafetyTest extends TestCase
{
    private string $directory;

    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/school-recovery-'.bin2hex(random_bytes(8));
        File::makeDirectory($this->directory, 0700);
        $this->originalConnection = DB::getDefaultConnection();
        config(['backup.password' => str_repeat('recovery-test-secret-', 3), 'database.connections.backup_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('backup_test');
    }

    protected function tearDown(): void
    {
        DB::purge('backup_test');
        DB::setDefaultConnection($this->originalConnection);
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_backup_refuses_missing_encryption_password(): void
    {
        config(['backup.password' => null]);
        $this->artisan('school:backup', ['--destination' => $this->directory])->assertFailed();
        $this->assertCount(0, File::allFiles($this->directory));
    }

    public function test_sqlite_snapshot_restores_quotes_unicode_null_and_existing_target(): void
    {
        DB::statement('CREATE TABLE records (id INTEGER PRIMARY KEY, value TEXT, optional TEXT)');
        DB::table('records')->insert(['id' => 1, 'value' => "O'Brien \\ sekolah 日本", 'optional' => null]);
        $archive = $this->backup();
        $target = $this->directory.'/restored.sqlite';
        $pdo = new PDO('sqlite:'.$target);
        $pdo->exec('CREATE TABLE stale (id INTEGER)');
        $pdo = null;
        $this->artisan('school:restore', ['file' => $archive, '--target-db' => $target, '--target-files' => $this->directory.'/files', '--force' => true])->assertSuccessful();
        $restored = new PDO('sqlite:'.$target);
        $this->assertSame("O'Brien \\ sekolah 日本", $restored->query('SELECT value FROM records')->fetchColumn());
        $this->assertNull($restored->query('SELECT optional FROM records')->fetchColumn());
        $this->assertSame(0, (int) $restored->query("SELECT COUNT(*) FROM sqlite_master WHERE name='stale'")->fetchColumn());
    }

    public function test_file_sqlite_backup_includes_wal_and_restores_to_default_connection(): void
    {
        DB::purge('backup_test');
        $path = $this->directory.'/live.sqlite';
        touch($path);
        config(['database.connections.backup_test.database' => $path]);
        DB::statement('PRAGMA journal_mode=WAL');
        DB::statement('CREATE TABLE records (id INTEGER PRIMARY KEY, value TEXT)');
        DB::table('records')->insert(['id' => 1, 'value' => 'before']);
        $archive = $this->backup();
        DB::table('records')->where('id', 1)->update(['value' => 'after']);
        $this->artisan('school:restore', ['file' => $archive, '--force' => true])->assertSuccessful();
        $this->assertSame('before', DB::table('records')->where('id', 1)->value('value'));
    }

    public function test_wrong_password_cannot_modify_target(): void
    {
        DB::statement('CREATE TABLE records (id INTEGER PRIMARY KEY)');
        $archive = $this->backup();
        config(['backup.password' => str_repeat('wrong-secret-', 4)]);
        $target = $this->directory.'/unchanged.sqlite';
        File::put($target, 'unchanged');
        $this->artisan('school:restore', ['file' => $archive, '--target-db' => $target, '--force' => true])->assertFailed();
        $this->assertSame('unchanged', File::get($target));
    }

    public function test_tampered_dump_is_rejected_before_target_mutation(): void
    {
        $archive = $this->archive([
            'database_dump.sql' => 'DROP TABLE records;',
        ], ['database_dump.sql' => hash('sha256', 'original dump')]);
        $target = $this->directory.'/unchanged.sqlite';
        File::put($target, 'unchanged');
        $this->artisan('school:restore', ['file' => $archive, '--target-db' => $target, '--force' => true])->assertFailed();
        $this->assertSame('unchanged', File::get($target));
    }

    public function test_missing_private_file_is_rejected_before_target_mutation(): void
    {
        $archive = $this->archive(['database_dump.sql' => 'SELECT 1;'], [
            'database_dump.sql' => hash('sha256', 'SELECT 1;'),
            'private_files/missing.pdf' => hash('sha256', 'missing'),
        ]);
        $target = $this->directory.'/missing.sqlite';
        $this->artisan('school:restore', ['file' => $archive, '--target-db' => $target, '--force' => true])->assertFailed();
        $this->assertFileDoesNotExist($target);
    }

    public function test_archive_traversal_is_rejected(): void
    {
        $outside = 'escaped-'.bin2hex(random_bytes(8));
        $archive = $this->archive(['../'.$outside => 'escape'], []);
        $this->artisan('school:restore', ['file' => $archive, '--target-db' => $this->directory.'/target.sqlite', '--force' => true])->assertFailed();
        $this->assertFileDoesNotExist(storage_path('app/'.$outside));
    }

    private function backup(): string
    {
        $this->artisan('school:backup', ['--destination' => $this->directory, '--database-only' => true])->assertSuccessful();

        return glob($this->directory.'/*.zip')[0];
    }

    private function archive(array $files, array $checksums): string
    {
        $path = $this->directory.'/custom.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->setPassword((string) config('backup.password'));
        $files['manifest.json'] = json_encode(['version' => 2, 'db_format' => 'sqlite-sql', 'files' => $checksums], JSON_THROW_ON_ERROR);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
            $zip->setEncryptionName($name, ZipArchive::EM_AES_256);
        }
        $zip->close();

        return $path;
    }
}
