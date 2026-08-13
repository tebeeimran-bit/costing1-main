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
HandlesCostingComparison
{
    public function compare(Request $request)
    {
        $businessCategoryFilter = trim((string) $request->input('business_category', 'all'));
        $customerFilter = trim((string) $request->input('customer_id', 'all'));
        $modelFilter = trim((string) $request->input('model', 'all'));

        $businessCategoryOptions = CostingData::query()
            ->join('products', 'products.id', '=', 'costing_data.product_id')
            ->whereNotNull('products.line')
            ->where('products.line', '!=', '')
            ->select('products.line')
            ->distinct()
            ->orderBy('products.line')
            ->pluck('products.line')
            ->values();

        if ($businessCategoryFilter !== 'all' && !$businessCategoryOptions->contains($businessCategoryFilter)) {
            $businessCategoryFilter = 'all';
        }

        $customerOptionsQuery = CostingData::query()
            ->join('customers', 'customers.id', '=', 'costing_data.customer_id')
            ->whereNotNull('customers.name')
            ->where('customers.name', '!=', '')
            ->select('customers.id', 'customers.name');

        if ($businessCategoryFilter !== '' && $businessCategoryFilter !== 'all') {
            $customerOptionsQuery->join('products', 'products.id', '=', 'costing_data.product_id')
                ->where('products.line', $businessCategoryFilter);
        }

        $customerOptions = $customerOptionsQuery
            ->distinct()
            ->orderBy('customers.name')
            ->get();

        $customerIdOptions = $customerOptions
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values();

        if ($customerFilter !== 'all' && !$customerIdOptions->contains((string) $customerFilter)) {
            $customerFilter = 'all';
        }

        $modelOptionsQuery = CostingData::query()
            ->whereNotNull('model')
            ->where('model', '!=', '')
            ->select('model');

        if ($businessCategoryFilter !== '' && $businessCategoryFilter !== 'all') {
            $modelOptionsQuery->whereHas('product', function ($productQuery) use ($businessCategoryFilter) {
                $productQuery->where('line', $businessCategoryFilter);
            });
        }

        if ($customerFilter !== '' && $customerFilter !== 'all') {
            $modelOptionsQuery->where('customer_id', (int) $customerFilter);
        }

        $modelOptions = $modelOptionsQuery
            ->distinct()
            ->orderBy('model')
            ->pluck('model')
            ->values();

        if ($modelFilter !== 'all' && !$modelOptions->contains($modelFilter)) {
            $modelFilter = 'all';
        }

        $activeFilters = [
            'business_category' => $businessCategoryFilter,
            'customer_id' => $customerFilter,
            'model' => $modelFilter,
        ];

        $revisionOptions = $this->buildCompareRevisionOptions('', 0, $activeFilters);

        $selectedAId = (int) $request->input('compare_a_id', 0);
        $selectedBId = (int) $request->input('compare_b_id', 0);

        if ($selectedAId <= 0) {
            $selectedAId = (int) ($revisionOptions->first()['id'] ?? 0);
        }

        if ($selectedBId <= 0) {
            $fallbackB = null;
            $selectedAOption = $revisionOptions->firstWhere('id', $selectedAId);
            if ($selectedAOption) {
                $fallbackB = $revisionOptions
                    ->first(function ($option) use ($selectedAOption, $selectedAId) {
                        return $option['id'] !== $selectedAId
                            && trim((string) ($option['assy_no'] ?? '')) === trim((string) ($selectedAOption['assy_no'] ?? ''));
                    });
            }

            if ($fallbackB) {
                $selectedBId = (int) $fallbackB['id'];
            } else {
                $selectedBId = (int) ($revisionOptions->firstWhere('id', '!=', $selectedAId)['id'] ?? $selectedAId);
            }
        }

        $costingA = $selectedAId > 0
            ? CostingData::with(['product', 'customer', 'trackingRevision', 'materialBreakdowns.material'])->find($selectedAId)
            : null;
        $costingB = $selectedBId > 0
            ? CostingData::with(['product', 'customer', 'trackingRevision', 'materialBreakdowns.material'])->find($selectedBId)
            : null;

        if (!$costingA && $revisionOptions->isNotEmpty()) {
            $selectedAId = (int) ($revisionOptions->first()['id'] ?? 0);
            $costingA = CostingData::with(['product', 'customer', 'trackingRevision', 'materialBreakdowns.material'])->find($selectedAId);
        }

        if (!$costingB && $revisionOptions->count() > 1) {
            $selectedBId = (int) ($revisionOptions->get(1)['id'] ?? $selectedAId);
            $costingB = CostingData::with(['product', 'customer', 'trackingRevision', 'materialBreakdowns.material'])->find($selectedBId);
        }

        if (!$costingB && $costingA) {
            $selectedBId = $selectedAId;
            $costingB = $costingA;
        }

        $resolveAssyLabel = function ($costing, string $fallback = '-') {
            if (!$costing) {
                return $fallback;
            }

            $parts = array_filter([
                trim((string) ($costing->assy_no ?? '')),
                trim((string) ($costing->assy_name ?? '')),
                trim((string) ($costing->model ?? '')),
            ]);

            $base = implode(' - ', $parts) ?: ('Costing #' . (string) $costing->id);
            $versionLabel = trim((string) ($costing->trackingRevision?->version_label ?? 'V-'));

            return $base . ' | ' . $versionLabel;
        };

        $formatNumeric = function ($value, int $decimals = 2) {
            return number_format((float) ($value ?? 0), $decimals, ',', '.');
        };

        $materialComparisonRows = collect();
        foreach ([['slot' => 'A', 'costing' => $costingA], ['slot' => 'B', 'costing' => $costingB]] as $bundle) {
            $slot = $bundle['slot'];
            $costing = $bundle['costing'];
            if (!$costing) {
                continue;
            }

            foreach ($costing->materialBreakdowns as $index => $material) {
                $currency = strtoupper(trim((string) ($material->currency ?? 'IDR')));
                $exchangeRate = 1.0;
                if ($currency === 'USD') {
                    $exchangeRate = (float) ($costing->exchange_rate_usd ?? 15500);
                } elseif ($currency === 'JPY') {
                    $exchangeRate = (float) ($costing->exchange_rate_jpy ?? 103);
                }

                $qtyReq = (float) ($material->qty_req ?? 0);
                $amount1 = (float) ($material->amount1 ?? 0);
                $amount2 = (float) ($material->amount2 ?? 0);
                $totalPriceIdr = $qtyReq * $amount2 * $exchangeRate;

                $keyParts = array_filter([
                    trim((string) ($material->part_no ?? '')),
                    trim((string) ($material->id_code ?? '')),
                    trim((string) ($material->part_name ?? '')),
                ]);
                $rowKey = implode(' | ', $keyParts);
                if ($rowKey === '') {
                    $rowKey = 'ROW-' . ((int) ($material->row_no ?? ($index + 1)));
                }

                if (!$materialComparisonRows->has($rowKey)) {
                    $materialComparisonRows->put($rowKey, [
                        'row_key' => $rowKey,
                        'row_no' => (int) ($material->row_no ?? ($index + 1)),
                        'part_no' => trim((string) ($material->part_no ?? '')),
                        'part_name' => trim((string) ($material->part_name ?? '')),
                        'id_code' => trim((string) ($material->id_code ?? '')),
                        'A' => null,
                        'B' => null,
                    ]);
                }

                $existing = $materialComparisonRows->get($rowKey);
                $existing[$slot] = [
                    'qty_req' => (int) $qtyReq,
                    'amount1' => round($amount1, 6),
                    'amount2' => round($amount2, 6),
                    'unit_price_basis' => trim((string) ($material->unit_price_basis ?? '')),
                    'unit_price_basis_text' => trim((string) ($material->unit_price_basis_text ?? '')),
                    'currency' => $currency,
                    'qty_moq' => (float) ($material->qty_moq ?? 0),
                    'cn_type' => trim((string) ($material->cn_type ?? '')),
                    'supplier' => trim((string) (($material->material->maker ?? '') ?: '')),
                    'import_tax_percent' => (float) ($material->import_tax_percent ?? 0),
                    'total_price_idr' => $totalPriceIdr,
                ];
                $materialComparisonRows->put($rowKey, $existing);
            }
        }

        $materialComparisonRows = $materialComparisonRows->values()->sortBy(function ($row) {
            return sprintf('%06d-%s', (int) ($row['row_no'] ?? 0), (string) ($row['row_key'] ?? ''));
        })->values();

        $cycleTimeComparisonRows = collect();
        foreach ([['slot' => 'A', 'costing' => $costingA], ['slot' => 'B', 'costing' => $costingB]] as $bundle) {
            $slot = $bundle['slot'];
            $costing = $bundle['costing'];
            if (!$costing) {
                continue;
            }

            $cycleTimes = collect($costing->cycle_times ?? []);
            foreach ($cycleTimes as $index => $cycleTime) {
                $process = trim((string) data_get($cycleTime, 'process', ''));
                $area = trim((string) data_get($cycleTime, 'area_of_process', ''));
                $rowKey = trim($process . '|' . $area);
                if ($rowKey === '|') {
                    $rowKey = 'ROW-' . ($index + 1);
                }

                if (!$cycleTimeComparisonRows->has($rowKey)) {
                    $cycleTimeComparisonRows->put($rowKey, [
                        'row_key' => $rowKey,
                        'process' => $process,
                        'area_of_process' => $area,
                        'A' => null,
                        'B' => null,
                    ]);
                }

                $existing = $cycleTimeComparisonRows->get($rowKey);
                $existing[$slot] = [
                    'qty' => (float) data_get($cycleTime, 'qty', 0),
                    'time_hour' => (float) data_get($cycleTime, 'time_hour', 0),
                    'time_sec' => (float) data_get($cycleTime, 'time_sec', 0),
                    'time_sec_per_qty' => (float) data_get($cycleTime, 'time_sec_per_qty', 0),
                    'cost_per_sec' => (float) data_get($cycleTime, 'cost_per_sec', 0),
                    'cost_per_unit' => (float) data_get($cycleTime, 'cost_per_unit', 0),
                ];
                $cycleTimeComparisonRows->put($rowKey, $existing);
            }
        }

        $cycleTimeComparisonRows = $cycleTimeComparisonRows->values()->sortBy(function ($row) {
            return sprintf('%s-%s-%s', (string) ($row['process'] ?? ''), (string) ($row['area_of_process'] ?? ''), (string) ($row['row_key'] ?? ''));
        })->values();

        $resumeRows = collect([
            'material_cost' => 'Material Cost',
            'labor_cost' => 'Labor Cost',
            'overhead_cost' => 'Depresiasi Tooling Cost',
            'scrap_cost' => 'Administrasi Cost',
            'total_cost' => 'COGM',
        ])->map(function ($label, $key) use ($costingA, $costingB, $formatNumeric) {
            $valueA = $costingA ? ($costingA->{$key} ?? 0) : 0;
            $valueB = $costingB ? ($costingB->{$key} ?? 0) : 0;

            $formattedA = $formatNumeric($valueA, 2);
            $formattedB = $formatNumeric($valueB, 2);
            $deltaValue = (float) ($valueA ?? 0) - (float) ($valueB ?? 0);
            $delta = $formatNumeric($deltaValue, 2);

            return [
                'key' => $key,
                'label' => $label,
                'value_a' => $formattedA,
                'value_b' => $formattedB,
                'delta' => $delta,
            ];
        })->values();

        $selectedAOptionLabel = (string) data_get($revisionOptions->firstWhere('id', $selectedAId), 'label', '');
        $selectedBOptionLabel = (string) data_get($revisionOptions->firstWhere('id', $selectedBId), 'label', '');

        return view('compare-costing', [
            'revisionOptions' => $revisionOptions,
            'businessCategoryOptions' => $businessCategoryOptions,
            'customerOptions' => $customerOptions,
            'modelOptions' => $modelOptions,
            'businessCategoryFilter' => $businessCategoryFilter,
            'customerFilter' => $customerFilter,
            'modelFilter' => $modelFilter,
            'selectedAId' => $selectedAId,
            'selectedBId' => $selectedBId,
            'selectedAOptionLabel' => $selectedAOptionLabel,
            'selectedBOptionLabel' => $selectedBOptionLabel,
            'costingA' => $costingA,
            'costingB' => $costingB,
            'labelA' => $resolveAssyLabel($costingA, 'Assy A belum dipilih'),
            'labelB' => $resolveAssyLabel($costingB, 'Assy B belum dipilih'),
            'materialComparisonRows' => $materialComparisonRows,
            'cycleTimeComparisonRows' => $cycleTimeComparisonRows,
            'resumeRows' => $resumeRows,
        ]);
    }

    public function searchCompareRevisions(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 20);
        $filters = [
            'business_category' => trim((string) $request->query('business_category', 'all')),
            'customer_id' => trim((string) $request->query('customer_id', 'all')),
            'model' => trim((string) $request->query('model', 'all')),
        ];

        if ($limit <= 0) {
            $limit = 20;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $options = $this->buildCompareRevisionOptions($keyword, $limit, $filters);

        return response()->json([
            'data' => $options->values(),
        ]);
    }

    private function buildCompareRevisionOptions(string $keyword = '', int $limit = 0, array $filters = [])
    {
        $keywordLower = Str::lower(trim($keyword));
        $businessCategoryFilter = trim((string) ($filters['business_category'] ?? 'all'));
        $customerFilter = trim((string) ($filters['customer_id'] ?? 'all'));
        $modelFilter = trim((string) ($filters['model'] ?? 'all'));

        $query = CostingData::query()
            ->with('trackingRevision')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($businessCategoryFilter !== '' && $businessCategoryFilter !== 'all') {
            $query->whereHas('product', function ($productQuery) use ($businessCategoryFilter) {
                $productQuery->where('line', $businessCategoryFilter);
            });
        }

        if ($customerFilter !== '' && $customerFilter !== 'all') {
            $query->where('customer_id', (int) $customerFilter);
        }

        if ($modelFilter !== '' && $modelFilter !== 'all') {
            $query->where('model', $modelFilter);
        }

        $options = $query
            ->get()
            ->map(function ($item) {
                $assyNo = trim((string) ($item->assy_no ?? ''));
                $assyName = trim((string) ($item->assy_name ?? ''));
                $model = trim((string) ($item->model ?? ''));
                $versionLabel = trim((string) ($item->trackingRevision?->version_label ?? 'V-'));
                $periodLabel = trim((string) ($item->period ?? ''));
                $updatedLabel = optional($item->updated_at)?->format('d/m/Y H:i') ?? '-';

                $base = implode(' - ', array_filter([$assyNo, $assyName, $model]));
                if ($base === '') {
                    $base = 'Costing #' . (string) $item->id;
                }

                $label = $base . ' | ' . $versionLabel;
                if ($periodLabel !== '') {
                    $label .= ' | ' . $periodLabel;
                }
                $label .= ' | update ' . $updatedLabel;

                return [
                    'id' => (int) $item->id,
                    'assy_no' => $assyNo,
                    'label' => $label,
                    'version_label' => $versionLabel,
                    'search' => Str::lower(implode(' ', array_filter([
                        $assyNo,
                        $assyName,
                        $model,
                        $versionLabel,
                        $periodLabel,
                        $updatedLabel,
                    ]))),
                    'sort_key' => sprintf(
                        '%s|%09d|%010d',
                        $assyNo,
                        999999999 - (int) ($item->trackingRevision?->version_number ?? 0),
                        9999999999 - (optional($item->updated_at)->timestamp ?? 0)
                    ),
                ];
            });

        if ($keywordLower !== '') {
            $options = $options->filter(function ($item) use ($keywordLower) {
                return str_contains((string) ($item['search'] ?? ''), $keywordLower)
                    || str_contains(Str::lower((string) ($item['label'] ?? '')), $keywordLower);
            })->values();
        }

        $options = $options
            ->sortBy('sort_key')
            ->values()
            ->map(function ($item) {
                unset($item['search'], $item['sort_key']);

                return $item;
            });

        if ($limit > 0) {
            $options = $options->take($limit)->values();
        }

        return $options;
    }

}
