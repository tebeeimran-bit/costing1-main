<?php

namespace App\Services\Operations;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public function create(?int $userId = null, ?string $notes = null): SystemBackup
    {
        $driver = (string) config('database.default');
        $directory = storage_path('app/private/backups');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori backup tidak dapat dibuat.');
        }
        $filename = 'costing-'.now()->format('Ymd-His').($driver === 'sqlite' ? '.sqlite' : '.sql');
        $target = $directory.DIRECTORY_SEPARATOR.$filename;
        $this->dump($driver, $target);

        return SystemBackup::create([
            'created_by' => $userId, 'database_driver' => $driver, 'filename' => $filename,
            'path' => $target, 'size_bytes' => filesize($target) ?: 0,
            'checksum' => hash_file('sha256', $target), 'notes' => $notes, 'verified_at' => now(),
        ]);
    }

    public function verify(SystemBackup $backup): bool
    {
        $valid = is_file($backup->path) && filesize($backup->path) > 0
            && hash_equals((string) $backup->checksum, (string) hash_file('sha256', $backup->path));
        $backup->update(['status' => $valid ? 'ready' : 'corrupt', 'verified_at' => $valid ? now() : null]);

        return $valid;
    }

    public function restore(SystemBackup $backup, int $userId): void
    {
        if (! $this->verify($backup)) {
            throw new RuntimeException('Backup tidak lolos verifikasi checksum.');
        }
        $driver = (string) config('database.default');
        if ($backup->database_driver !== $driver) {
            throw new RuntimeException('Driver backup tidak sama dengan database aktif.');
        }
        if ($driver === 'sqlite') {
            $this->restoreSqlite($backup);
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->restoreMysql($backup);
        } else {
            throw new RuntimeException('Backup dan restore belum mendukung driver '.$driver.'.');
        }
    }

    private function dump(string $driver, string $target): void
    {
        if ($driver === 'sqlite') {
            $source = $this->sqlitePath();
            DB::statement('PRAGMA wal_checkpoint(FULL)');
            if (! copy($source, $target)) {
                throw new RuntimeException('File backup SQLite gagal dibuat.');
            }
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->dumpMysql($target);
        } else {
            throw new RuntimeException('Backup belum mendukung driver '.$driver.'.');
        }
    }

    private function dumpMysql(string $target): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $this->run([
            $this->binary('mysqldump'),
            '--host='.(string) $connection['host'], '--port='.(string) $connection['port'],
            '--user='.(string) $connection['username'], '--single-transaction', '--quick', '--routines', '--triggers',
            '--default-character-set='.(string) ($connection['charset'] ?? 'utf8mb4'), '--result-file='.$target,
            (string) $connection['database'],
        ], ['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);
        if (! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('mysqldump tidak menghasilkan file backup.');
        }
    }

    private function restoreSqlite(SystemBackup $backup): void
    {
        $source = $this->sqlitePath();
        $rescue = $source.'.before-restore-'.now()->format('Ymd-His');
        if (! copy($source, $rescue)) {
            throw new RuntimeException('Safety copy sebelum restore gagal dibuat.');
        }
        DB::disconnect();
        if (! copy($backup->path, $source)) {
            copy($rescue, $source);
            throw new RuntimeException('Restore gagal; database sudah dikembalikan ke kondisi semula.');
        }
    }

    private function restoreMysql(SystemBackup $backup): void
    {
        $rescue = storage_path('app/private/backups/mysql-before-restore-'.now()->format('Ymd-His').'.sql');
        $this->dumpMysql($rescue);
        DB::disconnect();
        try {
            $this->importMysql($backup->path);
        } catch (RuntimeException $exception) {
            $this->importMysql($rescue);
            throw new RuntimeException('Restore MySQL gagal; safety backup berhasil dikembalikan. '.$exception->getMessage(), 0, $exception);
        } finally {
            DB::reconnect();
        }
    }

    private function importMysql(string $path): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $process = new Process([
            $this->binary('mysql'),
            '--host='.(string) $connection['host'], '--port='.(string) $connection['port'],
            '--user='.(string) $connection['username'],
            '--default-character-set='.(string) ($connection['charset'] ?? 'utf8mb4'),
            (string) $connection['database'],
        ], null, ['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('File backup tidak dapat dibaca.');
        }
        try {
            $process->setInput($stream);
            $this->runProcess($process);
        } finally {
            fclose($stream);
        }
    }

    private function run(array $command, array $environment = []): void
    {
        $this->runProcess(new Process($command, null, $environment));
    }

    private function runProcess(Process $process): void
    {
        $process->setTimeout((int) config('operations.backup.timeout_seconds', 600));
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Perintah database gagal: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    private function sqlitePath(): string
    {
        $path = (string) config('database.connections.sqlite.database');
        if ($path === '' || $path === ':memory:' || ! is_file($path)) {
            throw new RuntimeException('File database SQLite tidak ditemukan.');
        }

        return $path;
    }

    private function binary(string $name): string
    {
        $configured = (string) config('operations.backup.'.$name.'_path', $name);
        if ($configured !== $name || PHP_OS_FAMILY !== 'Windows') {
            return $configured;
        }
        foreach (['C:\\xampp\\mysql\\bin\\'.$name.'.exe', 'F:\\xampp\\mysql\\bin\\'.$name.'.exe'] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $name;
    }
}
