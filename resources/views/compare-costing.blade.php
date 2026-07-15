@extends('layouts.app')

@section('title', 'Compare Costing')
@section('page-title', 'Compare Costing Assy')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Compare Costing</span>
@endsection

@section('header-filters')
@endsection

@section('content')
    <style>
        .compare-filter-panel {
            width: 100%;
            max-width: none;
            background: #ffffff;
            border: 1px solid #dbe4f2;
            border-radius: 16px;
            padding: 1rem;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .compare-filter-table {
            border-collapse: separate;
            border-spacing: 0.75rem 0.55rem;
            width: 100%;
            table-layout: fixed;
            margin: -0.55rem -0.75rem;
        }

        .compare-filter-table th,
        .compare-filter-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
            background: transparent;
        }

        .compare-filter-label {
            background: transparent;
            color: #64748b;
            font-size: 0.68rem;
            letter-spacing: 0.04em;
            font-weight: 850;
            text-transform: uppercase;
            text-align: left;
            white-space: nowrap;
            padding: 0 0.15rem;
        }

        .compare-filter-table td {
            background: transparent;
        }

        .compare-filter-input {
            min-width: 0;
            width: 100%;
            border: 1px solid #cfe0f5;
            border-radius: 10px;
            padding: 0.62rem 0.72rem;
            font-size: 0.80rem;
            line-height: 1.2;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            height: 39px;
            font-weight: 750;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .compare-filter-input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.16);
            background: #ffffff;
        }

        .compare-filter-input option {
            background: #ffffff;
            color: #0f172a;
        }

        .compare-filter-actions {
            margin-top: 0.85rem;
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .compare-filter-actions .btn {
            height: 39px;
            padding: 0 1rem;
            font-size: 0.78rem;
            line-height: 1.1;
            border-radius: 10px;
            font-weight: 900;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
        }

        .compare-filter-actions .btn-secondary {
            background: #f8fafc;
            border: 1px solid #cfe0f5;
            color: #334155;
            box-shadow: none;
        }

        .material-compare-table {
            min-width: 2450px;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
        }

        .material-compare-wrap {
            overflow-x: auto;
            position: relative;
        }

        .material-compare-table th {
            white-space: nowrap;
            font-size: 0.78rem;
            letter-spacing: 0.02em;
            vertical-align: middle;
        }

        .material-compare-table thead tr:first-child th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 100%);
            height: 40px;
        }

        .material-compare-table thead tr:nth-child(2) th {
            position: sticky;
            top: 40px;
            z-index: 6;
            background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 100%);
            height: 40px;
        }

        .material-compare-table .group-header {
            text-align: center;
            font-weight: 700;
        }

        .material-compare-table td {
            font-size: 0.86rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .material-compare-table .key-main {
            font-weight: 700;
            color: var(--slate-700);
            line-height: 1.35;
            margin-bottom: 0.15rem;
            overflow-wrap: anywhere;
        }

        .material-compare-table .key-meta {
            font-size: 0.8rem;
            color: var(--slate-500);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .material-compare-table th:first-child,
        .material-compare-table td:first-child {
            position: sticky;
            left: 0;
        }

        .material-compare-table th:first-child {
            z-index: 8;
            background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 100%);
            box-shadow: 8px 0 14px -12px rgba(15, 23, 42, 0.6);
        }

        .material-compare-table thead tr:first-child th:first-child {
            z-index: 9;
        }

        .material-compare-table td:first-child {
            z-index: 2;
            background: #f8fbff;
            box-shadow: 8px 0 14px -12px rgba(15, 23, 42, 0.35);
            min-width: 220px;
            max-width: 220px;
        }

        .material-compare-table .supplier-cell {
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .material-compare-table .delta-positive {
            color: #166534;
            font-weight: 700;
        }

        .material-compare-table .delta-negative {
            color: #b91c1c;
            font-weight: 700;
        }

        .material-compare-table .delta-neutral {
            color: var(--slate-500);
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .compare-filter-panel {
                max-width: 100%;
            }

            .compare-filter-label {
                font-size: 0.8rem;
            }

            .compare-filter-input {
                font-size: 0.84rem;
                height: 34px;
            }
        }
    </style>

    <div style="margin-bottom: 1rem;">
        <form method="GET" action="{{ route('compare.costing', absolute: false) }}" id="compareFilterForm" class="compare-filter-panel">
            <table class="compare-filter-table" role="presentation">
                <tbody>
                    <tr>
                        <th class="compare-filter-label" style="width: 190px;">Business Categories</th>
                        <td>
                            <select name="business_category" class="compare-filter-input" onchange="document.getElementById('compareFilterForm').submit()">
                                <option value="all" {{ ($businessCategoryFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                                @foreach($businessCategoryOptions ?? collect() as $category)
                                    <option value="{{ $category }}" {{ ($businessCategoryFilter ?? 'all') === $category ? 'selected' : '' }}>{{ $category }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th class="compare-filter-label">Customers</th>
                        <td>
                            <select name="customer_id" class="compare-filter-input" onchange="document.getElementById('compareFilterForm').submit()">
                                <option value="all" {{ ($customerFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                                @foreach($customerOptions ?? collect() as $customer)
                                    <option value="{{ $customer->id }}" {{ (string) ($customerFilter ?? 'all') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th class="compare-filter-label">Model</th>
                        <td>
                            <select name="model" class="compare-filter-input" onchange="document.getElementById('compareFilterForm').submit()">
                                <option value="all" {{ ($modelFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                                @foreach($modelOptions ?? collect() as $model)
                                    <option value="{{ $model }}" {{ ($modelFilter ?? 'all') === $model ? 'selected' : '' }}>{{ $model }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th class="compare-filter-label">Assy A</th>
                        <td>
                            <select name="compare_a_id" id="compareAId" class="compare-filter-input">
                                @foreach($revisionOptions as $option)
                                    <option value="{{ $option['id'] }}" {{ (int) ($selectedAId ?? 0) === (int) $option['id'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th class="compare-filter-label">Assy B</th>
                        <td>
                            <select name="compare_b_id" id="compareBId" class="compare-filter-input">
                                @foreach($revisionOptions as $option)
                                    <option value="{{ $option['id'] }}" {{ (int) ($selectedBId ?? 0) === (int) $option['id'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="compare-filter-actions">
                <a
                    href="{{ route('compare.costing', absolute: false) }}"
                    class="btn btn-secondary"
                    style="padding: 0.52rem 1.15rem; font-size: 0.86rem; line-height: 1.1; border-radius: 8px;"
                >
                    Reset Filter
                </a>
                <button type="submit" class="btn btn-primary">Bandingkan Revisi</button>
            </div>
        </form>
    </div>

    <div class="kpi-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        <div class="kpi-card" style="background: linear-gradient(135deg, #1d4ed8, #2563eb);">
            <div class="kpi-label" style="color: white;">Assy A</div>
            <div class="kpi-value" style="color: white; font-size: 1.1rem; line-height: 1.4;">{{ $labelA }}</div>
        </div>
        <div class="kpi-card" style="background: linear-gradient(135deg, #0f766e, #14b8a6);">
            <div class="kpi-label" style="color: white;">Assy B</div>
            <div class="kpi-value" style="color: white; font-size: 1.1rem; line-height: 1.4;">{{ $labelB }}</div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">Resume COGM</h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table" style="min-width: 1000px;">
                <thead>
                    <tr>
                        <th style="width: 28%;">Komponen</th>
                        <th class="text-right" style="width: 24%;">Assy A</th>
                        <th class="text-right" style="width: 24%;">Assy B</th>
                        <th class="text-right" style="width: 24%;">Selisih A - B</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumeRows as $row)
                        <tr>
                            <td style="font-weight: 600; color: var(--slate-700);">{{ $row['label'] }}</td>
                            <td class="text-right">{{ $row['value_a'] }}</td>
                            <td class="text-right">{{ $row['value_b'] }}</td>
                            <td class="text-right">{{ $row['delta'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">Material vs Material</h3>
        </div>
        <div class="material-compare-wrap">
            <table class="data-table material-compare-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 220px; text-align: left;">Key Material</th>
                        <th colspan="9" class="group-header">Assy A</th>
                        <th colspan="9" class="group-header">Assy B</th>
                        <th colspan="2" class="group-header">Selisih (A - B)</th>
                    </tr>
                    <tr>
                        <th class="text-right" style="width: 5%;">Qty Req</th>
                        <th class="text-right" style="width: 7%;">Amount</th>
                        <th style="width: 7%;">Unit Basis</th>
                        <th style="width: 5%;">Currency</th>
                        <th class="text-right" style="width: 5%;">MOQ</th>
                        <th style="width: 4%;">C/N</th>
                        <th style="width: 8%;">Supplier</th>
                        <th class="text-right" style="width: 6%;">Import Tax (%)</th>
                        <th class="text-right" style="width: 8%;">Total Price (IDR)</th>
                        <th class="text-right" style="width: 5%;">Qty Req</th>
                        <th class="text-right" style="width: 7%;">Amount</th>
                        <th style="width: 7%;">Unit Basis</th>
                        <th style="width: 5%;">Currency</th>
                        <th class="text-right" style="width: 5%;">MOQ</th>
                        <th style="width: 4%;">C/N</th>
                        <th style="width: 8%;">Supplier</th>
                        <th class="text-right" style="width: 6%;">Import Tax (%)</th>
                        <th class="text-right" style="width: 8%;">Total Price (IDR)</th>
                        <th class="text-right" style="width: 6%;">Qty Req</th>
                        <th class="text-right" style="width: 9%;">Total Price (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materialComparisonRows as $row)
                        @php
                            $a = $row['A'] ?? null;
                            $b = $row['B'] ?? null;
                            $qtyReqA = (float) ($a['qty_req'] ?? 0);
                            $qtyReqB = (float) ($b['qty_req'] ?? 0);
                            $amount1A = (float) ($a['amount1'] ?? 0);
                            $amount1B = (float) ($b['amount1'] ?? 0);
                            $totalPriceIdrA = (float) ($a['total_price_idr'] ?? 0);
                            $totalPriceIdrB = (float) ($b['total_price_idr'] ?? 0);
                            $importTaxA = (float) ($a['import_tax_percent'] ?? 0);
                            $importTaxB = (float) ($b['import_tax_percent'] ?? 0);
                            $deltaQtyReq = $qtyReqA - $qtyReqB;
                            $deltaTotalPrice = $totalPriceIdrA - $totalPriceIdrB;
                            $deltaQtyClass = $deltaQtyReq > 0 ? 'delta-positive' : ($deltaQtyReq < 0 ? 'delta-negative' : 'delta-neutral');
                            $deltaTotalPriceClass = $deltaTotalPrice > 0 ? 'delta-positive' : ($deltaTotalPrice < 0 ? 'delta-negative' : 'delta-neutral');
                        @endphp
                        <tr>
                            <td style="vertical-align: top; color: var(--slate-700);">
                                <div class="key-main">
                                    {{ ($row['part_no'] ?? '') !== '' ? $row['part_no'] : '-' }} |
                                    {{ ($row['id_code'] ?? '') !== '' ? $row['id_code'] : '-' }}
                                </div>
                                @if(($row['part_name'] ?? '') !== '')
                                    <div class="key-meta">{{ $row['part_name'] }}</div>
                                @endif
                                @if(($row['part_name'] ?? '') === '' && ($row['row_key'] ?? '') !== '')
                                    <div class="key-meta">{{ $row['row_key'] }}</div>
                                @endif
                            </td>
                            <td class="text-right">{{ $a ? number_format($qtyReqA, 0, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $a ? ('Rp ' . number_format($amount1A, 0, ',', '.')) : '-' }}</td>
                            <td>{{ $a ? (($a['unit_price_basis_text'] ?? '') ?: ($a['unit_price_basis'] ?? '-')) : '-' }}</td>
                            <td>{{ $a ? (($a['currency'] ?? '') ?: '-') : '-' }}</td>
                            <td class="text-right">{{ $a ? number_format((float) ($a['qty_moq'] ?? 0), 0, ',', '.') : '-' }}</td>
                            <td>{{ $a ? (($a['cn_type'] ?? '') ?: '-') : '-' }}</td>
                            <td class="supplier-cell" title="{{ $a ? (($a['supplier'] ?? '') ?: '-') : '-' }}">{{ $a ? (($a['supplier'] ?? '') ?: '-') : '-' }}</td>
                            <td class="text-right">{{ $a ? number_format($importTaxA, 2, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $a ? ('Rp ' . number_format($totalPriceIdrA, 0, ',', '.')) : '-' }}</td>
                            <td class="text-right">{{ $b ? number_format($qtyReqB, 0, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $b ? ('Rp ' . number_format($amount1B, 0, ',', '.')) : '-' }}</td>
                            <td>{{ $b ? (($b['unit_price_basis_text'] ?? '') ?: ($b['unit_price_basis'] ?? '-')) : '-' }}</td>
                            <td>{{ $b ? (($b['currency'] ?? '') ?: '-') : '-' }}</td>
                            <td class="text-right">{{ $b ? number_format((float) ($b['qty_moq'] ?? 0), 0, ',', '.') : '-' }}</td>
                            <td>{{ $b ? (($b['cn_type'] ?? '') ?: '-') : '-' }}</td>
                            <td class="supplier-cell" title="{{ $b ? (($b['supplier'] ?? '') ?: '-') : '-' }}">{{ $b ? (($b['supplier'] ?? '') ?: '-') : '-' }}</td>
                            <td class="text-right">{{ $b ? number_format($importTaxB, 2, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $b ? ('Rp ' . number_format($totalPriceIdrB, 0, ',', '.')) : '-' }}</td>
                            <td class="text-right {{ $deltaQtyClass }}">{{ number_format($deltaQtyReq, 0, ',', '.') }}</td>
                            <td class="text-right {{ $deltaTotalPriceClass }}">Rp {{ number_format($deltaTotalPrice, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="21" class="text-center" style="color: var(--slate-400);">Belum ada data material untuk dibandingkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Cycle Time vs Cycle Time</h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table" style="min-width: 1300px;">
                <thead>
                    <tr>
                        <th style="width: 18%;">Process</th>
                        <th style="width: 41%;">Assy A</th>
                        <th style="width: 41%;">Assy B</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cycleTimeComparisonRows as $row)
                        <tr>
                            <td style="vertical-align: top; font-weight: 600; color: var(--slate-700);">
                                <div>{{ $row['process'] ?: $row['row_key'] }}</div>
                                @if(($row['area_of_process'] ?? '') !== '')
                                    <div style="font-size: 0.8rem; color: var(--slate-500);">{{ $row['area_of_process'] }}</div>
                                @endif
                            </td>
                            <td style="vertical-align: top;">
                                @if(!empty($row['A']))
                                    <div><strong>Qty:</strong> {{ number_format((float) $row['A']['qty'], 0, ',', '.') }}</div>
                                    <div><strong>Time Hour:</strong> {{ number_format((float) $row['A']['time_hour'], 4, ',', '.') }}</div>
                                    <div><strong>Time Sec:</strong> {{ number_format((float) $row['A']['time_sec'], 0, ',', '.') }}</div>
                                    <div><strong>Sec / Qty:</strong> {{ number_format((float) $row['A']['time_sec_per_qty'], 0, ',', '.') }}</div>
                                    <div><strong>Cost / Sec:</strong> {{ number_format((float) $row['A']['cost_per_sec'], 4, ',', '.') }}</div>
                                    <div><strong>Cost / Unit:</strong> {{ number_format((float) $row['A']['cost_per_unit'], 2, ',', '.') }}</div>
                                @else
                                    <span style="color: var(--slate-400);">Tidak ada cycle time pada Assy A.</span>
                                @endif
                            </td>
                            <td style="vertical-align: top;">
                                @if(!empty($row['B']))
                                    <div><strong>Qty:</strong> {{ number_format((float) $row['B']['qty'], 0, ',', '.') }}</div>
                                    <div><strong>Time Hour:</strong> {{ number_format((float) $row['B']['time_hour'], 4, ',', '.') }}</div>
                                    <div><strong>Time Sec:</strong> {{ number_format((float) $row['B']['time_sec'], 0, ',', '.') }}</div>
                                    <div><strong>Sec / Qty:</strong> {{ number_format((float) $row['B']['time_sec_per_qty'], 0, ',', '.') }}</div>
                                    <div><strong>Cost / Sec:</strong> {{ number_format((float) $row['B']['cost_per_sec'], 4, ',', '.') }}</div>
                                    <div><strong>Cost / Unit:</strong> {{ number_format((float) $row['B']['cost_per_unit'], 2, ',', '.') }}</div>
                                @else
                                    <span style="color: var(--slate-400);">Tidak ada cycle time pada Assy B.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center" style="color: var(--slate-400);">Belum ada cycle time untuk dibandingkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
