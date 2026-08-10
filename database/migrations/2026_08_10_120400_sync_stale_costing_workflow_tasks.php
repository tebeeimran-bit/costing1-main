<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $tasks = DB::table('project_workflow_tasks as task')
            ->join('document_revisions as revision', 'revision.id', '=', 'task.document_revision_id')
            ->join('costing_data as costing', 'costing.tracking_revision_id', '=', 'revision.id')
            ->where('task.stage', 'costing')
            ->whereIn('task.status', ['pending', 'in_progress'])
            ->select('task.id', 'revision.status as revision_status', 'revision.updated_at', 'costing.created_at as costing_created_at')
            ->get();

        foreach ($tasks as $task) {
            $completed = $task->revision_status === 'submitted_to_marketing';
            DB::table('project_workflow_tasks')->where('id', $task->id)->update([
                'status' => $completed ? 'completed' : 'in_progress',
                'started_at' => $task->costing_created_at,
                'completed_at' => $completed ? $task->updated_at : null,
                'notes' => $completed
                    ? 'Disinkronkan: Costing telah selesai dan dikirim ke Marketing.'
                    : 'Disinkronkan: Form Costing sudah dibuat dan sedang diproses.',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {}
};
