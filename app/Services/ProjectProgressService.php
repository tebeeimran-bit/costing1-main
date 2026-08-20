<?php

namespace App\Services;

use App\Models\DocumentRevision;
use App\Models\ProjectWorkflowTask;

class ProjectProgressService
{
    public function compact(?DocumentRevision $revision): array
    {
        if (!$revision) {
            return ['current' => 1, 'label' => 'A00', 'steps' => $this->emptySteps()];
        }

        $revision->loadMissing(['workflowTasks', 'latestSubmission', 'latestApproval', 'unpricedParts']);
        $tasks = $revision->workflowTasks->keyBy('stage');
        $taskDone = fn (string $stage): bool => $tasks->get($stage)?->status === ProjectWorkflowTask::STATUS_COMPLETED;
        $advancedCostingStatuses = [
            DocumentRevision::STATUS_SUDAH_COSTING,
            DocumentRevision::STATUS_PENDING_PRICING,
            DocumentRevision::STATUS_COGM_GENERATED,
            DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
        ];
        $submittedStatuses = [DocumentRevision::STATUS_APPROVED_BY_COORDINATOR, DocumentRevision::STATUS_SUBMITTED_TO_MARKETING];
        $hasCosting = in_array($revision->status, $advancedCostingStatuses, true);
        $openNewParts = $revision->unpricedParts->whereNull('resolved_at')->count();
        $hasNewPartActivity = $revision->unpricedParts->isNotEmpty() || $hasCosting;

        $steps = collect([
            ['key'=>'a00','label'=>'A00','done'=>$revision->a00 === 'ada' || filled($revision->a00_received_date)],
            ['key'=>'drawing','label'=>'Drawing','done'=>$taskDone(ProjectWorkflowTask::STAGE_DRAWING),'status'=>data_get($tasks->get(ProjectWorkflowTask::STAGE_DRAWING)?->metadata, 'drawing_unavailable') ? 'Tidak ada drawing — '.(data_get($tasks->get(ProjectWorkflowTask::STAGE_DRAWING)?->metadata, 'drawing_skip_reason') ?: 'Tanpa keterangan') : null],
            ['key'=>'breakdown','label'=>'Breakdown','done'=>$taskDone(ProjectWorkflowTask::STAGE_BREAKDOWN)],
            ['key'=>'costing','label'=>'Costing','done'=>$hasCosting],
            ['key'=>'new_part','label'=>'New Part Request','done'=>$hasNewPartActivity && $openNewParts === 0],
            ['key'=>'submit','label'=>'Submit','done'=>in_array($revision->status, $submittedStatuses, true)],
            ['key'=>'cogm','label'=>'COGM','done'=>(bool) $revision->latestSubmission],
        ])->values();

        $firstIncomplete = $steps->search(fn (array $step) => !$step['done']);
        $currentIndex = $firstIncomplete === false ? 6 : (int) $firstIncomplete;
        $breakdownSource = data_get($tasks->get(ProjectWorkflowTask::STAGE_BREAKDOWN)?->metadata, 'source');
        if (in_array($breakdownSource, ['manual_breakdown', 'a00_group_direct', 'a00_direct'], true)
            && !$taskDone(ProjectWorkflowTask::STAGE_BREAKDOWN)) {
            $currentIndex = 2;
        }
        $steps = $steps->map(function (array $step, int $index) use ($currentIndex): array {
            $step['state'] = $step['done'] ? 'done' : ($index === $currentIndex ? 'active' : 'pending');
            return $step;
        })->all();

        return ['current' => $currentIndex + 1, 'label' => $steps[$currentIndex]['label'], 'steps' => $steps];
    }

    private function emptySteps(): array
    {
        return collect(['A00','Drawing','Breakdown','Costing','New Part Request','Submit','COGM'])
            ->map(fn (string $label, int $index) => ['label'=>$label,'state'=>$index === 0 ? 'active' : 'pending'])
            ->all();
    }
}
