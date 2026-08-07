<?php

namespace App\Services\Costing;

use App\Models\CostingGroup;
use App\Models\CostingGroupEvent;
use App\Models\CostingGroupVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkyCogmSnapshotService
{
    public function create(CostingGroup $group, string $type = 'draft', ?int $actorId = null): CostingGroupVersion
    {
        if (!in_array($type, ['draft', 'final'], true)) {
            throw ValidationException::withMessages(['type' => 'Jenis snapshot harus draft atau final.']);
        }

        return DB::transaction(function () use ($group, $type, $actorId) {
            $locked = CostingGroup::whereKey($group->id)->lockForUpdate()->firstOrFail();
            app(CostingGroupService::class)->refreshStatus($locked);
            $locked->load(['activeItems.a00Item', 'activeItems.project', 'activeItems.revision', 'activeItems.costingData']);

            $rows = $locked->activeItems->map(function ($item) {
                $costing = $item->costingData;
                $unitCogm = $costing ? (float) $costing->total_cost : 0.0;
                $quantity = $item->quantity === null ? null : (float) $item->quantity;
                $unpriced = $item->revision->unpricedParts()->whereNull('resolved_at')->count();
                return compact('item', 'costing', 'unitCogm', 'quantity', 'unpriced');
            });

            $incompletePrice = $rows->contains(fn ($row) => !$row['costing'] || $row['unpriced'] > 0);
            $incompleteQuantity = $rows->contains(fn ($row) => $row['quantity'] === null || $row['quantity'] <= 0);
            $notApproved = $rows->contains(fn ($row) => !in_array($row['item']->status, ['approved','submitted'], true));
            if ($type === 'final' && ($incompletePrice || $incompleteQuantity || $notApproved)) {
                throw ValidationException::withMessages([
                    'final' => 'Final COGM ditolak: seluruh item harus lengkap harga dan quantity serta sudah approved.',
                ]);
            }

            $number = $locked->current_version_number + 1;
            $version = CostingGroupVersion::create([
                'costing_group_id' => $locked->id,
                'version_number' => $number,
                'type' => $type,
                'status' => 'generated',
                'total_unit_cogm' => $rows->sum('unitCogm'),
                'total_extended_cogm' => $incompleteQuantity ? null : $rows->sum(fn ($row) => $row['unitCogm'] * $row['quantity']),
                'has_incomplete_price' => $incompletePrice,
                'has_incomplete_quantity' => $incompleteQuantity,
                'generated_by_id' => $actorId,
                'generated_at' => now(),
            ]);

            foreach ($rows as $row) {
                $item = $row['item']; $costing = $row['costing']; $project = $item->project;
                $version->items()->create([
                    'costing_group_item_id' => $item->id,
                    'document_revision_id' => $item->active_document_revision_id,
                    'costing_data_id' => $costing?->id,
                    'item_revision_number' => max(0, (int) $item->revision->version_number - 1),
                    'assy_number' => $item->a00Item?->assy_number,
                    'assy_name' => $item->a00Item?->assy_name,
                    'project_name' => $project?->part_name,
                    'customer' => $project?->customer,
                    'model' => $project?->model,
                    'pic_engineering' => $item->effectivePicEngineering(),
                    'pic_marketing' => $item->effectivePicMarketing(),
                    'quantity' => $row['quantity'], 'quantity_uom' => $item->quantity_uom,
                    'material_cost' => $costing?->material_cost ?? 0,
                    'labor_cost' => $costing?->labor_cost ?? 0,
                    'overhead_cost' => $costing?->overhead_cost ?? 0,
                    'scrap_cost' => $costing?->scrap_cost ?? 0,
                    'unit_cogm' => $row['unitCogm'],
                    'extended_cogm' => $row['quantity'] === null ? null : $row['unitCogm'] * $row['quantity'],
                    'unpriced_part_count' => $row['unpriced'],
                    'change_type' => $item->added_after_submission ? 'added' : 'unchanged',
                    'change_reason' => $item->change_reason,
                ]);
            }
            $locked->update(['current_version_number' => $number, 'updated_by_id' => $actorId]);
            CostingGroupEvent::create(['costing_group_id'=>$locked->id,'costing_group_version_id'=>$version->id,'event_type'=>$type.'_generated','actor_id'=>$actorId,'metadata'=>['version'=>$number]]);
            return $version->load('items');
        });
    }
}
