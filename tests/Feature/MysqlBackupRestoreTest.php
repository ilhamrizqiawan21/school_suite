<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use PDOException;
use Tests\TestCase;

class MysqlBackupRestoreTest extends TestCase
{
    public function test_pilot_school_mysql_backup_restores_all_tables_and_data_checksums_into_an_empty_database(): void
    {
        $socket = getenv('BACKUP_MYSQL_TEST_SOCKET');
        if (! $socket || ! is_file($socket)) {
            $this->markTestSkipped('MySQL drill skipped: set BACKUP_MYSQL_TEST_SOCKET to an isolated local MySQL socket.');
        }

        try {
            $pdo = new PDO('mysql:unix_socket='.$socket, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException) {
            $this->markTestSkipped('MySQL drill skipped: the configured isolated MySQL socket is not accepting root connections.');
        }

        $source = 'backup_source_'.bin2hex(random_bytes(6));
        $target = 'backup_target_'.bin2hex(random_bytes(6));
        $directory = sys_get_temp_dir().'/school-mysql-backup-'.bin2hex(random_bytes(6));
        $original = DB::getDefaultConnection();
        $createdDatabases = [];
        try {
            foreach ([$source, $target] as $name) {
                $pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $createdDatabases[] = $name;
            }

            $connection = [
                'driver' => 'mysql',
                'unix_socket' => $socket,
                'host' => 'localhost',
                'port' => 3306,
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ];
            config([
                'database.connections.backup_mysql_source' => [...$connection, 'database' => $source],
                'database.connections.backup_mysql_target' => [...$connection, 'database' => $target],
                'backup.password' => str_repeat('mysql-drill-password-', 3),
            ]);
            DB::setDefaultConnection('backup_mysql_source');

            $this->artisan('migrate', ['--database' => 'backup_mysql_source', '--force' => true])->assertSuccessful();
            $this->artisan('db:seed', [
                '--database' => 'backup_mysql_source',
                '--class' => 'Database\\Seeders\\PilotSchoolSeeder',
                '--force' => true,
            ])->assertSuccessful();

            $source = DB::connection('backup_mysql_source');
            $expectedCounts = $this->tableCounts($source);
            $this->assertNotEmpty($expectedCounts);
            $this->assertGreaterThan(0, $source->table('schools')->count());
            $this->assertGreaterThan(0, $source->table('students')->count());
            $this->assertGreaterThan(0, $source->table('enrollments')->count());
            $expectedChecksums = $this->importantDataChecksums($source);

            $this->assertSame([], $this->tableCounts(DB::connection('backup_mysql_target')));
            $this->artisan('school:backup', [
                '--destination' => $directory,
                '--database-only' => true,
            ])->assertSuccessful();

            $archive = glob($directory.'/*.zip')[0] ?? null;
            $this->assertNotNull($archive, 'Backup command should create an archive.');
            $this->artisan('school:restore', [
                'file' => $archive,
                '--target-db' => 'backup_mysql_target',
                '--force' => true,
            ])->assertSuccessful();

            $restored = DB::connection('backup_mysql_target');
            $this->assertSame($expectedCounts, $this->tableCounts($restored));
            $this->assertSame($expectedChecksums, $this->importantDataChecksums($restored));
        } finally {
            DB::purge('backup_mysql_source');
            DB::purge('backup_mysql_target');
            DB::setDefaultConnection($original);
            foreach ($createdDatabases as $name) {
                $pdo->exec("DROP DATABASE IF EXISTS `$name`");
            }
            File::deleteDirectory($directory);
        }
    }

    /** @return array<string, int> */
    private function tableCounts(Connection $connection): array
    {
        $tables = $connection->table('information_schema.tables')
            ->where('table_schema', $connection->getDatabaseName())
            ->where('table_type', 'BASE TABLE')
            ->orderBy('table_name')
            ->pluck('table_name');

        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = $connection->table($table)->count();
        }

        return $counts;
    }

    /** @return array<string, string> */
    private function importantDataChecksums(Connection $connection): array
    {
        $checksums = [];
        foreach (['schools', 'roles', 'users', 'students', 'enrollments', 'teaching_assignments', 'scores', 'report_snapshots'] as $table) {
            $rows = $connection->table($table)->orderBy('id')->get()->map(fn ($row): array => (array) $row)->all();
            $checksums[$table] = hash('sha256', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        return $checksums;
    }
}
