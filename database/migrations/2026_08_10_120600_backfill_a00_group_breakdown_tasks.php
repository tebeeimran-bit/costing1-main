<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groupIds = DB::table('project_a00_items')
            ->select('project_a00_form_id')
            ->groupBy('project_a00_form_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('project_a00_form_id');

        $items = DB::table('project_a00_items as item')
            ->join('project_a00_forms as form', 'form.id', '=', 'item.project_a00_form_id')
            ->whereIn('item.project_a00_form_id', $groupIds)
            ->select([
                'item.document_project_id',
                'item.document_revision_id',
                'item.project_a00_form_id',
                'form.document_number',
            ])
            ->get();

        foreach ($items as $item) {
            DB::table('project_workflow_tasks')->insertOrIgnore([
                'document_project_id' => $item->document_project_id,
                'document_revision_id' => $item->document_revision_id,
                'stage' => 'breakdown',
                'assigned_role' => 'admin_costing',
                'status' => 'pending',
                'available_at' => now(),
                'notes' => 'A00 Gabung dapat langsung diproses di Breakdown tanpa menunggu distribusi Drawing.',
                'metadata' => json_encode([
                    'source' => 'a00_group_direct',
                    'a00_form_id' => $item->project_a00_form_id,
                    'a00_number' => $item->document_number,
                    'drawing_optional' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('project_workflow_tasks')
            ->where('stage', 'breakdown')
            ->where('metadata', 'like', '%"source":"a00_group_direct"%')
            ->delete();
    }
};
