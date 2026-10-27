<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class MysqlBackupRestoreTest extends TestCase
{
    public function test_encrypted_native_mysql_backup_restores_schema_and_related_data_into_another_database(): void
    {
        $socket = getenv('BACKUP_MYSQL_TEST_SOCKET');
        if (! $socket) {
            $this->markTestSkipped('Set BACKUP_MYSQL_TEST_SOCKET to an isolated MySQL drill server.');
        }
        $pdo = new PDO('mysql:unix_socket='.$socket, 'root', '');
        $source = 'backup_source_'.bin2hex(random_bytes(6));
        $target = 'backup_target_'.bin2hex(random_bytes(6));
        $directory = sys_get_temp_dir().'/school-mysql-backup-'.bin2hex(random_bytes(6));
        $original = DB::getDefaultConnection();
        try {
            $pdo->exec("CREATE DATABASE `$source` CHARACTER SET utf8mb4");
            $pdo->exec("CREATE DATABASE `$target` CHARACTER SET utf8mb4");
            $connection = ['driver' => 'mysql', 'unix_socket' => $socket, 'host' => 'localhost', 'port' => 3306, 'username' => 'root', 'password' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true];
            config(['database.connections.backup_mysql_source' => [...$connection, 'database' => $source], 'database.connections.backup_mysql_target' => [...$connection, 'database' => $target], 'backup.password' => str_repeat('mysql-drill-password-', 3)]);
            DB::setDefaultConnection('backup_mysql_source');
            DB::statement('CREATE TABLE parents (id BIGINT PRIMARY KEY, name TEXT, optional TEXT NULL) ENGINE=InnoDB');
            DB::statement('CREATE TABLE children (id BIGINT PRIMARY KEY, parent_id BIGINT, payload BLOB, FOREIGN KEY (parent_id) REFERENCES parents(id)) ENGINE=InnoDB');
            DB::table('parents')->insert(['id' => 1, 'name' => "O'Brien \\ 日本", 'optional' => null]);
            DB::table('children')->insert(['id' => 2, 'parent_id' => 1, 'payload' => "\0\x01test"]);
            DB::connection('backup_mysql_target')->statement('CREATE TABLE parents (old INT)');
            $this->artisan('school:backup', ['--destination' => $directory, '--database-only' => true])->assertSuccessful();
            DB::table('parents')->where('id', 1)->update(['name' => 'changed after backup']);
            $this->artisan('school:restore', ['file' => glob($directory.'/*.zip')[0], '--target-db' => 'backup_mysql_target', '--target-files' => $directory.'/files', '--force' => true])->assertSuccessful();
            $restored = DB::connection('backup_mysql_target');
            $this->assertSame("O'Brien \\ 日本", $restored->table('parents')->value('name'));
            $this->assertNull($restored->table('parents')->value('optional'));
            $this->assertSame("\0\x01test", $restored->table('children')->value('payload'));
            $this->assertSame(1, $restored->table('children')->join('parents', 'parents.id', '=', 'children.parent_id')->count());
            $this->assertSame('changed after backup', DB::table('parents')->value('name'));
            $this->assertSame(1, (int) $restored->selectOne('SELECT COUNT(*) total FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ?', [$target])->total);
        } finally {
            DB::purge('backup_mysql_source');
            DB::purge('backup_mysql_target');
            DB::setDefaultConnection($original);
            $pdo->exec("DROP DATABASE IF EXISTS `$target`");
            $pdo->exec("DROP DATABASE IF EXISTS `$source`");
            File::deleteDirectory($directory);
        }
    }
}
