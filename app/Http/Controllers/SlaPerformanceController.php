<?php

namespace App\Http\Controllers;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Services\Project\ProjectDeadlineService;
use App\Services\Project\ProjectWorkflowService;
use Illuminate\Http\Request;

class SlaPerformanceController extends Controller
{
    public function __invoke(
        Request $request,
        ProjectWorkflowService $workflowService,
        ProjectDeadlineService $deadlineService
    ) {
        $role = (string) ($request->user()?->role ?? 'viewer');
        $stageFilter = trim((string) $request->query('stage', 'all'));
        $statusFilter = trim((string) $request->query('status', 'all'));

        $revisions = DocumentRevision::query()
            ->with(['project.product', 'unpricedParts:id,document_revision_id,resolved_at', 'taskSetting'])
            ->latest('updated_at')
            ->get();

        $costingByRevision = CostingData::query()
            ->with('customer')
            ->whereNotNull('tracking_revision_id')
            ->get()
            ->keyBy('tracking_revision_id');

        $rows = $revisions->map(function (DocumentRevision $revision) use ($costingByRevision, $workflowService, $deadlineService, $role) {
            $costing = $costingByRevision->get($revision->id);
            $workflow = $workflowService->build($revision, $costing, $role);
            $deadline = $deadlineService->resolve($revision, $workflow);
            $stage = $deadline['category'];
            $project = $revision->project;

            return (object) [
                'revision' => $revision,
                'stage' => $stage,
                'stage_label' => $this->stageLabel($stage),
                'is_complete' => $workflow['is_complete'],
                'is_overdue' => $deadline['is_overdue'],
                'due_at' => $deadline['due_at'],
                'days_remaining' => $deadline['days_remaining'],
                'aging_days' => $deadline['aging_days'],
                'progress' => $workflow['progress'],
                'pic' => $this->resolvePic($revision, $stage),
                'part_number' => $costing?->assy_no ?: $project?->part_number ?: '-',
                'project_name' => $project?->part_name ?: $costing?->assy_name ?: 'Project Costing',
                'customer' => $costing?->customer?->name ?: $project?->customer ?: '-',
                'model' => $costing?->model ?: $project?->model ?: '-',
            ];
        });

        $activeRows = $rows->where('is_complete', false)->values();
        $activeCount = $activeRows->count();
        $overdueCount = $activeRows->where('is_overdue', true)->count();
        $onTimeCount = $activeCount - $overdueCount;

        $kpis = [
            'active' => $activeCount,
            'overdue' => $overdueCount,
            'on_time' => $onTimeCount,
            'compliance' => $activeCount > 0 ? (int) round(($onTimeCount / $activeCount) * 100) : 100,
            'average_aging' => $activeCount > 0 ? round((float) $activeRows->avg('aging_days'), 1) : 0,
        ];

        $stageOrder = ['documents', 'pricing', 'costing', 'approval', 'marketing'];
        $stageSummary = collect($stageOrder)->map(function (string $stage) use ($activeRows) {
            $items = $activeRows->where('stage', $stage);
            $total = $items->count();
            $overdue = $items->where('is_overdue', true)->count();

            return (object) [
                'key' => $stage,
                'label' => $this->stageLabel($stage),
                'total' => $total,
                'overdue' => $overdue,
                'compliance' => $total > 0 ? (int) round((($total - $overdue) / $total) * 100) : 100,
                'average_aging' => $total > 0 ? round((float) $items->avg('aging_days'), 1) : 0,
            ];
        });

        $picSummary = $activeRows->groupBy('pic')->map(function ($items, string $pic) {
            $total = $items->count();
            $overdue = $items->where('is_overdue', true)->count();

            return (object) [
                'pic' => $pic,
                'total' => $total,
                'overdue' => $overdue,
                'compliance' => $total > 0 ? (int) round((($total - $overdue) / $total) * 100) : 100,
                'average_aging' => round((float) $items->avg('aging_days'), 1),
            ];
        })->sort(fn ($a, $b) => ($b->overdue <=> $a->overdue)
            ?: ($b->average_aging <=> $a->average_aging))->values();

        $filteredRows = $activeRows
            ->when($stageFilter !== 'all', fn ($items) => $items->where('stage', $stageFilter))
            ->when($statusFilter === 'overdue', fn ($items) => $items->where('is_overdue', true))
            ->when($statusFilter === 'on_time', fn ($items) => $items->where('is_overdue', false))
            ->sort(fn ($a, $b) => ($b->is_overdue <=> $a->is_overdue)
                ?: ($a->days_remaining <=> $b->days_remaining))->values();

        return view('reports.sla-performance', compact(
            'kpis',
            'stageSummary',
            'picSummary',
            'filteredRows',
            'stageFilter',
            'statusFilter'
        ));
    }

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            'documents' => 'Dokumen',
            'pricing' => 'Harga Part',
            'costing' => 'Costing',
            'approval' => 'Approval',
            'marketing' => 'Marketing',
            default => ucfirst($stage),
        };
    }

    private function resolvePic(DocumentRevision $revision, string $stage): string
    {
        $pic = $stage === 'marketing' ? $revision->pic_marketing : $revision->pic_engineering;

        return trim((string) $pic) ?: 'Belum ditentukan';
    }
}
