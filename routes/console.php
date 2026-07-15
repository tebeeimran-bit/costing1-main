<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:backup-sqlite {--keep=14 : Number of backup files to retain}', function () {
    $connection = (string) config('database.default');
    if ($connection !== 'sqlite') {
        $this->error('This command is intended for sqlite connection only.');
        return self::FAILURE;
    }

    $databasePath = (string) config('database.connections.sqlite.database');
    if ($databasePath === '' || !is_file($databasePath)) {
        $this->error('SQLite database file was not found: ' . $databasePath);
        return self::FAILURE;
    }

    $backupDir = database_path('backups');
    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        $this->error('Unable to create backup directory: ' . $backupDir);
        return self::FAILURE;
    }

    $timestamp = now()->format('Ymd_His');
    $backupFile = $backupDir . '/database.sqlite.' . $timestamp . '.bak';

    if (!copy($databasePath, $backupFile)) {
        $this->error('Failed to create backup file.');
        return self::FAILURE;
    }

    $keep = max(1, (int) $this->option('keep'));
    $files = glob($backupDir . '/database.sqlite.*.bak') ?: [];
    rsort($files, SORT_STRING);

    $removedCount = 0;
    foreach (array_slice($files, $keep) as $oldFile) {
        if (is_file($oldFile) && @unlink($oldFile)) {
            $removedCount++;
        }
    }

    $this->info('Backup created: ' . $backupFile);
    $this->line('Retention: keeping ' . $keep . ' file(s), removed ' . $removedCount . '.');

    return self::SUCCESS;
})->purpose('Create and rotate SQLite database backup files');

Schedule::command('db:backup-sqlite --keep=14')->dailyAt('01:30');
