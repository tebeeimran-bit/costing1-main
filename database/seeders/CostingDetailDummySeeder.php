<?php

namespace Database\Seeders;

use App\Models\CostingData;
use App\Models\Material;
use App\Models\MaterialBreakdown;
use Illuminate\Database\Seeder;

class CostingDetailDummySeeder extends Seeder
{
    private function resolveDummyUnitPrice($material, string $unitLabel): float
    {
        $existingPrice = (float) ($material->price ?? 0);
        if ($existingPrice > 0) {
            return $existingPrice;
        }

        return match ($unitLabel) {
            'Meter' => rand(80, 2500),
            'Kg' => rand(8000, 35000),
            'Set' => rand(12000, 75000),
            'Unit' => rand(1000, 18000),
            default => rand(300, 12000),
        };
    }

    private function resolveDummyQty(string $unitLabel): int
    {
        return match ($unitLabel) {
            'Meter' => rand(5, 120),
            'Kg' => rand(1, 30),
            'Set' => rand(1, 25),
            'Unit' => rand(1, 50),
            default => rand(1, 80),
        };
    }

    private function resolveExchangeRate(string $currency, $costing): float
    {
        return match (strtoupper($currency)) {
            'USD' => (float) ($costing->exchange_rate_usd ?? 15500),
            'JPY' => (float) ($costing->exchange_rate_jpy ?? 103),
            default => 1.0,
        };
    }

    private function normalizeUnitLabel(?string $rawUnit, ?string $partName = null, ?string $partNo = null, ?string $proCode = null): string
    {
        $value = strtoupper(trim((string) $rawUnit));
        $textCandidates = implode(' ', array_filter([
            trim((string) $partName),
            trim((string) $partNo),
            trim((string) $proCode),
        ]));
        $textCandidates = strtoupper(trim($textCandidates));

        if ($value === '' || is_numeric($value) || $value === '0') {
            $value = $textCandidates;
        }

        if ($value === '') {
            return 'PCE';
        }

        if (preg_match('/\b(WIRE|CABLE|CORD|HARNESS|TUBE|HOSE|HOSES|METER|MTR|MM)\b/', $value) === 1) {
            return 'Meter';
        }

        if (preg_match('/\b(SET|SETS)\b/', $value) === 1) {
            return 'Set';
        }

        if (preg_match('/\b(UNIT|UNITS)\b/', $value) === 1) {
            return 'Unit';
        }

        if (preg_match('/\b(KG|KGS|KILOGRAM|KILOGRAMS)\b/', $value) === 1) {
            return 'Kg';
        }

        return match (true) {
            in_array($value, ['PCS', 'PCE', 'PC', 'PCS.', 'EA'], true) => 'PCE',
            in_array($value, ['MM', 'MTR', 'METER', 'METRE', 'M'], true) => 'Meter',
            in_array($value, ['SET', 'SETS'], true) => 'Set',
            in_array($value, ['UNIT', 'UNITS'], true) => 'Unit',
            in_array($value, ['KG', 'KGS', 'KILOGRAM', 'KILOGRAMS'], true) => 'Kg',
            default => 'PCE',
        };
    }

    public function run(): void
    {
        $materials = Material::query()
            ->whereNotNull('material_description')
            ->where('material_description', '!=', '')
            ->orderBy('id')
            ->get();

        if ($materials->isEmpty()) {
            $this->command?->warn('No materials found. Skipped CostingDetailDummySeeder.');
            return;
        }

        $costingList = CostingData::query()
            ->with(['materialBreakdowns'])
            ->orderBy('id')
            ->get();

        foreach ($costingList as $costing) {
            $existingBreakdowns = $costing->materialBreakdowns;
            $needsMaterialBreakdowns = $existingBreakdowns->isEmpty();
            $cycleTimes = is_array($costing->cycle_times) ? $costing->cycle_times : [];
            $needsCycleTimes = empty($cycleTimes);

            if ($needsMaterialBreakdowns) {
                $selectedMaterials = $materials->count() >= 4
                    ? $materials->shuffle()->take(4)->values()
                    : $materials->shuffle()->values();

                $materialCostTotal = 0;

                foreach ($selectedMaterials as $index => $material) {
                    $unitLabel = $this->normalizeUnitLabel($material->base_uom, $material->material_description, $material->material_code, (string) ($costing->product->code ?? ''));
                    $unitPriceBasis = $this->resolveDummyUnitPrice($material, $unitLabel);
                    $qtyReq = $this->resolveDummyQty($unitLabel);

                    $currency = trim((string) ($material->currency ?? ''));
                    if ($currency === '') {
                        $currency = 'IDR';
                    }

                    $taxPercent = (float) ($material->add_cost_import_tax ?? 0);
                    if ($taxPercent < 0) {
                        $taxPercent = 0;
                    }

                    $unitDivisor = in_array($unitLabel, ['Meter'], true) ? 1000 : 1;
                    $amount2 = ($unitPriceBasis * (1 + ($taxPercent / 100))) / max(1, $unitDivisor);
                    $unitPrice2 = $amount2;
                    $lineCost = $qtyReq * $amount2 * $this->resolveExchangeRate($currency, $costing);
                    $materialCostTotal += $lineCost;

                    MaterialBreakdown::create([
                        'costing_data_id' => $costing->id,
                        'material_id' => $material->id,
                        'row_no' => (string) ($index + 1),
                        'part_no' => (string) ($material->material_code ?? '-'),
                        'id_code' => (string) ($material->material_code ?? '-'),
                        'part_name' => (string) ($material->material_description ?? '-'),
                        'pro_code' => (string) ($costing->product->code ?? '-'),
                        'qty_req' => $qtyReq,
                        'amount1' => round($unitPriceBasis, 4),
                        'unit_price_basis' => round($unitPriceBasis, 4),
                        'unit_price_basis_text' => $this->normalizeUnitLabel($material->base_uom, $material->material_description, $material->material_code, (string) ($costing->product->code ?? '')),
                        'currency' => $currency,
                        'qty_moq' => max($qtyReq, (int) round($qtyReq * 1.2)),
                        'cn_type' => 'N',
                        'import_tax_percent' => round($taxPercent, 2),
                        'amount2' => round($amount2, 4),
                        'currency2' => $currency,
                        'unit_price2' => round($unitPrice2, 4),
                    ]);
                }

                $costing->update([
                    'material_cost' => round($materialCostTotal, 2),
                ]);
            }

            $breakdownsToNormalize = $costing->materialBreakdowns()->with('material')->orderBy('id')->get();
            if ($breakdownsToNormalize->isNotEmpty()) {
                $recalculatedMaterialCost = 0;

                foreach ($breakdownsToNormalize as $index => $breakdown) {
                    $material = $breakdown->material;
                    $unitLabel = $this->normalizeUnitLabel(
                        $material?->base_uom,
                        $breakdown->part_name,
                        $breakdown->part_no,
                        $breakdown->pro_code
                    );

                    $qtyReq = max(1, (int) round((float) ($breakdown->qty_req ?? 0)));
                    $existingPrice = (float) ($breakdown->unit_price_basis ?? $breakdown->amount1 ?? 0);
                    $newAmount1 = $existingPrice > 0 ? $existingPrice : $this->resolveDummyUnitPrice($material, $unitLabel);

                    $currency = strtoupper(trim((string) ($breakdown->currency ?? 'IDR')));
                    if (!in_array($currency, ['IDR', 'USD', 'JPY'], true)) {
                        $currency = 'IDR';
                    }

                    $taxPercent = (float) ($breakdown->import_tax_percent ?? 0);
                    $unitDivisor = in_array($unitLabel, ['Meter'], true) ? 1000 : 1;
                    $newAmount2 = ($newAmount1 * (1 + ($taxPercent / 100))) / max(1, $unitDivisor);
                    $exchangeRate = $this->resolveExchangeRate($currency, $costing);
                    $recalculatedMaterialCost += $qtyReq * $newAmount2 * $exchangeRate;

                    $unitPrice2 = $newAmount2;

                    $breakdown->update([
                        'row_no' => (string) ($breakdown->row_no ?? ($index + 1)),
                        'qty_req' => $qtyReq,
                        'unit_price_basis_text' => $unitLabel,
                        'amount1' => round($newAmount1, 4),
                        'amount2' => round($newAmount2, 4),
                        'unit_price2' => round($unitPrice2, 4),
                    ]);
                }

                $costing->update([
                    'material_cost' => round($recalculatedMaterialCost, 2),
                ]);
            }

            if ($needsCycleTimes) {
                $forecastQty = (float) ($costing->forecast ?? 0);
                $cuttingQty = (int) max(1, round($forecastQty / 100));

                $cycleTimesDummy = [
                    [
                        'process' => 'Cutting, Stripping',
                        'qty' => $cuttingQty,
                        'time_hour' => 0.85,
                        'time_sec' => 3060,
                        'time_sec_per_qty' => $cuttingQty > 0 ? round(3060 / $cuttingQty, 4) : 0,
                        'cost_per_sec' => 12.5,
                        'cost_per_unit' => 1450,
                        'area_of_process' => 'PP - Preparation',
                    ],
                    [
                        'process' => 'Terminal Crimping',
                        'qty' => $cuttingQty,
                        'time_hour' => 0.72,
                        'time_sec' => 2592,
                        'time_sec_per_qty' => $cuttingQty > 0 ? round(2592 / $cuttingQty, 4) : 0,
                        'cost_per_sec' => 13.2,
                        'cost_per_unit' => 1320,
                        'area_of_process' => 'PP - Preparation',
                    ],
                    [
                        'process' => 'Sub Assy',
                        'qty' => $cuttingQty,
                        'time_hour' => 0.66,
                        'time_sec' => 2376,
                        'time_sec_per_qty' => $cuttingQty > 0 ? round(2376 / $cuttingQty, 4) : 0,
                        'cost_per_sec' => 14.1,
                        'cost_per_unit' => 1190,
                        'area_of_process' => 'FA - Final Assy',
                    ],
                    [
                        'process' => 'Final Inspection',
                        'qty' => $cuttingQty,
                        'time_hour' => 0.41,
                        'time_sec' => 1476,
                        'time_sec_per_qty' => $cuttingQty > 0 ? round(1476 / $cuttingQty, 4) : 0,
                        'cost_per_sec' => 11.8,
                        'cost_per_unit' => 910,
                        'area_of_process' => 'FA - Final Assy',
                    ],
                ];

                $costing->update([
                    'cycle_times' => $cycleTimesDummy,
                ]);
            }
        }

        $this->command?->info('CostingDetailDummySeeder finished.');
    }
}
