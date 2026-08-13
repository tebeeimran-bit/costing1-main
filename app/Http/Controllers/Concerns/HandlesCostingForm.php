<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Models\Customer;
use App\Models\CogmSubmission;
use App\Models\Material;
use App\Models\CostingData;
use App\Models\CostingExcelTemplate;
use App\Models\UnpricedPart;
use App\Models\DocumentRevision;
use App\Models\CycleTimeTemplate;
use App\Models\MaterialBreakdown;
use App\Models\Plant;
use App\Models\BusinessCategory;
use App\Models\Wire;
use App\Models\WireRate;
use App\Models\ExchangeRate;
use App\Models\Pic;
use App\Models\ProjectA00Form;
use App\Models\ProjectWorkflowTask;
use App\Http\Requests\StoreCostingRequest;
use App\Http\Requests\UpdateStatusProjectRequest;
use App\Services\Costing\CostingImportService;
use App\Services\Costing\CostingMaterialService;
use App\Services\Costing\CostingPersistenceService;
use App\Services\Costing\CostingResponseService;
use App\Services\Costing\CostingStatusService;
use App\Services\Costing\MissingProjectInformationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

trait 
HandlesCostingForm
{
    public function form(Request $request, CostingImportService $importService)
    {
        $readOnlyMode = $request->boolean('view_only');
        $editSubmittedMode = false;
        $cogmSubmission = null;
        $products = Product::all();
        $businessCategories = BusinessCategory::orderBy('code')->orderBy('name')->get();
        $customers = Customer::all();
        $materials = $this->validMasterMaterialsQuery()
            ->orderBy('material_code')
            ->get();
        $cycleTimeTemplates = CycleTimeTemplate::orderBy('id')->get();
        $plants = Plant::orderBy('code')->orderBy('name')->get();
        $periods = CostingData::distinct('period')->orderBy('period', 'desc')->pluck('period');
        $wireRates = WireRate::orderBy('period_month', 'asc')->orderBy('id', 'asc')->get();
        $exchangeRates = ExchangeRate::orderByDesc('period_date')->orderByDesc('id')->get();
        $picsEngineering = Pic::where('type', 'engineering')->orderBy('name')->get();
        $picsMarketing = Pic::where('type', 'marketing')->orderBy('name')->get();

        // Merge wire rate periods into available periods
        $wireRatePeriods = $wireRates
            ->filter(fn ($r) => $r->period_month)
            ->map(fn ($r) => $r->period_month->format('Y-m'));
        $periods = $periods->merge($wireRatePeriods)->unique()->sortDesc()->values();
        $selectedWireRateId = (int) session('wire_selected_rate_id', 0);

        if ($selectedWireRateId <= 0 && $wireRates->isNotEmpty()) {
            $selectedWireRateId = (int) $wireRates->last()->id;
        }

        $activeWireRate = $wireRates->firstWhere('id', $selectedWireRateId);
        if (!$activeWireRate) {
            $activeWireRate = $wireRates->last();
            $selectedWireRateId = (int) ($activeWireRate?->id ?? 0);
        }

        // Get existing costing data if editing
        $costingDataId = $request->get('id');
        $costingData = null;
        $materialBreakdowns = collect();
        $trackingRevision = null;
        $openUnpricedParts = collect();
        $trackingRevisionId = $request->get('tracking_revision_id');
        $trackingProjectPrefill = [
            'business_category_id' => null,
            'customer_id' => null,
            'model' => null,
            'assy_no' => null,
            'assy_name' => null,
            'pic_engineering' => null,
            'pic_marketing' => null,
            'plant_code' => null,
            'period' => null,
        ];
        $a00CostingTabs = collect();

        if ($trackingRevisionId) {
            $trackingRevision = DocumentRevision::with(['project','plant'])->find($trackingRevisionId);

            if ($trackingRevision) {
                $a00Form = ProjectA00Form::with([
                    'items.projectRevision',
                    'items.project.revisions' => fn ($query) => $query->latest('version_number'),
                ])
                    ->where(function ($query) use ($trackingRevision) {
                        $query->where('document_project_id', $trackingRevision->document_project_id)
                            ->orWhereHas('items', fn ($itemQuery) => $itemQuery
                                ->where('document_project_id', $trackingRevision->document_project_id));
                    })
                    ->first();
                if ($a00Form) {
                    $a00CostingTabs = $a00Form->items->map(function ($item) {
                        $revision = $item->projectRevision ?: $item->project?->revisions?->first();

                        return (object) [
                            'assy_number' => $item->assy_number,
                            'assy_name' => $item->assy_name,
                            'revision' => $revision,
                        ];
                    })->filter(fn ($tab) => $tab->revision)->values();
                }
                $editSubmittedMode = $request->boolean('edit_submitted')
                    && $trackingRevision->status === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING
                    && in_array((string) ($request->user()->role ?? ''), ['admin', 'admin_costing', 'editor'], true);

                if ($trackingRevision->status === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING
                    && !$editSubmittedMode
                    && !$request->boolean('view_only')) {
                    $readOnlyMode = true;
                }
                if (filled($trackingRevision->period)) {
                    $periods = $periods->push($trackingRevision->period)->filter()->unique()->sortDesc()->values();
                }
                $openUnpricedParts = UnpricedPart::where('document_revision_id', $trackingRevision->id)
                    ->whereNull('resolved_at')
                    ->orderBy('part_number')
                    ->get()
                    ->unique('part_number');

                // Pre-load all materials & wires once (2 queries total instead of N*8)
                $allMaterials = Material::query()
                    ->where(function ($q) {
                        $q->whereNull('material_code')->orWhere('material_code', 'not like', '__ROW_%');
                    })
                    ->whereNotNull('material_description')
                    ->where('material_description', '!=', '')
                    ->orderBy('material_code')
                    ->get();
                $allWires = Wire::all();

                // Build lookup indexes in memory
                $matByDescExact = $allMaterials->groupBy(fn ($m) => Str::lower($m->material_description));
                $wireByItemLower = $allWires->groupBy(fn ($w) => Str::lower($w->item));
                $wireByIdcodeLower = $allWires->groupBy(fn ($w) => Str::lower($w->idcode));

                $openUnpricedParts = $openUnpricedParts->map(function ($item) use ($allMaterials, $allWires, $matByDescExact, $wireByItemLower, $wireByIdcodeLower) {
                    $partNumber = trim((string) ($item->part_number ?? ''));
                    $partName = trim((string) ($item->part_name ?? ''));

                    $matchedMaterials = collect();

                    $searchTerms = array_filter([$partNumber, $partName], fn ($v) => $v !== '' && $v !== '-');

                    foreach ($searchTerms as $term) {
                        $lower = Str::lower($term);

                        // 1) Exact match
                        $matchedMaterials = $matByDescExact->get($lower, collect());

                        // 2) Suffix match (e.g. "604152-0" matches "TERM 604152-0")
                        if ($matchedMaterials->isEmpty()) {
                            $suffix = ' ' . $lower;
                            $matchedMaterials = $allMaterials->filter(fn ($m) => Str::endsWith(Str::lower($m->material_description), $suffix))->values();
                        }

                        // 3) Contains match
                        if ($matchedMaterials->isEmpty()) {
                            $matchedMaterials = $allMaterials->filter(fn ($m) => str_contains(Str::lower($m->material_description), $lower))->values();
                        }

                        if ($matchedMaterials->isNotEmpty()) break;
                    }

                    $item->matched_materials = $matchedMaterials;

                    $firstMatched = $matchedMaterials->first();
                    if ($firstMatched) {
                        $item->matched_material_description = $firstMatched->material_description;
                        $item->matched_price = $firstMatched->price;
                        $item->matched_purchase_unit = $firstMatched->purchase_unit;
                        $item->matched_currency = $firstMatched->currency;
                        $item->matched_moq = $firstMatched->moq;
                        $item->matched_cn = $firstMatched->cn;
                        $item->matched_maker = $firstMatched->maker;
                        $item->matched_add_cost_import_tax = $firstMatched->add_cost_import_tax;
                        $item->matched_price_update = $firstMatched->price_update;
                        $item->matched_price_before = $firstMatched->price_before;
                    }

                    // Wire matching (in-memory)
                    $item->matched_wires = collect();
                    foreach ($searchTerms as $term) {
                        $lower = Str::lower($term);

                        // Exact match on item or idcode
                        $wires = $wireByItemLower->get($lower, collect());
                        if ($wires->isEmpty()) $wires = $wireByIdcodeLower->get($lower, collect());

                        // Prefix match (e.g., "AVSS 0.5 B" starts with wire item "AVSS 0.5")
                        if ($wires->isEmpty()) {
                            $wires = $allWires->filter(function ($w) use ($lower) {
                                $wItem = Str::lower($w->item);
                                return $wItem !== '' && (
                                    str_starts_with($lower, $wItem . ' ') ||
                                    str_starts_with($lower, $wItem)
                                );
                            })->values();
                        }

                        if ($wires->isNotEmpty()) {
                            $item->matched_wires = $wires;
                            break;
                        }
                    }

                    // Fallback: populate matched fields from wire data when no material match
                    if ($matchedMaterials->isEmpty() && $item->matched_wires->isNotEmpty()) {
                        $firstWire = $item->matched_wires->first();
                        $item->matched_material_description = 'WIRE ' . $firstWire->item;
                        $item->matched_price = $firstWire->price;
                        $item->matched_currency = 'IDR';
                        $item->matched_wire_idcode = $firstWire->idcode;
                        $item->matched_source = 'wire';
                    }

                    if ($item->new_part_price_imported_at) {
                        $item->matched_materials = collect();
                        $item->matched_wires = collect();
                        $item->matched_source = 'new_part_request';
                        $item->matched_price = $item->detected_price;
                        $item->matched_purchase_unit = $item->purchase_unit;
                        $item->matched_currency = $item->currency;
                        $item->matched_moq = $item->moq;
                        $item->matched_cn = $item->cn_type;
                        $item->matched_maker = $item->maker;
                        $item->matched_add_cost_import_tax = $item->add_cost_percent;
                        $item->matched_price_update = null;
                        $item->matched_price_before = null;
                    }

                    return $item;
                });

                $project = $trackingRevision->project;
                if ($project) {
                    $normalize = fn (?string $value): string => preg_replace('/[^a-z0-9]/', '', Str::lower((string) $value));
                    $trackingCustomer = $normalize($project->customer);
                    $trackingModel = $normalize($project->model);
                    $trackingPartNumber = $normalize($project->part_number);
                    $trackingPartName = $normalize($project->part_name);

                    $matchedCustomer = $customers->first(function ($customer) use ($normalize, $trackingCustomer) {
                        if ($trackingCustomer === '') {
                            return false;
                        }

                        $nameNorm = $normalize($customer->name);
                        $codeNorm = $normalize($customer->code ?? '');

                        return $nameNorm === $trackingCustomer
                            || $codeNorm === $trackingCustomer
                            || ($nameNorm !== '' && str_contains($nameNorm, $trackingCustomer))
                            || ($nameNorm !== '' && str_contains($trackingCustomer, $nameNorm));
                    });

                    $matchedProduct = $project->product_id
                        ? $products->firstWhere('id', (int) $project->product_id)
                        : null;

                    $matchedBusinessCategory = null;
                    if ($matchedProduct) {
                        $productLineNorm = $normalize($matchedProduct->line ?? '');
                        $productCodeNorm = $normalize($matchedProduct->code ?? '');
                        $productNameNorm = $normalize($matchedProduct->name ?? '');

                        $matchedBusinessCategory = $businessCategories->first(function ($category) use ($normalize, $productLineNorm, $productCodeNorm, $productNameNorm) {
                            $categoryCodeNorm = $normalize($category->code ?? '');
                            $categoryNameNorm = $normalize($category->name ?? '');

                            return ($productLineNorm !== '' && ($categoryNameNorm === $productLineNorm || str_contains($categoryNameNorm, $productLineNorm) || str_contains($productLineNorm, $categoryNameNorm)))
                                || ($productCodeNorm !== '' && $categoryCodeNorm === $productCodeNorm)
                                || ($productNameNorm !== '' && $categoryNameNorm === $productNameNorm);
                        });
                    }

                    if (!$matchedProduct) {
                        $matchedProduct = $products->first(function ($product) use (
                            $normalize,
                            $trackingModel,
                            $trackingPartNumber,
                            $trackingPartName
                        ) {
                            $productCode = $normalize($product->code ?? '');
                            $productName = $normalize($product->name ?? '');

                            $needles = array_filter([$trackingModel, $trackingPartNumber, $trackingPartName]);
                            if (empty($needles)) {
                                return false;
                            }

                            foreach ($needles as $needle) {
                                if ($needle === '') {
                                    continue;
                                }

                                if ($productCode === $needle || $productName === $needle) {
                                    return true;
                                }

                                if (($productCode !== '' && str_contains($productCode, $needle))
                                    || ($productName !== '' && str_contains($productName, $needle))
                                    || ($productCode !== '' && str_contains($needle, $productCode))
                                    || ($productName !== '' && str_contains($needle, $productName))) {
                                    return true;
                                }
                            }

                            return false;
                        });
                    }

                    $trackingProjectPrefill = [
                        'business_category_id' => $matchedBusinessCategory?->id,
                        'customer_id' => $matchedCustomer?->id,
                        'model' => $project->model,
                        'assy_no' => $project->part_number,
                        'assy_name' => $project->part_name,
                        'pic_engineering' => $trackingRevision->pic_engineering ?? null,
                        'pic_marketing' => $trackingRevision->pic_marketing ?? null,
                        'plant_code' => $trackingRevision->plant?->code,
                        'period' => $trackingRevision->period,
                    ];
                }

                if (!isset($trackingProjectPrefill['pic_engineering'])) {
                    $trackingProjectPrefill['pic_engineering'] = $trackingRevision->pic_engineering ?? null;
                }
                if (!isset($trackingProjectPrefill['pic_marketing'])) {
                    $trackingProjectPrefill['pic_marketing'] = $trackingRevision->pic_marketing ?? null;
                }
            }
        }

        if ($trackingRevisionId) {
            $cogmSubmissionQuery = CogmSubmission::with('comments.user')
                ->where('document_revision_id', $trackingRevisionId);

            if ($request->filled('cogm_submission_id')) {
                $cogmSubmissionQuery->whereKey((int) $request->get('cogm_submission_id'));
            } else {
                $cogmSubmissionQuery->latest('submitted_at');
            }

            $cogmSubmission = $cogmSubmissionQuery->first();
        }

        if (!$costingDataId && $trackingRevisionId) {
            $costingDataId = CostingData::where('tracking_revision_id', $trackingRevisionId)
                ->latest('id')
                ->value('id');
        }

        if ($costingDataId) {
            $costingData = CostingData::with('materialBreakdowns.material')->find($costingDataId);
            if ($costingData) {
                $importService->backfillMissingMaterialUnits($costingData);
                $costingData->load('materialBreakdowns.material');
                $materialBreakdowns = $costingData->materialBreakdowns;
            }
        }
        $rateSelectionKey = $costingData
            ? 'costing_' . $costingData->id
            : ($trackingRevisionId ? 'revision_' . $trackingRevisionId : 'new');
        $rememberedRateSelections = (array) session('costing_rate_selections', []);
        $rememberedExchangeRateId = array_key_exists($rateSelectionKey, $rememberedRateSelections)
            ? (int) $rememberedRateSelections[$rateSelectionKey]
            : (int) ($costingData->exchange_rate_id ?? 0);
        $selectedExchangeRateId = (int) old('exchange_rate_id', $rememberedExchangeRateId);
        $partlistAutoImportMessage = null;
        if ($materialBreakdowns->isEmpty() && $trackingRevision && filled($trackingRevision->partlist_file_path)) {
            $importResult = $importService->preparePartlistImport(
                ['tracking_revision_id' => (int) $trackingRevision->id],
                $request
            );

            if (!empty($importResult['rows'])) {
                $materialBreakdowns = collect($importResult['rows'])->map(function (array $row) {
                    $breakdown = new MaterialBreakdown();
                    $breakdown->forceFill([
                        'row_no' => $row['row_no'] ?? null,
                        'part_no' => $row['part_no'] ?? null,
                        'id_code' => $row['id_code'] ?? null,
                        'part_name' => $row['part_name'] ?? null,
                        'qty_req' => is_numeric($row['qty_req'] ?? null) ? (int) $row['qty_req'] : 0,
                        'pro_code' => $row['pro_code'] ?? null,
                        'amount1' => $row['amount1'] ?? 0,
                        'unit_price_basis' => $row['unit_price_basis'] ?? null,
                        'currency' => $row['currency'] ?? 'IDR',
                        'qty_moq' => $row['qty_moq'] ?? 0,
                        'cn_type' => $row['cn_type'] ?? 'N',
                        'import_tax_percent' => $row['import_tax'] ?? 0,
                    ]);
                    $breakdown->setAttribute('unit', $row['unit'] ?? null);
                    $breakdown->setAttribute('supplier', $row['supplier'] ?? null);
                    $breakdown->setRelation('material', null);
                    return $breakdown;
                });
                $partlistAutoImportMessage = count($importResult['rows']).' baris Material dimuat otomatis dari '.$trackingRevision->partlist_original_name.'.';
            } else {
                $partlistAutoImportMessage = $importResult['error'] ?? $importResult['warning'] ?? 'Partlist tidak dapat dimuat otomatis.';
            }
        }

        // A revision can receive New Part Request prices before the first
        // CostingData/MaterialBreakdown records are saved. Re-apply those
        // resolved prices to the in-memory partlist rows so a page refresh
        // never turns successfully priced Material rows back to zero.
        if ($trackingRevisionId && $materialBreakdowns->isNotEmpty()) {
            $materialBreakdowns = $this->applyResolvedUnpricedPricesToRows(
                $materialBreakdowns,
                (int) $trackingRevisionId
            );
        }

        $initialCycleTimes = $request->old('cycle_times', $costingData->cycle_times ?? []);
        if (!is_array($initialCycleTimes)) {
            $initialCycleTimes = [];
        }
        if (count($initialCycleTimes) === 0 && $cycleTimeTemplates->count() > 0) {
            $initialCycleTimes = $cycleTimeTemplates->toArray();
        }
        $initialCycleCount = count($initialCycleTimes) > 0 ? count($initialCycleTimes) : 5;

        // Slim down materials JSON for the JS lookup (only needed fields, using array to reduce size)
        $materialsSlim = $materials->map(function ($m) {
            return [
                $m->material_code,
                $m->material_description,
                $m->base_uom,
                $m->currency,
                $m->price,
                $m->moq,
                $m->cn,
                $m->maker,
                $m->add_cost_import_tax,
            ];
        })->values();

        return view('form', compact(
            'products',
            'businessCategories',
            'customers',
            'materials',
            'materialsSlim',
            'cycleTimeTemplates',
            'initialCycleCount',
            'plants',
            'periods',
            'wireRates',
            'exchangeRates',
            'selectedExchangeRateId',
            'rateSelectionKey',
            'activeWireRate',
            'selectedWireRateId',
            'costingData',
            'materialBreakdowns',
            'trackingRevision',
            'trackingRevisionId',
            'openUnpricedParts',
            'partlistAutoImportMessage',
            'trackingProjectPrefill',
            'picsEngineering',
            'picsMarketing',
            'readOnlyMode',
            'editSubmittedMode',
            'cogmSubmission',
            'a00CostingTabs'
        ));
    }

    private function applyResolvedUnpricedPricesToRows(
        \Illuminate\Support\Collection $rows,
        int $trackingRevisionId
    ): \Illuminate\Support\Collection {
        $resolvedPrices = UnpricedPart::query()
            ->where('document_revision_id', $trackingRevisionId)
            ->whereNotNull('resolved_at')
            ->where('manual_price', '>', 0)
            ->orderByDesc('resolved_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (UnpricedPart $part) => Str::lower(trim((string) $part->part_number)))
            ->keyBy(fn (UnpricedPart $part) => Str::lower(trim((string) $part->part_number)));

        $masterPrices = Material::query()
            ->whereIn('material_code', $resolvedPrices->pluck('part_number')->filter()->values())
            ->get()
            ->keyBy(fn (Material $material) => Str::lower(trim((string) $material->material_code)));

        return $rows->each(function (MaterialBreakdown $row) use ($resolvedPrices, $masterPrices) {
            if ((float) ($row->amount1 ?? 0) > 0) {
                return;
            }

            $partKey = Str::lower(trim((string) ($row->part_no ?? '')));
            $price = $resolvedPrices->get($partKey);
            if (!$price) {
                return;
            }

            $master = $masterPrices->get($partKey);

            $row->setAttribute('amount1', (float) $price->manual_price);
            $row->setAttribute('unit_price_basis', null);
            $row->setAttribute('unit_price_basis_text', $price->purchase_unit ?: $master?->purchase_unit);
            $row->setAttribute('currency', $price->currency ?: ($master?->currency ?: 'IDR'));
            $row->setAttribute('qty_moq', $price->moq ?? $master?->moq);
            $row->setAttribute('cn_type', $price->cn_type ?: $master?->cn);
            $row->setAttribute('supplier', $price->maker ?: $master?->maker);
            $row->setAttribute('import_tax_percent', $price->add_cost_percent ?? $master?->add_cost_import_tax);
        });
    }

}
