@extends('layouts.app')

@section('title', 'Analisis Tren')
@section('page-title', 'Analisis Tren')

@section('breadcrumb')
    @php
        $backToTrendQuery = array_filter([
            'period_from' => request('period_from'),
            'period_to' => request('period_to'),
            'business_model' => request('business_model'),
            'customer' => request('customer'),
            'model' => request('model'),
        ], fn ($value) => filled($value));
    @endphp

    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="{{ route('analisis-tren', $backToTrendQuery, false) }}" style="color:#2563eb;font-weight:500;text-decoration:none;">Document Trend Analysis</a>
    <span class="breadcrumb-separator">/</span>
    <span>Detail Dokumen Engineering</span>
@endsection

@section('content')
<style>
    .eng-page {
        display: grid;
        gap: 1.25rem;
    }

    .eng-filter-card,
    .eng-card,
    .eng-panel {
        background: #fff;
        border: 1px solid #dbe4f2;
        border-radius: 16px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
    }

    .eng-filter-card {
        padding: 1rem;
    }

    .eng-filter-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
        gap: 0.75rem;
        align-items: end;
    }

    .eng-filter-field label {
        display: block;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }

    .eng-input {
        width: 100%;
        border: 1px solid #cfe0f5;
        border-radius: 10px;
        padding: 0.62rem 0.72rem;
        font-size: 0.80rem;
        font-weight: 750;
        color: #0f172a;
        background: #fff;
        outline: none;
    }

    .eng-btn {
        height: 39px;
        border: 0;
        border-radius: 10px;
        padding: 0 1rem;
        font-size: 0.78rem;
        font-weight: 900;
        cursor: pointer;
        background: #2563eb;
        color: #fff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.22);
        white-space: nowrap;
    }

    .eng-title {
        color: #0f172a;
        font-size: 1.1rem;
        font-weight: 950;
        margin: 0;
        letter-spacing: -0.015em;
    }

    .eng-page-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .eng-back-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        height: 38px;
        padding: 0 0.9rem;
        border-radius: 10px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.78rem;
        font-weight: 950;
        text-decoration: none;
        white-space: nowrap;
    }

    .eng-back-button:hover {
        background: #dbeafe;
    }

    .eng-kpi-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 1rem;
    }

    .eng-card {
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-height: 102px;
    }

    .eng-icon {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .eng-icon svg {
        width: 28px;
        height: 28px;
    }

    .eng-label {
        color: #1e293b;
        font-size: 0.80rem;
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }

    .eng-value {
        font-size: 1.7rem;
        font-weight: 950;
        line-height: 1;
        letter-spacing: -0.045em;
    }

    .eng-sub {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 750;
        margin-top: 0.25rem;
    }

    .bg-blue { background: #dbeafe; color: #2563eb; }
    .bg-red { background: #fee2e2; color: #ef4444; }
    .bg-purple { background: #ede9fe; color: #7c3aed; }
    .bg-green { background: #d1fae5; color: #059669; }
    .bg-orange { background: #ffedd5; color: #f97316; }

    .text-blue { color: #2563eb; }
    .text-red { color: #dc2626; }
    .text-purple { color: #7c3aed; }
    .text-green { color: #059669; }
    .text-orange { color: #f97316; }

    .eng-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(250px, 290px);
        gap: 1rem;
        align-items: stretch;
    }

    .eng-bottom-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr);
        gap: 1rem;
    }

    .eng-panel {
        padding: 1rem;
    }

    .eng-panel-title {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 950;
        margin: 0 0 1rem;
    }

    .info-dot {
        width: 15px;
        height: 15px;
        border-radius: 999px;
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.58rem;
        font-weight: 950;
    }

    .chart-wrap {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr);
        gap: 0.6rem;
        align-items: stretch;
    }

    .y-axis-labels {
        height: 230px;
        display: grid;
        grid-template-rows: repeat(5, 1fr);
        align-items: center;
        justify-items: end;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 800;
        padding-top: 0.15rem;
    }

    .line-chart {
        --max: 100;
        height: 230px;
        border-left: 1px solid #dbe4f2;
        border-bottom: 1px solid #dbe4f2;
        position: relative;
        margin: 0.2rem 0.25rem 0.6rem 0;
        overflow: hidden;
        background:
            linear-gradient(to top, rgba(226,232,240,.75) 1px, transparent 1px) 0 0 / 100% 25%;
    }

    .line-svg {
        position: absolute;
        inset: 0;
        overflow: visible;
    }

    .point-text {
        font-size: 4px;
        font-weight: 900;
        fill: #0f172a;
        text-anchor: middle;
    }

    .chart-legend {
        display: flex;
        gap: 1rem;
        color: #334155;
        font-size: 0.74rem;
        font-weight: 800;
        margin-bottom: 0.6rem;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
    }

    .axis-labels {
        display: grid;
        grid-template-columns: repeat(var(--count), minmax(0, 1fr));
        gap: 0.3rem;
        margin-left: 2.95rem;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 750;
        text-align: center;
    }

    .point-label {
        display: none;
    }

    .insight-list {
        display: grid;
        gap: 0.85rem;
    }

    .insight-item {
        display: grid;
        grid-template-columns: 10px minmax(0, 1fr);
        gap: 0.55rem;
        align-items: start;
        color: #334155;
        font-size: 0.80rem;
        font-weight: 760;
        line-height: 1.45;
    }

    .insight-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        margin-top: 0.32rem;
    }

    .eng-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #cfe0f5;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        font-size: 0.72rem;
        table-layout: fixed;
    }

    .eng-table th {
        background: #2563eb;
        color: #fff;
        padding: 0.66rem 0.58rem;
        text-align: center;
        font-size: 0.68rem;
        font-weight: 950;
        line-height: 1.2;
    }

    .eng-table td {
        padding: 0.62rem 0.58rem;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-weight: 730;
        text-align: center;
        vertical-align: middle;
        line-height: 1.35;
        overflow: hidden;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .eng-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .eng-table td:nth-child(2),
    .eng-table th:nth-child(2),
    .eng-table td:nth-child(3),
    .eng-table th:nth-child(3),
    .eng-table td:nth-child(4),
    .eng-table th:nth-child(4) {
        text-align: left;
    }

    .eng-table th:nth-child(1),
    .eng-table td:nth-child(1) { width: 6%; }

    .eng-table th:nth-child(2),
    .eng-table td:nth-child(2) { width: 20%; }

    .eng-table th:nth-child(3),
    .eng-table td:nth-child(3) { width: 10%; }

    .eng-table th:nth-child(4),
    .eng-table td:nth-child(4) { width: 16%; }

    .eng-table th:nth-child(5),
    .eng-table td:nth-child(5) { width: 11%; }

    .eng-table th:nth-child(6),
    .eng-table td:nth-child(6) { width: 11%; }

    .eng-table th:nth-last-child(2),
    .eng-table td:nth-last-child(2) { width: 14%; }

    .eng-table th:last-child,
    .eng-table td:last-child { width: 12%; }


    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 0.45rem;
        border-radius: 999px;
        font-size: 0.60rem;
        font-weight: 950;
        white-space: normal;
        line-height: 1.15;
        max-width: 100%;
        word-break: break-word;
        overflow-wrap: anywhere;
        text-align: center;
    }

    .badge-red {
        color: #dc2626;
        background: #fee2e2;
        border: 1px solid #fecaca;
    }

    .badge-orange {
        color: #ea580c;
        background: #ffedd5;
        border: 1px solid #fed7aa;
    }

    .badge-blue {
        color: #2563eb;
        background: #dbeafe;
        border: 1px solid #bfdbfe;
    }

    .badge-green {
        color: #047857;
        background: #d1fae5;
        border: 1px solid #a7f3d0;
    }

    .eng-link-row {
        display: flex;
        justify-content: flex-end;
        margin-top: 0.75rem;
    }

    .eng-link {
        color: #2563eb;
        text-decoration: none;
        font-size: 0.78rem;
        font-weight: 950;
    }

    @media (max-width: 1280px) {
        .eng-filter-grid,
        .eng-kpi-grid,
        .eng-main-grid,
        .eng-bottom-grid {
            grid-template-columns: 1fr;
        }

        .eng-panel {
            overflow-x: auto;
        }

        .eng-table {
            min-width: 900px;
        }
    }
</style>

@php
    $filterRoute = route('analisis-tren', absolute: false);

    $maxPartlist = max(1, collect($trendPartlist)->max(fn($row) => max($row->partlist_masuk, $row->revisi_partlist)) ?: 1);
    $maxUmh = max(1, collect($trendUmh)->max(fn($row) => max($row->umh_masuk, $row->revisi_umh)) ?: 1);

    $makePoints = function ($items, $valueKey, $maxValue) {
        $items = collect($items)->values();
        $total = $items->count();

        return $items->map(function ($item, $index) use ($valueKey, $maxValue, $total) {
            $value = (float) $item->{$valueKey};

            if ($total <= 1) {
                $x = 50;
            } else {
                $leftPadding = 6;
                $rightPadding = 94;
                $x = $leftPadding + (($index / ($total - 1)) * ($rightPadding - $leftPadding));
            }

            $usableHeight = 78;
            $topPadding = 12;
            $y = 100 - ($maxValue > 0 ? (($value / $maxValue) * $usableHeight + $topPadding) : $topPadding);
            $y = min(92, max(10, $y));

            return (object) [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'value' => $value,
            ];
        });
    };

    $makeTicks = function ($maxValue) {
        $maxValue = max(1, (int) ceil($maxValue));
        return collect(range(4, 0))->map(function ($step) use ($maxValue) {
            return (int) round(($maxValue / 4) * $step);
        });
    };

    $partlistMainPoints = $makePoints($trendPartlist, 'partlist_masuk', $maxPartlist);
    $partlistRevisionPoints = $makePoints($trendPartlist, 'revisi_partlist', $maxPartlist);
    $umhMainPoints = $makePoints($trendUmh, 'umh_masuk', $maxUmh);
    $umhRevisionPoints = $makePoints($trendUmh, 'revisi_umh', $maxUmh);

    $partlistTicks = $makeTicks($maxPartlist);
    $umhTicks = $makeTicks($maxUmh);

    $toPolyline = fn ($points) => $points->map(fn ($p) => $p->x . ',' . $p->y)->implode(' ');

    $badgeClass = function ($status) {
        return match ($status) {
            'Belum Partlist' => 'badge-red',
            'Menunggu UMH' => 'badge-orange',
            'Revisi Berlangsung' => 'badge-blue',
            'Sudah dokumen lengkap' => 'badge-green',
            'Sudah Costing' => 'badge-green',
            default => 'badge-blue',
        };
    };
@endphp

<div class="eng-page">
    <form class="eng-filter-card" method="GET" action="{{ $filterRoute }}">
        <div class="eng-filter-grid">
            <div class="eng-filter-field">
                <label>Periode Dari</label>
                <select name="period_from" class="eng-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['periods'] as $period)
                        <option value="{{ $period }}" {{ ($filters['period_from'] ?? '') === $period ? 'selected' : '' }}>{{ $period }}</option>
                    @endforeach
                </select>
            </div>

            <div class="eng-filter-field">
                <label>Periode Sampai</label>
                <select name="period_to" class="eng-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['periods'] as $period)
                        <option value="{{ $period }}" {{ ($filters['period_to'] ?? '') === $period ? 'selected' : '' }}>{{ $period }}</option>
                    @endforeach
                </select>
            </div>

            <div class="eng-filter-field">
                <label>Business Model</label>
                <select name="business_model" class="eng-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['businessModels'] as $businessModel)
                        <option value="{{ $businessModel }}" {{ ($filters['business_model'] ?? '') === $businessModel ? 'selected' : '' }}>{{ $businessModel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="eng-filter-field">
                <label>Customer</label>
                <select name="customer" class="eng-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['customers'] as $customer)
                        <option value="{{ $customer }}" {{ ($filters['customer'] ?? '') === $customer ? 'selected' : '' }}>{{ $customer }}</option>
                    @endforeach
                </select>
            </div>

            <div class="eng-filter-field">
                <label>Model</label>
                <select name="model" class="eng-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['models'] as $model)
                        <option value="{{ $model }}" {{ ($filters['model'] ?? '') === $model ? 'selected' : '' }}>{{ $model }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="eng-btn">Terapkan</button>
        </div>
    </form>

    <div class="eng-page-heading">
        <h2 class="eng-title">Detail Dokumen Engineering</h2>
        <a href="{{ route('analisis-tren', $backToTrendQuery, false) }}" class="eng-back-button">
            ← Kembali ke Document Trend Analysis
        </a>
    </div>

    <div class="eng-kpi-grid">
        <div class="eng-card">
            <div class="eng-icon bg-blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M7 3h7l5 5v13H7z"/>
                    <path d="M14 3v5h5"/>
                    <path d="M10 13h6M10 17h6"/>
                </svg>
            </div>
            <div>
                <div class="eng-label">Project<br>Sudah Partlist</div>
                <div class="eng-value text-blue">{{ number_format($summary->project_sudah_partlist, 0, ',', '.') }}</div>
                <div class="eng-sub">Project</div>
            </div>
        </div>

        <div class="eng-card">
            <div class="eng-icon bg-red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M7 3h7l5 5v13H7z"/>
                    <path d="M14 3v5h5"/>
                    <path d="M10 14h6"/>
                </svg>
            </div>
            <div>
                <div class="eng-label">Project<br>Belum Partlist</div>
                <div class="eng-value text-red">{{ number_format($summary->project_belum_partlist, 0, ',', '.') }}</div>
                <div class="eng-sub">Project</div>
            </div>
        </div>

        <div class="eng-card">
            <div class="eng-icon bg-purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M21 12a9 9 0 0 1-15 6.7"/>
                    <path d="M3 12a9 9 0 0 1 15-6.7"/>
                    <path d="M6 19H3v-3M18 5h3v3"/>
                </svg>
            </div>
            <div>
                <div class="eng-label">Total Revisi<br>Partlist</div>
                <div class="eng-value text-purple">{{ number_format($summary->total_revisi_partlist, 0, ',', '.') }}</div>
                <div class="eng-sub">Revisi</div>
            </div>
        </div>

        <div class="eng-card">
            <div class="eng-icon bg-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M9 4h6l1 2h3v15H5V6h3l1-2Z"/>
                    <path d="M8 13l2.5 2.5L16 10"/>
                </svg>
            </div>
            <div>
                <div class="eng-label">Project<br>Sudah UMH</div>
                <div class="eng-value text-green">{{ number_format($summary->project_sudah_umh, 0, ',', '.') }}</div>
                <div class="eng-sub">Project</div>
            </div>
        </div>

        <div class="eng-card">
            <div class="eng-icon bg-orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M6 3h12M6 21h12M8 3c0 5 8 5 8 9s-8 4-8 9"/>
                    <path d="M16 3c0 5-8 5-8 9s8 4 8 9"/>
                </svg>
            </div>
            <div>
                <div class="eng-label">Project<br>Belum UMH</div>
                <div class="eng-value text-orange">{{ number_format($summary->project_belum_umh, 0, ',', '.') }}</div>
                <div class="eng-sub">Project</div>
            </div>
        </div>

        <div class="eng-card">
            <div class="eng-icon bg-purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M21 12a9 9 0 0 1-15 6.7"/>
                    <path d="M3 12a9 9 0 0 1 15-6.7"/>
                    <path d="M6 19H3v-3M18 5h3v3"/>
                </svg>
            </div>
            <div>
                <div class="eng-label">Total Revisi<br>UMH</div>
                <div class="eng-value text-purple">{{ number_format($summary->total_revisi_umh, 0, ',', '.') }}</div>
                <div class="eng-sub">Revisi</div>
            </div>
        </div>
    </div>

    <div class="eng-main-grid">
        <div class="eng-panel">
            <h3 class="eng-panel-title">Tren Dokumen Partlist per Periode <span class="info-dot">i</span></h3>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-dot" style="background:#2563eb;"></span>Partlist Masuk</span>
                <span class="legend-item"><span class="legend-dot" style="background:#f97316;"></span>Revisi Partlist</span>
            </div>

            <div class="chart-wrap">
                <div class="y-axis-labels">
                    @foreach($partlistTicks as $tick)
                        <span>{{ $tick }}</span>
                    @endforeach
                </div>
                <div class="line-chart">
                    <svg class="line-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <polyline points="{{ $toPolyline($partlistMainPoints) }}" fill="none" stroke="#2563eb" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                        <polyline points="{{ $toPolyline($partlistRevisionPoints) }}" fill="none" stroke="#f97316" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                        @foreach($partlistMainPoints as $p)
                            <circle cx="{{ $p->x }}" cy="{{ $p->y }}" r="1.5" fill="#2563eb" vector-effect="non-scaling-stroke"/>
                            <text class="point-text" x="{{ $p->x }}" y="{{ max(6, $p->y - 4) }}">{{ (int) $p->value }}</text>
                        @endforeach
                        @foreach($partlistRevisionPoints as $p)
                            <circle cx="{{ $p->x }}" cy="{{ $p->y }}" r="1.5" fill="#f97316" vector-effect="non-scaling-stroke"/>
                            @if((int) $p->value > 0)
                                <text class="point-text" x="{{ $p->x }}" y="{{ min(97, $p->y + 7) }}">{{ (int) $p->value }}</text>
                            @endif
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="axis-labels" style="--count:{{ max(1, $trendPartlist->count()) }};">
                @forelse($trendPartlist as $row)
                    <span>{{ $row->period }}</span>
                @empty
                    <span>Belum ada data</span>
                @endforelse
            </div>
        </div>

        <div class="eng-panel">
            <h3 class="eng-panel-title">Tren Dokumen UMH per Periode <span class="info-dot">i</span></h3>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-dot" style="background:#2563eb;"></span>UMH Masuk</span>
                <span class="legend-item"><span class="legend-dot" style="background:#f97316;"></span>Revisi UMH</span>
            </div>

            <div class="chart-wrap">
                <div class="y-axis-labels">
                    @foreach($umhTicks as $tick)
                        <span>{{ $tick }}</span>
                    @endforeach
                </div>
                <div class="line-chart">
                    <svg class="line-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <polyline points="{{ $toPolyline($umhMainPoints) }}" fill="none" stroke="#2563eb" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                        <polyline points="{{ $toPolyline($umhRevisionPoints) }}" fill="none" stroke="#f97316" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                        @foreach($umhMainPoints as $p)
                            <circle cx="{{ $p->x }}" cy="{{ $p->y }}" r="1.5" fill="#2563eb" vector-effect="non-scaling-stroke"/>
                            <text class="point-text" x="{{ $p->x }}" y="{{ max(6, $p->y - 4) }}">{{ (int) $p->value }}</text>
                        @endforeach
                        @foreach($umhRevisionPoints as $p)
                            <circle cx="{{ $p->x }}" cy="{{ $p->y }}" r="1.5" fill="#f97316" vector-effect="non-scaling-stroke"/>
                            @if((int) $p->value > 0)
                                <text class="point-text" x="{{ $p->x }}" y="{{ min(97, $p->y + 7) }}">{{ (int) $p->value }}</text>
                            @endif
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="axis-labels" style="--count:{{ max(1, $trendUmh->count()) }};">
                @forelse($trendUmh as $row)
                    <span>{{ $row->period }}</span>
                @empty
                    <span>Belum ada data</span>
                @endforelse
            </div>
        </div>

        <div class="eng-panel">
            <h3 class="eng-panel-title">Insight Utama</h3>
            <div class="insight-list">
                @forelse($insights as $insight)
                    <div class="insight-item">
                        <span class="insight-dot" style="background:{{ $insight->color }}"></span>
                        <span>{{ $insight->text }}</span>
                    </div>
                @empty
                    <div class="insight-item">
                        <span class="insight-dot" style="background:#2563eb;"></span>
                        <span>Belum ada insight dokumen engineering.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="eng-bottom-grid">
        <div class="eng-panel">
            <h3 class="eng-panel-title">Top Project dengan Revisi Terbanyak</h3>

            <div style="overflow:hidden;border-radius:12px;"><table class="eng-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Customer</th>
                        <th>Model</th>
                        <th>Assy No</th>
                        <th>Revisi Partlist</th>
                        <th>Revisi UMH</th>
                        <th>Last Update</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topRevisionProjects as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->customer }}</td>
                            <td>{{ $row->model }}</td>
                            <td>{{ $row->assy_no }}</td>
                            <td>{{ number_format($row->partlist_revision_count, 0, ',', '.') }}</td>
                            <td>{{ number_format($row->umh_revision_count, 0, ',', '.') }}</td>
                            <td>{{ $row->last_updated_at ? $row->last_updated_at->format('d/m/Y H:i') : '-' }}</td>
                            <td>
                                <span class="status-badge {{ $badgeClass($row->bottleneck_status) }}">
                                    {{ $row->bottleneck_status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:#64748b;">Belum ada data revisi dokumen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>

        <div class="eng-panel">
            <h3 class="eng-panel-title">Project Bottleneck</h3>

            <div style="overflow:hidden;border-radius:12px;"><table class="eng-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Customer</th>
                        <th>Model</th>
                        <th>Assy No</th>
                        <th>Tahap Terhambat</th>
                        <th>Durasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bottleneckProjects as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->customer }}</td>
                            <td>{{ $row->model }}</td>
                            <td>{{ $row->assy_no }}</td>
                            <td>{{ $row->bottleneck_stage }}</td>
                            <td>{{ $row->duration_days !== null ? $row->duration_days . ' hari' : '-' }}</td>
                            <td>
                                <span class="status-badge {{ $badgeClass($row->bottleneck_status) }}">
                                    {{ $row->bottleneck_status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:#64748b;">Belum ada bottleneck dokumen engineering.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
</div>
@endsection
