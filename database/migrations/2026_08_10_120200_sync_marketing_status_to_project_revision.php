<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('cogm_submissions')->whereIn('marketing_status',['die_go','cancel'])->get() as $submission) {
            if ($submission->marketing_status === 'die_go') {
                DB::table('document_revisions')->where('id',$submission->document_revision_id)->update([
                    'a05'=>'ada','a05_received_date'=>$submission->marketing_status_at ?: now(),
                    'a04'=>'belum_ada','a04_received_date'=>null,'a04_reason'=>null,
                ]);
            } else {
                DB::table('document_revisions')->where('id',$submission->document_revision_id)->update([
                    'a04'=>'ada','a04_received_date'=>$submission->marketing_status_at ?: now(),
                    'a04_reason'=>$submission->marketing_status_reason,'a05'=>'belum_ada','a05_received_date'=>null,
                ]);
            }
        }
    }
    public function down(): void {}
};
