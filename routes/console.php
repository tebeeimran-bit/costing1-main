<?php

use App\Http\Controllers\ExportCenterController;
use App\Models\ExportJob;
use App\Models\SystemEvent;
use App\Services\Operations\DatabaseBackupService;
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
    if ($databasePath === '' || ! is_file($databasePath)) {
        $this->error('SQLite database file was not found: '.$databasePath);

        return self::FAILURE;
    }

    $backupDir = database_path('backups');
    if (! is_dir($backupDir) && ! mkdir($backupDir, 0775, true) && ! is_dir($backupDir)) {
        $this->error('Unable to create backup directory: '.$backupDir);

        return self::FAILURE;
    }

    $timestamp = now()->format('Ymd_His');
    $backupFile = $backupDir.'/database.sqlite.'.$timestamp.'.bak';

    if (! copy($databasePath, $backupFile)) {
        $this->error('Failed to create backup file.');

        return self::FAILURE;
    }

    $keep = max(1, (int) $this->option('keep'));
    $files = glob($backupDir.'/database.sqlite.*.bak') ?: [];
    rsort($files, SORT_STRING);

    $removedCount = 0;
    foreach (array_slice($files, $keep) as $oldFile) {
        if (is_file($oldFile) && @unlink($oldFile)) {
            $removedCount++;
        }
    }

    $this->info('Backup created: '.$backupFile);
    $this->line('Retention: keeping '.$keep.' file(s), removed '.$removedCount.'.');

    return self::SUCCESS;
})->purpose('Create and rotate SQLite database backup files');

Artisan::command('costing:backup', function (DatabaseBackupService $service) {
    $backup = $service->create(null, 'Scheduled automatic backup');
    $this->info('Verified backup created: '.$backup->filename);

    return self::SUCCESS;
})->purpose('Create a verified Costing System backup');

Schedule::command('costing:backup')->dailyAt('01:30')->withoutOverlapping();

Artisan::command('exports:run-scheduled', function (ExportCenterController $controller) {
    $jobs = ExportJob::whereNotNull('scheduled_for')->where('scheduled_for', '<=', now())->get();
    foreach ($jobs as $job) {
        $controller->generate($job);
    }
    $this->info($jobs->count().' scheduled export(s) generated.');
})->purpose('Generate due scheduled exports');

Artisan::command('system-events:prune', function () {
    $count = SystemEvent::where('occurred_at', '<', now()->subDays(config('operations.retention_days', 30)))->delete();
    $this->info($count.' old event(s) removed.');
})->purpose('Prune old monitoring events');

Schedule::command('exports:run-scheduled')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('system-events:prune')->dailyAt('02:15')->withoutOverlapping();
