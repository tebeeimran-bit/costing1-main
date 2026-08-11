<?php

namespace App\Http\Controllers;

use App\Models\CogmSubmission;
use App\Models\CostingData;
use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\DocumentRevision;
use App\Models\DocumentProject;
use App\Models\ExchangeRate;
use App\Models\MaterialBreakdown;
use App\Models\Product;
use App\Models\ProjectDocumentRevision;
use App\Models\WireRate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    /**
     * Resume COGM - Summary per project/customer
     */
    public function resumeCogm(Request $request)
    {
        $costings = CogmSubmission::with([
            'revision.project.product',
            'revision.costingData.customer',
            'revision.costingData.product',
            'revision.costingData.materialBreakdowns',
        ])->orderByDesc('submitted_at')->get()
            ->unique('document_revision_id')
            ->map(function (CogmSubmission $submission) {
                $revision = $submission->revision;
                $project = $revision?->project;
                $item = $revision?->costingData;
                if (!$revision || !$project || !$item) {
                    return null;
                }

                // Nilai utama wajib mengikuti submission Marketing. Field ini juga
                // diperbarui ketika History Costing diedit dan disimpan kembali.
                $cogm = (float) ($submission->cogm_value ?? 0);

                $forecast = (float) ($item->forecast ?? 0);
                $period = (float) ($item->project_period ?? 0);
                $potential = $forecast * $period * $cogm;

                $materialRows = $item->materialBreakdowns;

                $missingPartCount = $materialRows
                    ->filter(function ($row) {
                        return (float) ($row->amount1 ?? 0) <= 0;
                    })
                    ->map(function ($row, $index) {
                        $partNo = trim((string) ($row->part_no ?? ''));

                        return $partNo !== '' ? strtoupper($partNo) : ('ROW-' . ($index + 1));
                    })
                    ->unique()
                    ->count();

                $estimatePartCount = $materialRows
                    ->filter(function ($row) {
                        return strtoupper(trim((string) ($row->cn_type ?? ''))) === 'E';
                    })
                    ->map(function ($row, $index) {
                        $partNo = trim((string) ($row->part_no ?? ''));

                        return $partNo !== '' ? strtoupper($partNo) : ('ROW-' . ($index + 1));
                    })
                    ->unique()
                    ->count();

                $cycleTimeRows = collect($item->cycle_times ?? [])
                    ->filter(function ($row) {
                        return trim((string) data_get($row, 'process', '')) !== ''
                            || (float) data_get($row, 'qty', 0) > 0
                            || (float) data_get($row, 'time_hour', 0) > 0
                            || (float) data_get($row, 'time_sec', 0) > 0;
                    })
                    ->values();

                $cycleTimeIncomplete = $cycleTimeRows->isEmpty()
                    || $cycleTimeRows->contains(function ($row) {
                        $process = trim((string) data_get($row, 'process', ''));
                        $qty = (float) data_get($row, 'qty', 0);
                        $timeHour = (float) data_get($row, 'time_hour', 0);
                        $timeSec = (float) data_get($row, 'time_sec', 0);

                        return $process === '' || $qty <= 0 || ($timeHour <= 0 && $timeSec <= 0);
                    });

                /*
                 * Di form saat ini field overhead_cost dipakai sebagai Depresiasi Tooling Cost.
                 * Maka Full Price tidak boleh true kalau Depresiasi Tooling Cost masih kosong / 0.
                 */
                $toolingDepreciationIncomplete = (float) ($item->overhead_cost ?? 0) <= 0;

                $trackingRevisionId = $revision->id;

                return (object) [
                    'id' => $submission->id,
                    'tracking_revision_id' => $trackingRevisionId,
                    'form_url' => route('marketing.cogm-costing.show', $submission, false),
                    'customer' => $item->customer?->code ?? $item->customer?->name ?? $project->customer ?? '-',
                    'model' => $project->model ?: ($item->model ?: '-'),
                    'assy_name' => $project->part_name ?: ($item->assy_name ?: '-'),
                    'assy_no' => $project->part_number ?: ($item->assy_no ?: '-'),
                    'period' => $submission->submitted_at?->format('Y-m') ?? '-',
                    'material' => (float) $item->material_cost,
                    'labor' => (float) $item->labor_cost,
                    'overhead' => (float) $item->overhead_cost,
                    'scrap' => 0,
                    'cogm' => $cogm,
                    'forecast' => $forecast,
                    'project_period' => $period,
                    'potential' => $potential,
                    'status' => $submission->last_updated_at ? 'Updated' : 'Submitted',
                    'line' => $project->product?->line ?? $item->product?->line ?? $item->line ?? '-',
                    'submitted_at' => $submission->submitted_at,
                    'last_updated_at' => $submission->last_updated_at,
                    'last_updated_by' => $submission->last_updated_by,
                    'update_count' => (int) $submission->update_count,
                    'missing_part_count' => $missingPartCount,
                    'estimate_part_count' => $estimatePartCount,
                    'cycle_time_incomplete' => $cycleTimeIncomplete,
                    'tooling_depreciation_incomplete' => $toolingDepreciationIncomplete,
                    'is_full_price' => $missingPartCount <= 0
                        && $estimatePartCount <= 0
                        && !$cycleTimeIncomplete
                        && !$toolingDepreciationIncomplete,
                ];
            })
            ->filter()
            ->sortByDesc(fn ($item) => $item->last_updated_at ?? $item->submitted_at)
            ->values();

        $byCustomer = $costings->groupBy('customer')
            ->map(function ($items, $name) {
                return (object) [
                    'customer' => $name,
                    'count' => $items->count(),
                    'total_cogm' => $items->sum('cogm'),
                    'total_potential' => $items->sum('potential'),
                ];
            })
            ->sortByDesc('total_potential')
            ->values();

        $totalProjects = $costings->count();
        $totalCogm = $costings->sum('cogm');
        $totalPotential = $costings->sum('potential');

        $customerPage = max((int) $request->query('customer_page', 1), 1);
        $projectPage = max((int) $request->query('project_page', 1), 1);
        $customerPerPage = 10;
        $projectPerPage = 10;

        $paginateCollection = function (Collection $items, int $perPage, int $page, string $pageName) use ($request) {
            $pageItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

            return new LengthAwarePaginator(
                $pageItems,
                $items->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                    'pageName' => $pageName,
                ]
            );
        };

        $customerSummary = $paginateCollection($byCustomer, $customerPerPage, $customerPage, 'customer_page');
        $projectDetails = $paginateCollection($costings, $projectPerPage, $projectPage, 'project_page');

        return view('reports.resume-cogm', compact(
            'costings',
            'byCustomer',
            'customerSummary',
            'projectDetails',
            'totalProjects',
            'totalCogm',
            'totalPotential'
        ));
    }

    /**
     * Analisis Tren - Project funnel A00 -> A04/A05.
     */
    public function analisisTren(Request $request)
    {
        $filters = $this->buildTrendFilters($request);
        $revisions = $this->getTrendRevisions($filters);
        $rows = $this->buildTrendProjectRows($revisions);

        $totalA00 = $rows->where('has_a00', true)->count();
        $totalA04 = $rows->where('has_a04', true)->count();
        $totalA05 = $rows->where('has_a05', true)->count();
        $stillA00 = max(0, $totalA00 - $totalA04 - $totalA05);

        $conversionRate = $totalA00 > 0 ? ($totalA05 / $totalA00 * 100) : 0;
        $cancellationRate = $totalA00 > 0 ? ($totalA04 / $totalA00 * 100) : 0;

        $summary = (object) [
            'total_project_masuk' => $totalA00,
            'total_a00' => $totalA00,
            'total_a04' => $totalA04,
            'total_a05' => $totalA05,
            'still_a00' => $stillA00,
            'conversion_rate' => $conversionRate,
            'cancellation_rate' => $cancellationRate,
        ];

        $periods = $rows
            ->pluck('period')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $businessModels = $rows
            ->pluck('business_model')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $statusByBusinessModel = $rows
            ->groupBy('business_model')
            ->map(function ($items, $businessModel) use ($periods) {
                $periodData = $periods->mapWithKeys(function ($period) use ($items) {
                    $periodItems = $items->where('period', $period);
                    $a00 = $periodItems->where('has_a00', true)->count();
                    $a04 = $periodItems->where('has_a04', true)->count();
                    $a05 = $periodItems->where('has_a05', true)->count();

                    return [$period => (object) [
                        'a00' => $a00,
                        'a04' => $a04,
                        'a05' => $a05,
                    ]];
                });

                $totalA00 = $items->where('has_a00', true)->count();
                $totalA05 = $items->where('has_a05', true)->count();

                return (object) [
                    'business_model' => $businessModel,
                    'periods' => $periodData,
                    'conversion_rate' => $totalA00 > 0 ? ($totalA05 / $totalA00 * 100) : 0,
                ];
            })
            ->sortBy('business_model')
            ->values();

        $trendByPeriod = $periods
            ->map(function ($period) use ($rows) {
                $items = $rows->where('period', $period);
                $a00 = $items->where('has_a00', true)->count();
                $a04 = $items->where('has_a04', true)->count();
                $a05 = $items->where('has_a05', true)->count();

                return (object) [
                    'period' => $period,
                    'a00' => $a00,
                    'a04' => $a04,
                    'a05' => $a05,
                    'conversion_rate' => $a00 > 0 ? ($a05 / $a00 * 100) : 0,
                    'cancellation_rate' => $a00 > 0 ? ($a04 / $a00 * 100) : 0,
                ];
            })
            ->values();

        $filterOptions = $this->buildTrendFilterOptions();

        return view('reports.analisis-tren', compact(
            'filters',
            'filterOptions',
            'summary',
            'periods',
            'statusByBusinessModel',
            'trendByPeriod'
        ));
    }

    /**
     * Detail alasan A04 Canceled/Failed.
     */
    public function analisisTrenCanceled(Request $request)
    {
        $filters = $this->buildTrendFilters($request);
        $revisions = $this->getTrendRevisions($filters);
        $rows = $this->buildTrendProjectRows($revisions)
            ->where('has_a04', true)
            ->values();

        $reasonSummary = $rows
            ->groupBy('a04_reason')
            ->map(function ($items, $reason) use ($rows) {
                $count = $items->count();

                return (object) [
                    'reason' => $reason ?: 'Belum ada alasan',
                    'count' => $count,
                    'percentage' => $rows->count() > 0 ? ($count / $rows->count() * 100) : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();

        $dominantReason = $reasonSummary->first();

        $businessModelSummary = $rows
            ->groupBy('business_model')
            ->map(function ($items, $businessModel) use ($rows) {
                $count = $items->count();

                return (object) [
                    'business_model' => $businessModel ?: '-',
                    'count' => $count,
                    'percentage' => $rows->count() > 0 ? ($count / $rows->count() * 100) : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();

        $dominantBusinessModel = $businessModelSummary->first();

        $detailRows = $rows
            ->sortByDesc('a04_date')
            ->values();

        $summary = (object) [
            'total_a04' => $rows->count(),
            'dominant_reason' => $dominantReason?->reason ?? '-',
            'dominant_reason_count' => $dominantReason?->count ?? 0,
            'dominant_reason_percentage' => $dominantReason?->percentage ?? 0,
            'dominant_business_model' => $dominantBusinessModel?->business_model ?? '-',
            'dominant_business_model_count' => $dominantBusinessModel?->count ?? 0,
            'dominant_business_model_percentage' => $dominantBusinessModel?->percentage ?? 0,
            'period_label' => $this->buildTrendPeriodLabel($filters, $detailRows),
        ];

        $filterOptions = $this->buildTrendFilterOptions();

        return view('reports.analisis-tren-canceled', compact(
            'filters',
            'filterOptions',
            'summary',
            'reasonSummary',
            'businessModelSummary',
            'detailRows'
        ));
    }

    private function buildTrendFilters(Request $request): array
    {
        return [
            'period_from' => trim((string) $request->input('period_from', '')),
            'period_to' => trim((string) $request->input('period_to', '')),
            'business_model' => trim((string) $request->input('business_model', '')),
            'customer' => trim((string) $request->input('customer', '')),
            'model' => trim((string) $request->input('model', '')),
        ];
    }

    private function getTrendRevisions(array $filters)
    {
        $query = DocumentRevision::query()
            ->with(['project.product'])
            ->orderBy('received_date')
            ->orderBy('id');

        if ($filters['period_from'] !== '') {
            $query->whereDate('received_date', '>=', $filters['period_from'] . '-01');
        }

        if ($filters['period_to'] !== '') {
            $query->whereDate('received_date', '<=', date('Y-m-t', strtotime($filters['period_to'] . '-01')));
        }

        if ($filters['business_model'] !== '') {
            $query->whereHas('project.product', function ($q) use ($filters) {
                $q->where('line', $filters['business_model'])
                    ->orWhere('name', $filters['business_model'])
                    ->orWhere('code', $filters['business_model']);
            });
        }

        if ($filters['customer'] !== '') {
            $query->whereHas('project', function ($q) use ($filters) {
                $q->where('customer', $filters['customer']);
            });
        }

        if ($filters['model'] !== '') {
            $query->whereHas('project', function ($q) use ($filters) {
                $q->where('model', $filters['model']);
            });
        }

        return $query->get();
    }

    private function buildTrendProjectRows($revisions)
    {
        $hasA04Reason = Schema::hasColumn('document_revisions', 'a04_reason');
        $hasA04ReasonNote = Schema::hasColumn('document_revisions', 'a04_reason_note');

        return $revisions->map(function (DocumentRevision $revision) use ($hasA04Reason, $hasA04ReasonNote) {
            $project = $revision->project;
            $businessModel = $project?->product?->line
                ?: $project?->product?->name
                ?: $project?->product?->code
                ?: '-';

            $a04Reason = $hasA04Reason ? trim((string) ($revision->a04_reason ?? '')) : '';
            if ($a04Reason === '') {
                $a04Reason = $this->inferA04Reason($revision);
            }

            return (object) [
                'revision_id' => $revision->id,
                'period' => optional($revision->received_date)->format('Y-m') ?: '-',
                'customer' => $project?->customer ?? '-',
                'business_model' => $businessModel,
                'model' => $project?->model ?? '-',
                'assy_no' => $project?->part_number ?? '-',
                'assy_name' => $project?->part_name ?? '-',
                'pic' => $revision->pic_engineering ?: ($revision->pic_marketing ?: '-'),
                'has_a00' => ($revision->a00 ?? null) === 'ada',
                'has_a04' => ($revision->a04 ?? null) === 'ada',
                'has_a05' => ($revision->a05 ?? null) === 'ada',
                'a00_date' => $revision->a00_received_date,
                'a04_date' => $revision->a04_received_date,
                'a05_date' => $revision->a05_received_date,
                'a04_reason' => $a04Reason,
                'a04_reason_note' => $hasA04ReasonNote ? trim((string) ($revision->a04_reason_note ?? '')) : '',
            ];
        })->values();
    }

    private function inferA04Reason(DocumentRevision $revision): string
    {
        $text = trim((string) ($revision->notes ?: $revision->change_remark ?: ''));

        if ($text === '') {
            return 'Belum ada alasan';
        }

        $lower = Str::lower($text);

        return match (true) {
            str_contains($lower, 'harga') || str_contains($lower, 'price') || str_contains($lower, 'cost') => 'Harga tidak kompetitif',
            str_contains($lower, 'customer') || str_contains($lower, 'cancel') => 'Customer cancel project',
            str_contains($lower, 'spec') || str_contains($lower, 'spesifikasi') || str_contains($lower, 'drawing') => 'Spesifikasi berubah',
            str_contains($lower, 'feasible') || str_contains($lower, 'kapasitas') || str_contains($lower, 'produksi') => 'Tidak feasible produksi',
            str_contains($lower, 'volume') || str_contains($lower, 'qty') || str_contains($lower, 'moq') => 'Volume tidak sesuai',
            str_contains($lower, 'lead') || str_contains($lower, 'delivery') || str_contains($lower, 'time') => 'Lead time tidak sesuai',
            default => $text,
        };
    }

    private function buildTrendFilterOptions(): array
    {
        $periods = DocumentRevision::query()
            ->whereNotNull('received_date')
            ->orderBy('received_date')
            ->get()
            ->map(fn ($revision) => optional($revision->received_date)->format('Y-m'))
            ->filter()
            ->unique()
            ->values();

        $businessModels = Product::query()
            ->get()
            ->map(fn ($product) => $product->line ?: $product->name ?: $product->code)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $customers = \App\Models\DocumentProject::query()
            ->select('customer')
            ->whereNotNull('customer')
            ->orderBy('customer')
            ->pluck('customer')
            ->filter()
            ->unique()
            ->values();

        $models = \App\Models\DocumentProject::query()
            ->select('model')
            ->whereNotNull('model')
            ->orderBy('model')
            ->pluck('model')
            ->filter()
            ->unique()
            ->values();

        return compact('periods', 'businessModels', 'customers', 'models');
    }

    private function buildTrendPeriodLabel(array $filters, $rows): string
    {
        if ($filters['period_from'] !== '' || $filters['period_to'] !== '') {
            return ($filters['period_from'] ?: 'Awal') . ' - ' . ($filters['period_to'] ?: 'Akhir');
        }

        $periods = $rows->pluck('period')->filter()->unique()->sort()->values();

        if ($periods->isEmpty()) {
            return '-';
        }

        if ($periods->count() === 1) {
            return $periods->first();
        }

        return $periods->first() . ' - ' . $periods->last();
    }

    /**
     * Analisis Tren - Detail Dokumen Engineering Partlist & UMH.
     * Halaman ini adalah halaman tambahan, bukan pengganti halaman Analisis Tren utama.
     */
    public function analisisTrenEngineering(Request $request)
    {
        $filters = $this->buildTrendFilters($request);
        $revisions = $this->getTrendRevisions($filters);
        $rows = $this->buildEngineeringDocumentRows($revisions);

        $totalProject = $rows->count();

        $projectSudahPartlist = $rows->where('has_partlist', true)->count();
        $projectBelumPartlist = max(0, $totalProject - $projectSudahPartlist);
        $totalRevisiPartlist = $rows->sum('partlist_revision_count');

        $projectSudahUmh = $rows->where('has_umh', true)->count();
        $projectBelumUmh = max(0, $totalProject - $projectSudahUmh);
        $totalRevisiUmh = $rows->sum('umh_revision_count');

        $dokumenLengkap = $rows
            ->where('has_partlist', true)
            ->where('has_umh', true)
            ->count();

        $sudahCosting = $rows->where('has_costing', true)->count();

        $summary = (object) [
            'total_project' => $totalProject,
            'project_sudah_partlist' => $projectSudahPartlist,
            'project_belum_partlist' => $projectBelumPartlist,
            'total_revisi_partlist' => $totalRevisiPartlist,
            'project_sudah_umh' => $projectSudahUmh,
            'project_belum_umh' => $projectBelumUmh,
            'total_revisi_umh' => $totalRevisiUmh,
            'dokumen_lengkap' => $dokumenLengkap,
            'sudah_costing' => $sudahCosting,
            'kelengkapan_rate' => $totalProject > 0 ? ($dokumenLengkap / $totalProject * 100) : 0,
        ];

        $periods = $rows
            ->pluck('period')
            ->filter(fn ($period) => $period && $period !== '-')
            ->unique()
            ->sort()
            ->values();

        $trendPartlist = $periods->map(function ($period) use ($rows) {
            $periodRows = $rows->where('period', $period);

            return (object) [
                'period' => $period,
                'partlist_masuk' => $periodRows->where('has_partlist', true)->count(),
                'revisi_partlist' => $periodRows->sum('partlist_revision_count'),
            ];
        })->values();

        $trendUmh = $periods->map(function ($period) use ($rows) {
            $periodRows = $rows->where('period', $period);

            return (object) [
                'period' => $period,
                'umh_masuk' => $periodRows->where('has_umh', true)->count(),
                'revisi_umh' => $periodRows->sum('umh_revision_count'),
            ];
        })->values();

        $topRevisionProjects = $rows
            ->sortByDesc(fn ($row) => $row->partlist_revision_count + $row->umh_revision_count)
            ->take(8)
            ->values();

        $bottleneckProjects = $rows
            ->filter(fn ($row) => $row->bottleneck_status !== 'Sudah Costing' || $row->partlist_revision_count > 1 || $row->umh_revision_count > 1)
            ->sortByDesc(fn ($row) => $row->bottleneck_priority)
            ->take(8)
            ->values();

        $insights = $this->buildEngineeringInsights($rows, $summary);

        $filterOptions = $this->buildTrendFilterOptions();

        return view('reports.analisis-tren-engineering', compact(
            'filters',
            'filterOptions',
            'summary',
            'periods',
            'trendPartlist',
            'trendUmh',
            'topRevisionProjects',
            'bottleneckProjects',
            'insights'
        ));
    }

    private function buildEngineeringDocumentRows($revisions)
    {
        $revisionHistoryCounts = ProjectDocumentRevision::query()
            ->whereIn('document_revision_id', $revisions->pluck('id')->filter()->values())
            ->whereIn('revision_type', ['partlist', 'umh'])
            ->selectRaw('document_revision_id, revision_type, COUNT(*) as revision_count')
            ->groupBy('document_revision_id', 'revision_type')
            ->get()
            ->groupBy('document_revision_id')
            ->map(fn ($counts) => $counts->pluck('revision_count', 'revision_type'));

        $costingsByRevision = CostingData::query()
            ->whereIn('tracking_revision_id', $revisions->pluck('id')->filter()->values())
            ->get()
            ->keyBy('tracking_revision_id');

        return $revisions->map(function (DocumentRevision $revision) use ($costingsByRevision, $revisionHistoryCounts) {
            $project = $revision->project;
            $costing = $costingsByRevision->get($revision->id);

            $businessModel = $project?->product?->line
                ?: $project?->product?->name
                ?: $project?->product?->code
                ?: '-';

            $hasPartlist = filled($revision->partlist_file_path) || filled($revision->partlist_original_name);
            $hasUmh = filled($revision->umh_file_path) || filled($revision->umh_original_name);
            $hasCosting = (bool) $costing || ($revision->status === DocumentRevision::STATUS_SUDAH_COSTING);

            // An initial Partlist/UMH upload is document receipt, not a revision.
            // Only explicit entries in project_document_revisions are revisions.
            $documentRevisionCounts = $revisionHistoryCounts->get($revision->id, collect());
            $partlistRevisionCount = max(0, (int) $documentRevisionCounts->get('partlist', 0));
            $umhRevisionCount = max(0, (int) $documentRevisionCounts->get('umh', 0));

            $lastUpdateCandidates = collect([
                $revision->partlist_updated_at,
                $revision->umh_updated_at,
                $revision->updated_at,
            ])->filter();

            $lastUpdatedAt = $lastUpdateCandidates->sortDesc()->first();

            $bottleneckStatus = 'Sudah Costing';
            $bottleneckStage = 'Dalam Proses Costing';
            $bottleneckPriority = 1;

            if (! $hasPartlist) {
                $bottleneckStatus = 'Belum Partlist';
                $bottleneckStage = 'Menunggu Partlist';
                $bottleneckPriority = 50;
            } elseif (! $hasUmh) {
                $bottleneckStatus = 'Menunggu UMH';
                $bottleneckStage = 'Menunggu UMH';
                $bottleneckPriority = 45;
            } elseif ($partlistRevisionCount > 1 || $umhRevisionCount > 1) {
                $bottleneckStatus = 'Revisi Berlangsung';
                $bottleneckStage = $partlistRevisionCount >= $umhRevisionCount ? 'Partlist Revisi' : 'UMH Revisi';
                $bottleneckPriority = 35 + $partlistRevisionCount + $umhRevisionCount;
            } elseif (! $hasCosting) {
                $bottleneckStatus = 'Sudah dokumen lengkap';
                $bottleneckStage = 'Dokumen Lengkap';
                $bottleneckPriority = 25;
            }

            $durationDays = null;
            if ($lastUpdatedAt && $revision->received_date) {
                $durationDays = $revision->received_date->diffInDays($lastUpdatedAt, false);
                $durationDays = $durationDays < 0 ? null : $durationDays;
            }

            return (object) [
                'revision_id' => $revision->id,
                'period' => optional($revision->received_date)->format('Y-m') ?: '-',
                'customer' => $project?->customer ?? '-',
                'business_model' => $businessModel,
                'model' => $project?->model ?? '-',
                'assy_no' => $project?->part_number ?? '-',
                'assy_name' => $project?->part_name ?? '-',
                'has_partlist' => $hasPartlist,
                'has_umh' => $hasUmh,
                'has_costing' => $hasCosting,
                'partlist_revision_count' => $partlistRevisionCount,
                'umh_revision_count' => $umhRevisionCount,
                'partlist_updated_at' => $revision->partlist_updated_at,
                'umh_updated_at' => $revision->umh_updated_at,
                'last_updated_at' => $lastUpdatedAt,
                'bottleneck_stage' => $bottleneckStage,
                'bottleneck_status' => $bottleneckStatus,
                'bottleneck_priority' => $bottleneckPriority,
                'duration_days' => $durationDays,
            ];
        })->values();
    }

    private function buildEngineeringInsights($rows, object $summary)
    {
        $insights = collect();

        $topPartlist = $rows->sortByDesc('partlist_revision_count')->first();
        if ($topPartlist && $topPartlist->partlist_revision_count > 0) {
            $insights->push((object) [
                'color' => '#2563eb',
                'text' => 'Project ' . $topPartlist->model . ' mengalami revisi partlist ' . $topPartlist->partlist_revision_count . 'x.',
            ]);
        }

        $topUmh = $rows->sortByDesc('umh_revision_count')->first();
        if ($topUmh && $topUmh->umh_revision_count > 0) {
            $insights->push((object) [
                'color' => '#7c3aed',
                'text' => 'Revisi UMH tertinggi terjadi pada model ' . $topUmh->model . ' (' . $topUmh->umh_revision_count . ' revisi).',
            ]);
        }

        $waitingUmh = $rows->where('bottleneck_status', 'Menunggu UMH')->count();
        if ($waitingUmh > 0) {
            $insights->push((object) [
                'color' => '#f97316',
                'text' => $waitingUmh . ' project masih menunggu dokumen UMH.',
            ]);
        }

        $missingPartlist = $rows->where('bottleneck_status', 'Belum Partlist')->count();
        if ($missingPartlist > 0) {
            $insights->push((object) [
                'color' => '#ef4444',
                'text' => $missingPartlist . ' project belum menerima dokumen partlist.',
            ]);
        }

        $insights->push((object) [
            'color' => '#059669',
            'text' => 'Tingkat kelengkapan dokumen Partlist & UMH mencapai ' . number_format($summary->kelengkapan_rate, 1, ',', '.') . '%.',
        ]);

        return $insights->take(5)->values();
    }

    /**
     * Rate & Kurs management
     */
    public function rateKurs()
    {
        $exchangeRates = ExchangeRate::orderByDesc('period_date')->get();
        $wireRates = WireRate::orderByDesc('period_month')->get();

        return view('reports.rate-kurs', compact('exchangeRates', 'wireRates'));
    }

    public function storeExchangeRate(Request $request)
    {
        $validated = $this->validateExchangeRate($request);
        ExchangeRate::create($validated);

        return back()->with('success', 'Exchange rate berhasil ditambahkan.');
    }

    public function updateExchangeRate(Request $request, $id)
    {
        $exchangeRate = ExchangeRate::findOrFail($id);
        $exchangeRate->update($this->validateExchangeRate($request));

        return back()->with('success', 'Exchange rate berhasil diperbarui.');
    }

    public function destroyExchangeRate($id)
    {
        ExchangeRate::findOrFail($id)->delete();
        return back()->with('success', 'Exchange rate berhasil dihapus.');
    }

    private function validateExchangeRate(Request $request): array
    {
        $validated = $request->validate([
            'period_date' => 'required|date',
            'usd_to_idr' => 'nullable|numeric',
            'jpy_to_idr' => 'nullable|numeric',
            'lme_copper' => 'nullable|numeric',
            'source' => 'nullable|string|max:100',
        ]);
        $validated['source'] = filled($validated['source'] ?? null)
            ? trim((string) $validated['source'])
            : 'Manual';

        return $validated;
    }

    /**
     * COGM Submission / Approval
     */
    public function cogmSubmissions()
    {
        $submissions = CogmSubmission::with(['revision.project'])
            ->orderByDesc('submitted_at')
            ->get()
            ->map(function ($sub) {
                // Find CostingData that references this revision
                $costing = CostingData::with('customer')
                    ->where('tracking_revision_id', $sub->document_revision_id)
                    ->first();
                return (object)[
                    'id' => $sub->id,
                    'customer' => $costing?->customer?->name ?? '-',
                    'model' => $costing?->model ?? '-',
                    'assy_name' => $costing?->assy_name ?? '-',
                    'cogm_value' => (float) $sub->cogm_value,
                    'submitted_by' => $sub->submitted_by ?? '-',
                    'pic_marketing' => $sub->pic_marketing ?? '-',
                    'submitted_at' => $sub->submitted_at,
                    'notes' => $sub->notes,
                    'revision_id' => $sub->document_revision_id,
                ];
            });

        $totalSubmissions = $submissions->count();
        $totalCogmValue = $submissions->sum('cogm_value');

        return view('reports.cogm-submissions', compact('submissions', 'totalSubmissions', 'totalCogmValue'));
    }

    /**
     * Laporan & Export
     */
    public function laporan()
    {
        $projectRows = $this->projectCostingReportRows();
        $costingsByCustomer = $this->aggregateProjectCosts($projectRows, 'customer', 'customer');
        $costingsByCategory = $this->aggregateProjectCosts($projectRows, 'category', 'category');

        return view('reports.laporan', compact('projectRows', 'costingsByCustomer', 'costingsByCategory'));
    }

    public function exportLaporan()
    {
        $projectRows = $this->projectCostingReportRows();
        $byCustomer = $this->aggregateProjectCosts($projectRows, 'customer', 'customer');
        $byCategory = $this->aggregateProjectCosts($projectRows, 'category', 'category');
        $spreadsheet = new Spreadsheet();

        $detail = $spreadsheet->getActiveSheet();
        $detail->setTitle('Detail Project');
        $detail->fromArray(['PROJECT', 'CUSTOMER', 'MODEL', 'NO. ASSY', 'REV.', 'BUSINESS CATEGORY', 'STATUS', 'MATERIAL', 'LABOR', 'OVERHEAD', 'TOTAL COGM'], null, 'A1');
        $row = 2;
        foreach ($projectRows as $item) {
            $detail->fromArray([
                $item->project, $item->customer, $item->model, $item->assy_no, $item->revision,
                $item->category, $item->status, $item->material, $item->labor,
                $item->overhead, $item->cogm,
            ], null, 'A'.$row++);
        }

        $this->fillReportSummarySheet($spreadsheet->createSheet(), 'Rekap Customer', 'CUSTOMER', $byCustomer, 'customer');
        $this->fillReportSummarySheet($spreadsheet->createSheet(), 'Rekap Kategori', 'BUSINESS CATEGORY', $byCategory, 'category');

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheet->freezePane('A2');
            $sheet->getStyle('A1:L1')->getFont()->setBold(true);
            $sheet->getStyle('A1:L1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFDCE9F9');
            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            if ($sheet->getHighestRow() >= 2) {
                $sheet->getStyle('H2:L'.$sheet->getHighestRow())->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }

        $filename = 'Laporan-Project-COGM-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function projectCostingReportRows(): Collection
    {
        return DocumentProject::with([
            'product',
            'revisions' => fn ($query) => $query->orderByDesc('version_number')->orderByDesc('id'),
            'revisions.costingData',
        ])->orderBy('customer')->orderBy('model')->orderBy('part_number')->get()
            ->map(function (DocumentProject $project) {
                $revision = $project->revisions->first();
                $costing = $revision?->costingData;
                $material = (float) ($costing?->material_cost ?? 0);
                $labor = (float) ($costing?->labor_cost ?? 0);
                $overhead = (float) ($costing?->overhead_cost ?? 0);

                return (object) [
                    'project' => $project->part_name ?: '-',
                    'customer' => $project->customer ?: 'Belum ditentukan',
                    'model' => $project->model ?: '-',
                    'assy_no' => $project->part_number ?: '-',
                    'revision' => $revision?->version_label ?: '-',
                    'category' => $project->product?->line ?: ($project->product?->name ?: 'Belum ditentukan'),
                    'status' => $revision?->status_label ?: 'Belum ada revisi',
                    'material' => $material,
                    'labor' => $labor,
                    'overhead' => $overhead,
                    'cogm' => $material + $labor + $overhead,
                ];
            })->values();
    }

    private function aggregateProjectCosts(Collection $rows, string $groupField, string $labelField): Collection
    {
        return $rows->groupBy($groupField)->map(function (Collection $items, string $label) use ($labelField) {
            return (object) [
                $labelField => $label,
                'projects' => $items->count(),
                'material' => $items->sum('material'),
                'labor' => $items->sum('labor'),
                'overhead' => $items->sum('overhead'),
                'cogm' => $items->sum('cogm'),
            ];
        })->sortByDesc('cogm')->values();
    }

    private function fillReportSummarySheet($sheet, string $title, string $firstHeader, Collection $rows, string $labelField): void
    {
        $sheet->setTitle($title);
        $sheet->fromArray([$firstHeader, 'PROJECTS', 'MATERIAL', 'LABOR', 'OVERHEAD', 'TOTAL COGM'], null, 'A1');
        $row = 2;
        foreach ($rows as $item) {
            $sheet->fromArray([$item->{$labelField}, $item->projects, $item->material, $item->labor, $item->overhead, $item->cogm], null, 'A'.$row++);
        }
    }

}
