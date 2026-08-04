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
    .rate-actions { display: flex; justify-content: center; gap: 0.4rem; }
    .rate-action { border: 0; border-radius: 6px; padding: 0.35rem 0.5rem; cursor: pointer; font-size: 0.75rem; font-weight: 700; }
    .rate-action-edit { background: #dbeafe; color: #1d4ed8; }
    .rate-action-delete { background: #fee2e2; color: #dc2626; }
    .rate-modal { position: fixed; inset: 0; z-index: 1100; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(15, 23, 42, 0.5); }
    .rate-modal.is-open { display: flex; }
    .rate-modal-panel { width: min(720px, 100%); max-height: calc(100vh - 2rem); overflow-y: auto; border-radius: 12px; background: #fff; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25); }
    .rate-modal-header, .rate-modal-footer { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 1rem 1.25rem; }
    .rate-modal-header { border-bottom: 1px solid var(--slate-200); }
    .rate-modal-footer { justify-content: flex-end; border-top: 1px solid var(--slate-200); }
    .rate-modal-body { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.85rem; padding: 1.25rem; }
    .rate-field label { display: block; margin-bottom: 0.3rem; color: var(--slate-600); font-size: 0.75rem; font-weight: 600; }
    @media (max-width: 700px) { .rate-modal-body { grid-template-columns: 1fr; } }
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
        <div class="rc-label">LME</div>
        <div class="rc-value">{{ $latest && $latest->lme_copper !== null ? 'Rp '.number_format($latest->lme_copper, 0, ',', '.') : '-' }}</div>
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
            <div><label style="display:block; font-size:0.75rem; font-weight:600; color:var(--slate-600); margin-bottom:0.3rem;">LME</label><input type="number" step="0.01" name="lme_copper" class="form-input" placeholder="Rp 13.574"></div>
            <div><label style="display:block; font-size:0.75rem; font-weight:600; color:var(--slate-600); margin-bottom:0.3rem;">Sumber</label><input type="text" name="source" class="form-input" placeholder="Bank Indonesia"></div>
            <button type="submit" class="btn btn-primary" style="height: fit-content;">Simpan</button>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h3 class="card-title">Riwayat Exchange Rate</h3></div>
    <div class="material-table-container">
        <table class="rate-table">
            <thead><tr><th>Periode</th><th style="text-align:right;">USD/IDR</th><th style="text-align:right;">JPY/IDR</th><th style="text-align:right;">LME</th><th>Sumber</th><th style="width:110px;text-align:center;">Aksi</th></tr></thead>
            <tbody>
                @forelse($exchangeRates as $r)
                <tr>
                    <td><strong>{{ $r->period_date->format('M Y') }}</strong></td>
                    <td style="text-align:right;">Rp {{ number_format($r->usd_to_idr, 0, ',', '.') }}</td>
                    <td style="text-align:right;">Rp {{ number_format($r->jpy_to_idr, 2, ',', '.') }}</td>
                    <td style="text-align:right;">{{ $r->lme_copper !== null ? 'Rp '.number_format($r->lme_copper, 0, ',', '.') : '-' }}</td>
                    <td>{{ $r->source }}</td>
                    <td style="text-align:center;">
                        <div class="rate-actions">
                            <button type="button" class="rate-action rate-action-edit" data-edit-rate data-action="{{ route('rate-kurs.update', $r->id) }}" data-period="{{ $r->period_date->format('Y-m-d') }}" data-usd="{{ $r->usd_to_idr }}" data-jpy="{{ $r->jpy_to_idr }}" data-lme="{{ $r->lme_copper }}" data-source="{{ $r->source }}">Edit</button>
                            <form method="POST" action="{{ route('rate-kurs.destroy', $r->id) }}" onsubmit="return confirm('Hapus rate ini?')">@csrf @method('DELETE')
                                <button type="submit" class="rate-action rate-action-delete" aria-label="Hapus"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                            </form>
                        </div>
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

<div id="edit-rate-modal" class="rate-modal" aria-hidden="true">
    <div class="rate-modal-panel" role="dialog" aria-modal="true" aria-labelledby="edit-rate-title">
        <form id="edit-rate-form" method="POST">
            @csrf
            @method('PUT')
            <div class="rate-modal-header">
                <h3 id="edit-rate-title" class="card-title">Edit Exchange Rate</h3>
                <button type="button" class="rate-action" data-close-rate aria-label="Tutup">&times;</button>
            </div>
            <div class="rate-modal-body">
                <div class="rate-field"><label>Periode</label><input type="date" name="period_date" class="form-input" required></div>
                <div class="rate-field"><label>USD/IDR</label><input type="number" step="0.01" name="usd_to_idr" class="form-input"></div>
                <div class="rate-field"><label>JPY/IDR</label><input type="number" step="0.00001" name="jpy_to_idr" class="form-input"></div>
                <div class="rate-field"><label>LME</label><input type="number" step="0.01" name="lme_copper" class="form-input"></div>
                <div class="rate-field"><label>Sumber</label><input type="text" name="source" class="form-input" maxlength="100"></div>
            </div>
            <div class="rate-modal-footer">
                <button type="button" class="btn btn-secondary" data-close-rate>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('edit-rate-modal');
        const form = document.getElementById('edit-rate-form');
        if (!modal || !form) return;

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        };

        document.querySelectorAll('[data-edit-rate]').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.action;
                form.elements.period_date.value = button.dataset.period || '';
                form.elements.usd_to_idr.value = button.dataset.usd || '';
                form.elements.jpy_to_idr.value = button.dataset.jpy || '';
                form.elements.lme_copper.value = button.dataset.lme || '';
                form.elements.source.value = button.dataset.source || '';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                form.elements.period_date.focus();
            });
        });

        document.querySelectorAll('[data-close-rate]').forEach((button) => button.addEventListener('click', closeModal));
        modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
    })();
</script>
@endsection
