<?php

namespace App\Services\Operations;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseBackupService
{
    public function create(?int $userId = null, ?string $notes = null): SystemBackup
    {
        $source = $this->sqlitePath();
        $directory = storage_path('app/private/backups');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori backup tidak dapat dibuat.');
        }

        $filename = 'costing-'.now()->format('Ymd-His').'.sqlite';
        $target = $directory.DIRECTORY_SEPARATOR.$filename;
        DB::statement('PRAGMA wal_checkpoint(FULL)');
        if (! copy($source, $target)) {
            throw new RuntimeException('File backup gagal dibuat.');
        }

        return SystemBackup::create([
            'created_by' => $userId,
            'filename' => $filename,
            'path' => $target,
            'size_bytes' => filesize($target) ?: 0,
            'checksum' => hash_file('sha256', $target),
            'notes' => $notes,
            'verified_at' => now(),
        ]);
    }

    public function verify(SystemBackup $backup): bool
    {
        $valid = is_file($backup->path) && hash_file('sha256', $backup->path) === $backup->checksum;
        $backup->update(['status' => $valid ? 'ready' : 'corrupt', 'verified_at' => $valid ? now() : null]);

        return $valid;
    }

    public function restore(SystemBackup $backup, int $userId): void
    {
        if (! $this->verify($backup)) {
            throw new RuntimeException('Backup tidak lolos verifikasi checksum.');
        }
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

    private function sqlitePath(): string
    {
        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException('Backup otomatis saat ini hanya mendukung database SQLite.');
        }
        $path = (string) config('database.connections.sqlite.database');
        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('File database SQLite tidak ditemukan.');
        }

        return $path;
    }
}
