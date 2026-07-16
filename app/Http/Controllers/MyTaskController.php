<?php

namespace App\Http\Controllers;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Services\Project\ProjectWorkflowService;
use App\Services\Project\ProjectDeadlineService;
use Illuminate\Http\Request;

class MyTaskController extends Controller
{
    public function index(Request $request, ProjectWorkflowService $workflowService, ProjectDeadlineService $deadlineService)
    {
        $role = (string) ($request->user()?->role ?? 'viewer');
        $category = trim((string) $request->query('category', 'all'));

        $revisions = DocumentRevision::query()
            ->with(['project.product', 'unpricedParts:id,document_revision_id,resolved_at', 'taskSetting'])
            ->latest('updated_at')
            ->get();

        $costingByRevision = CostingData::query()
            ->with('customer')
            ->whereNotNull('tracking_revision_id')
            ->get()
            ->keyBy('tracking_revision_id');

        $tasks = $revisions->map(function (DocumentRevision $revision) use ($costingByRevision, $workflowService, $deadlineService, $role) {
            $costing = $costingByRevision->get($revision->id);
            $workflow = $workflowService->build($revision, $costing, $role);
            $deadline = $deadlineService->resolve($revision, $workflow);
            $step = collect($workflow['steps'])->first(fn ($item) => !$item['complete']);
            $taskCategory = $step['key'] ?? 'marketing';

            if (!$this->isRelevantToRole($role, $revision, $taskCategory, $workflow['next_action']['type'])) {
                return null;
            }

            $project = $revision->project;
            $partNumber = $costing?->assy_no ?: $project?->part_number ?: '-';
            $url = $workflow['next_action']['url'];
            if ($url === null || str_starts_with($url, '#')) {
                $url = route('project', ['search' => $partNumber], false) . ($url ?: '');
            }
            if ($role === 'marketing' && $revision->status === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING) {
                $url = route('marketing.cogm-inbox', absolute: false);
            }

            return (object) [
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
                'updated_at' => $revision->updated_at,
            ];
        })->filter()->values();

        $counts = collect(['documents', 'pricing', 'costing', 'approval', 'marketing'])
            ->mapWithKeys(fn ($key) => [$key => $tasks->where('category', $key)->count()]);

        $filteredTasks = $category === 'all' ? $tasks : $tasks->where('category', $category)->values();

        return view('tasks.index', compact('filteredTasks', 'counts', 'category', 'role'));
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
