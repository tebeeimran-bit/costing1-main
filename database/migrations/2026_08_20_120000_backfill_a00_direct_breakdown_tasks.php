<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $items = DB::table('project_a00_items as item')
            ->join('project_a00_forms as form', 'form.id', '=', 'item.project_a00_form_id')
            ->leftJoin('project_workflow_tasks as breakdown', function ($join) {
                $join->on('breakdown.document_revision_id', '=', 'item.document_revision_id')
                    ->where('breakdown.stage', '=', 'breakdown');
            })
            ->whereNull('breakdown.id')
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
                'notes' => 'Project dari A00 dapat langsung diproses di Breakdown tanpa menunggu Drawing.',
                'metadata' => json_encode([
                    'source' => 'a00_direct',
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
            ->where('metadata', 'like', '%"source":"a00_direct"%')
            ->delete();
    }
};
