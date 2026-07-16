<?php

namespace App\Services\Operations;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\SlaSnapshot;
use App\Services\Project\ProjectDeadlineService;
use App\Services\Project\ProjectWorkflowService;

class SlaSnapshotService
{
    public function __construct(
        private readonly ProjectWorkflowService $workflowService,
        private readonly ProjectDeadlineService $deadlineService
    ) {}

    public function capture(string $role = 'admin'): int
    {
        $revisions = DocumentRevision::latestPerProject()
            ->with(['project.product', 'unpricedParts:id,document_revision_id,resolved_at', 'taskSetting'])
            ->get();
        $costing = CostingData::whereIn('tracking_revision_id', $revisions->pluck('id'))
            ->latest('id')->get()->unique('tracking_revision_id')->keyBy('tracking_revision_id');
        $count = 0;

        foreach ($revisions as $revision) {
            $workflow = $this->workflowService->build($revision, $costing->get($revision->id), $role);
            if ($workflow['is_complete']) {
                continue;
            }
            $deadline = $this->deadlineService->resolve($revision, $workflow);
            $stage = $deadline['category'];
            SlaSnapshot::updateOrCreate(
                ['snapshot_date' => today()->toDateString(), 'document_revision_id' => $revision->id],
                [
                    'stage' => $stage,
                    'pic' => $stage === 'marketing' ? $revision->pic_marketing : $revision->pic_engineering,
                    'due_at' => $deadline['due_at'],
                    'is_overdue' => $deadline['is_overdue'],
                    'aging_days' => $deadline['aging_days'],
                    'compliance' => $deadline['is_overdue'] ? 0 : 100,
                ]
            );
            $count++;
        }

        return $count;
    }
}
