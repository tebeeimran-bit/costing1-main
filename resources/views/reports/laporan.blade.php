@extends('layouts.app')
@section('title', 'Laporan & Export')
@section('page-title', 'Laporan & Export')
@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Menu Utama</a>
    <span class="breadcrumb-separator">/</span>
    <span>Laporan & Export</span>
@endsection

@section('content')
<style>
    .lap-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
    .lap-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .lap-table th { padding: 0.6rem 0.75rem; text-align: left; }
    .lap-table td { padding: 0.55rem 0.75rem; border-bottom: 1px solid var(--slate-200); }
    .lap-table tr:hover { background: #f8fafc; }
    .th-blue { background: #2563eb; color: #fff; }
    .th-teal { background: #0f766e; color: #fff; }
    .cost-bar { height: 6px; border-radius: 3px; margin-top: 0.25rem; }
</style>

{{-- By Customer --}}
<div class="lap-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Rekap per Customer</h3></div>
        <div class="material-table-container">
            <table class="lap-table">
                <thead><tr><th class="th-blue">Customer</th><th class="th-blue" style="text-align:center;">Projects</th><th class="th-blue" style="text-align:right;">Material</th><th class="th-blue" style="text-align:right;">Labor</th><th class="th-blue" style="text-align:right;">Overhead</th><th class="th-blue" style="text-align:right;">Total COGM</th></tr></thead>
                <tbody>
                    @php $custMax = $costingsByCustomer->max('cogm') ?: 1; @endphp
                    @foreach($costingsByCustomer as $c)
                    <tr>
                        <td>
                            <strong>{{ $c->customer }}</strong>
                            <div class="cost-bar" style="width: {{ $c->cogm / $custMax * 100 }}%; background: #2563eb;"></div>
                        </td>
                        <td style="text-align:center;">{{ $c->projects }}</td>
                        <td style="text-align:right;">Rp {{ number_format($c->material, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($c->labor, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($c->overhead, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($c->cogm, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr style="background: #f1f5f9;">
                        <td><strong>TOTAL</strong></td>
                        <td style="text-align:center; font-weight:700;">{{ $costingsByCustomer->sum('projects') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($costingsByCustomer->sum('material'), 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($costingsByCustomer->sum('labor'), 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($costingsByCustomer->sum('overhead'), 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($costingsByCustomer->sum('cogm'), 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Rekap per Business Category</h3></div>
        <div class="material-table-container">
            <table class="lap-table">
                <thead><tr><th class="th-teal">Kategori</th><th class="th-teal" style="text-align:center;">Projects</th><th class="th-teal" style="text-align:right;">Material</th><th class="th-teal" style="text-align:right;">Labor</th><th class="th-teal" style="text-align:right;">Overhead</th><th class="th-teal" style="text-align:right;">Total COGM</th></tr></thead>
                <tbody>
                    @php $catMax = $costingsByCategory->max('cogm') ?: 1; @endphp
                    @foreach($costingsByCategory as $c)
                    <tr>
                        <td>
                            <strong>{{ $c->category }}</strong>
                            <div class="cost-bar" style="width: {{ $c->cogm / $catMax * 100 }}%; background: #0f766e;"></div>
                        </td>
                        <td style="text-align:center;">{{ $c->projects }}</td>
                        <td style="text-align:right;">Rp {{ number_format($c->material, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($c->labor, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($c->overhead, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($c->cogm, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr style="background: #f1f5f9;">
                        <td><strong>TOTAL</strong></td>
                        <td style="text-align:center; font-weight:700;">{{ $costingsByCategory->sum('projects') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($costingsByCategory->sum('material'), 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($costingsByCategory->sum('labor'), 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($costingsByCategory->sum('overhead'), 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($costingsByCategory->sum('cogm'), 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Visual Breakdown --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Komposisi Biaya per Customer</h3></div>
    <div style="padding: 1rem;">
        @php $totalAll = $costingsByCustomer->sum('cogm') ?: 1; @endphp
        @foreach($costingsByCustomer as $c)
        <div style="margin-bottom: 1rem;">
            <div style="display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:0.3rem;">
                <strong>{{ $c->customer }}</strong>
                <span style="color:var(--slate-600);">Rp {{ number_format($c->cogm, 0, ',', '.') }} ({{ number_format($c->cogm / $totalAll * 100, 1) }}%)</span>
            </div>
            <div style="display:flex; height:20px; border-radius:6px; overflow:hidden; background:var(--slate-100);">
                @php $cogm = $c->material + $c->labor + $c->overhead; $cogm = $cogm ?: 1; @endphp
                <div style="width:{{ $c->material / $cogm * 100 }}%; background:#f97316;" title="Material: Rp {{ number_format($c->material, 0, ',', '.') }}"></div>
                <div style="width:{{ $c->labor / $cogm * 100 }}%; background:#0f766e;" title="Labor: Rp {{ number_format($c->labor, 0, ',', '.') }}"></div>
                <div style="width:{{ $c->overhead / $cogm * 100 }}%; background:#7c3aed;" title="Overhead: Rp {{ number_format($c->overhead, 0, ',', '.') }}"></div>
            </div>
        </div>
        @endforeach
        <div style="display:flex; gap:1.5rem; margin-top:1rem; font-size:0.78rem;">
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#f97316;margin-right:0.3rem;"></span>Material</span>
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#0f766e;margin-right:0.3rem;"></span>Labor</span>
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#7c3aed;margin-right:0.3rem;"></span>Overhead</span>
        </div>
    </div>
</div>
@endsection
