@extends('layouts.app')
@section('title', 'COGM Submission')
@section('page-title', 'COGM Submission')
@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Menu Utama</a>
    <span class="breadcrumb-separator">/</span>
    <span>COGM Submission</span>
@endsection

@section('content')
<style>
    .sub-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .sub-card { padding: 1.25rem; border-radius: 12px; color: #fff; }
    .sub-card .sc-label { font-size: 0.78rem; font-weight: 600; opacity: 0.9; }
    .sub-card .sc-value { font-size: 1.5rem; font-weight: 800; }
    .sub-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .sub-table th { background: var(--blue-600); color: #fff; padding: 0.6rem 0.75rem; text-align: left; }
    .sub-table td { padding: 0.55rem 0.75rem; border-bottom: 1px solid var(--slate-200); }
    .sub-table tr:hover { background: #f8fafc; }
    .submitter-badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; background: #dbeafe; color: #1d4ed8; }
</style>

<div class="sub-cards">
    <div class="sub-card" style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
        <div class="sc-label">Total Submissions</div>
        <div class="sc-value">{{ $totalSubmissions }}</div>
    </div>
    <div class="sub-card" style="background: linear-gradient(135deg, #0f766e, #0d9488);">
        <div class="sc-label">Total Nilai COGM Submitted</div>
        <div class="sc-value">Rp {{ number_format($totalCogmValue, 0, ',', '.') }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Riwayat COGM Submission</h3></div>
    <div class="material-table-container">
        <table class="sub-table" style="min-width: 900px;">
            <thead>
                <tr><th>No.</th><th>Customer</th><th>Model</th><th>Assy Name</th><th style="text-align:right;">Nilai COGM</th><th>Submitted By</th><th>PIC Marketing</th><th>Tanggal</th><th>Notes</th></tr>
            </thead>
            <tbody>
                @forelse($submissions as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $s->customer }}</td>
                    <td>{{ $s->model }}</td>
                    <td>{{ $s->assy_name }}</td>
                    <td style="text-align:right; font-weight:700;">Rp {{ number_format($s->cogm_value, 0, ',', '.') }}</td>
                    <td><span class="submitter-badge">{{ $s->submitted_by }}</span></td>
                    <td>{{ $s->pic_marketing }}</td>
                    <td>{{ $s->submitted_at ? $s->submitted_at->format('d M Y H:i') : '-' }}</td>
                    <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $s->notes }}">{{ $s->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center; color:var(--slate-400); padding:2rem;">Belum ada submission.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
