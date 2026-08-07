<?php

namespace App\Services\Costing;

use App\Models\CostingData;
use App\Models\CostingGroup;
use App\Models\CostingGroupEvent;
use App\Models\CostingGroupItem;
use App\Models\DocumentRevision;
use App\Models\ProjectA00Form;
use Illuminate\Support\Facades\DB;

class CostingGroupService
{
    public function syncFromA00(ProjectA00Form $form, ?int $actorId = null): CostingGroup
    {
        return DB::transaction(function () use ($form, $actorId) {
            $form->loadMissing(['items.projectRevision', 'items.project']);
            $firstRevision = $form->items->first()?->projectRevision;
            $group = CostingGroup::firstOrCreate(
                ['project_a00_form_id' => $form->id],
                [
                    'mode' => $form->items->count() > 1 ? CostingGroup::MODE_BULKY : CostingGroup::MODE_NORMAL,
                    'status' => CostingGroup::STATUS_DRAFT,
                    'pic_engineering' => $firstRevision?->pic_engineering,
                    'pic_marketing' => $firstRevision?->pic_marketing,
                    'created_by_id' => $actorId,
                    'updated_by_id' => $actorId,
                ]
            );

            $group->update([
                'mode' => $group->mode === CostingGroup::MODE_BULKY || $form->items->count() > 1
                    ? CostingGroup::MODE_BULKY : CostingGroup::MODE_NORMAL,
                'pic_engineering' => $firstRevision?->pic_engineering,
                'pic_marketing' => $firstRevision?->pic_marketing,
                'updated_by_id' => $actorId,
            ]);

            foreach ($form->items as $item) {
                $costing = CostingData::where('tracking_revision_id', $item->document_revision_id)->latest('id')->first();
                CostingGroupItem::updateOrCreate(
                    ['project_a00_item_id' => $item->id],
                    [
                        'costing_group_id' => $group->id,
                        'document_project_id' => $item->document_project_id,
                        'active_document_revision_id' => $item->document_revision_id,
                        'costing_data_id' => $costing?->id,
                        'sequence' => $item->line_number,
                        'quantity' => $item->quantity,
                        'quantity_uom' => $item->quantity_uom,
                    ]
                );
            }

            if ($group->wasRecentlyCreated) {
                CostingGroupEvent::create([
                    'costing_group_id' => $group->id,
                    'event_type' => 'group_created',
                    'actor_id' => $actorId,
                    'metadata' => ['mode' => $group->mode, 'item_count' => $form->items->count()],
                ]);
            }

            return $this->refreshStatus($group);
        });
    }

    public function refreshStatus(CostingGroup $group): CostingGroup
    {
        $group->load(['activeItems.revision', 'activeItems.costingData']);
        foreach ($group->activeItems as $item) {
            $item->update(['status' => $this->itemStatus($item->revision, $item->costing_data_id)]);
        }

        $statuses = $group->activeItems->pluck('status');
        $status = match (true) {
            $group->last_submitted_version_id !== null && $statuses->contains(fn ($value) => in_array($value, ['changed','pending','in_progress','waiting_price','rejected'], true)) => CostingGroup::STATUS_UNDER_REVISION,
            $statuses->isNotEmpty() && $statuses->every(fn ($value) => $value === 'submitted') => CostingGroup::STATUS_SUBMITTED,
            $statuses->isNotEmpty() && $statuses->every(fn ($value) => in_array($value, ['approved','submitted'], true)) => CostingGroup::STATUS_APPROVED,
            $statuses->contains('waiting_approval') => CostingGroup::STATUS_WAITING_APPROVAL,
            $statuses->contains(fn ($value) => in_array($value, ['in_progress','waiting_price','rejected'], true)) => CostingGroup::STATUS_IN_PROGRESS,
            default => CostingGroup::STATUS_DRAFT,
        };
        $group->update(['status' => $status]);
        return $group->refresh();
    }

    private function itemStatus(?DocumentRevision $revision, ?int $costingDataId): string
    {
        if (!$revision || !$costingDataId) return 'pending';
        if ($revision->unpricedParts()->whereNull('resolved_at')->exists()) return 'waiting_price';
        return match ($revision->status) {
            DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL => 'waiting_approval',
            DocumentRevision::STATUS_REJECTED_BY_COORDINATOR => 'rejected',
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR => 'approved',
            DocumentRevision::STATUS_SUBMITTED_TO_MARKETING => 'submitted',
            default => 'in_progress',
        };
    }
}
