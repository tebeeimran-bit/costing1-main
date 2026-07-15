<?php

namespace App\Services\Costing;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\MaterialBreakdown;
use App\Models\UnpricedPart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CostingStatusService
{
    public function syncUnpricedPartsFromBreakdowns(int $trackingRevisionId, CostingData $costingData): array
    {
        $partAggregation = $this->buildUnpricedAggregationFromBreakdowns((int) $costingData->id, collect());
        $trackedPartKeys = collect($partAggregation)->keys();
        $openItems = UnpricedPart::where('document_revision_id', $trackingRevisionId)
            ->whereNull('resolved_at')
            ->get()
            ->keyBy(fn ($item) => strtolower($item->part_number));

        foreach ($partAggregation as $partKey => $partInfo) {
            if ($partInfo['is_unpriced']) {
                UnpricedPart::updateOrCreate(
                    [
                        'document_revision_id' => $trackingRevisionId,
                        'part_number' => $partInfo['part_number'],
                        'resolved_at' => null,
                    ],
                    [
                        'costing_data_id' => $costingData->id,
                        'part_name' => $partInfo['part_name'] ?: null,
                        'detected_price' => $partInfo['detected_price'],
                        'manual_price' => null,
                        'notes' => 'Dideteksi via Update Rekapan Part Tanpa Harga.',
                    ]
                );
                continue;
            }

            $existingOpen = $openItems->get($partKey);
            if ($existingOpen) {
                $existingOpen->update([
                    'costing_data_id' => $costingData->id,
                    'manual_price' => null,
                    'resolved_at' => now(),
                    'resolution_source' => 'manual_or_master_price',
                ]);
            }
        }

        foreach ($openItems as $partKey => $openItem) {
            if (!$trackedPartKeys->contains($partKey)) {
                $openItem->update([
                    'costing_data_id' => $costingData->id,
                    'resolved_at' => now(),
                    'resolution_source' => 'part_removed_in_current_processing',
                ]);
            }
        }

        return $partAggregation;
    }

    public function updateTrackingRevisionStatus(?int $trackingRevisionId, string $updateSection): void
    {
        if (!$trackingRevisionId) {
            return;
        }

        if (in_array($updateSection, ['', 'resume_cogm'], true)) {
            DocumentRevision::whereKey($trackingRevisionId)->update([
                'status' => DocumentRevision::STATUS_SUDAH_COSTING,
            ]);

            return;
        }

        $remainingUnpriced = UnpricedPart::where('document_revision_id', $trackingRevisionId)
            ->whereNull('resolved_at')
            ->count();

        $statusPayload = $remainingUnpriced > 0
            ? ['status' => DocumentRevision::STATUS_PENDING_PRICING]
            : ['status' => DocumentRevision::STATUS_COGM_GENERATED, 'cogm_generated_at' => now()];

        DocumentRevision::whereKey($trackingRevisionId)->update($statusPayload);
    }

    public function buildUnpricedAggregationFromBreakdowns(int $costingDataId, Collection $manualUnpricedPrices): array
    {
        $breakdowns = DB::table('material_breakdowns as mb')
            ->leftJoin('materials as m', 'm.id', '=', 'mb.material_id')
            ->where('mb.costing_data_id', $costingDataId)
            ->select([
                'mb.part_no',
                'mb.part_name',
                'mb.amount1',
                'mb.unit_price_basis',
                'm.price as master_price',
            ])
            ->orderBy('mb.id')
            ->get();

        $partAggregation = [];

        foreach ($breakdowns as $breakdown) {
            $partNumber = trim((string) ($breakdown->part_no ?? ''));
            if ($partNumber === '' || $partNumber === '-') {
                continue;
            }

            $partKey = strtolower($partNumber);
            $manualPrice = (float) $manualUnpricedPrices->get($partKey, 0);
            $detectedPrice = (float) ($breakdown->master_price ?? 0);
            $amount1 = (float) ($breakdown->amount1 ?? 0);
            $basisPrice = (float) ($breakdown->unit_price_basis ?? 0);
            $isUnpriced = ($amount1 <= 0) && ($basisPrice <= 0) && ($manualPrice <= 0);

            if (!isset($partAggregation[$partKey])) {
                $partAggregation[$partKey] = [
                    'part_number' => $partNumber,
                    'part_name' => trim((string) ($breakdown->part_name ?? '')),
                    'detected_price' => $detectedPrice,
                    'manual_price' => $manualPrice > 0 ? $manualPrice : null,
                    'is_unpriced' => false,
                ];
            }

            $partAggregation[$partKey]['is_unpriced'] = $partAggregation[$partKey]['is_unpriced'] || $isUnpriced;
            if ($manualPrice > 0) {
                $partAggregation[$partKey]['manual_price'] = $manualPrice;
            }
        }

        return $partAggregation;
    }
}
