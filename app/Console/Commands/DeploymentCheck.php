<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        if (blank(config('app.key'))) {
            $errors[] = 'APP_KEY belum diisi. Jalankan php artisan key:generate.';
        }

        try {
            DB::connection()->getPdo();
            $this->components->info('Koneksi database berhasil.');
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
