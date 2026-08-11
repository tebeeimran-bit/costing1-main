<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_a00_forms')->orderBy('id')->each(function ($form) {
            $revision=DB::table('document_revisions')->where('id',$form->document_revision_id)->first();
            DB::table('project_a00_forms')->where('id',$form->id)->update([
                'prepared_by' => filled($form->prepared_by) ? $form->prepared_by : ($revision?->pic_marketing ?: null),
                'acknowledged_by' => filled($form->acknowledged_by) ? $form->acknowledged_by : 'L. Andri H.',
                'approved_by' => filled($form->approved_by) ? $form->approved_by : 'Y. Susanto',
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Nama penandatangan adalah data bisnis; tidak dikosongkan saat rollback.
    }
};
