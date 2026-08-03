<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('document_revisions')->where(function ($query) {
            $query->where('a00', 'ada')->orWhere('status', 'a00_issued_waiting_decision');
        })->orderBy('id')->eachById(function ($revision) {
            DB::table('project_workflow_tasks')->insertOrIgnore([
                'document_project_id'=>$revision->document_project_id,'document_revision_id'=>$revision->id,
                'stage'=>'drawing','assigned_role'=>'document_control','status'=>'pending',
                'available_at'=>$revision->created_at ?? now(),
                'notes'=>'A00 telah diterbitkan. Menunggu registrasi dan distribusi drawing.',
                'metadata'=>json_encode(['source'=>'a00_backfill']),'created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('project_workflow_tasks')->where('stage','drawing')->where('metadata',json_encode(['source'=>'a00_backfill']))->delete();
    }
};
