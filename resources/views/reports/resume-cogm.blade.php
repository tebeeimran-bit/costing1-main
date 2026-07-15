@extends('layouts.app')

@section('title', 'COGM Resume Analysis')
@section('page-title', 'COGM Resume Analysis')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>COGM Resume Analysis</span>
@endsection

@section('content')
<style>
    /*
     * Resume COGM - layout dibuat mengikuti mockup:
     * - KPI cards 3 kolom di atas
     * - Ringkasan Customer kiri
     * - Detail Project kanan
     * - Project clickable ke Form Costing
     * - Keterangan: Full Price / part belum harga / part estimate
     */
    .resume-cogm-page {
        width: 100%;
        display: grid;
        gap: 1.35rem;
    }

    .resume-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .resume-kpi-card {
        min-height: 94px;
        border-radius: 14px;
        padding: 1.22rem 1.25rem;
        color: #ffffff;
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.10);
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    .resume-kpi-card::after {
        content: '';
        position: absolute;
        inset: auto -20% -60% auto;
        width: 210px;
        height: 210px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.10);
        pointer-events: none;
    }

    .resume-kpi-label {
        font-size: 0.76rem;
        font-weight: 850;
        letter-spacing: -0.01em;
        opacity: 0.94;
        margin-bottom: 0.35rem;
        position: relative;
        z-index: 1;
    }

    .resume-kpi-value {
        font-size: clamp(1.38rem, 1.9vw, 1.82rem);
        font-weight: 900;
        line-height: 1.08;
        letter-spacing: -0.035em;
        position: relative;
        z-index: 1;
    }

    .resume-two-column {
        display: grid;
        grid-template-columns: 0.82fr 1.68fr;
        gap: 1rem;
        align-items: start;
    }

    .resume-panel {
        background: #ffffff;
        border: 1px solid #dbe4f2;
        border-radius: 14px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.065);
        overflow: hidden;
        min-width: 0;
    }

    .resume-panel-header {
        min-height: 55px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1rem 0.72rem;
    }

    .resume-panel-title {
        margin: 0;
        color: #0f172a;
        font-size: 0.98rem;
        font-weight: 900;
        letter-spacing: -0.015em;
    }

    .resume-panel-hint {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: #64748b;
        font-size: 0.70rem;
        font-weight: 750;
        white-space: nowrap;
    }

    .resume-panel-hint svg {
        width: 13px;
        height: 13px;
        color: #2563eb;
        flex: 0 0 auto;
    }

    .resume-table-wrap {
        padding: 0 1rem 1rem;
        overflow: visible;
    }

    .resume-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
        color: #334155;
        border: 1px solid #cfe0f5;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
    }

    .resume-table th {
        background: #2563eb;
        color: #ffffff;
        padding: 0.58rem 0.55rem;
        text-align: left;
        font-size: 0.64rem;
        font-weight: 900;
        white-space: nowrap;
        line-height: 1.2;
    }

    .resume-table td {
        padding: 0.58rem 0.55rem;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        font-size: 0.70rem;
        font-weight: 600;
        color: #334155;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .resume-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .resume-table tbody tr:hover {
        background: #f8fbff;
    }

    .resume-table .num,
    .resume-table .text-right {
        text-align: right;
    }

    .resume-table .text-center {
        text-align: center;
    }

    .customer-table th,
    .customer-table td {
        font-size: 0.66rem;
        padding-left: 0.42rem;
        padding-right: 0.42rem;
    }

    .customer-table th:nth-child(1),
    .customer-table td:nth-child(1) {
        width: 7%;
    }

    .customer-table th:nth-child(2),
    .customer-table td:nth-child(2) {
        width: 36%;
    }

    .customer-table th:nth-child(3),
    .customer-table td:nth-child(3) {
        width: 12%;
    }

    .customer-table th:nth-child(4),
    .customer-table td:nth-child(4) {
        width: 21%;
    }

    .customer-table th:nth-child(5),
    .customer-table td:nth-child(5) {
        width: 24%;
    }

    .customer-table th:nth-child(4),
    .customer-table td:nth-child(4),
    .customer-table th:nth-child(5),
    .customer-table td:nth-child(5) {
        overflow: visible;
        text-overflow: clip;
    }

    .project-table {
        font-size: 0.65rem;
    }

    .project-table th,
    .project-table td {
        padding-left: 0.26rem;
        padding-right: 0.26rem;
        font-size: 0.585rem;
    }

    .project-table th:nth-child(1),
    .project-table td:nth-child(1) { width: 3.0%; }

    .project-table th:nth-child(2),
    .project-table td:nth-child(2) { width: 5.6%; }

    .project-table th:nth-child(3),
    .project-table td:nth-child(3) { width: 5.8%; }

    .project-table th:nth-child(4),
    .project-table td:nth-child(4) {
        width: 13.9%;
        overflow: visible;
        text-overflow: clip;
    }

    .project-table th:nth-child(5),
    .project-table td:nth-child(5) { width: 8.4%; }

    .project-table th:nth-child(6),
    .project-table td:nth-child(6) { width: 4.7%; }

    .project-table th:nth-child(7),
    .project-table td:nth-child(7),
    .project-table th:nth-child(8),
    .project-table td:nth-child(8),
    .project-table th:nth-child(9),
    .project-table td:nth-child(9),
    .project-table th:nth-child(10),
    .project-table td:nth-child(10) { width: 6.4%; }

    .project-table th:nth-child(11),
    .project-table td:nth-child(11),
    .project-table th:nth-child(12),
    .project-table td:nth-child(12) { width: 4.7%; }

    .project-table th:nth-child(13),
    .project-table td:nth-child(13) { width: 9.2%; }

    .project-table th:nth-child(14),
    .project-table td:nth-child(14) {
        width: 10.6%;
        overflow: visible;
        text-overflow: clip;
    }

    .project-link {
        display: inline-flex;
        align-items: center;
        gap: 0.22rem;
        max-width: 100%;
        color: #2563eb;
        font-weight: 900;
        text-decoration: underline;
        text-decoration-thickness: 1px;
        text-underline-offset: 2px;
        vertical-align: middle;
    }

    .project-link span {
        display: inline-block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .assy-no-link span {
        max-width: 20ch;
        overflow: visible;
        text-overflow: clip;
    }

    .project-link:hover {
        color: #1d4ed8;
    }

    .project-link svg {
        width: 10px;
        height: 10px;
        flex: 0 0 auto;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.28rem;
        font-size: 0.62rem;
        font-weight: 850;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 999px;
        display: inline-block;
        flex: 0 0 auto;
    }

    .status-dot.a00 {
        background: #2563eb;
    }

    .status-dot.a04 {
        background: #f97316;
    }

    .status-dot.a05 {
        background: #16a34a;
    }

    .note-stack {
        display: grid;
        gap: 0.24rem;
        justify-items: start;
        min-width: 0;
    }

    .note-badge {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 0.20rem;
        max-width: 100%;
        border-radius: 999px;
        border: 1px solid transparent;
        padding: 0.18rem 0.34rem;
        font-size: 0.53rem;
        font-weight: 900;
        line-height: 1.12;
        white-space: nowrap;
    }

    .note-badge svg {
        width: 9px;
        height: 9px;
        flex: 0 0 auto;
    }

    .note-badge.full {
        color: #15803d;
        background: #dcfce7;
        border-color: #bbf7d0;
    }

    .note-badge.missing {
        color: #7e22ce;
        background: #f3e8ff;
        border-color: #e9d5ff;
    }

    .note-badge.estimate {
        color: #c2410c;
        background: #ffedd5;
        border-color: #fed7aa;
    }

    .table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 0.85rem;
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .pager {
        display: inline-flex;
        align-items: center;
        gap: 0.34rem;
        flex-wrap: wrap;
    }

    .pager a,
    .pager span {
        min-width: 30px;
        height: 30px;
        padding: 0 0.55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbe4f2;
        border-radius: 9px;
        text-decoration: none;
        color: #334155;
        background: #ffffff;
        font-size: 0.76rem;
        font-weight: 850;
    }

    .pager .active {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
    }

    .pager .disabled {
        opacity: 0.42;
        pointer-events: none;
    }

    .empty-state {
        padding: 1.25rem;
        text-align: center;
        color: #64748b;
        font-size: 0.84rem;
    }

    @media (max-width: 1500px) {
        .resume-cogm-page {
            width: calc(100% + 8rem);
            margin-left: -4rem;
            margin-right: -4rem;
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }

        .resume-two-column {
            grid-template-columns: minmax(425px, 0.64fr) minmax(760px, 1.36fr);
        }

        .resume-table-wrap {
            padding-left: 0.65rem;
            padding-right: 0.65rem;
        }
    }

    @media (max-width: 1180px) {
        .resume-two-column {
            grid-template-columns: 1fr;
        }

        .resume-table-wrap {
            overflow-x: auto;
        }

        .project-table {
            min-width: 1180px;
            table-layout: auto;
        }

        .project-table th,
        .project-table td {
            font-size: 0.68rem;
            padding-left: 0.55rem;
            padding-right: 0.55rem;
        }
    }

    @media (max-width: 820px) {
        .resume-kpi-grid {
            grid-template-columns: 1fr;
        }

        .resume-panel-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    .resume-two-column .resume-panel {
        min-width: 0;
    }

    .resume-two-column table {
        width: 100%;
    }

    @media (max-width: 1180px) {
        .resume-two-column {
            grid-template-columns: 1fr;
        }
    }


    .resume-analytics-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 320px;
        gap: 1rem;
        align-items: stretch;
    }

    .resume-chart-panel {
        background: #ffffff;
        border: 1px solid #dbe4f2;
        border-radius: 14px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.065);
        padding: 1rem 1rem 1.05rem;
        min-width: 0;
        overflow: hidden;
    }

    .resume-chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .resume-chart-title {
        margin: 0;
        color: #0f172a;
        font-size: 0.98rem;
        font-weight: 900;
        letter-spacing: -0.015em;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .resume-info-dot {
        width: 15px;
        height: 15px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #2563eb;
        font-size: 0.58rem;
        font-weight: 950;
    }

    .resume-period-pill {
        border: 1px solid #dbe4f2;
        background: #f8fafc;
        color: #475569;
        border-radius: 9px;
        padding: 0.42rem 0.58rem;
        font-size: 0.70rem;
        font-weight: 850;
        white-space: nowrap;
    }

    .resume-chart-unit {
        color: #475569;
        font-size: 0.72rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }

    .resume-line-chart {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 0.6rem;
        height: 235px;
        align-items: stretch;
    }

    .resume-y-labels {
        display: grid;
        grid-template-rows: repeat(4, 1fr);
        align-items: center;
        justify-items: end;
        color: #64748b;
        font-size: 0.66rem;
        font-weight: 800;
        padding-top: 0.1rem;
    }

    .resume-chart-area {
        position: relative;
        border-left: 1px solid #dbe4f2;
        border-bottom: 1px solid #dbe4f2;
        overflow: hidden;
        background:
            linear-gradient(to top, rgba(226, 232, 240, 0.78) 1px, transparent 1px) 0 0 / 100% 33.333%;
    }

    .resume-chart-svg {
        position: absolute;
        inset: 0;
        overflow: visible;
    }

    .resume-chart-x {
        display: grid;
        grid-template-columns: repeat(var(--count), minmax(0, 1fr));
        gap: 0.35rem;
        margin-left: 3.05rem;
        padding-top: 0.35rem;
        color: #64748b;
        font-size: 0.65rem;
        font-weight: 750;
        text-align: center;
    }

    .resume-chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        justify-content: center;
        margin-top: 0.9rem;
        color: #334155;
        font-size: 0.70rem;
        font-weight: 820;
    }

    .resume-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .resume-legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
    }

    .resume-end-label {
        font-size: 3.2px;
        font-weight: 950;
        paint-order: stroke;
        stroke: #ffffff;
        stroke-width: 0.75px;
        stroke-linejoin: round;
    }

    .resume-insight-card {
        background: #ffffff;
        border: 1px solid #dbe4f2;
        border-radius: 14px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.065);
        padding: 1rem;
        min-width: 0;
        overflow: hidden;
    }

    .resume-insight-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
    }

    .resume-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #2563eb;
        flex: 0 0 auto;
    }

    .resume-insight-icon svg {
        width: 22px;
        height: 22px;
    }

    .resume-insight-title {
        color: #0f172a;
        font-size: 0.98rem;
        font-weight: 900;
        margin: 0;
    }

    .resume-insight-list {
        display: grid;
        gap: 0;
    }

    .resume-insight-item {
        display: grid;
        grid-template-columns: 14px minmax(0, 1fr);
        gap: 0.55rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 760;
        line-height: 1.48;
    }

    .resume-insight-item:last-child {
        border-bottom: 0;
    }

    .resume-insight-bullet {
        width: 8px;
        height: 8px;
        margin-top: 0.36rem;
        border-radius: 999px;
        background: #2563eb;
        box-shadow: 0 0 0 4px #dbeafe;
    }

</style>


<style>
    @media (max-width: 1500px) {
        .resume-cogm-page {
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .resume-two-column {
            grid-template-columns: 0.82fr 1.68fr !important;
        }
    }

    @media (max-width: 1180px) {
        .resume-analytics-grid,
        .resume-two-column {
            grid-template-columns: 1fr !important;
        }

        .resume-chart-panel,
        .resume-insight-card {
            overflow-x: auto;
        }
    }
</style>

@php

    $chartPeriods = collect($costings ?? [])
        ->pluck('period')
        ->filter(fn ($period) => $period && $period !== '-')
        ->unique()
        ->sort()
        ->values();

    $lineGroups = collect($costings ?? [])
        ->pluck('line')
        ->filter(fn ($line) => $line && $line !== '-')
        ->unique()
        ->sort()
        ->values();

    if ($lineGroups->isEmpty()) {
        $lineGroups = collect(['COGM']);
    }

    $periodCogmTrend = $chartPeriods
        ->map(function ($period) use ($costings, $lineGroups) {
            $periodItems = collect($costings ?? [])->where('period', $period);

            return (object) [
                'period' => $period,
                'lines' => $lineGroups->mapWithKeys(function ($line) use ($periodItems) {
                    return [$line => $periodItems->where('line', $line)->sum('cogm')];
                }),
                'total' => $periodItems->sum('cogm'),
            ];
        })
        ->values();

    $compositionTrend = $chartPeriods
        ->map(function ($period) use ($costings) {
            $periodItems = collect($costings ?? [])->where('period', $period);
            $total = (float) $periodItems->sum('cogm');

            return (object) [
                'period' => $period,
                'material' => $total > 0 ? ($periodItems->sum('material') / $total * 100) : 0,
                'labor' => $total > 0 ? ($periodItems->sum('labor') / $total * 100) : 0,
                'overhead' => $total > 0 ? (($periodItems->sum('overhead') + $periodItems->sum('scrap')) / $total * 100) : 0,
            ];
        })
        ->values();

    $maxCogmChart = max(1, (float) $periodCogmTrend->max('total'));
    $lineColors = ['#2563eb', '#059669', '#f97316', '#7c3aed', '#ef4444', '#0f766e'];
    $componentColors = ['material' => '#2563eb', 'labor' => '#059669', 'overhead' => '#f97316'];

    $makeResumePoints = function ($items, $valueGetter, $maxValue) {
        $items = collect($items)->values();
        $total = $items->count();

        return $items->map(function ($item, $index) use ($valueGetter, $maxValue, $total) {
            $value = (float) $valueGetter($item);
            $x = $total <= 1 ? 50 : 6 + (($index / ($total - 1)) * 88);
            $y = 100 - ($maxValue > 0 ? (($value / $maxValue) * 78 + 12) : 12);
            $y = min(92, max(10, $y));

            return (object) [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'value' => $value,
            ];
        });
    };

    $resumePolyline = fn ($points) => collect($points)->map(fn ($p) => $p->x . ',' . $p->y)->implode(' ');

    $resumeCogmTicks = collect(range(3, 0))->map(function ($step) use ($maxCogmChart) {
        return 'Rp ' . number_format(($maxCogmChart / 3) * $step, 0, ',', '.');
    });

    $resumeCompositionTicks = collect(['100%', '67%', '33%', '0%']);

    $topLine = collect($costings ?? [])
        ->groupBy('line')
        ->map(fn ($items, $line) => (object) ['line' => $line ?: '-', 'total' => $items->sum('cogm')])
        ->sortByDesc('total')
        ->first();

    $latestComposition = $compositionTrend->last();

    $largestChange = $periodCogmTrend
        ->sliding(2)
        ->map(function ($pair) {
            if ($pair->count() < 2) {
                return null;
            }

            $previous = $pair->first();
            $current = $pair->last();

            return (object) [
                'from' => $previous->period,
                'to' => $current->period,
                'change' => abs($current->total - $previous->total),
            ];
        })
        ->filter()
        ->sortByDesc('change')
        ->first();

    $resumeInsights = collect([
        'Tren COGM menunjukkan total Rp ' . number_format($totalCogm ?? 0, 0, ',', '.') . ' dari ' . number_format($totalProjects ?? collect($costings ?? [])->count(), 0, ',', '.') . ' project.',
        $topLine ? 'Business model ' . $topLine->line . ' memiliki kontribusi COGM tertinggi.' : 'Belum ada business model dominan.',
        $latestComposition ? 'Material menjadi komponen terbesar sebesar ' . number_format($latestComposition->material, 1, ',', '.') . '% pada periode terakhir.' : 'Belum ada komposisi COGM.',
        $largestChange ? 'Perubahan terbesar terjadi pada ' . $largestChange->from . ' ke ' . $largestChange->to . '.' : 'Belum cukup data periode untuk menghitung perubahan terbesar.',
    ]);

    $renderPager = function ($paginator) {
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        if ($last <= 1) {
            return '';
        }

        $html = '<div class="pager">';

        if ($current > 1) {
            $html .= '<a href="' . e($paginator->previousPageUrl()) . '" aria-label="Previous">&lsaquo;</a>';
        } else {
            $html .= '<span class="disabled">&lsaquo;</span>';
        }

        $start = max(1, $current - 1);
        $end = min($last, $current + 1);

        if ($start > 1) {
            $html .= '<a href="' . e($paginator->url(1)) . '">1</a>';
            if ($start > 2) {
                $html .= '<span class="disabled">...</span>';
            }
        }

        for ($page = $start; $page <= $end; $page++) {
            if ($page === $current) {
                $html .= '<span class="active">' . $page . '</span>';
            } else {
                $html .= '<a href="' . e($paginator->url($page)) . '">' . $page . '</a>';
            }
        }

        if ($end < $last) {
            if ($end < $last - 1) {
                $html .= '<span class="disabled">...</span>';
            }
            $html .= '<a href="' . e($paginator->url($last)) . '">' . $last . '</a>';
        }

        if ($current < $last) {
            $html .= '<a href="' . e($paginator->nextPageUrl()) . '" aria-label="Next">&rsaquo;</a>';
        } else {
            $html .= '<span class="disabled">&rsaquo;</span>';
        }

        $html .= '</div>';

        return $html;
    };
@endphp

<div class="resume-cogm-page">

    <div class="resume-analytics-grid">
        <div class="resume-chart-panel">
            <div class="resume-chart-header">
                <h3 class="resume-chart-title">
                    Tren COGM per Periode
                    <span class="resume-info-dot">i</span>
                </h3>
                <span class="resume-period-pill">Periode: Monthly</span>
            </div>

            <div class="resume-chart-unit">Rp</div>

            <div class="resume-line-chart">
                <div class="resume-y-labels">
                    @foreach($resumeCogmTicks as $tick)
                        <span>{{ $tick }}</span>
                    @endforeach
                </div>

                <div class="resume-chart-area">
                    <svg class="resume-chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                        @foreach($lineGroups as $lineIndex => $line)
                            @php
                                $linePoints = $makeResumePoints($periodCogmTrend, fn ($row) => $row->lines[$line] ?? 0, $maxCogmChart);
                                $lineColor = $lineColors[$lineIndex % count($lineColors)];
                                $lastPoint = $linePoints->last();
                            @endphp

                            <polyline points="{{ $resumePolyline($linePoints) }}" fill="none" stroke="{{ $lineColor }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                            @foreach($linePoints as $point)
                                <circle cx="{{ $point->x }}" cy="{{ $point->y }}" r="1.5" fill="{{ $lineColor }}" vector-effect="non-scaling-stroke" />
                            @endforeach

                            @if($lastPoint && $lastPoint->value > 0)
                                <text class="resume-end-label" x="{{ min(94, $lastPoint->x + 2.8) }}" y="{{ $lastPoint->y }}" fill="{{ $lineColor }}">
                                    Rp {{ number_format($lastPoint->value, 0, ',', '.') }}
                                </text>
                            @endif
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="resume-chart-x" style="--count:{{ max(1, $chartPeriods->count()) }};">
                @forelse($chartPeriods as $period)
                    <span>{{ $period }}</span>
                @empty
                    <span>Belum ada data</span>
                @endforelse
            </div>

            <div class="resume-chart-legend">
                @foreach($lineGroups as $lineIndex => $line)
                    <span class="resume-legend-item">
                        <span class="resume-legend-dot" style="background: {{ $lineColors[$lineIndex % count($lineColors)] }};"></span>
                        {{ $line }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="resume-chart-panel">
            <div class="resume-chart-header">
                <h3 class="resume-chart-title">
                    Komposisi COGM
                    <span class="resume-info-dot">i</span>
                </h3>
                <span class="resume-period-pill">Periode: Monthly</span>
            </div>

            <div class="resume-chart-unit">%</div>

            <div class="resume-line-chart">
                <div class="resume-y-labels">
                    @foreach($resumeCompositionTicks as $tick)
                        <span>{{ $tick }}</span>
                    @endforeach
                </div>

                <div class="resume-chart-area">
                    <svg class="resume-chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                        @foreach(['material' => 'Material', 'labor' => 'Labor', 'overhead' => 'Overhead'] as $key => $label)
                            @php
                                $componentPoints = $makeResumePoints($compositionTrend, fn ($row) => $row->{$key}, 100);
                                $componentColor = $componentColors[$key];
                                $lastComponentPoint = $componentPoints->last();
                            @endphp

                            <polyline points="{{ $resumePolyline($componentPoints) }}" fill="none" stroke="{{ $componentColor }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                            @foreach($componentPoints as $point)
                                <circle cx="{{ $point->x }}" cy="{{ $point->y }}" r="1.5" fill="{{ $componentColor }}" vector-effect="non-scaling-stroke" />
                            @endforeach

                            @if($lastComponentPoint)
                                <text class="resume-end-label" x="{{ min(94, $lastComponentPoint->x + 2.8) }}" y="{{ $lastComponentPoint->y }}" fill="{{ $componentColor }}">
                                    {{ number_format($lastComponentPoint->value, 1, ',', '.') }}%
                                </text>
                            @endif
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="resume-chart-x" style="--count:{{ max(1, $chartPeriods->count()) }};">
                @forelse($chartPeriods as $period)
                    <span>{{ $period }}</span>
                @empty
                    <span>Belum ada data</span>
                @endforelse
            </div>

            <div class="resume-chart-legend">
                <span class="resume-legend-item"><span class="resume-legend-dot" style="background:#2563eb;"></span>Material</span>
                <span class="resume-legend-item"><span class="resume-legend-dot" style="background:#059669;"></span>Labor</span>
                <span class="resume-legend-item"><span class="resume-legend-dot" style="background:#f97316;"></span>Overhead</span>
            </div>
        </div>

        <div class="resume-insight-card">
            <div class="resume-insight-header">
                <div class="resume-insight-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M9 18h6" />
                        <path d="M10 22h4" />
                        <path d="M12 2a7 7 0 0 0-4 12.74c.63.44 1 1.17 1 1.94V17h6v-.32c0-.77.37-1.5 1-1.94A7 7 0 0 0 12 2Z" />
                    </svg>
                </div>
                <h3 class="resume-insight-title">Insight Utama</h3>
            </div>

            <div class="resume-insight-list">
                @foreach($resumeInsights as $insight)
                    <div class="resume-insight-item">
                        <span class="resume-insight-bullet"></span>
                        <span>{{ $insight }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

<div class="resume-two-column">
        <div class="resume-panel">
            <div class="resume-panel-header">
                <h3 class="resume-panel-title">Ringkasan per Customer</h3>
            </div>

            <div class="resume-table-wrap">
                <table class="resume-table customer-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Customer</th>
                            <th class="text-center">Projects</th>
                            <th class="text-right">Total COGM</th>
                            <th class="text-right">Total Potensial</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customerSummary as $index => $c)
                            <tr>
                                <td>{{ $customerSummary->firstItem() + $index }}</td>
                                <td><strong>{{ $c->customer }}</strong></td>
                                <td class="text-center">{{ number_format($c->count, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($c->total_cogm, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($c->total_potential, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">Belum ada data customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="table-footer">
                    <span>
                        Menampilkan {{ $customerSummary->firstItem() ?? 0 }}-{{ $customerSummary->lastItem() ?? 0 }}
                        dari {{ number_format($customerSummary->total(), 0, ',', '.') }} customer
                    </span>
                    {!! $renderPager($customerSummary) !!}
                </div>
            </div>
        </div>

        <div class="resume-panel">
            <div class="resume-panel-header">
                <h3 class="resume-panel-title">Detail COGM per Project</h3>

                <span class="resume-panel-hint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                    Klik project untuk buka Form Costing
                </span>
            </div>

            <div class="resume-table-wrap">
                <table class="resume-table project-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Customer</th>
                            <th>Model</th>
                            <th>Assy No</th>
                            <th>Assy Name</th>
                            <th>Status</th>
                            <th class="text-right">Material</th>
                            <th class="text-right">Labor</th>
                            <th class="text-right">Overhead</th>
                            <th class="text-right">COGM</th>
                            <th class="text-right">Forecast</th>
                            <th class="text-right">Life (Y)</th>
                            <th class="text-right">Potensial Cost</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projectDetails as $index => $c)
                            <tr>
                                <td>{{ $projectDetails->firstItem() + $index }}</td>
                                <td>{{ $c->customer }}</td>
                                <td>{{ $c->model }}</td>
                                <td>
                                    <a href="{{ $c->form_url }}" class="project-link assy-no-link" title="Buka Form Costing {{ $c->assy_no }}">
                                        <span>{{ $c->assy_no }}</span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <path d="M7 17L17 7" />
                                            <path d="M9 7h8v8" />
                                            <path d="M19 13v6H5V5h6" />
                                        </svg>
                                    </a>
                                </td>
                                <td>{{ $c->assy_name }}</td>
                                <td>
                                    <span class="status-pill">
                                        <span class="status-dot {{ strtolower($c->status) }}"></span>
                                        {{ $c->status }}
                                    </span>
                                </td>
                                <td class="text-right">Rp {{ number_format($c->material, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($c->labor, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($c->overhead, 0, ',', '.') }}</td>
                                <td class="text-right" style="font-weight: 900;">Rp {{ number_format($c->cogm, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($c->forecast, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($c->project_period, 0, ',', '.') }}</td>
                                <td class="text-right" style="font-weight: 900;">Rp {{ number_format($c->potential, 0, ',', '.') }}</td>
                                <td>
                                    <div class="note-stack">
                                        @if($c->is_full_price)
                                            <span class="note-badge full">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                                    <path d="M20 6L9 17l-5-5" />
                                                </svg>
                                                Full Price
                                            </span>
                                        @else
                                            @php
                                            $priceNotes = collect();

                                            if (($project->missing_part_count ?? 0) > 0) {
                                                $priceNotes->push(number_format($project->missing_part_count, 0, ',', '.') . ' part belum ada harga');
                                            }

                                            if (($project->estimate_part_count ?? 0) > 0) {
                                                $priceNotes->push(number_format($project->estimate_part_count, 0, ',', '.') . ' part masih estimate');
                                            }

                                            if ($project->cycle_time_incomplete ?? false) {
                                                $priceNotes->push('Cycle time belum lengkap');
                                            }

                                            if ($project->tooling_depreciation_incomplete ?? false) {
                                                $priceNotes->push('Depresiasi tooling cost belum lengkap');
                                            }
                                        @endphp

                                        @if($priceNotes->isEmpty())
                                            <span class="price-status-badge full">Full Price</span>
                                        @else
                                            <div class="price-status-notes">
                                                @foreach($priceNotes as $note)
                                                    <div>- {{ $note }}</div>
                                                @endforeach
                                            </div>
                                        @endif

                                            @if($c->estimate_part_count > 0)
                                                <span class="note-badge estimate">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                                        <path d="M12 9v4" />
                                                        <path d="M12 17h.01" />
                                                    </svg>
                                                    {{ $c->estimate_part_count }} part masih estimate
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="empty-state">Belum ada data COGM per project.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="table-footer">
                    <span>
                        Menampilkan {{ $projectDetails->firstItem() ?? 0 }}-{{ $projectDetails->lastItem() ?? 0 }}
                        dari {{ number_format($projectDetails->total(), 0, ',', '.') }} project
                    </span>
                    {!! $renderPager($projectDetails) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
