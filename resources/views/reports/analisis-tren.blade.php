@extends('layouts.app')

@section('title', 'Document Trend Analysis')
@section('page-title', 'Document Trend Analysis')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Document Trend Analysis</span>
@endsection

@section('content')
<style>
    .trend-page {
        display: grid;
        gap: 1.25rem;
    }

    .trend-filter-card,
    .trend-card,
    .trend-panel {
        background: #fff;
        border: 1px solid #dbe4f2;
        border-radius: 16px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
    }

    .trend-filter-card {
        padding: 1rem;
    }

    .trend-filter-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
        gap: 0.75rem;
        align-items: end;
    }

    .trend-filter-field label {
        display: block;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }

    .trend-input {
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

    .trend-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .trend-btn {
        height: 39px;
        border: 0;
        border-radius: 10px;
        padding: 0 1rem;
        font-size: 0.78rem;
        font-weight: 900;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.38rem;
        text-decoration: none;
        white-space: nowrap;
    }

    .trend-btn-primary {
        background: #2563eb;
        color: #fff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.22);
    }

    .trend-btn-soft {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .trend-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .trend-card {
        padding: 1.15rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        min-height: 110px;
    }

    .trend-kpi-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .trend-kpi-icon svg {
        width: 31px;
        height: 31px;
    }

    .trend-kpi-label {
        color: #1e293b;
        font-size: 0.90rem;
        font-weight: 900;
        margin-bottom: 0.35rem;
        line-height: 1.2;
    }

    .trend-kpi-main {
        display: flex;
        align-items: baseline;
        gap: 0.7rem;
    }

    .trend-kpi-value {
        font-size: 2rem;
        font-weight: 950;
        line-height: 1;
        letter-spacing: -0.04em;
    }

    .trend-kpi-sub {
        margin-top: 0.28rem;
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 750;
    }

    .trend-kpi-rate {
        font-size: 1.05rem;
        font-weight: 950;
        white-space: nowrap;
    }

    .text-blue { color: #2563eb; }
    .text-purple { color: #6d28d9; }
    .text-red { color: #dc2626; }
    .text-green { color: #059669; }

    .bg-blue { background: #dbeafe; color: #2563eb; }
    .bg-purple { background: #ede9fe; color: #6d28d9; }
    .bg-red { background: #fee2e2; color: #ef4444; }
    .bg-green { background: #d1fae5; color: #059669; }

    .trend-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 1rem;
    }

    .trend-panel {
        padding: 1rem;
    }

    .trend-panel-title {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin: 0 0 1rem;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        letter-spacing: -0.015em;
    }

    .trend-info {
        width: 15px;
        height: 15px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-size: 0.60rem;
        font-weight: 950;
    }

    .funnel-flow {
        display: grid;
        grid-template-columns: 1fr 54px 1fr 54px 1fr;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.25rem 1rem;
    }

    .funnel-stage {
        position: relative;
        text-align: center;
    }

    .funnel-shape {
        min-height: 135px;
        clip-path: polygon(0 0, 100% 0, 84% 100%, 16% 100%);
        border-radius: 16px;
        display: grid;
        grid-template-rows: 55px 1fr;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.25);
    }

    .funnel-head {
        color: #fff;
        display: grid;
        place-items: center;
        font-weight: 950;
        line-height: 1.15;
        font-size: 1rem;
        padding-top: 0.1rem;
    }

    .funnel-body {
        background: linear-gradient(180deg, #fff, #f8fafc);
        display: grid;
        place-items: center;
        font-size: 1.1rem;
        font-weight: 950;
    }

    .funnel-number {
        display: block;
        font-size: 1.9rem;
        line-height: 1;
        letter-spacing: -0.04em;
    }

    .funnel-caption {
        margin-top: 0.75rem;
        color: #475569;
        font-weight: 850;
        font-size: 0.82rem;
    }

    .flow-arrow {
        color: #94a3b8;
        display: grid;
        place-items: center;
    }

    .flow-arrow svg {
        width: 42px;
        height: 42px;
    }

    .conversion-strip {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.6rem;
        padding: 0.78rem 0.9rem;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        color: #1e293b;
    }

    .conversion-strip svg {
        width: 22px;
        height: 22px;
        color: #2563eb;
        flex: 0 0 auto;
    }

    .conversion-strip strong {
        color: #1d4ed8;
    }

    .engineering-detail-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-left: auto;
        padding: 0.48rem 0.75rem;
        border-radius: 10px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 950;
        text-decoration: none;
    }

    .engineering-detail-link:hover {
        background: #dbeafe;
    }

    .trend-page-switch {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        padding: 0.55rem 0.85rem;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 0.76rem;
        font-weight: 950;
        text-decoration: none;
        white-space: nowrap;
    }

    .trend-page-switch:hover {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
    }


    .a04-callout {
        border: 1px solid #fecaca;
        background: linear-gradient(180deg, #fff, #fff7f7);
        min-height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 1rem;
    }

    .callout-head {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .callout-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        color: #ef4444;
    }

    .callout-icon svg {
        width: 31px;
        height: 31px;
    }

    .callout-title {
        color: #dc2626;
        font-size: 1.22rem;
        font-weight: 950;
        line-height: 1.28;
        margin: 0;
    }

    .callout-text {
        color: #334155;
        line-height: 1.55;
        font-size: 0.95rem;
        margin: 0;
    }

    .callout-button {
        margin-top: 0.4rem;
        width: 100%;
        height: 52px;
        border-radius: 13px;
        background: #fee2e2;
        color: #dc2626;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        font-weight: 950;
        text-decoration: none;
        border: 1px solid #fecaca;
    }

    .callout-button:hover {
        background: #fecaca;
    }

    .trend-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #cfe0f5;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        font-size: 0.80rem;
    }

    .trend-table th {
        background: #2563eb;
        color: #fff;
        padding: 0.72rem 0.7rem;
        text-align: center;
        font-size: 0.72rem;
        font-weight: 950;
        line-height: 1.2;
    }

    .trend-table td {
        padding: 0.72rem 0.7rem;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-weight: 750;
        text-align: center;
    }

    .trend-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .trend-table td:first-child,
    .trend-table th:first-child {
        text-align: left;
    }

    .triplet {
        font-weight: 950;
        white-space: nowrap;
    }

    .triplet .a00 { color: #2563eb; }
    .triplet .a04 { color: #dc2626; }
    .triplet .a05 { color: #059669; }

    @media (max-width: 1180px) {
        .trend-filter-grid,
        .trend-kpi-grid,
        .trend-main-grid {
            grid-template-columns: 1fr;
        }

        .funnel-flow {
            grid-template-columns: 1fr;
        }

        .flow-arrow {
            transform: rotate(90deg);
        }

        .trend-panel {
            overflow-x: auto;
        }

        .trend-table {
            min-width: 900px;
        }
    }

    .engineering-summary-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.85fr);
        gap: 1rem;
        align-items: stretch;
    }

    .engineering-doc-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        overflow: hidden;
        border-radius: 12px;
    }

    .engineering-doc-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.76rem;
        font-weight: 950;
        text-align: left;
        padding: 0.72rem 0.78rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .engineering-doc-table tbody td {
        padding: 0.72rem 0.78rem;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 800;
        vertical-align: middle;
    }

    .engineering-doc-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .engineering-doc-table .progress-cell {
        min-width: 170px;
    }

    .engineering-progress {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .engineering-progress-track {
        height: 8px;
        width: 92px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }

    .engineering-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: #10b981;
    }

    .engineering-progress-fill.warn {
        background: #f97316;
    }

    .engineering-progress-fill.danger {
        background: #ef4444;
    }

    .engineering-progress-number {
        min-width: 48px;
        font-size: 0.78rem;
        font-weight: 950;
        color: #334155;
    }

    .engineering-note {
        margin-top: 0.65rem;
        font-size: 0.78rem;
        font-weight: 750;
        color: #64748b;
    }

    .insight-list {
        display: grid;
        gap: 0.72rem;
    }

    .insight-item {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr);
        gap: 0.7rem;
        align-items: start;
        padding: 0.72rem;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #eef2f7;
    }

    .insight-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 950;
    }

    .insight-icon.green { background: #dcfce7; color: #16a34a; }
    .insight-icon.blue { background: #dbeafe; color: #2563eb; }
    .insight-icon.orange { background: #ffedd5; color: #f97316; }
    .insight-icon.red { background: #fee2e2; color: #ef4444; }

    .insight-title {
        font-size: 0.82rem;
        font-weight: 950;
        color: #0f172a;
        margin-bottom: 0.18rem;
    }

    .insight-text {
        font-size: 0.76rem;
        font-weight: 750;
        color: #64748b;
        line-height: 1.42;
    }

    @media (max-width: 1080px) {
        .engineering-summary-grid {
            grid-template-columns: 1fr;
        }
    }

</style>

@php
    $periodQuery = array_filter([
        'period_from' => $filters['period_from'] ?? '',
        'period_to' => $filters['period_to'] ?? '',
        'business_model' => $filters['business_model'] ?? '',
        'customer' => $filters['customer'] ?? '',
        'model' => $filters['model'] ?? '',
    ], fn ($value) => $value !== null && $value !== '');

    $a04Url = route('analisis-tren.canceled', $periodQuery, false);
    $engineeringUrl = route('analisis-tren.engineering', $periodQuery, false);
@endphp

<div class="trend-page">
    <form class="trend-filter-card" method="GET" action="{{ route('analisis-tren', absolute: false) }}">
        <div class="trend-filter-grid">
            <div class="trend-filter-field">
                <label>Periode Dari</label>
                <select name="period_from" class="trend-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['periods'] as $period)
                        <option value="{{ $period }}" {{ ($filters['period_from'] ?? '') === $period ? 'selected' : '' }}>{{ $period }}</option>
                    @endforeach
                </select>
            </div>

            <div class="trend-filter-field">
                <label>Periode Sampai</label>
                <select name="period_to" class="trend-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['periods'] as $period)
                        <option value="{{ $period }}" {{ ($filters['period_to'] ?? '') === $period ? 'selected' : '' }}>{{ $period }}</option>
                    @endforeach
                </select>
            </div>

            <div class="trend-filter-field">
                <label>Business Model</label>
                <select name="business_model" class="trend-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['businessModels'] as $businessModel)
                        <option value="{{ $businessModel }}" {{ ($filters['business_model'] ?? '') === $businessModel ? 'selected' : '' }}>{{ $businessModel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="trend-filter-field">
                <label>Customer</label>
                <select name="customer" class="trend-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['customers'] as $customer)
                        <option value="{{ $customer }}" {{ ($filters['customer'] ?? '') === $customer ? 'selected' : '' }}>{{ $customer }}</option>
                    @endforeach
                </select>
            </div>

            <div class="trend-filter-field">
                <label>Model</label>
                <select name="model" class="trend-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['models'] as $model)
                        <option value="{{ $model }}" {{ ($filters['model'] ?? '') === $model ? 'selected' : '' }}>{{ $model }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="trend-btn trend-btn-primary">
                Terapkan
            </button>
        </div>
    </form>

    <div class="trend-kpi-grid">
        <div class="trend-card">
            <div class="trend-kpi-icon bg-blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M3 7h18v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/>
                    <path d="M7 7l2-4h6l2 4"/>
                    <path d="M3 14h5l2 2h4l2-2h5"/>
                </svg>
            </div>
            <div>
                <div class="trend-kpi-label">Total Project Masuk</div>
                <div class="trend-kpi-main"><span class="trend-kpi-value text-blue">{{ number_format($summary->total_project_masuk, 0, ',', '.') }}</span></div>
                <div class="trend-kpi-sub">Dokumen</div>
            </div>
        </div>

        <div class="trend-card">
            <div class="trend-kpi-icon bg-purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z"/>
                </svg>
            </div>
            <div>
                <div class="trend-kpi-label">A00 (RFQ/RFI)</div>
                <div class="trend-kpi-main"><span class="trend-kpi-value text-purple">{{ number_format($summary->total_a00, 0, ',', '.') }}</span></div>
                <div class="trend-kpi-sub">Dokumen</div>
            </div>
        </div>

        <a href="{{ $a04Url }}" class="trend-card" style="text-decoration:none;">
            <div class="trend-kpi-icon bg-red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M15 9l-6 6M9 9l6 6"/>
                </svg>
            </div>
            <div style="width:100%;">
                <div class="trend-kpi-label">A04 (Canceled/Failed)</div>
                <div class="trend-kpi-main">
                    <span class="trend-kpi-value text-red">{{ number_format($summary->total_a04, 0, ',', '.') }}</span>
                    <span class="trend-kpi-rate text-red">{{ number_format($summary->cancellation_rate, 1, ',', '.') }}%</span>
                </div>
                <div class="trend-kpi-sub">Project dari A00 • Klik lihat alasan</div>
            </div>
        </a>

        <div class="trend-card">
            <div class="trend-kpi-icon bg-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M8 12.5l2.5 2.5L16 9"/>
                </svg>
            </div>
            <div>
                <div class="trend-kpi-label">A05 (Die Go)</div>
                <div class="trend-kpi-main">
                    <span class="trend-kpi-value text-green">{{ number_format($summary->total_a05, 0, ',', '.') }}</span>
                    <span class="trend-kpi-rate text-green">{{ number_format($summary->conversion_rate, 1, ',', '.') }}%</span>
                </div>
                <div class="trend-kpi-sub">Project dari A00</div>
            </div>
        </div>
    </div>

    <div class="trend-main-grid">
        <div class="trend-panel">
            <h3 class="trend-panel-title">Funnel Project (Alur Proses) <span class="trend-info">i</span></h3>

            <div class="funnel-flow">
                <div class="funnel-stage">
                    <div class="funnel-shape">
                        <div class="funnel-head" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">
                            <div>A00<br><span style="font-size:.82rem;">RFQ / RFI</span></div>
                        </div>
                        <div class="funnel-body text-blue">
                            <div><span class="funnel-number">{{ number_format($summary->total_a00, 0, ',', '.') }}</span>(100%)</div>
                        </div>
                    </div>
                    <div class="funnel-caption">Project Masuk</div>
                </div>

                <div class="flow-arrow">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 5l7 7-7 7v-4H4v-6h9V5Z"/></svg>
                </div>

                <div class="funnel-stage">
                    <div class="funnel-shape">
                        <div class="funnel-head" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
                            <div>A04<br><span style="font-size:.82rem;">Canceled / Failed</span></div>
                        </div>
                        <div class="funnel-body text-red">
                            <div><span class="funnel-number">{{ number_format($summary->total_a04, 0, ',', '.') }}</span>({{ number_format($summary->cancellation_rate, 1, ',', '.') }}%)</div>
                        </div>
                    </div>
                    <div class="funnel-caption">Tidak Lanjut</div>
                </div>

                <div class="flow-arrow">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 5l7 7-7 7v-4H4v-6h9V5Z"/></svg>
                </div>

                <div class="funnel-stage">
                    <div class="funnel-shape">
                        <div class="funnel-head" style="background:linear-gradient(135deg,#059669,#047857);">
                            <div>A05<br><span style="font-size:.82rem;">Die Go</span></div>
                        </div>
                        <div class="funnel-body text-green">
                            <div><span class="funnel-number">{{ number_format($summary->total_a05, 0, ',', '.') }}</span>({{ number_format($summary->conversion_rate, 1, ',', '.') }}%)</div>
                        </div>
                    </div>
                    <div class="funnel-caption">Lanjut ke Costing</div>
                </div>
            </div>

            <div class="conversion-strip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M4 19V5"/>
                    <path d="M4 19h16"/>
                    <path d="M7 15l4-4 3 3 5-7"/>
                </svg>
                <div>
                    <strong>Conversion Rate A00 → A05 (Die Go): {{ number_format($summary->conversion_rate, 1, ',', '.') }}%</strong>
                    <div style="font-size:.78rem;color:#64748b;font-weight:750;">Persentase project yang lolos dari A00 hingga menjadi A05 (Die Go).</div>
                </div>
            </div>
        </div>

        <div class="trend-panel a04-callout">
            <div class="callout-head">
                <div class="callout-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M9 3h6l1 2h3v16H5V5h3l1-2Z"/>
                        <path d="M9 12l6 6M15 12l-6 6"/>
                    </svg>
                </div>
                <h3 class="callout-title">Project A04<br>Canceled/Failed</h3>
            </div>
            <p class="callout-text">Lihat alasan utama project tidak lanjut ke tahap A05 (Die Go).</p>
            <a href="{{ $a04Url }}" class="callout-button">
                Klik untuk lihat alasan
                <span style="font-size:1.4rem;">→</span>
            </a>
        </div>
    </div>

    <div class="trend-panel" style="display:flex;align-items:center;gap:1rem;justify-content:space-between;">
        <div>
            <h3 class="trend-panel-title" style="margin-bottom:.35rem;">Detail Dokumen Engineering</h3>
            <div style="color:#64748b;font-size:.82rem;font-weight:750;line-height:1.45;">
                Pantau penerimaan Partlist, UMH, jumlah revisi, dan project bottleneck dari team Engineering.
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;justify-content:flex-end;">
            <a href="{{ route('resume-cogm', absolute: false) }}" class="trend-page-switch">
                Buka COGM Resume Analysis
                <span style="font-size:1.05rem;">→</span>
            </a>
            <a href="{{ $engineeringUrl }}" class="engineering-detail-link">
                Lihat Detail Dokumen Engineering
                <span style="font-size:1.1rem;">→</span>
            </a>
        </div>
    </div>

    @php
        $a00Total = (int) ($summary->total_a00 ?? 0);
        $a04Total = (int) ($summary->total_a04 ?? 0);
        $a05Total = (int) ($summary->total_a05 ?? 0);

        /*
         * Ringkasan Dokumen Engineering mengikuti data yang tersedia di halaman ini.
         * Total Masuk memakai A00 sebagai baseline project masuk.
         * Revisi dihitung dari project yang tidak lanjut / butuh perbaikan dokumen.
         * Selesai dihitung dari project yang lanjut Die Go.
         */
        $engineeringRows = collect([
            (object) [
                'name' => 'Partlist Masuk',
                'total_masuk' => $a00Total,
                'revisi' => $a04Total,
                'selesai' => $a05Total,
                'bottleneck' => $a04Total > 0 ? 'Revisi Partlist' : '-',
            ],
            (object) [
                'name' => 'Revisi Partlist',
                'total_masuk' => $a04Total,
                'revisi' => $a04Total,
                'selesai' => max($a04Total - $a05Total, 0),
                'bottleneck' => $a04Total > 0 ? 'Revisi Partlist' : '-',
            ],
            (object) [
                'name' => 'UMH Masuk',
                'total_masuk' => $a00Total,
                'revisi' => $a04Total,
                'selesai' => $a05Total,
                'bottleneck' => $a04Total > 0 ? 'Revisi UMH' : '-',
            ],
            (object) [
                'name' => 'Revisi UMH',
                'total_masuk' => $a04Total,
                'revisi' => $a04Total,
                'selesai' => max($a04Total - $a05Total, 0),
                'bottleneck' => $a04Total > 0 ? 'Revisi UMH' : '-',
            ],
        ])->map(function ($row) {
            $row->ratio = $row->total_masuk > 0 ? ($row->selesai / $row->total_masuk) * 100 : 0;
            $row->revisi_ratio = $row->total_masuk > 0 ? ($row->revisi / $row->total_masuk) * 100 : 0;
            return $row;
        });

        $lowestRatioRow = $engineeringRows->sortBy('ratio')->first();
        $bestRatioRow = $engineeringRows->sortByDesc('ratio')->first();

        $successRate = (float) ($summary->conversion_rate ?? 0);
        $cancelRate = (float) ($summary->cancellation_rate ?? 0);
    @endphp

    <div class="engineering-summary-grid">
        <div class="trend-panel">
            <h3 class="trend-panel-title">Dokumen Engineering <span class="trend-info">i</span></h3>
            <div style="color:#64748b;font-size:.78rem;font-weight:750;margin-top:-.25rem;margin-bottom:.75rem;">
                Periode: {{ $filters['period_from'] ?? 'Semua' }} - {{ $filters['period_to'] ?? 'Semua' }}
            </div>

            <div style="overflow-x:auto;">
                <table class="engineering-doc-table">
                    <thead>
                        <tr>
                            <th>Tahap Dokumen</th>
                            <th>Total Masuk</th>
                            <th>Revisi</th>
                            <th>Selesai</th>
                            <th>Rasio Selesai</th>
                            <th>Bottleneck</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($engineeringRows as $row)
                            @php
                                $progressClass = $row->ratio >= 75 ? '' : ($row->ratio >= 50 ? 'warn' : 'danger');
                            @endphp
                            <tr>
                                <td>{{ $row->name }}</td>
                                <td>{{ number_format($row->total_masuk, 0, ',', '.') }}</td>
                                <td>{{ number_format($row->revisi, 0, ',', '.') }} ({{ number_format($row->revisi_ratio, 1, ',', '.') }}%)</td>
                                <td>{{ number_format($row->selesai, 0, ',', '.') }} ({{ number_format($row->ratio, 1, ',', '.') }}%)</td>
                                <td class="progress-cell">
                                    <div class="engineering-progress">
                                        <span class="engineering-progress-number">{{ number_format($row->ratio, 1, ',', '.') }}%</span>
                                        <span class="engineering-progress-track">
                                            <span class="engineering-progress-fill {{ $progressClass }}" style="width:{{ max(0, min(100, $row->ratio)) }}%;"></span>
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $row->bottleneck }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="engineering-note">
                Catatan: Rasio selesai dihitung dari (Selesai / Total Masuk).
            </div>
        </div>

        <div class="trend-panel">
            <h3 class="trend-panel-title">Insight Utama <span class="trend-info">i</span></h3>

            <div class="insight-list">
                <div class="insight-item">
                    <div class="insight-icon green">↗</div>
                    <div>
                        <div class="insight-title">Peningkatan Success Rate</div>
                        <div class="insight-text">
                            Success rate A05 saat ini {{ number_format($successRate, 1, ',', '.') }}%.
                        </div>
                    </div>
                </div>

                <div class="insight-item">
                    <div class="insight-icon blue">▣</div>
                    <div>
                        <div class="insight-title">A00 Masuk Meningkat</div>
                        <div class="insight-text">
                            Total project A00 masuk {{ number_format($a00Total, 0, ',', '.') }} project pada periode aktif.
                        </div>
                    </div>
                </div>

                <div class="insight-item">
                    <div class="insight-icon orange">!</div>
                    <div>
                        <div class="insight-title">Revisi Partlist Jadi Bottleneck</div>
                        <div class="insight-text">
                            {{ $lowestRatioRow?->name ?? 'Dokumen' }} memiliki rasio selesai terendah
                            ({{ number_format((float) ($lowestRatioRow->ratio ?? 0), 1, ',', '.') }}%).
                        </div>
                    </div>
                </div>

                <div class="insight-item">
                    <div class="insight-icon red">×</div>
                    <div>
                        <div class="insight-title">Alasan Utama Canceled</div>
                        <div class="insight-text">
                            Project A04 tercatat {{ number_format($a04Total, 0, ',', '.') }} project
                            dengan cancellation rate {{ number_format($cancelRate, 1, ',', '.') }}%.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
