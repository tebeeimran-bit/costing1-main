@extends('layouts.app')
@section('title', 'Unpriced Parts')
@section('page-title', 'Unpriced Parts')
@section('breadcrumb')
    <a href="{{ route('database.parts') }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Unpriced Parts</span>
@endsection

@section('content')
<style>
    .up-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .up-card { padding: 1.25rem; border-radius: 12px; color: #fff; }
    .up-card .uc-label { font-size: 0.78rem; font-weight: 600; opacity: 0.9; }
    .up-card .uc-value { font-size: 1.5rem; font-weight: 800; }
    .up-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .up-table th { background: var(--blue-600); color: #fff; padding: 0.6rem 0.75rem; text-align: left; }
    .up-table td { padding: 0.55rem 0.75rem; border-bottom: 1px solid var(--slate-200); }
    .up-table tr:hover { background: #f8fafc; }
    .resolved-badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; }
    .resolved-badge.yes { background: #d1fae5; color: #065f46; }
    .resolved-badge.no { background: #fef3c7; color: #92400e; }
</style>

<div class="up-cards">
    <div class="up-card" style="background: linear-gradient(135deg, #f97316, #ea580c);">
        <div class="uc-label">Total Parts</div>
        <div class="uc-value">{{ $totalParts }}</div>
    </div>
    <div class="up-card" style="background: linear-gradient(135deg, #16a34a, #15803d);">
        <div class="uc-label">Resolved</div>
        <div class="uc-value">{{ $resolvedParts }}</div>
    </div>
    <div class="up-card" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
        <div class="uc-label">Unresolved</div>
        <div class="uc-value">{{ $unresolvedParts }}</div>
    </div>
</div>

@if($totalParts > 0)
<div style="margin-bottom:1rem;">
    <div style="display:flex; height:10px; border-radius:6px; overflow:hidden; background:var(--slate-200);">
        <div style="width:{{ $resolvedParts / $totalParts * 100 }}%; background:#16a34a;" title="Resolved {{ $resolvedParts }}"></div>
        <div style="width:{{ $unresolvedParts / $totalParts * 100 }}%; background:#dc2626;" title="Unresolved {{ $unresolvedParts }}"></div>
    </div>
    <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--slate-500); margin-top:0.3rem;">
        <span>{{ number_format($resolvedParts / $totalParts * 100, 1) }}% Resolved</span>
        <span>{{ number_format($unresolvedParts / $totalParts * 100, 1) }}% Unresolved</span>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header"><h3 class="card-title">Daftar Unpriced Parts</h3></div>
    <div class="material-table-container">
        <table class="up-table" style="min-width: 900px;">
            <thead>
                <tr><th>No.</th><th>Part Number</th><th>Part Name</th><th>Customer</th><th>Model</th><th style="text-align:right;">Detected Price</th><th style="text-align:right;">Manual Price</th><th>Status</th><th>Source</th><th>Resolved At</th></tr>
            </thead>
            <tbody>
                @forelse($parts as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $p->part_number }}</strong></td>
                    <td>{{ $p->part_name }}</td>
                    <td>{{ $p->customer }}</td>
                    <td>{{ $p->model }}</td>
                    <td style="text-align:right;">{{ $p->detected_price ? number_format($p->detected_price, 4, ',', '.') : '-' }}</td>
                    <td style="text-align:right;">{{ $p->manual_price ? number_format($p->manual_price, 4, ',', '.') : '-' }}</td>
                    <td>
                        @if($p->resolved_at)
                            <span class="resolved-badge yes">Resolved</span>
                        @else
                            <span class="resolved-badge no">Pending</span>
                        @endif
                    </td>
                    <td>{{ $p->resolution_source }}</td>
                    <td>{{ $p->resolved_at ? $p->resolved_at->format('d M Y') : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; color:var(--slate-400); padding:2rem;">Tidak ada unpriced parts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
