<?php

declare(strict_types=1);

namespace App\Shared\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PDO;
use RuntimeException;

class DatabaseBackupService
{
    public function dump(string $target): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->runMysql($connection, $target, false);

            return 'mysql-sql';
        }
        if ($driver !== 'sqlite') {
            throw new RuntimeException('Driver backup tidak didukung.');
        }
        if ($connection->getDatabaseName() !== ':memory:') {
            // VACUUM INTO includes committed WAL pages in a consistent SQLite snapshot.
            $connection->getPdo()->exec('VACUUM INTO '.$connection->getPdo()->quote($target));

            return 'sqlite-binary';
        }

        $pdo = $connection->getPdo();
        $sql = "PRAGMA foreign_keys=OFF;\n";
        $objects = $connection->select("SELECT name, type, sql FROM sqlite_master WHERE sql IS NOT NULL AND type IN ('table','index','trigger','view') AND name NOT LIKE 'sqlite_%' ORDER BY CASE type WHEN 'table' THEN 0 WHEN 'index' THEN 1 ELSE 2 END");
        foreach ($objects as $object) {
            $sql .= $object->sql.";\n";
            if ($object->type !== 'table') {
                continue;
            }
            $table = '"'.str_replace('"', '""', $object->name).'"';
            foreach ($connection->table($object->name)->cursor() as $row) {
                $columns = array_map(fn ($column) => '"'.str_replace('"', '""', $column).'"', array_keys((array) $row));
                $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values((array) $row));
                $sql .= 'INSERT INTO '.$table.' ('.implode(',', $columns).') VALUES ('.implode(',', $values).");\n";
            }
        }
        File::put($target, $sql."PRAGMA foreign_keys=ON;\n");

        return 'sqlite-sql';
    }

    public function restore(string $dump, string $format, ?string $target = null): void
    {
        if ($format === 'mysql-sql') {
            $connection = DB::connection($target);
            if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
                throw new RuntimeException('Dump MySQL membutuhkan koneksi target MySQL/MariaDB.');
            }
            $this->runMysql($connection, $dump, true);

            return;
        }
        if (! in_array($format, ['sqlite-binary', 'sqlite-sql'], true)) {
            throw new RuntimeException('Format dump tidak didukung.');
        }
        $connection = DB::connection();
        if ($target === null && $connection->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Dump SQLite membutuhkan target SQLite.');
        }
        $database = $target ?? $connection->getDatabaseName();
        if ($database === ':memory:') {
            if ($format !== 'sqlite-sql') {
                throw new RuntimeException('Dump biner SQLite membutuhkan target file.');
            }
            $connection->getSchemaBuilder()->dropAllTables();
            $connection->getPdo()->exec((string) File::get($dump));

            return;
        }
        File::ensureDirectoryExists(dirname($database), 0700);
        $staged = $database.'.restore-'.bin2hex(random_bytes(8));
        try {
            if ($format === 'sqlite-binary') {
                if (file_get_contents($dump, false, null, 0, 16) !== "SQLite format 3\0") {
                    throw new RuntimeException('Header dump SQLite tidak valid.');
                }
                File::copy($dump, $staged);
            } else {
                $pdo = new PDO('sqlite:'.$staged);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec((string) File::get($dump));
                $pdo = null;
            }
            chmod($staged, 0600);
            if ($target === null) {
                DB::purge();
            }
            // Restore must run offline: discard sidecars belonging to the old database.
            File::delete([$database.'-wal', $database.'-shm']);
            if (! rename($staged, $database)) {
                throw new RuntimeException('Tidak dapat mengganti database target.');
            }
        } finally {
            File::delete($staged);
        }
    }

    private function runMysql(Connection $connection, string $dump, bool $restore): void
    {
        $config = $connection->getConfig();
        $credentials = tempnam(sys_get_temp_dir(), 'school-db-');
        if ($credentials === false) {
            throw new RuntimeException('Tidak dapat menyiapkan konfigurasi database sementara.');
        }
        chmod($credentials, 0600);
        $input = null;
        try {
            $options = ['user' => $config['username'] ?? '', 'password' => $config['password'] ?? '', 'host' => $config['host'] ?? '127.0.0.1', 'port' => $config['port'] ?? 3306];
            if (! empty($config['unix_socket'])) {
                $options['socket'] = $config['unix_socket'];
            }
            if (defined('PDO::MYSQL_ATTR_SSL_CA') && ! empty($config['options'][constant('PDO::MYSQL_ATTR_SSL_CA')])) {
                $options['ssl-ca'] = $config['options'][constant('PDO::MYSQL_ATTR_SSL_CA')];
            }
            $contents = "[client]\n";
            foreach ($options as $key => $value) {
                $contents .= $key.'="'.strtr((string) $value, ["\\" => "\\\\", '"' => '\\"', "\n" => '\\n', "\r" => '\\r']).'"'."\n";
            }
            File::put($credentials, $contents);
            $maria = $connection->getDriverName() === 'mariadb';
            $command = [$restore ? ($maria ? 'mariadb' : 'mysql') : ($maria ? 'mariadb-dump' : 'mysqldump'), '--defaults-extra-file='.$credentials];
            if ($restore) {
                $command[] = '--database='.$config['database'];
                $input = fopen($dump, 'rb');
                if ($input === false) {
                    throw new RuntimeException('Dump tidak dapat dibaca.');
                }
            } else {
                $command = [...$command, '--single-transaction', '--quick', '--routines', '--events', '--triggers', '--hex-blob', '--add-drop-table', '--no-tablespaces'];
                if (! $maria) {
                    $command[] = '--set-gtid-purged=OFF';
                }
                $command = [...$command, '--result-file='.$dump, '--databases', $config['database'], '--no-create-db'];
            }
            $result = Process::timeout(1800)->input($input)->run($command);
            if (! $result->successful()) {
                // Do not echo native stderr: it can contain SQL, credentials, or student data.
                throw new RuntimeException('Proses database native gagal. Periksa instalasi client, izin, dan konfigurasi TLS/database.');
            }
        } finally {
            if (is_resource($input)) {
                fclose($input);
            }
            File::delete($credentials);
        }
    }
}
