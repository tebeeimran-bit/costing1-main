<?php

namespace App\Console\Commands;

use App\Models\ProjectA00Form;
use App\Services\Costing\CostingGroupService;
use Illuminate\Console\Command;

class BackfillCostingGroups extends Command
{
    protected $signature = 'costing-groups:backfill {--dry-run : Hanya tampilkan jumlah tanpa menulis data}';
    protected $description = 'Membuat atau menyinkronkan Costing Group dari seluruh dokumen A00 secara idempotent.';

    public function handle(CostingGroupService $groups): int
    {
        $count = ProjectA00Form::count();
        $this->info("A00 ditemukan: {$count}");
        if ($this->option('dry-run')) return self::SUCCESS;

        $processed = 0;
        ProjectA00Form::query()->orderBy('id')->chunkById(100, function ($forms) use ($groups, &$processed) {
            foreach ($forms as $form) {
                $groups->syncFromA00($form);
                $processed++;
            }
        });
        $this->info("Costing Group disinkronkan: {$processed}");
        return self::SUCCESS;
    }
}
