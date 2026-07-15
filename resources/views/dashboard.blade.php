@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Costing')

@section('breadcrumb')
    <span>Dashboard</span>
@endsection

@section('header-filters')
@endsection

@section('content')
    <style>
        .dashboard-filter-card {
            background: #ffffff;
            border: 1px solid #dbe4f2;
            border-radius: 16px;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
            padding: 1rem;
            margin-bottom: 1.25rem;
        }

        .dashboard-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
            gap: 0.75rem;
            align-items: end;
        }

        .dashboard-filter-field label {
            display: block;
            color: #64748b;
            font-size: 0.68rem;
            font-weight: 850;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .dashboard-filter-input {
            width: 100%;
            border: 1px solid #cfe0f5;
            border-radius: 10px;
            padding: 0.62rem 0.72rem;
            font-size: 0.80rem;
            font-weight: 750;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            height: 39px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .dashboard-filter-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .dashboard-filter-btn {
            height: 39px;
            border: 0;
            border-radius: 10px;
            padding: 0 1rem;
            background: #2563eb;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.22);
            white-space: nowrap;
        }

        .dashboard-filter-btn:hover {
            background: #1d4ed8;
        }

        @media (max-width: 1180px) {
            .dashboard-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-filter-btn {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .dashboard-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="dashboard-filter-card">
        <div class="dashboard-filter-grid">

    <div class="dashboard-filter-field">
        <label>Periode</label>
        <select id="periodFilter" class="dashboard-filter-input">
            <option value="all" {{ $period == 'all' ? 'selected' : '' }}>Semua Periode</option>
            @foreach($periods as $p)
                @php
                    $periodLabel = $p;
                    if (preg_match('/^\d{4}-\d{2}$/', (string) $p) === 1) {
                        $periodLabel = \Carbon\Carbon::createFromFormat('Y-m', (string) $p)->format('M Y');
                    }
                @endphp
                <option value="{{ $p }}" {{ $period == $p ? 'selected' : '' }}>
                    {{ $periodLabel }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="dashboard-filter-field">
        <label>Business Category</label>
        <select id="businessCategoryFilter" class="dashboard-filter-input">
            <option value="all" {{ $businessCategoryFilter == 'all' ? 'selected' : '' }}>Semua</option>
            @foreach($businessCategories as $businessCategory)
                <option value="{{ $businessCategory->id }}" {{ $businessCategoryFilter == $businessCategory->id ? 'selected' : '' }}>
                    {{ $businessCategory->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="dashboard-filter-field">
        <label>Customers</label>
        <select id="customerFilter" class="dashboard-filter-input">
            <option value="all" {{ $customerFilter == 'all' ? 'selected' : '' }}>Semua</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ $customerFilter == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="dashboard-filter-field">
        <label>Model</label>
        <select id="modelFilter" class="dashboard-filter-input">
            <option value="all" {{ $modelFilter == 'all' ? 'selected' : '' }}>Semua</option>
            @foreach($models as $model)
                <option value="{{ $model }}" {{ $modelFilter == $model ? 'selected' : '' }}>{{ $model }}</option>
            @endforeach
        </select>
    </div>
            <button type="button" class="dashboard-filter-btn" onclick="applyFilters()">Terapkan</button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Project Tracking</div>
            <div class="kpi-value">{{ number_format($trackingProjectCount ?? 0, 0, ',', '.') }}</div>
            <div style="margin-top: 0.4rem; font-size: 0.72rem; color: rgba(255,255,255,0.7);">
                Dari menu Project / Tracking
            </div>
            <div class="kpi-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                    <line x1="8" y1="9" x2="16" y2="9"/>
                    <line x1="8" y1="13" x2="16" y2="13"/>
                </svg>
            </div>
        </div>

        <div class="kpi-card" style="background: #0f766e; border-color: #0d9488;">
            <div class="kpi-label" style="color: white;">Project Sudah Costing ({{ $periodDisplayLabel }})</div>
            <div class="kpi-value" style="color: white;">{{ number_format($costingProjectCount ?? $totalProjectCount, 0, ',', '.') }}</div>
            <div style="margin-top: 0.4rem; font-size: 0.72rem; color: rgba(255,255,255,0.75);">
                Pending Form Costing: {{ number_format($pendingFormCostingCount ?? 0, 0, ',', '.') }}
            </div>
            <div class="kpi-icon" style="color: white;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <path d="M9 15l2 2 4-4"/>
                </svg>
            </div>
        </div>

        <div class="kpi-card" style="background: #3b82f6;">
            <div class="kpi-label" style="color: white;">A00 (RFQ/RFI)</div>
            <div class="kpi-value" style="color: white;">{{ number_format($a00ProjectCount, 0, ',', '.') }}</div>
            <div class="kpi-icon" style="color: white;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M4 4h16v12H4z"/>
                    <path d="M4 7l8 5 8-5"/>
                </svg>
            </div>
        </div>
        <div class="kpi-card" style="background: #dc2626;">
            <div class="kpi-label" style="color: white;">A04 (Canceled/Failed)</div>
            <div class="kpi-value" style="color: white;">{{ number_format($a04ProjectCount, 0, ',', '.') }}</div>
            <div class="kpi-icon" style="color: white;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <circle cx="12" cy="12" r="9"/>
                    <line x1="8" y1="8" x2="16" y2="16"/>
                </svg>
            </div>
        </div>
        <div class="kpi-card" style="background: #22c55e;">
            <div class="kpi-label" style="color: white;">A05 (Die Go/Berhasil)</div>
            <div class="kpi-value" style="color: white;">{{ number_format($a05ProjectCount, 0, ',', '.') }}</div>
            <div class="kpi-icon" style="color: white;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="bottom-grid">
        <!-- Status Project A00/A04/A05 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Status Project (A00, A04, A05)</h3>
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <div style="position: relative; width: 180px; height: 180px; margin: 0 auto;">
                    <div style="width: 180px; height: 180px; border-radius: 50%; background: {{ $statusProjectPieGradient }}; animation: pie-spin-cw 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; transform-origin: center;"></div>
                    <div style="position: absolute; inset: 34px; border-radius: 50%; background: white; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <span style="font-size: 0.75rem; color: var(--slate-500);">Total</span>
                        <span style="font-size: 1.4rem; font-weight: 800; color: var(--slate-800);">{{ number_format($statusProjectTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div style="flex: 1; min-width: 220px;">
                    @foreach($statusProjectData as $statusItem)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--slate-200);">
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--slate-700);">
                                <span style="width: 12px; height: 12px; border-radius: 9999px; background: {{ $statusItem['color'] }};"></span>
                                <span style="font-size: 0.85rem;">{{ $statusItem['label'] }}</span>
                            </div>
                            <div style="font-size: 0.85rem; font-weight: 700; color: var(--slate-800);">
                                {{ number_format($statusItem['count'], 0, ',', '.') }}
                                ({{ number_format($statusItem['percentage'], 1, ',', '.') }}%)
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <table class="data-table" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 36%;">Status Project</th>
                        <th class="text-right" style="width: 14%;">Jumlah</th>
                        <th class="text-right" style="width: 16%;">%</th>
                        <th class="text-right" style="width: 34%;">Potensial Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statusProjectData as $statusItem)
                        <tr>
                            <td style="font-weight: 600; color: var(--slate-700);">{{ $statusItem['label'] }}</td>
                            <td class="text-right" style="white-space: nowrap;">{{ number_format($statusItem['count'], 0, ',', '.') }}</td>
                            <td class="text-right" style="white-space: nowrap;">{{ number_format($statusItem['percentage'], 1, ',', '.') }}%</td>
                            <td class="text-right" style="white-space: nowrap;"><span style="white-space: nowrap;">Rp {{ number_format((float) ($statusItem['potential_cost'] ?? 0), 0, ',', '.') }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center" style="color: var(--slate-400);">Belum ada status project pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Potensial Cost per Dimension -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Potensial Cost per {{ $analysisDimensionLabel }}</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ $analysisDimensionLabel }}</th>
                        <th class="text-right">Potensial Cost</th>
                        <th class="text-right">Projects</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $businessCategoryRows = $analysisSalesRows->take(8);
                        $totalPotentialCost = (float) $businessCategoryRows->sum('potential_sales');
                        $totalProjects = (int) $businessCategoryRows->sum('project_count');
                    @endphp
                    @forelse($businessCategoryRows as $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td class="text-right">Rp {{ number_format($item['potential_sales'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($item['project_count'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center" style="color: var(--slate-400);">Belum ada data {{ strtolower($analysisDimensionLabel) }}.</td>
                        </tr>
                    @endforelse
                    @if($businessCategoryRows->isNotEmpty())
                        <tr>
                            <td style="font-weight: 800;">Total</td>
                            <td class="text-right" style="font-weight: 800;">Rp {{ number_format($totalPotentialCost, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-weight: 800;">{{ number_format($totalProjects, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Top 5 Customer -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top 5 Customer</h3>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Business Category</th>
                        <th class="text-right">Potensial Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCustomerPotentialSales as $customer)
                        <tr>
                            <td>{{ $customer['customer_name'] }}</td>
                            <td>{{ $customer['business_category'] }}</td>
                            <td class="text-right">Rp {{ number_format($customer['potential_sales'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center" style="color: var(--slate-400);">Belum ada data customer.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-grid" style="margin-top: 1.5rem;">
        <!-- Cost Per Unit per Assy -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Total Project per {{ $analysisDimensionLabel }}</h3>
            </div>
            <div class="bar-chart" style="gap: 1rem;">
                @forelse($projectCountPerCustomer as $item)
                    @php
                        $totalCount = (int) ($item['total_count'] ?? 0);
                        $a00Pct = $maxProjectCount > 0 ? (($item['a00_count'] ?? 0) / $maxProjectCount) * 100 : 0;
                        $a04Pct = $maxProjectCount > 0 ? (($item['a04_count'] ?? 0) / $maxProjectCount) * 100 : 0;
                        $a05Pct = $maxProjectCount > 0 ? (($item['a05_count'] ?? 0) / $maxProjectCount) * 100 : 0;
                    @endphp
                    <div class="bar-item">
                        <span class="bar-label">{{ $item['name'] }}</span>
                        <div class="bar-container">
                            <div style="display: flex; width: 100%; height: 24px; border-radius: 4px; overflow: hidden; background: #f1f5f9;">
                                @if($item['a00_count'] > 0)
                                    <div style="width: {{ $a00Pct }}%; background: #3b82f6; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem; font-weight: 700;">
                                        {{ $item['a00_count'] }}
                                    </div>
                                @endif
                                @if($item['a04_count'] > 0)
                                    <div style="width: {{ $a04Pct }}%; background: #dc2626; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem; font-weight: 700;">
                                        {{ $item['a04_count'] }}
                                    </div>
                                @endif
                                @if($item['a05_count'] > 0)
                                    <div style="width: {{ $a05Pct }}%; background: #22c55e; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem; font-weight: 700;">
                                        {{ $item['a05_count'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <span style="margin-left: auto; font-weight: 700; color: var(--slate-800); white-space: nowrap;">{{ $totalCount }}</span>
                    </div>
                @empty
                    <div style="padding: 1rem; color: var(--slate-400);">Belum ada data project pada filter ini.</div>
                @endforelse
            </div>
            <div style="margin-top: 1rem; display: flex; gap: 1.5rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 16px; height: 16px; background: #3b82f6; border-radius: 3px;"></span>
                    <span style="font-size: 0.85rem; color: var(--slate-600);">A00 (RFQ/RFI)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 16px; height: 16px; background: #dc2626; border-radius: 3px;"></span>
                    <span style="font-size: 0.85rem; color: var(--slate-600);">A04 (Canceled/Failed)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 16px; height: 16px; background: #22c55e; border-radius: 3px;"></span>
                    <span style="font-size: 0.85rem; color: var(--slate-600);">A05 (Die Go/Berhasil)</span>
                </div>
            </div>
        </div>
        
        <!-- Breakdown Cost per Dimension -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Breakdown Cost per {{ $analysisDimensionLabel }}</h3>
            </div>
            <div class="stacked-bar-chart">
                @forelse($analysisSalesRows->take(8) as $item)
                    @php
                        $compositionTotal = (float) ($item['material_cost'] ?? 0) + (float) ($item['labor_cost'] ?? 0) + (float) ($item['overhead_cost'] ?? 0);
                        $materialPct = $compositionTotal > 0 ? (((float) ($item['material_cost'] ?? 0) / $compositionTotal) * 100) : 0;
                        $laborPct = $compositionTotal > 0 ? (((float) ($item['labor_cost'] ?? 0) / $compositionTotal) * 100) : 0;
                        $overheadPct = $compositionTotal > 0 ? (((float) ($item['overhead_cost'] ?? 0) / $compositionTotal) * 100) : 0;
                    @endphp
                    <div class="stacked-bar-item">
                        <span class="bar-label">{{ $item['name'] }}</span>
                        <div style="flex: 1;">
                        <div class="stacked-bar-container">
                            <div class="stacked-segment material" style="width: {{ $materialPct }}%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.625rem; font-weight: 700;">
                                @if($materialPct >= 10)
                                    {{ number_format($materialPct, 0, ',', '.') }}%
                                @endif
                            </div>
                            <div class="stacked-segment labor" style="width: {{ $laborPct }}%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.625rem; font-weight: 700;">
                                @if($laborPct >= 10)
                                    {{ number_format($laborPct, 0, ',', '.') }}%
                                @endif
                            </div>
                            <div class="stacked-segment overhead" style="width: {{ $overheadPct }}%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.625rem; font-weight: 700;">
                                @if($overheadPct >= 10)
                                    {{ number_format($overheadPct, 0, ',', '.') }}%
                                @endif
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.75rem; margin-top: 0.35rem; font-size: 0.7rem; color: var(--slate-600);">
                            <span>Material {{ number_format($materialPct, 1, ',', '.') }}%</span>
                            <span>Labor {{ number_format($laborPct, 1, ',', '.') }}%</span>
                            <span>Overhead {{ number_format($overheadPct, 1, ',', '.') }}%</span>
                        </div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 1rem; color: var(--slate-400);">Belum ada data potensial cost untuk ditampilkan.</div>
                @endforelse
            </div>
            <div class="chart-legend" style="margin-top: 1rem;">
                <div class="legend-item">
                    <div class="legend-color material"></div>
                    <span>Material Cost</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color labor"></div>
                    <span>Labor Cost</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color overhead"></div>
                    <span>Overhead Cost</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">Detail Costing</h3>
        </div>
        <div style="padding: 0 1rem 1rem 1rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: flex-start;">
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.2rem 0.8rem; border: 1px solid var(--slate-200); border-radius: 12px; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); max-width: 460px; width: 100%;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--slate-400); flex-shrink: 0;">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M20 20l-3.5-3.5"></path>
                </svg>
                <input
                    type="text"
                    id="detailCostingSearch"
                    placeholder="Cari status, customer, model, assy, atau periode..."
                    oninput="filterDetailCostingTable()"
                    style="border: 0; outline: none; width: 100%; padding: 0.7rem 0; font-size: 0.95rem; color: var(--slate-800); background: transparent;"
                >
                <button
                    type="button"
                    id="detailCostingSearchClear"
                    onclick="clearDetailCostingSearch()"
                    style="display: none; border: 0; background: var(--slate-100); color: var(--slate-600); border-radius: 9999px; padding: 0.4rem 0.7rem; font-size: 0.8rem; font-weight: 700; cursor: pointer;"
                >
                    Hapus
                </button>
            </div>
        </div>
        <div class="material-table-container">
            <table class="data-table" id="detailCostingTable" style="min-width: 2400px; width: 100%;">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Status Project</th>
                        <th>Business Model</th>
                        <th>Customer</th>
                        <th>Model</th>
                        <th>ID Code</th>
                        <th>Assy No</th>
                        <th>Assy Name</th>
                        <th>Revisi</th>
                        <th>Qty/Month</th>
                        <th>Product's Life</th>
                        <th>Circuit</th>
                        <th>Cycle Time (hour)</th>
                        <th class="text-right">Material Cost</th>
                        <th class="text-right">Labor Cost</th>
                        <th class="text-right">Overhead Cost</th>
                        <th class="text-right">COGM</th>
                        <th class="text-right">Potensial Cost</th>
                        <th class="text-right">USD</th>
                        <th class="text-right">JPY</th>
                        <th class="text-right">LME</th>
                        <th class="text-right">Rate Periode</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costingData as $index => $row)
                        @php
                            $cycleTimeText = '-';
                            $cycleTimes = $row->cycle_times ?? [];
                            $circuitQty = 0;
                            $ratePeriodeText = '-';
                            $ratePeriodeValue = trim((string) ($row->rate_periode ?? ''));
                            $cogmValue = (float) (($row->material_cost ?? 0) + ($row->labor_cost ?? 0) + ($row->overhead_cost ?? 0) + ($row->scrap_cost ?? 0));
                            $qtyPerMonthValue = (float) ($row->forecast ?? 0);
                            $productLifeYearsValue = (float) ($row->project_period ?? 0);
                            $potentialSalesValue = $qtyPerMonthValue * $productLifeYearsValue * $cogmValue;
                            $statusProjectValue = '';
                            $statusProjectLabel = '-';
                            if (($row->trackingRevision?->a05 ?? null) === 'ada') {
                                $statusProjectValue = 'A05';
                                $statusProjectLabel = 'A05 (Die Go)';
                            } elseif (($row->trackingRevision?->a04 ?? null) === 'ada') {
                                $statusProjectValue = 'A04';
                                $statusProjectLabel = 'A04 (Cancelled/Failed)';
                            } elseif (($row->trackingRevision?->a00 ?? null) === 'ada') {
                                $statusProjectValue = 'A00';
                                $statusProjectLabel = 'A00 (RFQ/RFI)';
                            }
                            if (is_array($cycleTimes) && count($cycleTimes) > 0) {
                                // Sum total time in hours from all processes
                                $totalCycleTime = collect($cycleTimes)->sum(function ($ct) {
                                    return (float) ($ct['time_hour'] ?? 0);
                                });
                                $cycleTimeText = $totalCycleTime > 0 ? number_format($totalCycleTime, 2, ',', '.') : '-';
                                
                                // Find Circuit QTY from "Cutting, Stripping" process
                                foreach ($cycleTimes as $ct) {
                                    $process = trim($ct['process'] ?? '');
                                    if ($process === 'Cutting, Stripping') {
                                        $circuitQty = (int) ($ct['qty'] ?? 0);
                                        break;
                                    }
                                }
                            }

                            if ($ratePeriodeValue !== '') {
                                $ratePeriodeText = $ratePeriodeValue;
                            } else {
                                $projectPeriod = trim((string) ($row->period ?? ''));
                                if ($projectPeriod !== '') {
                                    if (preg_match('/^\d{4}-\d{2}$/', $projectPeriod) === 1) {
                                        $ratePeriodeText = \Carbon\Carbon::createFromFormat('Y-m', $projectPeriod)->format('M-y');
                                    } elseif (preg_match('/^[A-Za-z]{3}\s+\d{4}$/', $projectPeriod) === 1) {
                                        $ratePeriodeText = \Carbon\Carbon::createFromFormat('M Y', $projectPeriod)->format('M-y');
                                    } else {
                                        $ratePeriodeText = $projectPeriod;
                                    }
                                }
                            }
                        @endphp
                        <tr data-search="{{ strtolower(trim(implode(' ', array_filter([
                            $statusProjectLabel,
                            $row->product->line ?? $row->product->name ?? '',
                            $row->customer->name ?? '',
                            $row->model ?? '',
                            $row->product->code ?? '',
                            $row->assy_no ?? '',
                            $row->assy_name ?? '',
                            $row->trackingRevision?->version_label ?? '',
                            (string) ($row->forecast ?? 0),
                            (string) ($row->project_period ?? 0),
                            (string) ($circuitQty ?? 0),
                            $cycleTimeText,
                            (string) $potentialSalesValue,
                            $ratePeriodeText,
                        ])))) }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @php
                                    $statusProjectColors = [
                                        'A00' => '#2563eb',
                                        'A04' => '#dc2626',
                                        'A05' => '#16a34a',
                                    ];
                                    $statusProjectDisplayValue = in_array($statusProjectValue, ['A00', 'A04', 'A05'], true)
                                        ? $statusProjectValue
                                        : 'A00';
                                    $statusProjectBgColor = $statusProjectColors[$statusProjectDisplayValue] ?? '#2563eb';
                                @endphp
                                <select class="status-project-select"
                                    onchange="saveStatusProject(this)"
                                    data-revision-id="{{ $row->trackingRevision?->id ?? '' }}"
                                    data-last-saved-status="{{ $statusProjectDisplayValue }}"
                                    data-status-project-color="{{ $statusProjectBgColor }}"
                                    style="border: 1px solid {{ $statusProjectBgColor }}; border-radius: 6px; padding: 0.3rem 0.5rem; font-size: 0.78rem; font-weight: 700; color: #ffffff; background: {{ $statusProjectBgColor }}; min-width: 170px;">
                                    <option value="A00" {{ $statusProjectDisplayValue === 'A00' ? 'selected' : '' }} style="background: #2563eb; color: #fff; font-weight: 700;">A00 (RFQ/RFI)</option>
                                    <option value="A04" {{ $statusProjectDisplayValue === 'A04' ? 'selected' : '' }} style="background: #dc2626; color: #fff; font-weight: 700;">A04 (Cancelled/Failed)</option>
                                    <option value="A05" {{ $statusProjectDisplayValue === 'A05' ? 'selected' : '' }} style="background: #16a34a; color: #fff; font-weight: 700;">A05 (Die Go)</option>
                                </select>
                            </td>
                            <td>{{ $row->product->line ?? $row->product->name ?? '-' }}</td>
                            <td>{{ $row->customer->name ?? '-' }}</td>
                            <td>{{ $row->model ?? '-' }}</td>
                            <td>{{ $row->product->code ?? '-' }}</td>
                            <td>{{ $row->assy_no ?? '-' }}</td>
                            <td>{{ $row->assy_name ?? '-' }}</td>
                            <td>{{ $row->trackingRevision?->version_label ?? '-' }}</td>
                            <td>{{ number_format((float) ($row->forecast ?? 0), 0, ',', '.') }}</td>
                            <td>{{ number_format((float) ($row->project_period ?? 0), 0, ',', '.') }}</td>
                            <td>{{ $circuitQty ?? 0 }}</td>
                            <td>{{ $cycleTimeText }}</td>
                            <td class="text-right">
                                <div class="cost-mask-cell" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <button type="button" onclick="toggleDetailCostCell(this)" aria-label="Lihat nilai" title="Lihat nilai" style="border: 0; background: transparent; color: var(--slate-500); cursor: pointer; padding: 0; display: inline-flex; align-items: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                    <span class="cost-masked" style="color: var(--slate-400);">••••••</span>
                                    <span class="cost-value" style="display: none;">Rp {{ number_format((float) ($row->material_cost ?? 0), 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="cost-mask-cell" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <button type="button" onclick="toggleDetailCostCell(this)" aria-label="Lihat nilai" title="Lihat nilai" style="border: 0; background: transparent; color: var(--slate-500); cursor: pointer; padding: 0; display: inline-flex; align-items: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                    <span class="cost-masked" style="color: var(--slate-400);">••••••</span>
                                    <span class="cost-value" style="display: none;">Rp {{ number_format((float) ($row->labor_cost ?? 0), 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="cost-mask-cell" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <button type="button" onclick="toggleDetailCostCell(this)" aria-label="Lihat nilai" title="Lihat nilai" style="border: 0; background: transparent; color: var(--slate-500); cursor: pointer; padding: 0; display: inline-flex; align-items: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                    <span class="cost-masked" style="color: var(--slate-400);">••••••</span>
                                    <span class="cost-value" style="display: none;">Rp {{ number_format((float) (($row->overhead_cost ?? 0) + ($row->scrap_cost ?? 0)), 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="cost-mask-cell" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <button type="button" onclick="toggleDetailCostCell(this)" aria-label="Lihat nilai" title="Lihat nilai" style="border: 0; background: transparent; color: var(--slate-500); cursor: pointer; padding: 0; display: inline-flex; align-items: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                    <span class="cost-masked" style="color: var(--slate-400);">••••••</span>
                                    <span class="cost-value" style="display: none;">Rp {{ number_format($cogmValue, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="cost-mask-cell" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <button type="button" onclick="toggleDetailCostCell(this)" aria-label="Lihat nilai" title="Lihat nilai" style="border: 0; background: transparent; color: var(--slate-500); cursor: pointer; padding: 0; display: inline-flex; align-items: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                    <span class="cost-masked" style="color: var(--slate-400);">••••••</span>
                                    <span class="cost-value" style="display: none;">Rp {{ number_format($potentialSalesValue, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="text-right">{{ number_format((float) ($row->exchange_rate_usd ?? 0), 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format((float) ($row->exchange_rate_jpy ?? 0), 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format((float) ($row->lme_rate ?? 0), 2, ',', '.') }}</td>
                            <td class="text-right">{{ $ratePeriodeText }}</td>
                        </tr>
                    @empty
                        @for($i = 0; $i < 8; $i++)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td colspan="21">&nbsp;</td>
                            </tr>
                        @endfor
                    @endforelse
                    <tr id="detailCostingNoResults" style="display: none;">
                        <td colspan="22" class="text-center" style="padding: 1rem; color: var(--slate-500); font-weight: 600;">
                            Tidak ada data yang cocok dengan pencarian.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="detailCostingPagination" style="padding: 0.9rem 1rem 1rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <span id="detailCostingPageInfo" style="font-size: 0.85rem; color: var(--slate-600);">Halaman 1 dari 1</span>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <button
                    type="button"
                    id="detailCostingPrev"
                    onclick="changeDetailCostingPage(-1)"
                    style="border: 1px solid var(--slate-300); background: #fff; color: var(--slate-700); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 700; cursor: pointer;"
                >
                    Sebelumnya
                </button>
                <button
                    type="button"
                    id="detailCostingNext"
                    onclick="changeDetailCostingPage(1)"
                    style="border: 1px solid var(--slate-300); background: #fff; color: var(--slate-700); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 700; cursor: pointer;"
                >
                    Berikutnya
                </button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

<form id="statusProjectUpdateForm" method="POST" style="display:none;">
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" id="statusProjectUpdateStatus">
</form>


<script>
    const detailCostingPageSize = 10;
    let detailCostingCurrentPage = 1;

    function applyFilters() {
        const period = document.getElementById('periodFilter').value;
        const businessCategory = document.getElementById('businessCategoryFilter').value;
        const customer = document.getElementById('customerFilter').value;
        const model = document.getElementById('modelFilter').value;
        
        const params = new URLSearchParams();
        params.set('period', period);
        params.set('business_category', businessCategory);
        params.set('customer', customer);
        params.set('model', model);
        
        window.location.href = '{{ route("dashboard") }}?' + params.toString();
    }

    function filterDetailCostingTable(resetPage = true) {
        const searchInput = document.getElementById('detailCostingSearch');
        const table = document.getElementById('detailCostingTable');
        const clearButton = document.getElementById('detailCostingSearchClear');
        const noResultsRow = document.getElementById('detailCostingNoResults');
        const pageInfo = document.getElementById('detailCostingPageInfo');
        const prevButton = document.getElementById('detailCostingPrev');
        const nextButton = document.getElementById('detailCostingNext');
        const paginationContainer = document.getElementById('detailCostingPagination');
        if (!searchInput || !table) {
            return;
        }

        const filter = searchInput.value.toLowerCase().trim();
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const dataRows = rows.filter(function (row) {
            return row.dataset.search;
        });
        const placeholderRows = rows.filter(function (row) {
            return row.id !== 'detailCostingNoResults' && !row.dataset.search;
        });

        if (dataRows.length > 0) {
            placeholderRows.forEach(function (row) {
                row.style.display = 'none';
            });
        }

        const matchedRows = dataRows.filter(function (row) {
            const rowText = row.textContent.toLowerCase();
            const searchText = row.dataset.search || rowText;
            return searchText.indexOf(filter) !== -1;
        });

        const totalMatched = matchedRows.length;
        const totalPages = Math.max(1, Math.ceil(totalMatched / detailCostingPageSize));

        if (resetPage) {
            detailCostingCurrentPage = 1;
        }

        if (detailCostingCurrentPage > totalPages) {
            detailCostingCurrentPage = totalPages;
        }
        if (detailCostingCurrentPage < 1) {
            detailCostingCurrentPage = 1;
        }

        const startIndex = (detailCostingCurrentPage - 1) * detailCostingPageSize;
        const endIndex = startIndex + detailCostingPageSize;

        dataRows.forEach(function (row) {
            row.style.display = 'none';
        });

        matchedRows.forEach(function (row, index) {
            row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
        });

        if (pageInfo) {
            pageInfo.textContent = totalMatched > 0
                ? 'Halaman ' + detailCostingCurrentPage + ' dari ' + totalPages + ' (' + totalMatched + ' baris)'
                : 'Halaman 0 dari 0 (0 baris)';
        }

        if (prevButton) {
            prevButton.disabled = detailCostingCurrentPage <= 1 || totalMatched === 0;
            prevButton.style.opacity = prevButton.disabled ? '0.5' : '1';
            prevButton.style.cursor = prevButton.disabled ? 'not-allowed' : 'pointer';
        }

        if (nextButton) {
            nextButton.disabled = detailCostingCurrentPage >= totalPages || totalMatched === 0;
            nextButton.style.opacity = nextButton.disabled ? '0.5' : '1';
            nextButton.style.cursor = nextButton.disabled ? 'not-allowed' : 'pointer';
        }

        if (paginationContainer) {
            paginationContainer.style.display = dataRows.length > 0 ? 'flex' : 'none';
        }

        if (clearButton) {
            clearButton.style.display = filter !== '' ? 'inline-flex' : 'none';
        }

        if (noResultsRow) {
            noResultsRow.style.display = totalMatched === 0 && dataRows.length > 0 ? '' : 'none';
        }
    }

    function changeDetailCostingPage(step) {
        detailCostingCurrentPage += step;
        filterDetailCostingTable(false);
    }

    function saveStatusProject(selectEl) {
        if (!selectEl) {
            return;
        }

        const revisionId = selectEl.dataset.revisionId;
        const previousStatus = selectEl.dataset.lastSavedStatus || 'A00';
        const status = (selectEl.value || '').trim();

        if (!revisionId) {
            alert('Revision ID tidak ditemukan.');
            selectEl.value = previousStatus;
            updateStatusProjectDropdownColor(selectEl);
            return;
        }

        updateStatusProjectDropdownColor(selectEl);

        /*
         * Untuk A04/A05 gunakan normal form submit, bukan fetch.
         * Alasannya: controller perlu mengirim session flash:
         * - open_document_revision_id
         * - open_document_target_status
         * agar halaman Project Document bisa auto-open modal.
         */
        if (status === 'A04' || status === 'A05') {
            showStatusProjectLoading('Membuka halaman Project Document...');

            const form = document.getElementById('statusProjectUpdateForm');
            const statusInput = document.getElementById('statusProjectUpdateStatus');

            if (!form || !statusInput) {
                hideStatusProjectLoading();
                selectEl.value = previousStatus;
                updateStatusProjectDropdownColor(selectEl);
                alert('Form update status project tidak ditemukan.');
                return;
            }

            statusInput.value = status;
            form.action = '/costing/status-project/' + encodeURIComponent(revisionId);
            form.submit();
            return;
        }

        showStatusProjectLoading('Menyimpan status project...');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value || '';

        selectEl.disabled = true;

        fetch('/costing/status-project/' + encodeURIComponent(revisionId), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status: status }),
        })
        .then(async function(res) {
            const data = await res.json().catch(function () {
                return {};
            });

            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Gagal menyimpan status project.');
            }

            return data;
        })
        .then(function() {
            selectEl.dataset.lastSavedStatus = status;
            selectEl.disabled = false;
            hideStatusProjectLoading();

            if (status === 'A00') {
                window.location.reload();
            }
        })
        .catch(function(error) {
            selectEl.disabled = false;
            selectEl.value = previousStatus;
            updateStatusProjectDropdownColor(selectEl);
            hideStatusProjectLoading();

            alert(error.message || 'Gagal menyimpan status project. Silakan coba lagi.');
        });
    }

    function showStatusProjectLoading(message) {
        let overlay = document.getElementById('statusProjectLoadingOverlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'statusProjectLoadingOverlay';
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.zIndex = '100000';
            overlay.style.background = 'rgba(15, 23, 42, 0.42)';
            overlay.style.backdropFilter = 'blur(2px)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.innerHTML = `
                <div style="background:#fff; border-radius:16px; padding:1.4rem 1.6rem; min-width:220px; box-shadow:0 18px 45px rgba(15,23,42,.20); text-align:center;">
                    <div style="width:42px;height:42px;border:3px solid #dbeafe;border-top-color:#2563eb;border-radius:999px;margin:0 auto .85rem;animation:statusProjectSpin .75s linear infinite;"></div>
                    <div id="statusProjectLoadingText" style="font-size:.86rem;font-weight:800;color:#334155;">Memuat halaman...</div>
                </div>
            `;

            const style = document.createElement('style');
            style.id = 'statusProjectLoadingStyle';
            style.textContent = '@keyframes statusProjectSpin{to{transform:rotate(360deg)}}';
            document.head.appendChild(style);
            document.body.appendChild(overlay);
        }

        const text = document.getElementById('statusProjectLoadingText');
        if (text) {
            text.textContent = message || 'Memuat halaman...';
        }

        overlay.style.display = 'flex';
    }

    function hideStatusProjectLoading() {
        const overlay = document.getElementById('statusProjectLoadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    function updateStatusProjectDropdownColor(selectEl) {
        if (!selectEl) {
            return;
        }

        const statusColors = {
            A00: '#2563eb',
            A04: '#dc2626',
            A05: '#16a34a',
        };

        const selectedValue = (selectEl.value || '').trim();
        const bgColor = statusColors[selectedValue] || '#64748b';
        selectEl.dataset.statusProjectColor = bgColor;
        selectEl.style.backgroundColor = bgColor;
        selectEl.style.borderColor = bgColor;
        selectEl.style.color = '#ffffff';

        // Ensure option colors stay applied
        selectEl.querySelectorAll('option').forEach(function(opt) {
            const optColor = statusColors[opt.value];
            if (optColor) {
                opt.style.backgroundColor = optColor;
                opt.style.color = '#fff';
                opt.style.fontWeight = '700';
            }
        });
    }

    function initializeStatusProjectDropdownColors() {
        const statusDropdowns = document.querySelectorAll('.status-project-select');
        statusDropdowns.forEach(function (dropdown) {
            updateStatusProjectDropdownColor(dropdown);
        });
    }

    function clearDetailCostingSearch() {
        const searchInput = document.getElementById('detailCostingSearch');
        if (!searchInput) {
            return;
        }

        searchInput.value = '';
        searchInput.focus();
        filterDetailCostingTable();
    }

    function toggleDetailCostCell(button) {
        const container = button.closest('.cost-mask-cell');
        if (!container) {
            return;
        }

        const masked = container.querySelector('.cost-masked');
        const value = container.querySelector('.cost-value');
        if (!masked || !value) {
            return;
        }

        const isHidden = value.style.display === 'none';
        if (isHidden) {
            value.style.display = 'inline';
            masked.style.display = 'none';
            button.style.color = 'var(--primary)';
            button.setAttribute('title', 'Sembunyikan nilai');
            button.setAttribute('aria-label', 'Sembunyikan nilai');
        } else {
            value.style.display = 'none';
            masked.style.display = 'inline';
            button.style.color = 'var(--slate-500)';
            button.setAttribute('title', 'Lihat nilai');
            button.setAttribute('aria-label', 'Lihat nilai');
        }
    }
    
    // Number formatting helper
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(number);
    }

    document.addEventListener('DOMContentLoaded', function () {
        filterDetailCostingTable(true);
        initializeStatusProjectDropdownColors();
    });
</script>
@endsection
