<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('cogm_submissions')->orderBy('id')->get() as $submission) {
            DB::table('cogm_submission_events')->insert([
                'cogm_submission_id'=>$submission->id,'user_id'=>null,'event_type'=>'submitted','source'=>'costing',
                'title'=>'COGM dikirim ke Marketing','description'=>$submission->notes,'cogm_value'=>$submission->cogm_value,
                'created_at'=>$submission->submitted_at ?: $submission->created_at,'updated_at'=>$submission->submitted_at ?: $submission->created_at,
            ]);
            if ($submission->last_updated_at) DB::table('cogm_submission_events')->insert([
                'cogm_submission_id'=>$submission->id,'user_id'=>null,'event_type'=>'updated','source'=>'costing_or_new_part',
                'title'=>'COGM atau harga diperbarui','description'=>'Update oleh '.($submission->last_updated_by ?: 'sistem').' ('.$submission->update_count.'x).',
                'cogm_value'=>$submission->cogm_value,'created_at'=>$submission->last_updated_at,'updated_at'=>$submission->last_updated_at,
            ]);
        }
    }
    public function down(): void { DB::table('cogm_submission_events')->whereNull('user_id')->whereIn('event_type',['submitted','updated'])->delete(); }
};
