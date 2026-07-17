<?php

namespace App\Http\Controllers;

use App\Models\CostingData;
use App\Models\DocumentProject;
use App\Models\Customer;
use App\Models\DocumentRevision;
use App\Models\ProjectManualTask;
use App\Models\User;
use App\Services\Project\ProjectCompletenessService;
use App\Services\Project\ProjectDeadlineService;
use App\Services\Project\ProjectWorkflowService;
use Illuminate\Http\Request;

class MyTaskController extends Controller
{
    public function index(Request $request, ProjectWorkflowService $workflowService, ProjectDeadlineService $deadlineService, ProjectCompletenessService $completenessService)
    {
        $role = (string) ($request->user()?->role ?? 'viewer');
        $customerLogos = Customer::query()->whereNotNull('logo_path')->get()->keyBy(fn ($customer) => mb_strtolower(trim($customer->name)));
        $category = trim((string) $request->query('category', 'all'));
        $search = trim((string) $request->query('q', ''));

        $revisions = DocumentRevision::query()
            ->latestPerProject()
            ->with(['project.product', 'unpricedParts:id,document_revision_id,resolved_at', 'taskSetting'])
            ->latest('updated_at')
            ->get();

        $costingByRevision = CostingData::query()->withCount('materialBreakdowns')
            ->with('customer')
            ->whereNotNull('tracking_revision_id')
            ->latest('id')
            ->get()
            ->unique('tracking_revision_id')
            ->keyBy('tracking_revision_id');

        $tasks = $revisions->map(function (DocumentRevision $revision) use ($costingByRevision, $workflowService, $deadlineService, $completenessService, $role, $customerLogos) {
            $costing = $costingByRevision->get($revision->id);
            $workflow = $workflowService->build($revision, $costing, $role);
            $deadline = $deadlineService->resolve($revision, $workflow);
            $completeness = $completenessService->build($revision, $costing);
            $step = collect($workflow['steps'])->first(fn ($item) => ! $item['complete']);
            $taskCategory = $step['key'] ?? 'marketing';

            if (! $this->isRelevantToRole($role, $revision, $taskCategory, $workflow['next_action']['type'])) {
                return null;
            }

            $project = $revision->project;
            $partNumber = $costing?->assy_no ?: $project?->part_number ?: '-';
            $url = $workflow['next_action']['url'];
            if ($url === null || str_starts_with($url, '#')) {
                $url = route('project', ['search' => $partNumber], false).($url ?: '');
            }
            if ($role === 'marketing' && $revision->status === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING) {
                $url = route('marketing.cogm-inbox', absolute: false);
            }

            return (object) [
                'id' => 'automatic-'.$revision->id,
                'is_manual' => false,
                'project_id' => $project?->id,
                'customer_logo' => $customerLogos->get(mb_strtolower(trim($costing?->customer?->name ?: $project?->customer)))?->logo_path,
                'revision_id' => $revision->id,
                'category' => $taskCategory,
                'priority' => $deadline['is_overdue'] ? 'high' : $this->priority($revision, $workflow),
                'title' => $role === 'marketing' && $revision->status === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING
                    ? 'Tinjau COGM dari Costing'
                    : $workflow['next_action']['label'],
                'description' => $workflow['next_action']['description'],
                'url' => $url,
                'collaboration_url' => route('project-collaboration.show', $revision, false),
                'project' => $project?->part_name ?: $costing?->assy_name ?: 'Project Costing',
                'part_number' => $partNumber,
                'customer' => $costing?->customer?->name ?: $project?->customer ?: '-',
                'model' => $costing?->model ?: $project?->model ?: '-',
                'revision' => $revision->version_label,
                'status' => $revision->status_label,
                'progress' => $workflow['progress'],
                'deadline' => $deadline,
                'completeness' => $completeness,
                'updated_at' => $revision->updated_at,
            ];
        })->filter()->values();

        $manualTasks = ProjectManualTask::query()
            ->with(['project', 'assignee:id,name'])
            ->where('assignee_id', $request->user()->id)
            ->where('status', '!=', 'completed')
            ->latest('updated_at')
            ->get()
            ->map(function (ProjectManualTask $task) use ($customerLogos) {
                $project = $task->project;
                $dueAt = $task->due_at;
                $isOverdue = $dueAt?->isBefore(today()) ?? false;

                return (object) [
                    'id' => 'manual-'.$task->id,
                    'manual_task_id' => $task->id,
                    'is_manual' => true,
                    'project_id' => $project->id,
                    'customer_logo' => $customerLogos->get(mb_strtolower(trim($project->customer)))?->logo_path,
                    'revision_id' => null,
                    'category' => $task->category,
                    'priority' => $isOverdue ? 'high' : $task->priority,
                    'title' => $task->title,
                    'description' => $task->description ?: 'Task manual untuk project ini.',
                    'url' => route('project', ['search' => $project->part_number], false),
                    'collaboration_url' => null,
                    'project' => $project->part_name,
                    'part_number' => $project->part_number,
                    'customer' => $project->customer,
                    'model' => $project->model,
                    'revision' => 'Manual',
                    'status' => 'Task manual',
                    'progress' => $task->progress,
                    'deadline' => [
                        'due_at' => $dueAt,
                        'is_overdue' => $isOverdue,
                        'is_custom' => true,
                        'label' => $dueAt ? ($isOverdue ? $dueAt->diffForHumans() : 'Tersisa '.$dueAt->diffInDays(today()).' hari') : 'Tanpa deadline',
                        'aging_days' => $task->created_at->diffInDays(now()),
                    ],
                    'completeness' => ['level' => 'normal', 'score' => $task->progress, 'missing' => []],
                    'updated_at' => $task->updated_at,
                ];
            });

        $tasks = $tasks->concat($manualTasks)->sortByDesc(fn ($task) => [
            $task->priority === 'high' ? 2 : ($task->priority === 'medium' ? 1 : 0),
            $task->updated_at?->timestamp ?? 0,
        ])->values();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $tasks = $tasks->filter(fn ($task) => str_contains(mb_strtolower(implode(' ', [$task->title, $task->description, $task->project, $task->part_number, $task->customer, $task->model, $task->category])), $needle))->values();
        }

        $counts = collect(['general', 'documents', 'pricing', 'costing', 'approval', 'marketing'])
            ->mapWithKeys(fn ($key) => [$key => $tasks->where('category', $key)->count()]);

        $filteredTasks = $category === 'all' ? $tasks : $tasks->where('category', $category)->values();
        $groupedTasks = $filteredTasks->groupBy('project_id');
        $projects = DocumentProject::query()->orderBy('customer')->orderBy('part_number')->get();
        $assignees = User::query()->where('role', '!=', 'viewer')->orderBy('name')->get(['id', 'name']);

        return view('tasks.index', compact('filteredTasks', 'groupedTasks', 'projects', 'assignees', 'counts', 'category', 'role', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_project_id' => ['required', 'exists:document_projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'in:general,documents,pricing,costing,approval,marketing'],
            'priority' => ['required', 'in:normal,medium,high'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
        ]);
        $data['assignee_id'] = $data['assignee_id'] ?? $request->user()->id;
        $data['created_by_id'] = $request->user()->id;
        ProjectManualTask::create($data);

        return redirect()->route('my-tasks')->with('success', 'Task manual berhasil ditambahkan ke project.');
    }

    public function update(Request $request, ProjectManualTask $manualTask)
    {
        abort_unless($manualTask->assignee_id === $request->user()->id || $manualTask->created_by_id === $request->user()->id || $request->user()->role === 'admin', 403);
        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'in:open,completed'],
        ]);
        if ($data['status'] === 'completed') {
            $data['progress'] = 100;
        }
        $manualTask->update($data);

        return back()->with('success', 'Task berhasil diperbarui.');
    }

    public function destroy(Request $request, ProjectManualTask $manualTask)
    {
        abort_unless($manualTask->created_by_id === $request->user()->id || $request->user()->role === 'admin', 403);
        $manualTask->delete();

        return back()->with('success', 'Task manual dihapus.');
    }

    private function isRelevantToRole(string $role, DocumentRevision $revision, string $category, string $actionType): bool
    {
        if ($role === 'marketing') {
            return $revision->status === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING;
        }
        if ($role === 'coordinator_costing') {
            return in_array($revision->status, [
                DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
                DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            ], true);
        }
        if (in_array($role, ['admin_costing', 'editor'], true)) {
            return in_array($category, ['documents', 'pricing', 'costing', 'approval'], true)
                && $revision->status !== DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL;
        }
        if ($role === 'viewer') {
            return false;
        }

        return $revision->status !== DocumentRevision::STATUS_SUBMITTED_TO_MARKETING;
    }

    private function priority(DocumentRevision $revision, array $workflow): string
    {
        if ($revision->status === DocumentRevision::STATUS_REJECTED_BY_COORDINATOR || $workflow['open_unpriced_count'] > 0) {
            return 'high';
        }
        if (in_array($revision->status, [
            DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
        ], true)) {
            return 'medium';
        }

        return 'normal';
    }
}
