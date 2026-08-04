<?php

namespace App\Http\Controllers;

use App\Models\ProjectWorkflowTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BreakdownInboxController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $tasks = ProjectWorkflowTask::with(['project.product', 'revision', 'assignedUser'])
            ->where('stage', ProjectWorkflowTask::STAGE_BREAKDOWN)
            ->where('assigned_role', 'admin_costing')
            ->whereIn('status', [ProjectWorkflowTask::STATUS_PENDING, ProjectWorkflowTask::STATUS_IN_PROGRESS])
            ->when($search !== '', fn ($query) => $query->whereHas('project', fn ($project) => $project
                ->where('customer', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('part_number', 'like', "%{$search}%")
                ->orWhere('part_name', 'like', "%{$search}%")))
            ->oldest('available_at')->oldest('id')
            ->paginate(20)->withQueryString();

        return view('breakdown.inbox', compact('tasks', 'search'));
    }

    public function complete(Request $request, ProjectWorkflowTask $task)
    {
        abort_unless($task->stage === ProjectWorkflowTask::STAGE_BREAKDOWN && $task->assigned_role === 'admin_costing', 404);
        abort_if($task->status === ProjectWorkflowTask::STATUS_COMPLETED, 422, 'Task Breakdown sudah selesai.');

        $validated = $request->validate([
            'partlist_file' => ['nullable', 'required_without:umh_file', 'file', 'mimes:xls,xlsx,pdf', 'max:20480'],
            'umh_file' => ['nullable', 'required_without:partlist_file', 'file', 'mimes:xls,xlsx,pdf', 'max:20480'],
        ], [
            'partlist_file.required_without' => 'Pilih minimal satu file Partlist atau UMH.',
            'umh_file.required_without' => 'Pilih minimal satu file Partlist atau UMH.',
            'partlist_file.mimes' => 'Partlist harus berupa Excel atau PDF.',
            'umh_file.mimes' => 'UMH harus berupa Excel atau PDF.',
        ]);

        $partlist = $validated['partlist_file'] ?? null;
        $umh = $validated['umh_file'] ?? null;
        $directory = 'workflow/breakdown/'.$task->document_revision_id;
        $partlistPath = $partlist?->store($directory.'/partlist');
        $umhPath = $umh?->store($directory.'/umh');

        DB::transaction(function () use ($request, $task, $partlist, $umh, $partlistPath, $umhPath) {
            $revisionUpdate = [];
            if ($partlist) {
                $revisionUpdate += [
                    'partlist_original_name' => $partlist->getClientOriginalName(),
                    'partlist_file_path' => $partlistPath,
                    'partlist_update_count' => ((int) $task->revision->partlist_update_count) + 1,
                    'partlist_updated_at' => now(),
                ];
            }
            if ($umh) {
                $revisionUpdate += [
                    'umh_original_name' => $umh->getClientOriginalName(),
                    'umh_file_path' => $umhPath,
                    'umh_update_count' => ((int) $task->revision->umh_update_count) + 1,
                    'umh_updated_at' => now(),
                ];
            }
            $task->revision->update($revisionUpdate);
            $task->revision->refresh();
            $isComplete = filled($task->revision->partlist_file_path) && filled($task->revision->umh_file_path);

            $task->update([
                'status' => $isComplete ? ProjectWorkflowTask::STATUS_COMPLETED : ProjectWorkflowTask::STATUS_IN_PROGRESS,
                'assigned_user_id' => $task->assigned_user_id ?: $request->user()->id,
                'started_at' => $task->started_at ?: now(),
                'completed_by_id' => $isComplete ? $request->user()->id : null,
                'completed_at' => $isComplete ? now() : null,
                'metadata' => array_merge($task->metadata ?? [], [
                    'partlist_name' => $task->revision->partlist_original_name,
                    'umh_name' => $task->revision->umh_original_name,
                ]),
            ]);

            if (filled($task->revision->partlist_file_path)) {
                ProjectWorkflowTask::firstOrCreate([
                    'document_revision_id' => $task->document_revision_id,
                    'stage' => ProjectWorkflowTask::STAGE_COSTING,
                ], [
                    'document_project_id' => $task->document_project_id,
                    'assigned_role' => 'admin_costing',
                    'status' => ProjectWorkflowTask::STATUS_PENDING,
                    'available_at' => now(),
                    'metadata' => ['source' => 'breakdown_upload', 'breakdown_task_id' => $task->id],
                ]);
            }
        });

        $task->revision->refresh();
        if (filled($task->revision->partlist_file_path) && filled($task->revision->umh_file_path)) {
            return redirect()->route('breakdown.inbox')->with('success', 'Partlist dan UMH sudah lengkap. Breakdown selesai dan task Costing telah dibuat.');
        }

        $waitingFor = filled($task->revision->partlist_file_path) ? 'UMH' : 'Partlist';
        $message = filled($task->revision->partlist_file_path)
            ? 'Partlist berhasil disimpan. Form Costing sudah dapat diproses sementara Breakdown menunggu UMH.'
            : 'Dokumen berhasil disimpan. Breakdown tetap terbuka dan menunggu '.$waitingFor.'.';
        return redirect()->route('breakdown.inbox')->with('success', $message);
    }

    public function startCosting(Request $request, ProjectWorkflowTask $task)
    {
        abort_unless($task->stage === ProjectWorkflowTask::STAGE_BREAKDOWN && $task->assigned_role === 'admin_costing', 404);
        abort_unless(filled($task->revision?->partlist_file_path), 422, 'Partlist harus tersedia sebelum Costing dimulai.');

        $costingTask = ProjectWorkflowTask::firstOrCreate([
            'document_revision_id' => $task->document_revision_id,
            'stage' => ProjectWorkflowTask::STAGE_COSTING,
        ], [
            'document_project_id' => $task->document_project_id,
            'assigned_role' => 'admin_costing',
            'status' => ProjectWorkflowTask::STATUS_PENDING,
            'available_at' => now(),
            'metadata' => ['source' => 'partlist_available', 'breakdown_task_id' => $task->id],
        ]);

        if ($costingTask->status === ProjectWorkflowTask::STATUS_PENDING) {
            $costingTask->update([
                'status' => ProjectWorkflowTask::STATUS_IN_PROGRESS,
                'assigned_user_id' => $request->user()->id,
                'started_at' => now(),
            ]);
        }

        return redirect()->route('form', ['tracking_revision_id' => $task->document_revision_id]);
    }
}
