<?php

namespace App\Services\Costing;

use App\Models\CostingData;
use App\Models\Tube;
use App\Models\TubeBreakdown;
use Illuminate\Support\Collection;

class TubeCostingService
{
    public function calculateAmount(float $usageQty, string $usageUnit, float $price, string $priceUnit): float
    {
        $normalizedUsageQty = $this->normalizeUsageToPriceUnit($usageQty, $usageUnit, $priceUnit);

        return round($normalizedUsageQty * $price, 4);
    }

    public function normalizeUsageToPriceUnit(float $usageQty, string $usageUnit, string $priceUnit): float
    {
        if ($usageUnit === $priceUnit) {
            return $usageQty;
        }

        if ($usageUnit === 'mm' && $priceUnit === 'meter') {
            return $usageQty / 1000;
        }

        if ($usageUnit === 'meter' && $priceUnit === 'mm') {
            return $usageQty * 1000;
        }

        // pcs, set, unit tidak dikonversi otomatis karena business meaning-nya bisa beda.
        return $usageQty;
    }

    public function syncBreakdowns(CostingData $costingData, array $rows): Collection
    {
        $idsToKeep = collect();

        foreach ($rows as $row) {
            $tubeCode = trim((string) ($row['tube_code'] ?? ''));
            if ($tubeCode === '') {
                continue;
            }

            $tube = Tube::where('tube_code', $tubeCode)->first();

            $usageQty = (float) ($row['usage_qty'] ?? 0);
            $usageUnit = $row['usage_unit'] ?? ($tube?->unit ?? 'pcs');
            $price = (float) ($row['price'] ?? ($tube?->price ?? 0));
            $priceUnit = $row['price_unit'] ?? ($tube?->price_unit ?? 'pcs');

            $amount = $this->calculateAmount($usageQty, $usageUnit, $price, $priceUnit);

            $breakdown = TubeBreakdown::updateOrCreate(
                [
                    'costing_data_id' => $costingData->id,
                    'tube_code' => $tubeCode,
                ],
                [
                    'tube_id' => $tube?->id,
                    'tube_name' => $row['tube_name'] ?? $tube?->tube_name,
                    'spec' => $row['spec'] ?? $tube?->spec,
                    'usage_qty' => $usageQty,
                    'usage_unit' => $usageUnit,
                    'price' => $price,
                    'price_unit' => $priceUnit,
                    'amount' => $amount,
                    'is_estimate' => (bool) ($row['is_estimate'] ?? $tube?->is_estimate ?? false),
                    'notes' => $row['notes'] ?? null,
                ]
            );

            $idsToKeep->push($breakdown->id);
        }

        TubeBreakdown::where('costing_data_id', $costingData->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();

        return TubeBreakdown::where('costing_data_id', $costingData->id)->get();
    }

    public function calculateTotal(CostingData $costingData): float
    {
        return (float) TubeBreakdown::where('costing_data_id', $costingData->id)->sum('amount');
    }

    public function getFullPriceIssues(CostingData $costingData): array
    {
        $rows = TubeBreakdown::where('costing_data_id', $costingData->id)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $issues = [];

        $missingPrice = $rows->filter(fn ($row) => (float) $row->price <= 0)->count();
        if ($missingPrice > 0) {
            $issues[] = $missingPrice . ' tube belum ada harga';
        }

        $estimate = $rows->filter(fn ($row) => (bool) $row->is_estimate)->count();
        if ($estimate > 0) {
            $issues[] = $estimate . ' tube masih estimate';
        }

        $missingUsage = $rows->filter(fn ($row) => (float) $row->usage_qty <= 0)->count();
        if ($missingUsage > 0) {
            $issues[] = $missingUsage . ' tube belum ada usage';
        }

        return $issues;
    }
}
