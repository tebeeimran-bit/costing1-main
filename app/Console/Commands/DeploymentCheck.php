<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeploymentCheck extends Command
{
    protected $signature = 'app:deployment-check';

    protected $description = 'Memeriksa kesesuaian mode aplikasi dan database sebelum digunakan';

    public function handle(): int
    {
        $mode = (string) config('deployment.mode');
        $connection = (string) config('database.default');
        $errors = [];

        $this->components->info("Mode: {$mode}; database: {$connection}");

        if (! in_array($mode, ['demo', 'production'], true)) {
            $errors[] = 'APP_MODE harus bernilai demo atau production.';
        }

        if ($mode === 'demo' && $connection !== 'sqlite') {
            $errors[] = 'Mode demo harus menggunakan DB_CONNECTION=sqlite.';
        }

        if ($mode === 'production' && $connection !== 'mysql') {
            $errors[] = 'Mode production harus menggunakan DB_CONNECTION=mysql.';
        }

        if ($mode === 'production' && (bool) config('app.debug')) {
            $errors[] = 'APP_DEBUG harus false pada production.';
        }

        if ($mode === 'production' && app()->environment() !== 'production') {
            $errors[] = 'APP_ENV harus production pada mode production.';
        }

        if ($mode === 'production' && config('queue.default') === 'sync') {
            $errors[] = 'QUEUE_CONNECTION tidak boleh sync pada production.';
        }

        if ($mode === 'production' && config('session.driver') === 'array') {
            $errors[] = 'SESSION_DRIVER tidak boleh array pada production.';
        }

        if ($mode === 'production' && config('cache.default') === 'array') {
            $errors[] = 'CACHE_STORE tidak boleh array pada production.';
        }

        $appUrl = (string) config('app.url');
        if ($mode === 'production' && (Str::contains($appUrl, ['localhost', '127.0.0.1']) || $appUrl === '')) {
            $errors[] = 'APP_URL harus menggunakan IP statis atau hostname internal server.';
        }

        if ($mode === 'production' && str_starts_with($appUrl, 'https://') && ! (bool) config('session.secure')) {
            $errors[] = 'SESSION_SECURE_COOKIE harus true ketika APP_URL menggunakan HTTPS.';
        }

        if (blank(config('app.key'))) {
            $errors[] = 'APP_KEY belum diisi. Jalankan php artisan key:generate.';
        }

        try {
            DB::connection()->getPdo();
            $this->components->info('Koneksi database berhasil.');

            foreach (['users', 'sessions', 'cache', 'jobs'] as $table) {
                if (! DB::getSchemaBuilder()->hasTable($table)) {
                    $errors[] = "Tabel wajib belum tersedia: {$table}. Jalankan php artisan migrate --force.";
                }
            }
        } catch (\Throwable $exception) {
            $errors[] = 'Koneksi database gagal: '.$exception->getMessage();
        }

        foreach ([
            storage_path('app/templates/form-costing-v9.xlsx'),
            storage_path('app/templates/new-part-request-template.xlsx'),
        ] as $template) {
            if (! is_file($template)) {
                $errors[] = 'Template tidak ditemukan: '.$template;
            }
        }

        foreach ([storage_path(), base_path('bootstrap/cache')] as $directory) {
            if (! is_dir($directory) || ! is_writable($directory)) {
                $errors[] = 'Folder harus tersedia dan dapat ditulis oleh web server: '.$directory;
            }
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->components->info('Konfigurasi siap digunakan.');

        return self::SUCCESS;
    }
}
