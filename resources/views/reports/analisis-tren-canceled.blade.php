@extends('layouts.app')

@section('title', 'Alasan Canceled/Failed')
@section('page-title', 'Alasan Canceled/Failed')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="{{ route('analisis-tren', absolute: false) }}" style="color:#2563eb;font-weight:500;text-decoration:none;">Document Trend Analysis</a>
    <span class="breadcrumb-separator">/</span>
    <span>Alasan Canceled/Failed</span>
@endsection

@section('content')
<style>
    .cancel-page {
        display: grid;
        gap: 1.25rem;
    }

    .cancel-topbar {
        display: flex;
        justify-content: flex-end;
        margin-top: -0.5rem;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: #2563eb;
        font-size: 0.82rem;
        font-weight: 900;
        text-decoration: none;
    }

    .cancel-filter-card,
    .cancel-card,
    .cancel-panel {
        background: #fff;
        border: 1px solid #dbe4f2;
        border-radius: 16px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
    }

    .cancel-filter-card {
        padding: 1rem;
    }

    .cancel-filter-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
        gap: 0.75rem;
        align-items: end;
    }

    .cancel-filter-field label {
        display: block;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }

    .cancel-input {
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

    .cancel-btn {
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
    }

    .cancel-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .cancel-card {
        padding: 1.15rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        min-height: 108px;
    }

    .cancel-kpi-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .cancel-kpi-icon svg {
        width: 31px;
        height: 31px;
    }

    .bg-red { background: #fee2e2; color: #ef4444; }
    .bg-purple { background: #ede9fe; color: #6d28d9; }
    .bg-green { background: #d1fae5; color: #059669; }
    .bg-blue { background: #dbeafe; color: #2563eb; }

    .cancel-kpi-label {
        color: #1e293b;
        font-size: 0.86rem;
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: 0.35rem;
    }

    .cancel-kpi-value {
        font-size: 1.45rem;
        font-weight: 950;
        letter-spacing: -0.035em;
        line-height: 1.08;
    }

    .cancel-kpi-sub {
        margin-top: 0.28rem;
        color: #64748b;
        font-size: 0.80rem;
        font-weight: 750;
    }

    .text-red { color: #dc2626; }
    .text-purple { color: #6d28d9; }
    .text-green { color: #059669; }
    .text-blue { color: #2563eb; }

    .cancel-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(420px, 0.85fr);
        gap: 1rem;
    }

    .cancel-panel {
        padding: 1rem;
    }

    .cancel-title {
        margin: 0 0 1rem;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        display: flex;
        align-items: center;
        gap: 0.35rem;
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
        font-size: 0.60rem;
        font-weight: 950;
    }

    .reason-row {
        display: grid;
        grid-template-columns: 190px minmax(0, 1fr) 95px;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.72rem;
    }

    .reason-label {
        color: #334155;
        font-size: 0.82rem;
        font-weight: 800;
        text-align: right;
    }

    .reason-track {
        height: 16px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
    }

    .reason-fill {
        height: 100%;
        background: linear-gradient(90deg, #ef4444, #fb923c);
        border-radius: 999px;
        min-width: 5px;
    }

    .reason-value {
        color: #334155;
        font-size: 0.80rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .donut-layout {
        display: grid;
        grid-template-columns: 210px minmax(0, 1fr);
        align-items: center;
        gap: 1.5rem;
    }

    .donut {
        --blue: 0deg;
        width: 190px;
        height: 190px;
        border-radius: 999px;
        background: conic-gradient(#2563eb 0 var(--blue), #059669 var(--blue) calc(var(--blue) + var(--green)), #f97316 calc(var(--blue) + var(--green)) 360deg);
        position: relative;
        margin: 0 auto;
    }

    .donut::after {
        content: '';
        position: absolute;
        inset: 47px;
        background: #fff;
        border-radius: 999px;
        box-shadow: inset 0 0 0 1px #e2e8f0;
    }

    .donut-center {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        text-align: center;
        z-index: 1;
        color: #1e293b;
        font-weight: 900;
        line-height: 1.2;
    }

    .donut-center strong {
        display: block;
        font-size: 2rem;
        line-height: 1;
    }

    .bm-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .bm-table th,
    .bm-table td {
        padding: 0.65rem 0.55rem;
        border-bottom: 1px solid #e2e8f0;
        text-align: right;
        font-weight: 780;
        color: #334155;
    }

    .bm-table th:first-child,
    .bm-table td:first-child {
        text-align: left;
    }

    .bm-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        margin-right: 0.45rem;
    }

    .cancel-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #cfe0f5;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        font-size: 0.80rem;
    }

    .cancel-table th {
        background: #2563eb;
        color: #fff;
        padding: 0.72rem 0.7rem;
        text-align: left;
        font-size: 0.74rem;
        font-weight: 950;
    }

    .cancel-table td {
        padding: 0.66rem 0.7rem;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-weight: 740;
        vertical-align: top;
    }

    .cancel-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .model-link {
        color: #2563eb;
        font-weight: 950;
        text-decoration: none;
    }

    .note {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        margin-top: 0.2rem;
    }

    @media (max-width: 1180px) {
        .cancel-filter-grid,
        .cancel-kpi-grid,
        .cancel-grid,
        .donut-layout {
            grid-template-columns: 1fr;
        }

        .cancel-panel {
            overflow-x: auto;
        }

        .cancel-table {
            min-width: 900px;
        }
    }
</style>

@php
    $backQuery = array_filter([
        'period_from' => $filters['period_from'] ?? '',
        'period_to' => $filters['period_to'] ?? '',
        'business_model' => $filters['business_model'] ?? '',
        'customer' => $filters['customer'] ?? '',
        'model' => $filters['model'] ?? '',
    ], fn ($value) => $value !== null && $value !== '');

    $maxReason = max(1, (int) $reasonSummary->max('count'));
    $totalA04 = max(1, (int) $summary->total_a04);
    $bmTop = $businessModelSummary->take(3)->values();
    $colors = ['#2563eb', '#059669', '#f97316'];
    $blueDeg = (($bmTop[0]->count ?? 0) / $totalA04) * 360;
    $greenDeg = (($bmTop[1]->count ?? 0) / $totalA04) * 360;
@endphp

<div class="cancel-page">
    <div class="cancel-topbar">
        <a href="{{ route('analisis-tren', $backQuery, false) }}" class="back-link">← Kembali ke Document Trend Analysis</a>
    </div>

    <form class="cancel-filter-card" method="GET" action="{{ route('analisis-tren.canceled', absolute: false) }}">
        <div class="cancel-filter-grid">
            <div class="cancel-filter-field">
                <label>Periode Dari</label>
                <select name="period_from" class="cancel-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['periods'] as $period)
                        <option value="{{ $period }}" {{ ($filters['period_from'] ?? '') === $period ? 'selected' : '' }}>{{ $period }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cancel-filter-field">
                <label>Periode Sampai</label>
                <select name="period_to" class="cancel-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['periods'] as $period)
                        <option value="{{ $period }}" {{ ($filters['period_to'] ?? '') === $period ? 'selected' : '' }}>{{ $period }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cancel-filter-field">
                <label>Business Model</label>
                <select name="business_model" class="cancel-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['businessModels'] as $businessModel)
                        <option value="{{ $businessModel }}" {{ ($filters['business_model'] ?? '') === $businessModel ? 'selected' : '' }}>{{ $businessModel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cancel-filter-field">
                <label>Customer</label>
                <select name="customer" class="cancel-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['customers'] as $customer)
                        <option value="{{ $customer }}" {{ ($filters['customer'] ?? '') === $customer ? 'selected' : '' }}>{{ $customer }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cancel-filter-field">
                <label>Model</label>
                <select name="model" class="cancel-input">
                    <option value="">Semua</option>
                    @foreach($filterOptions['models'] as $model)
                        <option value="{{ $model }}" {{ ($filters['model'] ?? '') === $model ? 'selected' : '' }}>{{ $model }}</option>
                    @endforeach
                </select>
            </div>

            <button class="cancel-btn" type="submit">Terapkan</button>
        </div>
    </form>

    <div class="cancel-kpi-grid">
        <div class="cancel-card">
            <div class="cancel-kpi-icon bg-red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M15 9l-6 6M9 9l6 6"/>
                </svg>
            </div>
            <div>
                <div class="cancel-kpi-label">Total A04 (Canceled/Failed)</div>
                <div class="cancel-kpi-value text-red">{{ number_format($summary->total_a04, 0, ',', '.') }}</div>
                <div class="cancel-kpi-sub">Project</div>
            </div>
        </div>

        <div class="cancel-card">
            <div class="cancel-kpi-icon bg-purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M5 4v16"/>
                    <path d="M5 5h12l-2 4 2 4H5"/>
                </svg>
            </div>
            <div>
                <div class="cancel-kpi-label">Dominan Reason</div>
                <div class="cancel-kpi-value text-purple" style="font-size:1.2rem;">{{ $summary->dominant_reason }}</div>
                <div class="cancel-kpi-sub">{{ number_format($summary->dominant_reason_count, 0, ',', '.') }} Project ({{ number_format($summary->dominant_reason_percentage, 1, ',', '.') }}%)</div>
            </div>
        </div>

        <div class="cancel-card">
            <div class="cancel-kpi-icon bg-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M4 21V9l8-6 8 6v12"/>
                    <path d="M9 21v-7h6v7"/>
                    <path d="M8 10h.01M12 10h.01M16 10h.01"/>
                </svg>
            </div>
            <div>
                <div class="cancel-kpi-label">Business Model Terdampak</div>
                <div class="cancel-kpi-value text-green" style="font-size:1.2rem;">{{ $summary->dominant_business_model }}</div>
                <div class="cancel-kpi-sub">{{ number_format($summary->dominant_business_model_count, 0, ',', '.') }} Project ({{ number_format($summary->dominant_business_model_percentage, 1, ',', '.') }}%)</div>
            </div>
        </div>

        <div class="cancel-card">
            <div class="cancel-kpi-icon bg-blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M8 2v4M16 2v4M3 10h18"/>
                    <path d="M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z"/>
                </svg>
            </div>
            <div>
                <div class="cancel-kpi-label">Periode Analisa</div>
                <div class="cancel-kpi-value text-blue" style="font-size:1.2rem;">{{ $summary->period_label }}</div>
                <div class="cancel-kpi-sub">Periode data</div>
            </div>
        </div>
    </div>

    <div class="cancel-grid">
        <div class="cancel-panel">
            <h3 class="cancel-title">Distribusi Alasan Canceled/Failed <span class="info-dot">i</span></h3>

            @forelse($reasonSummary as $reason)
                <div class="reason-row">
                    <div class="reason-label">{{ $reason->reason }}</div>
                    <div class="reason-track">
                        <div class="reason-fill" style="width: {{ $maxReason > 0 ? ($reason->count / $maxReason * 100) : 0 }}%;"></div>
                    </div>
                    <div class="reason-value">{{ number_format($reason->count, 0, ',', '.') }} ({{ number_format($reason->percentage, 1, ',', '.') }}%)</div>
                </div>
            @empty
                <p style="color:#64748b;font-weight:750;">Belum ada project A04.</p>
            @endforelse
        </div>

        <div class="cancel-panel">
            <h3 class="cancel-title">Sebaran A04 per Business Model</h3>

            <div class="donut-layout">
                <div class="donut" style="--blue: {{ $blueDeg }}deg; --green: {{ $greenDeg }}deg;">
                    <div class="donut-center">
                        <div>Total<br><strong>{{ number_format($summary->total_a04, 0, ',', '.') }}</strong>Project</div>
                    </div>
                </div>

                <table class="bm-table">
                    <thead>
                        <tr>
                            <th>Business Model</th>
                            <th>Project</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($businessModelSummary as $index => $bm)
                            <tr>
                                <td><span class="bm-dot" style="background:{{ $colors[$index % count($colors)] }};"></span>{{ $bm->business_model }}</td>
                                <td>{{ number_format($bm->count, 0, ',', '.') }}</td>
                                <td>{{ number_format($bm->percentage, 1, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;">Belum ada data.</td></tr>
                        @endforelse
                        @if($businessModelSummary->isNotEmpty())
                            <tr style="font-weight:950;">
                                <td>Total</td>
                                <td>{{ number_format($summary->total_a04, 0, ',', '.') }}</td>
                                <td>100%</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cancel-panel">
        <h3 class="cancel-title">Detail Project A04</h3>

        <table class="cancel-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Business Model</th>
                    <th>Model</th>
                    <th>Assy No</th>
                    <th>Tanggal A00</th>
                    <th>Tanggal A04</th>
                    <th>Alasan</th>
                    <th>PIC</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detailRows as $row)
                    <tr>
                        <td>{{ $row->customer }}</td>
                        <td>{{ $row->business_model }}</td>
                        <td><span class="model-link">{{ $row->model }}</span></td>
                        <td>{{ $row->assy_no }}</td>
                        <td>{{ $row->a00_date ? $row->a00_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $row->a04_date ? $row->a04_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            <strong>{{ $row->a04_reason }}</strong>
                            @if($row->a04_reason_note)
                                <div class="note">{{ $row->a04_reason_note }}</div>
                            @endif
                        </td>
                        <td>{{ $row->pic }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;color:#64748b;">Belum ada project A04 Canceled/Failed.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
