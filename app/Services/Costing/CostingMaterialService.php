<?php

namespace App\Services\Costing;

use App\Models\CostingData;
use App\Models\Material;
use App\Models\MaterialBreakdown;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CostingMaterialService
{
    public function normalizeManualUnpricedPrices($request): Collection
    {
        $manualUnpricedPrices = collect($this->decodeJsonArrayInput($request->input('manual_unpriced_prices_json')))
            ->mapWithKeys(function ($value, $key) {
                return [strtolower(trim((string) $key)) => $this->toFloatValue($value)];
            });

        if ($manualUnpricedPrices->isEmpty()) {
            $manualUnpricedPrices = collect($request->input('manual_unpriced_prices', []))
                ->mapWithKeys(function ($value, $key) {
                    return [strtolower(trim((string) $key)) => $this->toFloatValue($value)];
                });
        }

        return $manualUnpricedPrices;
    }

    public function syncMaterialBreakdowns(CostingData $costingData, $request, array $options = []): array
    {
        $importFromPartlist = (bool) ($options['import_from_partlist'] ?? false);
        $importedMaterialRows = $options['imported_material_rows'] ?? [];
        $updateSection = (string) ($options['update_section'] ?? '');
        $manualUnpricedPrices = $options['manual_unpriced_prices'] ?? collect();

        $partAggregation = [];
        $hasMaterialPayload = $request->has('materials') || $request->filled('materials_json') || !empty($importedMaterialRows);
        $shouldProcessMaterials = $updateSection === '' || $updateSection === 'material';
        $shouldSyncMaterialBreakdowns = $shouldProcessMaterials
            && ($hasMaterialPayload || $updateSection === 'material');

        if ($shouldSyncMaterialBreakdowns) {
            MaterialBreakdown::where('costing_data_id', $costingData->id)->delete();
        }

        $materialsInput = $importFromPartlist
            ? $importedMaterialRows
            : $request->input('materials', []);

        if ((!is_array($materialsInput) || empty($materialsInput)) && $request->filled('materials_json')) {
            $decodedMaterials = $this->decodeJsonArrayInput($request->input('materials_json'));
            if (!empty($decodedMaterials)) {
                $materialsInput = $decodedMaterials;
            }
        }

        $masterMaterialsCache = null;
        if ($shouldSyncMaterialBreakdowns && is_array($materialsInput) && count($materialsInput) > 0) {
            $lookupCodes = [];
            foreach ($materialsInput as $matData) {
                $p = trim((string) ($matData['part_no'] ?? ''));
                $i = trim((string) ($matData['id_code'] ?? ''));
                if ($p !== '' && $p !== '-') {
                    $lookupCodes[] = Str::lower($p);
                }
                if ($i !== '' && $i !== '-') {
                    $lookupCodes[] = Str::lower($i);
                }
            }
            $lookupCodes = array_values(array_unique($lookupCodes));

            if (!empty($lookupCodes)) {
                $masterMaterialsCache = $this->validMasterMaterialsQuery()
                    ->whereRaw('LOWER(material_code) IN (' . implode(',', array_fill(0, count($lookupCodes), '?')) . ')', $lookupCodes)
                    ->get()
                    ->keyBy(fn ($m) => Str::lower($m->material_code));
            } else {
                $masterMaterialsCache = collect();
            }
        }

        if ($shouldSyncMaterialBreakdowns && is_array($materialsInput)) {
            $pendingBreakdowns = [];
            $placeholderMaterial = null;
            $materialBreakdownColumns = \Illuminate\Support\Facades\Schema::getColumnListing('material_breakdowns');
            $materialBreakdownColumnMap = array_fill_keys($materialBreakdownColumns, true);

            foreach ($materialsInput as $rowIndex => $matData) {
                $rowNo = trim((string) ($matData['row_no'] ?? ''));
                $rowPartNo = trim((string) ($matData['part_no'] ?? ''));
                $rowIdCode = trim((string) ($matData['id_code'] ?? ''));
                $normalizedRowPartNo = ($rowPartNo === '-' ? '' : $rowPartNo);
                $normalizedRowIdCode = ($rowIdCode === '-' ? '' : $rowIdCode);
                $partNumber = $normalizedRowPartNo;

                $masterMaterial = $this->findMasterMaterialFromCache($masterMaterialsCache, $rowPartNo, $rowIdCode);
                $partKey = $partNumber !== ''
                    ? strtolower($partNumber)
                    : ('__row_' . strtolower($rowNo !== '' ? $rowNo : (string) $rowIndex));
                $partNameInput = trim((string) ($matData['part_name'] ?? ''));
                $qtyReqRaw = $this->toFloatValue($matData['qty_req'] ?? 0);
                $qtyReq = max(0, $qtyReqRaw);
                $unitPriceBasisRaw = trim((string) ($matData['unit_price_basis_text'] ?? $matData['unit_price_basis'] ?? ''));
                $unitPriceBasisNumeric = $this->toFloatValue($unitPriceBasisRaw);
                $manualPrice = floatval($manualUnpricedPrices->get($partKey, 0));

                $resolvedUnit = $this->normalizeUnitValue($matData['unit'] ?? ($masterMaterial?->base_uom ?? 'PCS'));

                $resolvedCurrency = strtoupper(trim((string) ($matData['currency'] ?? '')));
                if ($resolvedCurrency === '' && $masterMaterial?->currency) {
                    $resolvedCurrency = strtoupper(trim((string) $masterMaterial->currency));
                }
                if (!in_array($resolvedCurrency, ['IDR', 'USD', 'JPY'], true)) {
                    $resolvedCurrency = 'IDR';
                }

                $resolvedSupplier = trim((string) ($matData['supplier'] ?? ''));
                if ($resolvedSupplier === '' && $masterMaterial?->maker) {
                    $resolvedSupplier = trim((string) $masterMaterial->maker);
                }

                $qtyMoqRaw = trim((string) ($matData['qty_moq'] ?? ''));
                $moq = $this->toFloatValue($matData['qty_moq'] ?? 0);
                if ($qtyMoqRaw === '' && $masterMaterial?->moq !== null) {
                    $moq = floatval($masterMaterial->moq);
                }
                if ($moq > 0) {
                    $maxMoq = max(1000, $qtyReq * 20);
                    $moq = max((float) $qtyReq, min($maxMoq, $moq));
                }

                $cnType = strtoupper(trim((string) ($matData['cn_type'] ?? '')));
                if (!in_array($cnType, ['C', 'N', 'E'], true)) {
                    $cnType = strtoupper(trim((string) ($masterMaterial?->cn ?? 'N')));
                    if (!in_array($cnType, ['C', 'N', 'E'], true)) {
                        $cnType = 'N';
                    }
                }

                $importTaxRaw = trim((string) ($matData['import_tax'] ?? ''));
                $importTax = $this->toFloatValue($matData['import_tax'] ?? 0);
                if ($importTaxRaw === '' && $masterMaterial?->add_cost_import_tax !== null) {
                    $importTax = floatval($masterMaterial->add_cost_import_tax);
                }

                $priceBaseInput = $this->toFloatValue($matData['amount1'] ?? 0);
                $masterPrice = floatval($masterMaterial?->price ?? 0);
                $priceBase = $priceBaseInput;

                $material = $masterMaterial;
                if (!$material) {
                    if (!$placeholderMaterial) {
                        $placeholderMaterial = Material::firstOrCreate(
                            ['material_code' => '__PLACEHOLDER__'],
                            [
                                'material_description' => null,
                                'base_uom' => 'PCS',
                                'currency' => 'IDR',
                                'price' => 0,
                            ]
                        );
                    }
                    $material = $placeholderMaterial;
                }

                if (!$importFromPartlist && $masterMaterial) {
                    $needsSave = false;
                    if ($resolvedUnit !== '' && $resolvedUnit !== '-') {
                        $currentBaseUom = strtoupper(trim((string) ($masterMaterial->base_uom ?? '')));
                        if ($currentBaseUom !== $resolvedUnit) {
                            $masterMaterial->base_uom = $resolvedUnit;
                            $needsSave = true;
                        }
                    }
                    if ($resolvedSupplier !== '' && $resolvedSupplier !== 'EMPTY_SUPPLIER!!!') {
                        $currentMaker = trim((string) ($masterMaterial->maker ?? ''));
                        if ($currentMaker !== $resolvedSupplier) {
                            $masterMaterial->maker = $resolvedSupplier;
                            $needsSave = true;
                        }
                    }
                    if ($needsSave) {
                        $masterMaterial->save();
                    }
                }

                $resolvedPartNameForRecap = $partNameInput;
                $upperPartName = strtoupper(trim($resolvedPartNameForRecap));
                if (in_array($upperPartName, ['WIRE', 'TUBE'], true) || str_contains($upperPartName, 'PENGIKAT WIRE')) {
                    $resolvedPartNameForRecap = '';
                }

                $unit = strtoupper($resolvedUnit);

                /*
                 * Multiply Factor mengikuti rumus Excel:
                 * =IF(QTY_REQ=0;0;IF(OR(CN="C";(QTY_MOQ/(QUANTITY*PRODUCT_LIFE*12*QTY_REQ/IF(UNIT="MM";1000;1)))<1);1;QTY_MOQ/(QUANTITY*PRODUCT_LIFE*12*QTY_REQ/IF(UNIT="MM";1000;1))))
                 */
                $quantity = $this->toFloatValue($request->input('forecast', $request->input('quantity', $request->input('qty', 0))));
                $productLife = $this->toFloatValue($request->input('project_period', $request->input('product_life', 0)));
                $unitDivisor = ($unit === 'MM') ? 1000 : 1;

                if ($qtyReq <= 0) {
                    $multiplyFactor = 0;
                } else {
                    $denominator = $quantity * $productLife * 12 * $qtyReq;
                    $denominator = ($denominator != 0) ? ($denominator / $unitDivisor) : 0;
                    $ratio = ($denominator != 0) ? ($moq / $denominator) : 0;
                    $multiplyFactor = ($cnType === 'C' || $ratio < 1) ? 1 : $ratio;
                }

                $extra = $priceBase * ($importTax / 100);
                $base = $priceBase + $extra;
                $numerator = $multiplyFactor * $base;
                $unitDivisor2 = in_array(strtoupper($unit), ['METER', 'M', 'MTR', 'MM'], true) ? 1000 : 1;
                $amount2 = ($unitDivisor2 != 0) ? ($numerator / $unitDivisor2) : 0;

                $pendingData = [
                    'costing_data_id' => $costingData->id,
                    'material_id' => $material->id,
                    'row_no' => $rowNo !== '' ? $rowNo : null,
                    'part_no' => $normalizedRowPartNo !== '' ? $normalizedRowPartNo : null,
                    'id_code' => $normalizedRowIdCode !== '' ? $normalizedRowIdCode : null,
                    'part_name' => $partNameInput !== '' ? $partNameInput : null,
                    'pro_code' => trim((string) ($matData['pro_code'] ?? '')),
                    'unit' => $resolvedUnit,
                    'supplier' => $resolvedSupplier,
                    'qty_req' => $qtyReq,
                    'amount1' => $priceBase,
                    'unit_price_basis' => $unitPriceBasisNumeric,
                    'unit_price_basis_text' => $unitPriceBasisRaw !== '' ? $unitPriceBasisRaw : null,
                    'currency' => $resolvedCurrency,
                    'qty_moq' => $moq,
                    'cn_type' => $cnType,
                    'import_tax_percent' => $importTax,
                    'multiply_factor' => $multiplyFactor,
                    'amount2' => $amount2,
                    'currency2' => $resolvedCurrency,
                    'unit_price2' => $amount2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $pendingBreakdowns[] = array_intersect_key($pendingData, $materialBreakdownColumnMap);

                $rowAmount1 = $priceBase;
                $rowBasisPrice = $unitPriceBasisNumeric;
                $detectedPrice = $importFromPartlist ? 0 : $masterPrice;
                $isUnpriced = ($rowAmount1 <= 0) && ($rowBasisPrice <= 0) && ($manualPrice <= 0);

                if ($partNumber === '' || $partNumber === '-') {
                    continue;
                }

                if (!isset($partAggregation[$partKey])) {
                    $partAggregation[$partKey] = [
                        'part_number' => $partNumber,
                        'part_name' => $resolvedPartNameForRecap,
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

            if (!empty($pendingBreakdowns)) {
                $materialBreakdownColumns = \Illuminate\Support\Facades\Schema::getColumnListing('material_breakdowns');
                $materialBreakdownColumnMap = array_fill_keys($materialBreakdownColumns, true);

                foreach (array_chunk($pendingBreakdowns, 100) as $chunk) {
                    $chunk = array_map(function (array $row) use ($materialBreakdownColumnMap) {
                        if (!isset($materialBreakdownColumnMap['multiply_factor'])) {
                            unset($row['multiply_factor']);
                        }

                        return array_intersect_key($row, $materialBreakdownColumnMap);
                    }, $chunk);

                    MaterialBreakdown::insert($chunk);
                }
            }
        }

        return [
            'part_aggregation' => $partAggregation,
            'should_process_materials' => $shouldProcessMaterials,
            'should_sync_material_breakdowns' => $shouldSyncMaterialBreakdowns,
        ];
    }

    public function calculateMaterialCostFromBreakdowns(int $costingDataId, float $usdRate, float $jpyRate): float
    {
        $breakdowns = MaterialBreakdown::where('costing_data_id', $costingDataId)->get();

        return (float) $breakdowns->sum(function ($row) use ($usdRate, $jpyRate) {
            $amount2 = (float) ($row->amount2 ?? 0);
            $qtyReq = (float) ($row->qty_req ?? 0);
            $currency = strtoupper(trim((string) ($row->currency2 ?? $row->currency ?? 'IDR')));

            $value = $amount2 * $qtyReq;
            if ($currency === 'USD') {
                $value *= $usdRate > 0 ? $usdRate : 1;
            } elseif ($currency === 'JPY') {
                $value *= $jpyRate > 0 ? $jpyRate : 1;
            }

            return $value;
        });
    }

    private function toFloatValue($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }

        $normalized = preg_replace('/[^0-9,\.\-]/', '', $raw);
        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return 0.0;
        }

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            $lastCommaPos = strrpos($normalized, ',');
            $lastDotPos = strrpos($normalized, '.');

            if ($lastCommaPos !== false && $lastDotPos !== false && $lastCommaPos > $lastDotPos) {
                // Format Indonesia: 1.138,15
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                // Format international: 1,138.15
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($hasComma && !$hasDot) {
            // Koma sebagai desimal: 1138,15
            $normalized = str_replace(',', '.', $normalized);
        } elseif ($hasDot && !$hasComma) {
            $dotCount = substr_count($normalized, '.');
            $lastDotPos = strrpos($normalized, '.');
            $digitsAfterLastDot = $lastDotPos === false ? 0 : strlen($normalized) - $lastDotPos - 1;

            if ($dotCount > 1 || $digitsAfterLastDot === 3) {
                $normalized = str_replace('.', '', $normalized);
            }
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function decodeJsonArrayInput($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeUnitValue($value): string
    {
        $unit = strtoupper(trim((string) $value));

        return match ($unit) {
            'M', 'MTR', 'METER' => 'METER',
            'MM' => 'MM',
            'PCS', 'PC', 'EA' => 'PCS',
            'KG', 'KGS' => 'KG',
            default => $unit,
        };
    }

    private function validMasterMaterialsQuery()
    {
        return Material::query()
            ->where(function ($q) {
                $q->whereNull('deleted')
                    ->orWhere('deleted', '!=', 1);
            })
            ->where(function ($q) {
                $q->whereNull('block')
                    ->orWhere('block', '!=', 1);
            });
    }

    private function findMasterMaterialFromCache($cache, ?string $partNo, ?string $idCode): ?Material
    {
        $partNoKey = Str::lower(trim((string) $partNo));
        $idCodeKey = Str::lower(trim((string) $idCode));

        if ($cache instanceof Collection) {
            if ($partNoKey !== '' && $partNoKey !== '-' && $cache->has($partNoKey)) {
                return $cache->get($partNoKey);
            }

            if ($idCodeKey !== '' && $idCodeKey !== '-' && $cache->has($idCodeKey)) {
                return $cache->get($idCodeKey);
            }
        }

        return null;
    }
}
