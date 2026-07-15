@extends('layouts.app')
@section('title', 'Rate & Kurs')
@section('page-title', 'Rate & Kurs')
@section('breadcrumb')
    <a href="{{ route('database.parts') }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Rate & Kurs</span>
@endsection

@section('content')
<style>
    .rate-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .rate-card { padding: 1.25rem; border-radius: 12px; color: #fff; text-align: center; }
    .rate-card .rc-label { font-size: 0.78rem; font-weight: 600; opacity: 0.9; }
    .rate-card .rc-value { font-size: 1.4rem; font-weight: 800; margin-top: 0.25rem; }
    .rate-card .rc-sub { font-size: 0.72rem; opacity: 0.7; margin-top: 0.15rem; }
    .rate-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .rate-table th { background: var(--blue-600); color: #fff; padding: 0.6rem 0.75rem; text-align: left; }
    .rate-table td { padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--slate-200); }
    .rate-table tr:hover { background: #f8fafc; }
</style>

@if(session('success'))
<div style="background: #d1fae5; color: #065f46; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
@endif

@php
    $latest = $exchangeRates->first();
@endphp
<div class="rate-cards">
    <div class="rate-card" style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
        <div class="rc-label">USD / IDR</div>
        <div class="rc-value">Rp {{ $latest ? number_format($latest->usd_to_idr, 0, ',', '.') : '-' }}</div>
        <div class="rc-sub">{{ $latest ? $latest->period_date->format('M Y') : '-' }}</div>
    </div>
    <div class="rate-card" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
        <div class="rc-label">JPY / IDR</div>
        <div class="rc-value">Rp {{ $latest ? number_format($latest->jpy_to_idr, 2, ',', '.') : '-' }}</div>
        <div class="rc-sub">{{ $latest ? $latest->period_date->format('M Y') : '-' }}</div>
    </div>
    <div class="rate-card" style="background: linear-gradient(135deg, #16a34a, #15803d);">
        <div class="rc-label">LME Copper (USD/ton)</div>
        <div class="rc-value">$ {{ $latest ? number_format($latest->lme_copper, 0, ',', '.') : '-' }}</div>
        <div class="rc-sub">{{ $latest ? $latest->period_date->format('M Y') : '-' }}</div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Tambah Exchange Rate</h3>
    </div>
    <div style="padding: 1rem;">
        <form method="POST" action="{{ route('rate-kurs.store') }}" style="display: grid; grid-template-columns: repeat(5, 1fr) auto; gap: 0.75rem; align-items: end;">
            @csrf
            <div><label style="display:block; font-size:0.75rem; font-weight:600; color:var(--slate-600); margin-bottom:0.3rem;">Periode</label><input type="date" name="period_date" class="form-input" required></div>
            <div><label style="display:block; font-size:0.75rem; font-weight:600; color:var(--slate-600); margin-bottom:0.3rem;">USD/IDR</label><input type="number" step="0.01" name="usd_to_idr" class="form-input" placeholder="15800"></div>
            <div><label style="display:block; font-size:0.75rem; font-weight:600; color:var(--slate-600); margin-bottom:0.3rem;">JPY/IDR</label><input type="number" step="0.00001" name="jpy_to_idr" class="form-input" placeholder="107"></div>
            <div><label style="display:block; font-size:0.75rem; font-weight:600; color:var(--slate-600); margin-bottom:0.3rem;">LME Copper</label><input type="number" step="0.01" name="lme_copper" class="form-input" placeholder="8500"></div>
            <div><label style="display:block; font-size:0.75rem; font-weight:600; color:var(--slate-600); margin-bottom:0.3rem;">Sumber</label><input type="text" name="source" class="form-input" placeholder="Bank Indonesia"></div>
            <button type="submit" class="btn btn-primary" style="height: fit-content;">Simpan</button>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h3 class="card-title">Riwayat Exchange Rate</h3></div>
    <div class="material-table-container">
        <table class="rate-table">
            <thead><tr><th>Periode</th><th style="text-align:right;">USD/IDR</th><th style="text-align:right;">JPY/IDR</th><th style="text-align:right;">LME Copper</th><th>Sumber</th><th style="width:60px;text-align:center;">Aksi</th></tr></thead>
            <tbody>
                @forelse($exchangeRates as $r)
                <tr>
                    <td><strong>{{ $r->period_date->format('M Y') }}</strong></td>
                    <td style="text-align:right;">Rp {{ number_format($r->usd_to_idr, 0, ',', '.') }}</td>
                    <td style="text-align:right;">Rp {{ number_format($r->jpy_to_idr, 2, ',', '.') }}</td>
                    <td style="text-align:right;">$ {{ number_format($r->lme_copper, 0, ',', '.') }}</td>
                    <td>{{ $r->source }}</td>
                    <td style="text-align:center;">
                        <form method="POST" action="{{ route('rate-kurs.destroy', $r->id) }}" onsubmit="return confirm('Hapus rate ini?')">@csrf @method('DELETE')
                            <button type="submit" style="border:0;background:#fee2e2;color:#dc2626;border-radius:6px;padding:0.3rem;cursor:pointer;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; color:var(--slate-400); padding:2rem;">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Wire Rate per Bulan</h3></div>
    <div class="material-table-container">
        <table class="rate-table">
            <thead><tr><th>Periode</th><th>Request</th><th style="text-align:right;">JPY Rate</th><th style="text-align:right;">USD Rate</th><th style="text-align:right;">LME Active</th><th style="text-align:right;">LME Reference</th></tr></thead>
            <tbody>
                @forelse($wireRates as $wr)
                <tr>
                    <td><strong>{{ $wr->period_month ? $wr->period_month->format('M Y') : '-' }}</strong></td>
                    <td>{{ $wr->request_name ?? '-' }}</td>
                    <td style="text-align:right;">{{ number_format($wr->jpy_rate, 2, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($wr->usd_rate, 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($wr->lme_active, 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($wr->lme_reference, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; color:var(--slate-400); padding:2rem;">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
