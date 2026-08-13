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
HandlesCostingDashboard
{
    public function dashboard(Request $request)
    {
        if ((string) ($request->user()->role ?? '') !== 'admin') {
            return $this->roleDashboard($request);
        }

        $periods = CostingData::query()
            ->select('period')
            ->distinct()
            ->orderBy('period', 'desc')
            ->pluck('period')
            ->values();

        $requestedPeriod = trim((string) $request->get('period', ''));
        $period = $requestedPeriod !== '' ? $requestedPeriod : ((string) ($periods->first() ?? now()->format('Y-m')));

        if ($period !== 'all' && $periods->isNotEmpty() && !$periods->contains($period)) {
            $period = (string) $periods->first();
        }

        $businessCategoryFilter = trim((string) $request->get('business_category', 'all'));
        $customerFilter = trim((string) $request->get('customer', 'all'));
        $modelFilter = trim((string) $request->get('model', 'all'));

        $applyFilters = function ($query) use ($businessCategoryFilter, $customerFilter, $modelFilter) {
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

            return $query;
        };

        $resolveUnitQty = function ($item) {
            $qtyGood = (float) ($item->qty_good ?? 0);
            if ($qtyGood > 0) {
                return $qtyGood;
            }

            $forecast = (float) ($item->forecast ?? 0);
            if ($forecast > 0) {
                return $forecast;
            }

            return 0.0;
        };

        $resolvePotentialSales = function ($item) {
            $qtyPerMonth = (float) ($item->forecast ?? 0);
            $productLifeYears = (float) ($item->project_period ?? 0);
            $cogm = (float) ($item->material_cost ?? 0)
                + (float) ($item->labor_cost ?? 0)
                + (float) ($item->overhead_cost ?? 0)
                + (float) ($item->scrap_cost ?? 0);

            return $qtyPerMonth * $productLifeYears * $cogm;
        };

        $resolveAssyLabel = function ($item) {
            $candidates = [
                $item->assy_name ?? null,
                $item->assy_no ?? null,
                $item->model ?? null,
                $item->product->name ?? null,
            ];

            foreach ($candidates as $candidate) {
                $label = preg_replace('/\s+/u', ' ', (string) $candidate);
                $label = trim((string) $label);
                if ($label !== '') {
                    return $label;
                }
            }

            return 'Costing #' . (string) ($item->id ?? '-');
        };

        $resolveBusinessCategoryLabel = function ($item) {
            $line = trim((string) ($item->product->line ?? ''));
            if ($line !== '') {
                return $line;
            }

            $productName = trim((string) ($item->product->name ?? ''));
            return $productName !== '' ? $productName : 'Uncategorized';
        };

        // Get business category filter options from product line values used by costing records.
        $businessCategories = CostingData::query()
            ->join('products', 'products.id', '=', 'costing_data.product_id')
            ->whereNotNull('products.line')
            ->where('products.line', '!=', '')
            ->select('products.line')
            ->distinct()
            ->orderBy('products.line')
            ->pluck('products.line')
            ->map(function ($line) {
                return (object) [
                    'id' => (string) $line,
                    'name' => (string) $line,
                    'code' => null,
                ];
            })
            ->values();

        $customers = Customer::query()
            ->whereIn('id', CostingData::query()->select('customer_id')->distinct())
            ->orderBy('name')
            ->get();

        $selectedCustomerName = null;
        if ($customerFilter !== '' && $customerFilter !== 'all') {
            $selectedCustomerName = trim((string) optional($customers->firstWhere('id', (int) $customerFilter))->name);
            if ($selectedCustomerName === '') {
                $selectedCustomerName = null;
            }
        }

        $periodDisplayLabel = $period;
        if ($period === 'all') {
            $periodDisplayLabel = 'Semua Periode';
        } elseif (preg_match('/^\d{4}-\d{2}$/', (string) $period) === 1) {
            $periodDisplayLabel = \Carbon\Carbon::createFromFormat('Y-m', (string) $period)->format('M Y');
        }

        $periodStart = null;
        $periodEnd = null;
        if (preg_match('/^\d{4}-\d{2}$/', (string) $period) === 1) {
            $periodStart = \Carbon\Carbon::createFromFormat('Y-m', (string) $period)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
        }

        $applyProjectFilters = function ($query) use ($businessCategoryFilter, $modelFilter, $selectedCustomerName) {
            if ($businessCategoryFilter !== '' && $businessCategoryFilter !== 'all') {
                $query->whereHas('product', function ($productQuery) use ($businessCategoryFilter) {
                    $productQuery->where('line', $businessCategoryFilter);
                });
            }

            if ($modelFilter !== '' && $modelFilter !== 'all') {
                $query->where('model', $modelFilter);
            }

            if ($selectedCustomerName !== null) {
                $query->whereRaw('LOWER(customer) = ?', [Str::lower($selectedCustomerName)]);
            }

            return $query;
        };

        $models = CostingData::query()
            ->select('model')
            ->whereNotNull('model')
            ->where('model', '!=', '')
            ->distinct()
            ->orderBy('model')
            ->pluck('model')
            ->values();

        /*
         * Dashboard mode C:
         * - trackingProjectCount = semua project yang ada di menu Project / Tracking Document
         * - costingProjectCount  = project yang sudah punya data costing di costing_data
         *
         * Period dashboard tetap dipakai untuk data costing.
         * Project tracking sengaja tidak dibatasi period costing karena menu Project
         * menampilkan dokumen/project tracking, bukan hanya project yang sudah masuk costing_data.
         */
        $trackingProjectScope = DocumentRevision::query()
            ->whereHas('project', function ($projectQuery) use ($applyProjectFilters) {
                $applyProjectFilters($projectQuery);
            });

        $trackingProjectCount = (clone $trackingProjectScope)
            ->distinct('document_project_id')
            ->count('document_project_id');

        $submitScope = CogmSubmission::query()
            ->whereNotNull('submitted_at')
            ->whereHas('revision.project', function ($projectQuery) use ($applyProjectFilters) {
                $applyProjectFilters($projectQuery);
            });

        $totalSubmitCostingMonthly = 0;
        if ($periodStart && $periodEnd) {
            $totalSubmitCostingMonthly = (clone $submitScope)
                ->whereBetween('submitted_at', [$periodStart, $periodEnd])
                ->count();
        }

        $submitAnchorPeriod = $periodStart ? $periodStart->copy() : now()->startOfMonth();
        $submitPeriodCandidates = collect(range(5, 0))
            ->map(function ($offset) use ($submitAnchorPeriod) {
                return $submitAnchorPeriod->copy()->subMonths($offset)->format('Y-m');
            })
            ->values();

        // Batch-fetch all monthly submit counts in one query.
        $submitRangeStart = \Carbon\Carbon::createFromFormat('Y-m', (string) $submitPeriodCandidates->first())->startOfMonth();
        $submitRangeEnd   = \Carbon\Carbon::createFromFormat('Y-m', (string) $submitPeriodCandidates->last())->endOfMonth();
        $submitMonthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', submitted_at)"
            : "DATE_FORMAT(submitted_at, '%Y-%m')";
        $batchedSubmitCounts = (clone $submitScope)
            ->whereBetween('submitted_at', [$submitRangeStart, $submitRangeEnd])
            ->selectRaw($submitMonthExpression . ' as ym, COUNT(*) as cnt')
            ->groupByRaw($submitMonthExpression)
            ->pluck('cnt', 'ym');

        $monthlySubmitCounts = $submitPeriodCandidates->map(function ($submitPeriod) use ($batchedSubmitCounts) {
            $monthStart = \Carbon\Carbon::createFromFormat('Y-m', (string) $submitPeriod)->startOfMonth();

            return [
                'period' => $submitPeriod,
                'period_label' => $monthStart->format('M y'),
                'count' => (int) ($batchedSubmitCounts->get($submitPeriod, 0)),
            ];
        })->values();

        $maxMonthlySubmitCount = $monthlySubmitCounts->max('count') ?: 1;

        // Get costing data for selected period.
        $query = CostingData::with(['product', 'customer', 'trackingRevision.latestSubmission']);

        if ($period !== 'all') {
            $query->where('period', $period);
        }

        $applyFilters($query);

        $costingData = $query->get();

        /*
         * Satu project dapat memiliki lebih dari satu baris costing ketika statusnya
         * bergerak dari A00 ke A04/A05 (atau ketika dibuat revisi baru). Dashboard
         * adalah ringkasan project aktif, bukan histori baris costing, sehingga satu
         * customer + model + assy hanya boleh dihitung sekali. Ambil baris dengan
         * keputusan paling akhir; A05/A04 mengalahkan baris A00 lama.
         */
        $costingData = $costingData
            ->groupBy(function ($item) {
                $customerId = (string) ($item->customer_id ?? '');
                $model = Str::lower(trim((string) ($item->model ?? '')));
                $assyNo = Str::lower(trim((string) ($item->assy_no ?? '')));

                if ($assyNo !== '') {
                    return implode('|', [$customerId, $model, $assyNo]);
                }

                $projectId = $item->trackingRevision?->document_project_id;
                return $projectId
                    ? 'project|' . $projectId
                    : 'costing|' . $item->id;
            })
            ->map(function ($projectRows) {
                return $projectRows->sortByDesc(function ($row) {
                    $revision = $row->trackingRevision;
                    $statusRank = ($revision?->a05 ?? null) === 'ada'
                        ? 3
                        : ((($revision?->a04 ?? null) === 'ada')
                            ? 2
                            : ((($revision?->a00 ?? null) === 'ada') ? 1 : 0));

                    return ($statusRank * 1000000000000)
                        + (((int) ($revision?->version_number ?? 0)) * 1000000)
                        + (int) $row->id;
                })->first();
            })
            ->filter()
            ->values();

        // Calculate KPIs
        $totalCost = $costingData->sum('total_cost');
        $totalQty = $costingData->sum(function ($item) use ($resolveUnitQty) {
            return $resolveUnitQty($item);
        });
        $estimatedQtyProduksi = $costingData->sum(function ($item) {
            return (float) ($item->forecast ?? 0) * (float) ($item->project_period ?? 0);
        });
        $avgCostPerUnit = $totalQty > 0 ? $totalCost / $totalQty : 0;

        // Status KPI must follow filtered costing rows so total matches actual data count.
        $statusProjectCountsByLabel = [
            'A00 (RFQ/RFI)' => 0,
            'A04 (Canceled/Failed)' => 0,
            'A05 (Die Go)' => 0,
        ];
        $statusPotentialCostByLabel = [
            'A00 (RFQ/RFI)' => 0,
            'A04 (Canceled/Failed)' => 0,
            'A05 (Die Go)' => 0,
        ];

        foreach ($costingData as $item) {
            $revision = $item->trackingRevision;
            if (!$revision) {
                continue;
            }

            $potentialCost = $resolvePotentialSales($item);
            if (($revision->a05 ?? null) === 'ada') {
                $statusProjectCountsByLabel['A05 (Die Go)'] += 1;
                $statusPotentialCostByLabel['A05 (Die Go)'] += $potentialCost;
            } elseif (($revision->a04 ?? null) === 'ada') {
                $statusProjectCountsByLabel['A04 (Canceled/Failed)'] += 1;
                $statusPotentialCostByLabel['A04 (Canceled/Failed)'] += $potentialCost;
            } elseif (($revision->a00 ?? null) === 'ada') {
                $statusProjectCountsByLabel['A00 (RFQ/RFI)'] += 1;
                $statusPotentialCostByLabel['A00 (RFQ/RFI)'] += $potentialCost;
            }
        }

        $a00ProjectCount = (int) ($statusProjectCountsByLabel['A00 (RFQ/RFI)'] ?? 0);
        // KPI A00 menunjukkan status saat ini, bukan jumlah project yang pernah melewati A00.
        $a00ProjectEntryCount = $a00ProjectCount;
        $a04ProjectCount = (int) ($statusProjectCountsByLabel['A04 (Canceled/Failed)'] ?? 0);
        $a05ProjectCount = (int) ($statusProjectCountsByLabel['A05 (Die Go)'] ?? 0);
        $costingProjectCount = (int) $costingData->count();
        $pendingFormCostingCount = max(0, (int) $trackingProjectCount - (int) $costingProjectCount);
        $totalProjectCount = $costingProjectCount;
        $statusProjectTotal = $costingProjectCount;

        $statusProjectData = collect([
            [
                'label' => 'A00 (RFQ/RFI)',
                'count' => $a00ProjectCount,
                'color' => '#3b82f6',
            ],
            [
                'label' => 'A04 (Canceled/Failed)',
                'count' => $a04ProjectCount,
                'color' => '#dc2626',
            ],
            [
                'label' => 'A05 (Die Go)',
                'count' => $a05ProjectCount,
                'color' => '#22c55e',
            ],
        ])->map(function ($item) use ($statusProjectTotal, $statusPotentialCostByLabel) {
            $percentage = $statusProjectTotal > 0
                ? (((int) $item['count'] / $statusProjectTotal) * 100)
                : 0;

            return [
                'label' => $item['label'],
                'count' => (int) ($item['count'] ?? 0),
                'percentage' => round($percentage, 1),
                'color' => $item['color'] ?? '#94a3b8',
                'potential_cost' => (float) ($statusPotentialCostByLabel[$item['label']] ?? 0),
            ];
        })->values();

        $pieSegments = [];
        $pieStartAngle = 0.0;
        $pieStatusSum = $a00ProjectCount + $a04ProjectCount + $a05ProjectCount;
        foreach ($statusProjectData as $statusItem) {
            $count = (int) ($statusItem['count'] ?? 0);
            if ($count <= 0 || $pieStatusSum <= 0) {
                continue;
            }

            $sliceAngle = ($count / $pieStatusSum) * 360;
            $pieEndAngle = $pieStartAngle + $sliceAngle;
            $pieSegments[] = $statusItem['color']
                . ' '
                . number_format($pieStartAngle, 2, '.', '')
                . 'deg '
                . number_format($pieEndAngle, 2, '.', '')
                . 'deg';
            $pieStartAngle = $pieEndAngle;
        }

        if (empty($pieSegments)) {
            $statusProjectPieGradient = 'conic-gradient(#e2e8f0 0deg 360deg)';
        } else {
            $statusProjectPieGradient = 'conic-gradient(' . implode(', ', $pieSegments) . ')';
        }

        // Aggregate by assy to reflect project-level costing records.
        $costPerProduct = $costingData
            ->groupBy(function ($item) use ($resolveAssyLabel) {
                return Str::lower($resolveAssyLabel($item));
            })
            ->map(function ($items) use ($resolveUnitQty, $resolveAssyLabel, $resolvePotentialSales) {
                $first = $items->first();
                $productName = $resolveAssyLabel($first);

                $productTotalCost = $items->sum('total_cost');
                $productQty = $items->sum(function ($row) use ($resolveUnitQty) {
                    return $resolveUnitQty($row);
                });
                $materialCost = $items->sum('material_cost');
                $laborCost = $items->sum('labor_cost');
                $effectiveOverheadCost = $items->sum(function ($row) {
                    return (float) $row->overhead_cost + (float) $row->scrap_cost;
                });

                return [
                    'name' => $productName,
                    'total_cost' => $productTotalCost,
                    'total_qty' => $productQty,
                    'cost_per_unit' => $productQty > 0 ? ($productTotalCost / $productQty) : 0,
                    'potential_sales' => $items->sum(function ($row) use ($resolvePotentialSales) {
                        return $resolvePotentialSales($row);
                    }),
                    'material_cost' => $materialCost,
                    'labor_cost' => $laborCost,
                    'overhead_cost' => $effectiveOverheadCost,
                ];
            })
            ->sortByDesc(function ($item) {
                return ((float) $item['cost_per_unit'] * 1000000) + (float) $item['total_cost'];
            })
            ->values();

        // Find highest cost per unit product from aggregated dataset.
        $highestCostProduct = $costPerProduct->first();

        // Get max cost for chart scaling
        $maxCostPerUnit = $costPerProduct->max('cost_per_unit') ?: 1;

        // Get trend data (last 6 periods) from real costing records.
        $trendPeriodCandidates = CostingData::query()
            ->select('period')
            ->distinct()
            ->orderBy('period', 'desc')
            ->limit(6)
            ->pluck('period')
            ->reverse()
            ->values();

        // Batch-fetch all trend periods in a single query instead of N separate ones.
        $trendScope = CostingData::query()->with('product');
        $applyFilters($trendScope);
        $allTrendItems = $trendScope
            ->whereIn('period', $trendPeriodCandidates->all())
            ->get();
        $allTrendByPeriod = $allTrendItems->groupBy('period');

        $trendData = $trendPeriodCandidates->map(function ($trendPeriod) use ($allTrendByPeriod, $resolvePotentialSales) {
            $items = $allTrendByPeriod->get($trendPeriod, collect());
            $totalPotentialSalesPerPeriod = $items->sum(function ($row) use ($resolvePotentialSales) {
                return $resolvePotentialSales($row);
            });

            $label = $trendPeriod;
            if (preg_match('/^\d{4}-\d{2}$/', (string) $trendPeriod) === 1) {
                $label = \Carbon\Carbon::createFromFormat('Y-m', (string) $trendPeriod)->format('M y');
            }

            return [
                'period' => $trendPeriod,
                'period_label' => $label,
                'potential_sales' => $totalPotentialSalesPerPeriod,
            ];
        })->values();

        $maxTrendCost = $trendData->max('potential_sales') ?: 1;

        $monthlyProductCounts = $trendPeriodCandidates->map(function ($trendPeriod) use ($allTrendByPeriod, $resolveAssyLabel) {
            $items = $allTrendByPeriod->get($trendPeriod, collect());

            $count = $items
                ->map(function ($row) use ($resolveAssyLabel) {
                    return Str::lower($resolveAssyLabel($row));
                })
                ->filter(function ($label) {
                    return trim((string) $label) !== '';
                })
                ->unique()
                ->count();

            $label = $trendPeriod;
            if (preg_match('/^\d{4}-\d{2}$/', (string) $trendPeriod) === 1) {
                $label = \Carbon\Carbon::createFromFormat('Y-m', (string) $trendPeriod)->format('M y');
            }

            return [
                'period' => $trendPeriod,
                'period_label' => $label,
                'count' => $count,
            ];
        })->values();

        $maxMonthlyProducts = $monthlyProductCounts->max('count') ?: 1;

        // Get top 5 customers by revenue
        $topCustomers = $costingData
            ->groupBy('customer_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->customer->name ?? ('Customer #' . $items->first()->customer_id),
                    'revenue' => $items->sum('revenue'),
                ];
            })
            ->sortByDesc('revenue')
            ->take(5)
            ->values();

        $maxRevenue = $topCustomers->max('revenue') ?: 1;

        $businessCategorySales = $costingData
            ->groupBy(function ($item) use ($resolveBusinessCategoryLabel) {
                return $resolveBusinessCategoryLabel($item);
            })
            ->map(function ($items, $label) use ($resolvePotentialSales) {
                $materialCost = (float) $items->sum('material_cost');
                $laborCost = (float) $items->sum('labor_cost');
                $overheadCost = $items->sum(function ($row) {
                    return (float) $row->overhead_cost + (float) $row->scrap_cost;
                });
                $statusSummary = [
                    'a00_count' => 0, 'a00_potential' => 0.0,
                    'a04_count' => 0, 'a04_potential' => 0.0,
                    'a05_count' => 0, 'a05_potential' => 0.0,
                ];
                $potentialSources = [
                    'a00_sources' => [],
                    'a04_sources' => [],
                    'a05_sources' => [],
                    'all_sources' => [],
                ];
                foreach ($items as $row) {
                    $revision = $row->trackingRevision;
                    $potential = $resolvePotentialSales($row);
                    $cogm = (float) ($row->material_cost ?? 0)
                        + (float) ($row->labor_cost ?? 0)
                        + (float) ($row->overhead_cost ?? 0)
                        + (float) ($row->scrap_cost ?? 0);
                    $source = [
                        'customer' => $row->customer->name ?? ('Customer #' . $row->customer_id),
                        'project' => trim((string) ($row->assy_no ?? '')) ?: trim((string) ($row->assy_name ?? '')),
                        'model' => trim((string) ($row->model ?? '')),
                        'forecast' => (float) ($row->forecast ?? 0),
                        'product_life' => (float) ($row->project_period ?? 0),
                        'cogm' => $cogm,
                        'potential' => $potential,
                    ];
                    if (($revision?->a05 ?? null) === 'ada') {
                        $statusSummary['a05_count']++;
                        $statusSummary['a05_potential'] += $potential;
                        $source['status'] = 'A05';
                        $potentialSources['a05_sources'][] = $source;
                    } elseif (($revision?->a04 ?? null) === 'ada') {
                        $statusSummary['a04_count']++;
                        $statusSummary['a04_potential'] += $potential;
                        $source['status'] = 'A04';
                        $potentialSources['a04_sources'][] = $source;
                    } elseif (($revision?->a00 ?? null) === 'ada') {
                        $statusSummary['a00_count']++;
                        $statusSummary['a00_potential'] += $potential;
                        $source['status'] = 'A00';
                        $potentialSources['a00_sources'][] = $source;
                    }
                    $potentialSources['all_sources'][] = $source;
                }

                return [
                    'name' => $label,
                    'potential_sales' => $items->sum(function ($row) use ($resolvePotentialSales) {
                        return $resolvePotentialSales($row);
                    }),
                    'project_count' => $items->count(),
                    'material_cost' => $materialCost,
                    'labor_cost' => $laborCost,
                    'overhead_cost' => $overheadCost,
                    ...$statusSummary,
                    ...$potentialSources,
                ];
            })
            ->sortByDesc('potential_sales')
            ->values();

        $maxBusinessCategorySales = $businessCategorySales->max('potential_sales') ?: 1;

        $analysisMode = 'business_category';
        if ($modelFilter !== '' && $modelFilter !== 'all') {
            $analysisMode = 'assy_no';
        } elseif ($customerFilter !== '' && $customerFilter !== 'all') {
            $analysisMode = 'model';
        } elseif ($businessCategoryFilter !== '' && $businessCategoryFilter !== 'all') {
            $analysisMode = 'customer';
        }

        $analysisDimensionLabel = match ($analysisMode) {
            'assy_no' => 'Assy No',
            'model' => 'Model',
            'customer' => 'Customer',
            default => 'Business Category',
        };
        $showCustomerPerspective = $analysisMode === 'customer';

        $analysisSalesRows = $analysisMode === 'assy_no'
            ? $costingData
                ->groupBy(function ($item) {
                    $assyNo = trim((string) ($item->assy_no ?? ''));
                    return $assyNo !== '' ? $assyNo : '-';
                })
                ->map(function ($items, $assyNo) use ($resolvePotentialSales) {
                    $materialCost = (float) $items->sum('material_cost');
                    $laborCost = (float) $items->sum('labor_cost');
                    $overheadCost = $items->sum(function ($row) {
                        return (float) $row->overhead_cost + (float) $row->scrap_cost;
                    });

                    return [
                        'dimension_key' => (string) $assyNo,
                        'name' => (string) $assyNo,
                        'potential_sales' => $items->sum(function ($row) use ($resolvePotentialSales) {
                            return $resolvePotentialSales($row);
                        }),
                        'project_count' => $items->count(),
                        'material_cost' => $materialCost,
                        'labor_cost' => $laborCost,
                        'overhead_cost' => $overheadCost,
                    ];
                })
                ->sortByDesc('potential_sales')
                ->values()
            : ($analysisMode === 'model'
            ? $costingData
                ->groupBy(function ($item) {
                    $modelName = trim((string) ($item->model ?? ''));
                    return $modelName !== '' ? $modelName : '-';
                })
                ->map(function ($items, $modelName) use ($resolvePotentialSales) {
                    $materialCost = (float) $items->sum('material_cost');
                    $laborCost = (float) $items->sum('labor_cost');
                    $overheadCost = $items->sum(function ($row) {
                        return (float) $row->overhead_cost + (float) $row->scrap_cost;
                    });

                    return [
                        'dimension_key' => (string) $modelName,
                        'name' => (string) $modelName,
                        'potential_sales' => $items->sum(function ($row) use ($resolvePotentialSales) {
                            return $resolvePotentialSales($row);
                        }),
                        'project_count' => $items->count(),
                        'material_cost' => $materialCost,
                        'labor_cost' => $laborCost,
                        'overhead_cost' => $overheadCost,
                    ];
                })
                ->sortByDesc('potential_sales')
                ->values()
            : ($analysisMode === 'customer'
                ? $costingData
                    ->groupBy(function ($item) {
                        return (string) ($item->customer_id ?? '0');
                    })
                    ->map(function ($items, $customerId) use ($resolvePotentialSales) {
                        $materialCost = (float) $items->sum('material_cost');
                        $laborCost = (float) $items->sum('labor_cost');
                        $overheadCost = $items->sum(function ($row) {
                            return (float) $row->overhead_cost + (float) $row->scrap_cost;
                        });

                        return [
                            'dimension_key' => (string) $customerId,
                            'name' => $items->first()->customer->name ?? ('Customer #' . $customerId),
                            'potential_sales' => $items->sum(function ($row) use ($resolvePotentialSales) {
                                return $resolvePotentialSales($row);
                            }),
                            'project_count' => $items->count(),
                            'material_cost' => $materialCost,
                            'labor_cost' => $laborCost,
                            'overhead_cost' => $overheadCost,
                        ];
                    })
                    ->sortByDesc('potential_sales')
                    ->values()
                : $businessCategorySales
                    ->map(function ($item) {
                        return [
                            'dimension_key' => (string) ($item['name'] ?? ''),
                            'name' => (string) ($item['name'] ?? '-'),
                            'potential_sales' => (float) ($item['potential_sales'] ?? 0),
                            'project_count' => (int) ($item['project_count'] ?? 0),
                            'material_cost' => (float) ($item['material_cost'] ?? 0),
                            'labor_cost' => (float) ($item['labor_cost'] ?? 0),
                            'overhead_cost' => (float) ($item['overhead_cost'] ?? 0),
                            'a00_count' => (int) ($item['a00_count'] ?? 0),
                            'a00_potential' => (float) ($item['a00_potential'] ?? 0),
                            'a04_count' => (int) ($item['a04_count'] ?? 0),
                            'a04_potential' => (float) ($item['a04_potential'] ?? 0),
                            'a05_count' => (int) ($item['a05_count'] ?? 0),
                            'a05_potential' => (float) ($item['a05_potential'] ?? 0),
                            'a00_sources' => $item['a00_sources'] ?? [],
                            'a04_sources' => $item['a04_sources'] ?? [],
                            'a05_sources' => $item['a05_sources'] ?? [],
                            'all_sources' => $item['all_sources'] ?? [],
                        ];
                    })
                    ->values()));

        // Lengkapi breakdown status untuk seluruh mode analisis (business category,
        // customer, model, dan assy). Sebelumnya field ini hanya tersedia pada mode
        // business category sehingga kolom A00/A04/A05 tampil nol saat filter aktif.
        $analysisSalesRows = $analysisSalesRows->map(function ($dimensionRow) use (
            $costingData,
            $analysisMode,
            $resolveBusinessCategoryLabel,
            $resolvePotentialSales
        ) {
            $dimensionKey = (string) ($dimensionRow['dimension_key'] ?? '');
            $dimensionItems = $costingData->filter(function ($item) use ($analysisMode, $dimensionKey, $resolveBusinessCategoryLabel) {
                if ($analysisMode === 'assy_no') {
                    $assyNo = trim((string) ($item->assy_no ?? ''));
                    return ($assyNo !== '' ? $assyNo : '-') === $dimensionKey;
                }
                if ($analysisMode === 'model') {
                    $modelName = trim((string) ($item->model ?? ''));
                    return ($modelName !== '' ? $modelName : '-') === $dimensionKey;
                }
                if ($analysisMode === 'customer') {
                    return (string) ($item->customer_id ?? '') === $dimensionKey;
                }

                return $resolveBusinessCategoryLabel($item) === $dimensionKey;
            });

            $summary = [
                'a00_count' => 0, 'a00_potential' => 0.0, 'a00_sources' => [],
                'a04_count' => 0, 'a04_potential' => 0.0, 'a04_sources' => [],
                'a05_count' => 0, 'a05_potential' => 0.0, 'a05_sources' => [],
                'all_sources' => [],
            ];
            foreach ($dimensionItems as $item) {
                $revision = $item->trackingRevision;
                $statusKey = ($revision?->a05 ?? null) === 'ada' ? 'a05'
                    : ((($revision?->a04 ?? null) === 'ada') ? 'a04'
                        : ((($revision?->a00 ?? null) === 'ada') ? 'a00' : null));
                $potential = $resolvePotentialSales($item);
                $source = [
                    'customer' => $item->customer->name ?? ('Customer #' . $item->customer_id),
                    'project' => trim((string) ($item->assy_no ?? '')) ?: trim((string) ($item->assy_name ?? '')),
                    'model' => trim((string) ($item->model ?? '')),
                    'forecast' => (float) ($item->forecast ?? 0),
                    'product_life' => (float) ($item->project_period ?? 0),
                    'cogm' => (float) ($item->material_cost ?? 0) + (float) ($item->labor_cost ?? 0)
                        + (float) ($item->overhead_cost ?? 0) + (float) ($item->scrap_cost ?? 0),
                    'potential' => $potential,
                    'status' => $statusKey ? strtoupper($statusKey) : '-',
                ];
                if ($statusKey) {
                    $summary[$statusKey.'_count']++;
                    $summary[$statusKey.'_potential'] += $potential;
                    $summary[$statusKey.'_sources'][] = $source;
                }
                $summary['all_sources'][] = $source;
            }

            return array_merge($dimensionRow, $summary);
        })->values();

        $topCustomerPotentialSales = $costingData
            ->groupBy('customer_id')
            ->map(function ($items) {
                $customerName = $items->first()->customer->name ?? ('Customer #' . $items->first()->customer_id);
                $resolvePotentialSales = function ($row) {
                    $qtyPerMonth = (float) ($row->forecast ?? 0);
                    $productLifeYears = (float) ($row->project_period ?? 0);
                    $cogm = (float) ($row->material_cost ?? 0)
                        + (float) ($row->labor_cost ?? 0)
                        + (float) ($row->overhead_cost ?? 0)
                        + (float) ($row->scrap_cost ?? 0);

                    return $qtyPerMonth * $productLifeYears * $cogm;
                };

                $categoryBreakdown = $items
                    ->groupBy(function ($item) {
                        $line = trim((string) ($item->product->line ?? ''));
                        if ($line !== '') {
                            return $line;
                        }

                        $productName = trim((string) ($item->product->name ?? ''));
                        return $productName !== '' ? $productName : 'Uncategorized';
                    })
                    ->map(function ($categoryItems, $categoryName) {
                        $categoryPotentialSales = $categoryItems->sum(function ($row) {
                            $qtyPerMonth = (float) ($row->forecast ?? 0);
                            $productLifeYears = (float) ($row->project_period ?? 0);
                            $cogm = (float) ($row->material_cost ?? 0)
                                + (float) ($row->labor_cost ?? 0)
                                + (float) ($row->overhead_cost ?? 0)
                                + (float) ($row->scrap_cost ?? 0);

                            return $qtyPerMonth * $productLifeYears * $cogm;
                        });

                        return [
                            'category' => $categoryName,
                            'potential_sales' => $categoryPotentialSales,
                        ];
                    })
                    ->sortByDesc('potential_sales')
                    ->values();

                $dominantCategory = $categoryBreakdown->first();

                $statusCounts = [
                    'a00_count' => 0,
                    'a04_count' => 0,
                    'a05_count' => 0,
                ];
                $statusPotential = [
                    'a00_potential' => 0.0,
                    'a04_potential' => 0.0,
                    'a05_potential' => 0.0,
                ];
                foreach ($items as $item) {
                    $revision = $item->trackingRevision;
                    $itemPotential = $resolvePotentialSales($item);
                    if (($revision?->a05 ?? null) === 'ada') {
                        $statusCounts['a05_count']++;
                        $statusPotential['a05_potential'] += $itemPotential;
                    } elseif (($revision?->a04 ?? null) === 'ada') {
                        $statusCounts['a04_count']++;
                        $statusPotential['a04_potential'] += $itemPotential;
                    } elseif (($revision?->a00 ?? null) === 'ada') {
                        $statusCounts['a00_count']++;
                        $statusPotential['a00_potential'] += $itemPotential;
                    }
                }

                return [
                    'customer_name' => $customerName,
                    'business_category' => $dominantCategory['category'] ?? '-',
                    'potential_sales' => $items->sum(function ($row) use ($resolvePotentialSales) {
                        return $resolvePotentialSales($row);
                    }),
                    'a00_count' => $statusCounts['a00_count'],
                    'a04_count' => $statusCounts['a04_count'],
                    'a05_count' => $statusCounts['a05_count'],
                    'a00_potential' => $statusPotential['a00_potential'],
                    'a04_potential' => $statusPotential['a04_potential'],
                    'a05_potential' => $statusPotential['a05_potential'],
                ];
            })
            ->sortByDesc('potential_sales')
            ->take(5)
            ->values();

        // Material breakdown summary
        $materialBreakdown = $costPerProduct->map(function ($item) {
            $effectiveOverheadCost = (float) ($item['overhead_cost'] ?? 0);
            $materialCost = (float) ($item['material_cost'] ?? 0);
            $laborCost = (float) ($item['labor_cost'] ?? 0);
            $total = $materialCost + $laborCost + $effectiveOverheadCost;

            return [
                'name' => $item['name'] ?? '-',
                'material_pct' => $total > 0 ? ($materialCost / $total) * 100 : 0,
                'labor_pct' => $total > 0 ? ($laborCost / $total) * 100 : 0,
                'overhead_pct' => $total > 0 ? ($effectiveOverheadCost / $total) * 100 : 0,
            ];
        });

        // Count projects per business category broken down by status (A00, A04, A05)
        $projectCountPerCustomer = collect();
        foreach ($analysisSalesRows as $dimensionRow) {
            $dimensionKey = (string) ($dimensionRow['dimension_key'] ?? '');
            if ($dimensionKey === '') {
                continue;
            }

            $categoryItems = $costingData->filter(function ($item) use ($analysisMode, $dimensionKey, $resolveBusinessCategoryLabel) {
                if ($analysisMode === 'assy_no') {
                    $assyNo = trim((string) ($item->assy_no ?? ''));
                    return ($assyNo !== '' ? $assyNo : '-') === $dimensionKey;
                }

                if ($analysisMode === 'model') {
                    $modelName = trim((string) ($item->model ?? ''));
                    return ($modelName !== '' ? $modelName : '-') === $dimensionKey;
                }

                if ($analysisMode === 'customer') {
                    return (string) ($item->customer_id ?? '') === $dimensionKey;
                }

                return $resolveBusinessCategoryLabel($item) === $dimensionKey;
            })->values();

            $a00Count = 0;
            $a04Count = 0;
            $a05Count = 0;
            foreach ($categoryItems as $item) {
                $revision = $item->trackingRevision;
                if (!$revision) {
                    continue;
                }

                if (($revision->a05 ?? null) === 'ada') {
                    $a05Count++;
                } elseif (($revision->a04 ?? null) === 'ada') {
                    $a04Count++;
                } elseif (($revision->a00 ?? null) === 'ada') {
                    $a00Count++;
                }
            }

            $totalCount = $categoryItems->count();
            if ($totalCount > 0) {
                $projectCountPerCustomer->push([
                    'name' => (string) ($dimensionRow['name'] ?? '-'),
                    'a00_count' => $a00Count,
                    'a04_count' => $a04Count,
                    'a05_count' => $a05Count,
                    'total_count' => $totalCount,
                ]);
            }
        }
        $projectCountPerCustomer = $projectCountPerCustomer
            ->take(8)
            ->values();
        $maxProjectCount = $projectCountPerCustomer->max('total_count') ?: 1;

        return view('dashboard', compact(
            'period',
            'businessCategoryFilter',
            'customerFilter',
            'modelFilter',
            'businessCategories',
            'customers',
            'models',
            'costingData',
            'totalCost',
            'totalQty',
            'estimatedQtyProduksi',
            'avgCostPerUnit',
            'highestCostProduct',
            'costPerProduct',
            'maxCostPerUnit',
            'projectCountPerCustomer',
            'maxProjectCount',
            'trendData',
            'maxTrendCost',
            'monthlyProductCounts',
            'maxMonthlyProducts',
            'topCustomers',
            'maxRevenue',
            'businessCategorySales',
            'analysisSalesRows',
            'analysisDimensionLabel',
            'showCustomerPerspective',
            'maxBusinessCategorySales',
            'topCustomerPotentialSales',
            'materialBreakdown',
            'periods',
            'periodDisplayLabel',
            'trackingProjectCount',
            'costingProjectCount',
            'pendingFormCostingCount',
            'totalProjectCount',
            'a00ProjectCount',
            'a00ProjectEntryCount',
            'a04ProjectCount',
            'a05ProjectCount',
            'statusProjectData',
            'statusProjectTotal',
            'statusProjectPieGradient',
            'totalSubmitCostingMonthly',
            'monthlySubmitCounts',
            'maxMonthlySubmitCount'
        ));
    }

    private function roleDashboard(Request $request)
    {
        $user = $request->user();
        $role = (string) ($user->role ?? 'viewer');
        $latestRevisionIds = DocumentRevision::query()
            ->selectRaw('MAX(id)')
            ->whereNotNull('document_project_id')
            ->groupBy('document_project_id');
        $revisions = DocumentRevision::query()
            ->with(['project.product', 'costingData', 'latestSubmission'])
            ->whereIn('id', $latestRevisionIds);

        if ($role === 'marketing') {
            $revisions->whereRaw('LOWER(TRIM(pic_marketing)) = ?', [mb_strtolower(trim((string) $user->name))]);
        } elseif ($role === 'engineering') {
            $revisions->whereRaw('LOWER(TRIM(pic_engineering)) = ?', [mb_strtolower(trim((string) $user->name))]);
        }

        $roleRevisions = $revisions->get();
        $revisionIds = $roleRevisions->pluck('id');
        $openUnpriced = UnpricedPart::whereIn('document_revision_id', $revisionIds)->whereNull('resolved_at')->count();
        $waitingApproval = $roleRevisions->where('status', DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL)->count();
        $approved = $roleRevisions->where('status', DocumentRevision::STATUS_APPROVED_BY_COORDINATOR)->count();
        $submitted = $roleRevisions->where('status', DocumentRevision::STATUS_SUBMITTED_TO_MARKETING)->count();
        $activeCosting = $roleRevisions->whereIn('status', [
            DocumentRevision::STATUS_PENDING_FORM_INPUT,
            DocumentRevision::STATUS_SUDAH_COSTING,
            DocumentRevision::STATUS_PENDING_PRICING,
            DocumentRevision::STATUS_COGM_GENERATED,
            DocumentRevision::STATUS_REJECTED_BY_COORDINATOR,
        ])->count();
        $pendingPricing = $roleRevisions->where('status', DocumentRevision::STATUS_PENDING_PRICING)->count();
        $updatedSubmissions = CogmSubmission::whereIn('document_revision_id', $revisionIds)->whereNotNull('last_updated_at')->count();
        $totalSubmittedCogm = (float) CogmSubmission::whereIn('document_revision_id', $revisionIds)->sum('cogm_value');

        $profiles = [
            'admin_control_project' => ['title'=>'Dashboard Control Project','subtitle'=>'Pantau penerbitan A00 dan kesiapan project baru.','action'=>'Kelola A00','route'=>'control-project.a00.index'],
            'document_control' => ['title'=>'Dashboard Document Control','subtitle'=>'Pantau registrasi drawing dan distribusi dokumen project.','action'=>'Buka Inbox Document','route'=>'document-control.inbox'],
            'admin_costing' => ['title'=>'Dashboard Admin Costing','subtitle'=>'Fokus pada pekerjaan costing, harga kosong, dan submission.','action'=>'Buka Inbox Costing','route'=>'costing.inbox'],
            'coordinator_costing' => ['title'=>'Dashboard Coordinator Costing','subtitle'=>'Tinjau COGM yang menunggu approval dan siap dikirim.','action'=>'Periksa Approval','route'=>'costing.inbox'],
            'marketing' => ['title'=>'Dashboard Marketing','subtitle'=>'COGM yang ditujukan khusus untuk '.($user->name ?: 'PIC Marketing').'.','action'=>'Buka Inbox Marketing','route'=>'marketing.cogm-inbox'],
            'engineering' => ['title'=>'Dashboard Engineering','subtitle'=>'Project yang menjadi tanggung jawab '.($user->name ?: 'PIC Engineering').'.','action'=>'Lihat Project','route'=>'project'],
            'editor' => ['title'=>'Dashboard Editor Costing','subtitle'=>'Pantau data costing yang masih perlu dilengkapi.','action'=>'Buka Inbox Costing','route'=>'costing.inbox'],
            'viewer' => ['title'=>'Dashboard Project','subtitle'=>'Ringkasan status project dan COGM terbaru.','action'=>'Lihat Project','route'=>'project'],
        ];
        $profile = $profiles[$role] ?? $profiles['viewer'];

        $metrics = match ($role) {
            'admin_control_project' => [
                ['label'=>'Total Project','value'=>$roleRevisions->count(),'note'=>'Project aktual','tone'=>'blue'],
                ['label'=>'Dokumen A00','value'=>ProjectA00Form::count(),'note'=>'New Project Declaration','tone'=>'indigo'],
                ['label'=>'Menunggu Drawing','value'=>ProjectWorkflowTask::where('stage',ProjectWorkflowTask::STAGE_DRAWING)->whereIn('status',[ProjectWorkflowTask::STATUS_PENDING,ProjectWorkflowTask::STATUS_IN_PROGRESS])->count(),'note'=>'Perlu distribusi','tone'=>'orange'],
                ['label'=>'Dikirim Marketing','value'=>$submitted,'note'=>'Workflow selesai','tone'=>'green'],
            ],
            'document_control' => [
                ['label'=>'Inbox Drawing','value'=>ProjectWorkflowTask::where('stage',ProjectWorkflowTask::STAGE_DRAWING)->whereIn('status',[ProjectWorkflowTask::STATUS_PENDING,ProjectWorkflowTask::STATUS_IN_PROGRESS])->count(),'note'=>'Perlu diproses','tone'=>'orange'],
                ['label'=>'Drawing Selesai','value'=>ProjectWorkflowTask::where('stage',ProjectWorkflowTask::STAGE_DRAWING)->where('status',ProjectWorkflowTask::STATUS_COMPLETED)->count(),'note'=>'Sudah didistribusi','tone'=>'green'],
                ['label'=>'Total Project','value'=>$roleRevisions->count(),'note'=>'Revisi terbaru','tone'=>'blue'],
                ['label'=>'Masuk Costing','value'=>$roleRevisions->whereNotNull('costingData')->count(),'note'=>'Memiliki data costing','tone'=>'indigo'],
            ],
            'coordinator_costing' => [
                ['label'=>'Menunggu Approval','value'=>$waitingApproval,'note'=>'Perlu diperiksa','tone'=>'orange'],
                ['label'=>'Siap Dikirim','value'=>$approved,'note'=>'Sudah approved','tone'=>'blue'],
                ['label'=>'Perlu Revisi','value'=>$roleRevisions->where('status',DocumentRevision::STATUS_REJECTED_BY_COORDINATOR)->count(),'note'=>'Dikembalikan ke Costing','tone'=>'red'],
                ['label'=>'Dikirim Marketing','value'=>$submitted,'note'=>'Submission selesai','tone'=>'green'],
            ],
            'admin_costing', 'editor' => [
                ['label'=>'Costing Aktif','value'=>$activeCosting,'note'=>'Masih dikerjakan','tone'=>'blue'],
                ['label'=>'Part Tanpa Harga','value'=>$openUnpriced,'note'=>'Perlu dilengkapi','tone'=>'orange'],
                ['label'=>'Menunggu Approval','value'=>$waitingApproval,'note'=>'Sudah diajukan','tone'=>'indigo'],
                ['label'=>'Dikirim Marketing','value'=>$submitted,'note'=>'Workflow selesai','tone'=>'green'],
            ],
            'marketing' => [
                ['label'=>'COGM Masuk','value'=>$submitted,'note'=>'Untuk PIC '.$user->name,'tone'=>'blue'],
                ['label'=>'COGM Diperbarui','value'=>$updatedSubmissions,'note'=>'Ada revisi setelah submit','tone'=>'orange'],
                ['label'=>'Total Nilai COGM','value'=>$totalSubmittedCogm,'note'=>'Nilai submission terbaru','tone'=>'green','currency'=>true],
                ['label'=>'Total Project PIC','value'=>$roleRevisions->count(),'note'=>'Sesuai PIC Marketing','tone'=>'indigo'],
            ],
            'engineering' => [
                ['label'=>'Project Saya','value'=>$roleRevisions->count(),'note'=>'Sesuai PIC Engineering','tone'=>'blue'],
                ['label'=>'A00 Terbit','value'=>$roleRevisions->where('a00','ada')->count(),'note'=>'Project declaration','tone'=>'indigo'],
                ['label'=>'Costing Berjalan','value'=>$activeCosting,'note'=>'Masih diproses','tone'=>'orange'],
                ['label'=>'Dikirim Marketing','value'=>$submitted,'note'=>'Workflow selesai','tone'=>'green'],
            ],
            default => [
                ['label'=>'Project Aktif','value'=>$activeCosting,'note'=>'Masih dikerjakan','tone'=>'blue'],
                ['label'=>'Part Tanpa Harga','value'=>$openUnpriced,'note'=>'Belum resolved','tone'=>'orange'],
                ['label'=>'Menunggu Approval','value'=>$waitingApproval,'note'=>'Coordinator Costing','tone'=>'indigo'],
                ['label'=>'Dikirim Marketing','value'=>$submitted,'note'=>'Workflow selesai','tone'=>'green'],
            ],
        };

        $recentProjects = $roleRevisions
            ->sortByDesc(fn ($revision) => $revision->latestSubmission?->last_updated_at ?? $revision->updated_at)
            ->take(8)->values();

        return view('dashboard-role', compact('profile','metrics','recentProjects','role','openUnpriced','pendingPricing','waitingApproval','approved','submitted'));
    }

}
