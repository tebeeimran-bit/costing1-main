@extends('layouts.app')

@section('title', 'Project Document')
@section('page-title', 'Project Document')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Project Document</span>
@endsection

@section('content')
    <style>
        .doc-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .doc-status-badge.ada { color: #065f46; background: #d1fae5; }
        .doc-status-badge.belum { color: #92400e; background: #fef3c7; }
        .td-a00 .doc-status-badge.ada { color: #1e40af; background: #dbeafe; }
        .td-a04 .doc-status-badge.ada { color: #991b1b; background: #fee2e2; }
        .td-a05 .doc-status-badge.ada { color: #166534; background: #dcfce7; }
        .td-a00 .doc-status-badge.belum { color: #6b7280; background: #e8edf4; }
        .td-a04 .doc-status-badge.belum { color: #6b7280; background: #f5e6e6; }
        .td-a05 .doc-status-badge.belum { color: #6b7280; background: #e6f0e8; }
        .doc-download-link {
            color: var(--blue-600);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .doc-download-link:hover { text-decoration: underline; }
        .doc-summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .doc-summary-card {
            min-height: 88px;
            padding: 1.25rem;
            border-radius: 12px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.25rem;
            width: 100%;
            box-sizing: border-box;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
        }
        .doc-summary-card .doc-label { font-size: 0.78rem; font-weight: 600; opacity: 0.9; }
        .doc-summary-card .doc-count { font-size: 1.75rem; font-weight: 800; }
        .doc-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1rem;
        }
        .doc-filter-bar .form-input,
        .doc-filter-bar .form-select { max-width: 280px; }
        @media (max-width: 768px) {
            .doc-summary-cards { grid-template-columns: 1fr; }
        }
        /* Modal */
        .doc-modal {
            position: fixed; inset: 0; z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
        }
        .doc-modal.is-hidden { display: none; }
        .doc-modal-content {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 560px;
            padding: 1.5rem;
        }
        .doc-modal-content.doc-modal-wide {
            max-width: 1180px;
        }
        .doc-modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem;
        }
        .doc-modal-title { font-size: 1.1rem; font-weight: 700; color: var(--slate-800); }
        .doc-modal-close {
            border: 0; background: var(--slate-100); border-radius: 8px;
            padding: 0.4rem; cursor: pointer; color: var(--slate-500);
        }
        .doc-modal-close:hover { background: var(--slate-200); }
        .doc-form-group { margin-bottom: 0.75rem; }
        .doc-form-group label { display: block; font-size: 0.72rem; font-weight: 600; color: var(--slate-600); text-transform: uppercase; margin-bottom: 0.3rem; }
        .doc-form-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--slate-200); }
        .doc-section-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .doc-section-col {
            padding: 0.85rem;
            border-radius: 10px;
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
        }
        .doc-section-title {
            font-size: 0.78rem; font-weight: 700;
            margin: 0 0 0.65rem; padding-bottom: 0.35rem;
            border-bottom: 2px solid var(--slate-200);
        }
        .doc-section-col:nth-child(1) .doc-section-title { color: #2563eb; border-color: #93c5fd; }
        .doc-section-col:nth-child(2) .doc-section-title { color: #dc2626; border-color: #fca5a5; }
        .doc-section-col:nth-child(3) .doc-section-title { color: #16a34a; border-color: #86efac; }
        .doc-section-col:nth-child(4) .doc-section-title { color: #2563eb; border-color: #93c5fd; }
        .doc-section-col:nth-child(5) .doc-section-title { color: #0f766e; border-color: #5eead4; }
        .btn-action {
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: 6px; padding: 0.35rem; cursor: pointer;
            transition: background 0.15s;
        }
        .btn-action.btn-edit { background: #dbeafe; color: #2563eb; }
        .btn-action.btn-edit:hover { background: #bfdbfe; }
        .btn-action.btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-action.btn-delete:hover { background: #fecaca; }
        .delete-modal-body { text-align: center; padding: 1rem 0; }
        .delete-modal-text { font-size: 0.9rem; color: var(--slate-600); margin-bottom: 0.5rem; }
        .delete-modal-name { font-weight: 700; color: var(--slate-800); }
        /* Pagination */
        .doc-pagination .pagination { display: flex; gap: 0.25rem; margin: 0; list-style: none; padding: 0; }
        .doc-pagination .page-item .page-link {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 2rem; height: 2rem; padding: 0 0.5rem;
            border-radius: 6px; border: 1px solid var(--slate-200);
            font-size: 0.82rem; font-weight: 600; color: var(--slate-600);
            background: #fff; text-decoration: none; transition: all 0.15s;
        }
        .doc-pagination .page-item .page-link:hover { background: #eff6ff; color: #2563eb; border-color: #93c5fd; }
        .doc-pagination .page-item.active .page-link { background: #2563eb; color: #fff; border-color: #2563eb; }
        .doc-pagination .page-item.disabled .page-link { opacity: 0.4; pointer-events: none; }
        /* Color-coded table columns */
        .th-a00 { background: #2563eb !important; color: #fff !important; }
        .th-a04 { background: #dc2626 !important; color: #fff !important; }
        .th-a05 { background: #16a34a !important; color: #fff !important; }
        .td-a00 { background: #eff6ff; }
        .td-a04 { background: #fef2f2; }
        .td-a05 { background: #f0fdf4; }

        /* Engineering document collection cards */
        .engineering-doc-panel {
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 14px;
            padding: 1.15rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }
        .engineering-doc-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .engineering-doc-title {
            margin: 0;
            color: var(--slate-800);
            font-size: 1rem;
            font-weight: 850;
        }
        .engineering-doc-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .btn-folder-storage {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 34px;
            padding: 0.48rem 0.75rem;
            border-radius: 9px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: 1px solid rgba(37, 99, 235, 0.25);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 900;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
            white-space: nowrap;
        }
        .btn-folder-storage:hover {
            filter: brightness(1.03);
            transform: translateY(-1px);
        }
        .engineering-doc-note {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.65rem;
            border-radius: 9px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .engineering-doc-cards {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.85rem;
        }
        .engineering-doc-card {
            min-height: 86px;
            border-radius: 13px;
            border: 1px solid transparent;
            padding: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            overflow: hidden;
        }
        .engineering-doc-card.blue { background: linear-gradient(135deg, #eff6ff, #dbeafe); border-color: #bfdbfe; color: #1d4ed8; }
        .engineering-doc-card.yellow { background: linear-gradient(135deg, #fffbeb, #fef3c7); border-color: #fde68a; color: #b45309; }
        .engineering-doc-card.orange { background: linear-gradient(135deg, #fff7ed, #ffedd5); border-color: #fed7aa; color: #ea580c; }
        .engineering-doc-card.green { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-color: #bbf7d0; color: #15803d; }
        .engineering-doc-card.red { background: linear-gradient(135deg, #fef2f2, #fee2e2); border-color: #fecaca; color: #dc2626; }
        .engineering-doc-card.purple { background: linear-gradient(135deg, #faf5ff, #f3e8ff); border-color: #e9d5ff; color: #7e22ce; }
        .engineering-doc-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.66);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.5);
            flex: 0 0 auto;
        }
        .engineering-doc-icon svg {
            width: 22px;
            height: 22px;
        }
        .engineering-doc-label {
            font-size: 0.76rem;
            font-weight: 850;
            margin-bottom: 0.18rem;
        }
        .engineering-doc-count {
            font-size: 1.55rem;
            line-height: 1;
            font-weight: 950;
        }
        .engineering-doc-unit {
            font-size: 0.72rem;
            color: var(--slate-600);
            font-weight: 750;
            margin-top: 0.28rem;
        }
        .th-partlist { background: #2563eb !important; color: #fff !important; }
        .th-umh { background: #0f766e !important; color: #fff !important; }
        .td-partlist { background: #f8fafc; }
        .td-umh { background: #f0fdfa; }
        @media (max-width: 1280px) {
            .engineering-doc-cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .engineering-doc-cards { grid-template-columns: 1fr; }
            .engineering-doc-head { align-items: flex-start; flex-direction: column; }
            .engineering-doc-note { white-space: normal; }
        }
    </style>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fde68a;">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="doc-summary-cards">
        <div class="doc-summary-card" style="background: #2563eb;">
            <span class="doc-label">A00 (RFQ/RFI)</span>
            <span class="doc-count">{{ $a00Count }}</span>
        </div>
        <div class="doc-summary-card" style="background: #dc2626;">
            <span class="doc-label">A04 (Cancelled/Failed)</span>
            <span class="doc-count">{{ $a04Count }}</span>
        </div>
        <div class="doc-summary-card" style="background: #16a34a;">
            <span class="doc-label">A05 (Die Go)</span>
            <span class="doc-count">{{ $a05Count }}</span>
        </div>
    </div>

    @php
        $partlistMasukCount = $partlistMasukCount ?? 0;
        $belumPartlistCount = $belumPartlistCount ?? 0;
        $revisiPartlistCount = $revisiPartlistCount ?? 0;
        $umhMasukCount = $umhMasukCount ?? 0;
        $belumUmhCount = $belumUmhCount ?? 0;
        $revisiUmhCount = $revisiUmhCount ?? 0;
    @endphp

    <div class="engineering-doc-panel">
        <div class="engineering-doc-head">
            <h3 class="engineering-doc-title">Pengumpulan Dokumen Engineering</h3>
            <div class="engineering-doc-actions">
                <div class="engineering-doc-note">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 8h.01"></path>
                        <path d="M11 12h1v5h1"></path>
                    </svg>
                    Pengumpulan Partlist dapat lebih dari 1x karena adanya revisi atau perubahan spesifikasi dari customer.
                </div>
                <a href="{{ route('database.document-recap', absolute: false) }}" class="btn-folder-storage">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v1H3V7Z"></path>
                        <path d="M3 10h18v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7Z"></path>
                    </svg>
                    Buka Folder Penyimpanan
                </a>
            </div>
        </div>

        <div class="engineering-doc-cards">
            <div class="engineering-doc-card blue">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M4 4h16v16H4z"></path>
                        <path d="M8 8h8M8 12h8M8 16h5"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Partlist Masuk</div>
                    <div class="engineering-doc-count">{{ number_format($partlistMasukCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">project</div>
                </div>
            </div>

            <div class="engineering-doc-card yellow">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Belum Partlist</div>
                    <div class="engineering-doc-count">{{ number_format($belumPartlistCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">project</div>
                </div>
            </div>

            <div class="engineering-doc-card orange">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M21 12a9 9 0 0 1-15.4 6.36"></path>
                        <path d="M3 12A9 9 0 0 1 18.4 5.64"></path>
                        <path d="M3 3v6h6"></path>
                        <path d="M21 21v-6h-6"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Revisi Partlist</div>
                    <div class="engineering-doc-count">{{ number_format($revisiPartlistCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">revisi</div>
                </div>
            </div>

            <div class="engineering-doc-card green">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M7 3h7l5 5v13H7z"></path>
                        <path d="M14 3v5h5"></path>
                        <path d="M10 13h6M10 17h6"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">UMH Masuk</div>
                    <div class="engineering-doc-count">{{ number_format($umhMasukCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">project</div>
                </div>
            </div>

            <div class="engineering-doc-card red">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Belum UMH</div>
                    <div class="engineering-doc-count">{{ number_format($belumUmhCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">project</div>
                </div>
            </div>

            <div class="engineering-doc-card purple">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M21 12a9 9 0 0 1-15.4 6.36"></path>
                        <path d="M3 12A9 9 0 0 1 18.4 5.64"></path>
                        <path d="M3 3v6h6"></path>
                        <path d="M21 21v-6h-6"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Revisi UMH</div>
                    <div class="engineering-doc-count">{{ number_format($revisiUmhCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">revisi</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('database.project-documents') }}" id="docFilterForm">
    <div class="doc-filter-bar">
        <a href="{{ route('project', absolute: false) }}" class="btn btn-secondary" style="padding: 0.55rem 1rem; font-size: 0.85rem; white-space: nowrap;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5"></path>
                <path d="M12 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Project
        </a>
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.2rem 0.8rem; border: 1px solid var(--slate-200); border-radius: 12px; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); max-width: 420px; width: 100%;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--slate-400); flex-shrink: 0;">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="M20 20l-3.5-3.5"></path>
            </svg>
            <input
                type="text"
                name="search"
                id="docSearchInput"
                placeholder="Cari customer, model, part name..."
                value="{{ $search }}"
                style="border: 0; outline: none; width: 100%; padding: 0.7rem 0; font-size: 0.95rem; color: var(--slate-800); background: transparent;"
            >
        </div>
        <select name="status" id="docFilterStatus" class="form-select" onchange="document.getElementById('docFilterForm').submit()" style="padding: 0.55rem 0.75rem; font-size: 0.85rem;">
            <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Semua Dokumen</option>
            <option value="a00" {{ $statusFilter === 'a00' ? 'selected' : '' }}>A00 (RFQ/RFI)</option>
            <option value="a04" {{ $statusFilter === 'a04' ? 'selected' : '' }}>A04 (Cancelled/Failed)</option>
            <option value="a05" {{ $statusFilter === 'a05' ? 'selected' : '' }}>A05 (Die Go)</option>
        </select>
        <select name="per_page" class="form-select" onchange="document.getElementById('docFilterForm').submit()" style="padding: 0.55rem 0.75rem; font-size: 0.85rem; width: auto;">
            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 / hal</option>
            <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 / hal</option>
            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 / hal</option>
            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / hal</option>
        </select>
        <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem; font-size: 0.85rem; white-space:nowrap;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            Cari
        </button>
        @if($search || $statusFilter)
        <a href="{{ route('database.project-documents') }}" class="btn btn-secondary" style="padding: 0.55rem 1rem; font-size: 0.85rem;">Reset</a>
        @endif
    </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pengumpulan Dokumen A00, A04 &amp; A05</h3>
            <span style="font-size: 0.8rem; color: var(--slate-500);">Menampilkan {{ $pagedRows->firstItem() }}–{{ $pagedRows->lastItem() }} dari {{ $pagedRows->total() }} data</span>
        </div>
        <div class="material-table-container">
            <table class="data-table" id="docProjectTable" style="min-width: 1940px;">
                <thead>
                    <tr>
                        <th style="width: 40px;">No.</th>
                        <th>Customer</th>
                        <th>Model</th>
                        <th>Part Name</th>
                        <th>Part No</th>
                        <th>Revisi</th>
                        <th class="th-a00" style="text-align: center;">A00</th>
                        <th class="th-a00">Tgl Diterima A00</th>
                        <th class="th-a00">Dokumen A00</th>
                        <th class="th-a04" style="text-align: center;">A04</th>
                        <th class="th-a04">Tgl Diterima A04</th>
                        <th class="th-a04">Dokumen A04</th>
                        <th class="th-a05" style="text-align: center;">A05</th>
                        <th class="th-a05">Tgl Diterima A05</th>
                        <th class="th-a05">Dokumen A05</th>
                        <th class="th-partlist" style="text-align: center;">Partlist</th>
                        <th class="th-partlist">Tgl Diterima Partlist</th>
                        <th class="th-partlist">Dokumen Partlist</th>
                        <th class="th-partlist">Revisi Partlist</th>
                        <th class="th-umh" style="text-align: center;">UMH</th>
                        <th class="th-umh">Tgl Diterima UMH</th>
                        <th class="th-umh">Dokumen UMH</th>
                        <th class="th-umh">Revisi UMH</th>
                        <th style="width: 80px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagedRows as $index => $row)
                        @php
                            $rev = $row->revision;
                            $project = $row->project;
                            $costing = $row->costingData;
                            $hasA00 = ($rev->a00 ?? '') === 'ada';
                            $hasA04 = ($rev->a04 ?? '') === 'ada';
                            $hasA05 = ($rev->a05 ?? '') === 'ada';

                            $hasPartlist = (bool) data_get($rev, 'partlist_file_path')
                                || data_get($rev, 'partlist') === 'ada'
                                || (bool) data_get($rev, 'partlist_original_name');

                            $hasUmh = (bool) data_get($rev, 'umh_file_path')
                                || data_get($rev, 'umh') === 'ada'
                                || (bool) data_get($rev, 'umh_original_name');

                            $partlistReceivedDate = data_get($rev, 'partlist_received_date');
                            $umhReceivedDate = data_get($rev, 'umh_received_date');

                            $partlistDocName = data_get($rev, 'partlist_original_name') ?: '';
                            $umhDocName = data_get($rev, 'umh_original_name') ?: '';

                            $partlistRevisionCount = (int) (data_get($rev, 'partlist_revision_count') ?? 0);
                            $umhRevisionCount = (int) (data_get($rev, 'umh_revision_count') ?? 0);

                            $priorityStatus = $row->status;
                        @endphp
                        <tr data-search="{{ strtolower(implode(' ', array_filter([
                            $project->customer ?? '',
                            $costing->customer->name ?? '',
                            $project->model ?? '',
                            $costing->model ?? '',
                            $project->part_name ?? '',
                            $costing->assy_name ?? '',
                            $project->part_number ?? '',
                            $costing->assy_no ?? '',
                            $rev->version_label ?? '',
                        ]))) }}"
                        data-status="{{ $priorityStatus }}">
                            <td>{{ $pagedRows->firstItem() + $loop->index }}</td>
                            <td>{{ $costing->customer->name ?? $project->customer ?? '-' }}</td>
                            <td>{{ $costing->model ?? $project->model ?? '-' }}</td>
                            <td>{{ $costing->assy_name ?? $project->part_name ?? '-' }}</td>
                            <td>{{ $costing->assy_no ?? $project->part_number ?? '-' }}</td>
                            <td>{{ $rev->version_label ?? '-' }}</td>

                            {{-- A00 --}}
                            <td class="td-a00" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasA00 ? 'ada' : 'belum' }}">
                                    @if($hasA00)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-a00">{{ $hasA00 && $rev->a00_received_date ? $rev->a00_received_date->format('d M Y') : '-' }}</td>
                            <td class="td-a00">
                                @if($hasA00 && $rev->a00_document_file_path)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'a00']) }}" class="doc-download-link" title="{{ $rev->a00_document_original_name }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($rev->a00_document_original_name, 25) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>

                            {{-- A04 --}}
                            <td class="td-a04" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasA04 ? 'ada' : 'belum' }}">
                                    @if($hasA04)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-a04">{{ $hasA04 && $rev->a04_received_date ? $rev->a04_received_date->format('d M Y') : '-' }}</td>
                            <td class="td-a04">
                                @if($hasA04 && $rev->a04_document_file_path)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'a04']) }}" class="doc-download-link" title="{{ $rev->a04_document_original_name }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($rev->a04_document_original_name, 25) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>

                            {{-- A05 --}}
                            <td class="td-a05" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasA05 ? 'ada' : 'belum' }}">
                                    @if($hasA05)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-a05">{{ $hasA05 && $rev->a05_received_date ? $rev->a05_received_date->format('d M Y') : '-' }}</td>
                            <td class="td-a05">
                                @if($hasA05 && $rev->a05_document_file_path)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'a05']) }}" class="doc-download-link" title="{{ $rev->a05_document_original_name }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($rev->a05_document_original_name, 25) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>

                            {{-- Partlist --}}
                            <td class="td-partlist" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasPartlist ? 'ada' : 'belum' }}">
                                    @if($hasPartlist)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-partlist">
                                @if($hasPartlist && $partlistReceivedDate)
                                    {{ \Carbon\Carbon::parse($partlistReceivedDate)->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="td-partlist">
                                @if($hasPartlist)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'partlist']) }}" class="doc-download-link" title="{{ $partlistDocName }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($partlistDocName, 24) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>
                            <td class="td-partlist" style="text-align:center;">
                                {{ $partlistRevisionCount > 0 ? $partlistRevisionCount . 'x' : '-' }}
                            </td>

                            {{-- UMH --}}
                            <td class="td-umh" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasUmh ? 'ada' : 'belum' }}">
                                    @if($hasUmh)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-umh">
                                @if($hasUmh && $umhReceivedDate)
                                    {{ \Carbon\Carbon::parse($umhReceivedDate)->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="td-umh">
                                @if($hasUmh)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'umh']) }}" class="doc-download-link" title="{{ $umhDocName }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($umhDocName, 24) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>
                            <td class="td-umh" style="text-align:center;">
                                {{ $umhRevisionCount > 0 ? $umhRevisionCount . 'x' : '-' }}
                            </td>
                            {{-- Aksi --}}
                            <td style="text-align: center; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <button type="button" class="btn-action btn-edit js-edit-doc-btn" data-revision-id="{{ $rev->id }}" title="Edit Dokumen"
                                        onclick="openEditDocModal({{ $rev->id }}, {{ json_encode([
                                            'customer' => $costing->customer->name ?? $project->customer ?? '-',
                                            'model' => $costing->model ?? $project->model ?? '-',
                                            'part_name' => $costing->assy_name ?? $project->part_name ?? '-',
                                            'a00' => $rev->a00 ?? '',
                                            'a00_received_date' => $hasA00 && $rev->a00_received_date ? $rev->a00_received_date->format('Y-m-d') : '',
                                            'a00_doc' => $rev->a00_document_original_name ?? '',
                                            'a04' => $rev->a04 ?? '',
                                            'a04_received_date' => $hasA04 && $rev->a04_received_date ? $rev->a04_received_date->format('Y-m-d') : '',
                                            'a04_doc' => $rev->a04_document_original_name ?? '',
                                            'a04_reason' => $rev->a04_reason ?? '',
                                            'a05' => $rev->a05 ?? '',
                                            'a05_received_date' => $hasA05 && $rev->a05_received_date ? $rev->a05_received_date->format('Y-m-d') : '',
                                            'a05_doc' => $rev->a05_document_original_name ?? '',
                                            'partlist' => $rev->partlist ?? '',
                                            'partlist_received_date' => $hasPartlist && $rev->partlist_received_date ? \Carbon\Carbon::parse($rev->partlist_received_date)->format('Y-m-d') : '',
                                            'partlist_doc' => $rev->partlist_original_name ?? '',
                                            'partlist_revision_count' => $rev->partlist_revision_count ?? 0,
                                            'umh' => $rev->umh ?? '',
                                            'umh_received_date' => $hasUmh && $rev->umh_received_date ? \Carbon\Carbon::parse($rev->umh_received_date)->format('Y-m-d') : '',
                                            'umh_doc' => $rev->umh_original_name ?? '',
                                            'umh_revision_count' => $rev->umh_revision_count ?? 0,
                                        ]) }})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-action btn-delete" title="Hapus Dokumen"
                                        onclick="openDeleteDocModal({{ $rev->id }}, '{{ addslashes($costing->assy_name ?? $project->part_name ?? '-') }}')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="24" style="text-align: center; color: var(--slate-400); padding: 2rem;">
                                Belum ada data dokumen project.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pagedRows->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <div style="font-size: 0.82rem; color: var(--slate-500);">
                Halaman {{ $pagedRows->currentPage() }} dari {{ $pagedRows->lastPage() }}
                &nbsp;·&nbsp; {{ $pagedRows->total() }} data
            </div>
            <div class="doc-pagination">
                {{ $pagedRows->links('pagination.doc-paginator') }}
            </div>
        </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    <div id="editDocModal" class="doc-modal is-hidden" onclick="if(event.target===this)closeEditDocModal()">
        <div class="doc-modal-content doc-modal-wide">
            <div class="doc-modal-header">
                <h3 class="doc-modal-title">Edit Project Document</h3>
                <button type="button" class="doc-modal-close" onclick="closeEditDocModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="background: var(--slate-50); padding: 0.6rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.82rem; color: var(--slate-600); border: 1px solid var(--slate-200);">
                <strong id="editDocLabel"></strong>
            </div>
            <form id="editDocForm" method="POST" enctype="multipart/form-data" onsubmit="return validateAndSubmitProjectDocumentForm()">
                @csrf
                @method('PUT')
                @if(session('open_document_revision_id'))
                    <input type="hidden" name="return_to_dashboard" value="1">
                @endif

                <div class="doc-section-grid">
                    {{-- A00 Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">A00 (RFQ/RFI)</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="a00" id="editA00Status" class="form-select" onchange="toggleEditDateWrap('a00')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editA00DateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="a00_received_date" id="editA00Date" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (PDF)</label>
                                <input type="file" name="a00_document_file" accept=".pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editA00DocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                        </div>
                    </div>

                    {{-- A04 Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">A04 (Cancelled/Failed)</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="a04" id="editA04Status" class="form-select" onchange="toggleEditDateWrap('a04')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editA04DateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="a04_received_date" id="editA04Date" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Alasan Canceled/Failed <span style="color:#dc2626;">*</span></label>
                                <textarea name="a04_reason" id="editA04Reason" class="form-input" rows="3" placeholder="Tuliskan alasan project menjadi A04..." style="min-height:84px; resize:vertical;"></textarea>
                                <small style="color: var(--slate-500); font-size: 0.72rem;">Wajib diisi jika status A04 = Ada.</small>
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (PDF) <span style="color:#dc2626;">*</span></label>
                                <input type="file" name="a04_document_file" id="editA04File" accept=".pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editA04DocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                        </div>
                    </div>

                    {{-- A05 Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">A05 (Die Go)</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="a05" id="editA05Status" class="form-select" onchange="toggleEditDateWrap('a05')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editA05DateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="a05_received_date" id="editA05Date" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (PDF) <span style="color:#dc2626;">*</span></label>
                                <input type="file" name="a05_document_file" id="editA05File" accept=".pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editA05DocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                        </div>
                    </div>

                    {{-- Partlist Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">Partlist</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="partlist" id="editPartlistStatus" class="form-select" onchange="toggleEditDateWrap('partlist')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editPartlistDateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="partlist_received_date" id="editPartlistDate" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (Excel/PDF) <span style="color:#dc2626;">*</span></label>
                                <input type="file" name="partlist_document_file" id="editPartlistFile" accept=".xlsx,.xls,.csv,.pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editPartlistDocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                            <div class="doc-form-group">
                                <label>Jumlah Revisi</label>
                                <input type="number" min="0" name="partlist_revision_count" id="editPartlistRevisionCount" class="form-input" value="0">
                            </div>
                        </div>
                    </div>

                    {{-- UMH Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">UMH</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="umh" id="editUmhStatus" class="form-select" onchange="toggleEditDateWrap('umh')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editUmhDateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="umh_received_date" id="editUmhDate" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (Excel/PDF) <span style="color:#dc2626;">*</span></label>
                                <input type="file" name="umh_document_file" id="editUmhFile" accept=".xlsx,.xls,.csv,.pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editUmhDocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                            <div class="doc-form-group">
                                <label>Jumlah Revisi</label>
                                <input type="number" min="0" name="umh_revision_count" id="editUmhRevisionCount" class="form-input" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="doc-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditDocModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div id="deleteDocModal" class="doc-modal is-hidden" onclick="if(event.target===this)closeDeleteDocModal()">
        <div class="doc-modal-content" style="max-width: 420px;">
            <div class="doc-modal-header">
                <h3 class="doc-modal-title">Konfirmasi Hapus</h3>
                <button type="button" class="doc-modal-close" onclick="closeDeleteDocModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="delete-modal-body">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" style="margin-bottom: 0.75rem;">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <p class="delete-modal-text">Apakah Anda yakin ingin menghapus semua dokumen (A00, A04, A05) untuk:</p>
                <p class="delete-modal-name" id="deleteDocName"></p>
            </div>
            <form id="deleteDocForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="doc-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteDocModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #ef4444, #dc2626); border-color: #dc2626;">Hapus Dokumen</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function toggleEditDateWrap(prefix) {
        const status = document.getElementById('edit' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'Status').value;
        const wrap = document.getElementById('edit' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'DateWrap');
        wrap.style.display = status === 'ada' ? '' : 'none';

        applyBusinessRules(prefix);
    }

    function applyBusinessRules(changedPrefix) {
        const a00 = document.getElementById('editA00Status');
        const a04 = document.getElementById('editA04Status');
        const a05 = document.getElementById('editA05Status');

        // Rule: A04 and A05 are mutually exclusive
        if (changedPrefix === 'a04' && a04.value === 'ada') {
            a05.value = 'belum_ada';
            toggleEditDateWrap('a05');
        }
        if (changedPrefix === 'a05' && a05.value === 'ada') {
            a04.value = 'belum_ada';
            toggleEditDateWrap('a04');
        }

        // Rule: If A04 or A05 = ada, force A00 = ada
        if (a04.value === 'ada' || a05.value === 'ada') {
            a00.value = 'ada';
            a00.disabled = true;
            a00.title = 'A00 otomatis "Ada" karena A04/A05 sudah ada';
            a00.style.opacity = '0.6';
            document.getElementById('editA00DateWrap').style.display = '';
        } else {
            a00.disabled = false;
            a00.title = '';
            a00.style.opacity = '1';
        }
    }

    function openEditDocModal(revisionId, data) {
        const baseUrl = '{{ url("database/project-documents") }}';
        document.getElementById('editDocForm').action = baseUrl + '/' + revisionId;
        document.getElementById('editDocLabel').textContent = data.customer + ' — ' + data.model + ' — ' + data.part_name;

        // A00
        document.getElementById('editA00Status').value = data.a00 === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editA00Date').value = data.a00_received_date || '';
        document.getElementById('editA00DocName').textContent = data.a00_doc ? 'File saat ini: ' + data.a00_doc : '';
        toggleEditDateWrap('a00');

        // A04
        document.getElementById('editA04Status').value = data.a04 === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editA04Date').value = data.a04_received_date || '';
        document.getElementById('editA04DocName').textContent = data.a04_doc ? 'File saat ini: ' + data.a04_doc : '';
        document.getElementById('editA04Reason').value = data.a04_reason || '';
        toggleEditDateWrap('a04');

        // A05
        document.getElementById('editA05Status').value = data.a05 === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editA05Date').value = data.a05_received_date || '';
        document.getElementById('editA05DocName').textContent = data.a05_doc ? 'File saat ini: ' + data.a05_doc : '';
        toggleEditDateWrap('a05');

        // Partlist
        document.getElementById('editPartlistStatus').value = data.partlist === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editPartlistDate').value = data.partlist_received_date || '';
        document.getElementById('editPartlistDocName').textContent = data.partlist_doc ? 'File saat ini: ' + data.partlist_doc : '';
        document.getElementById('editPartlistRevisionCount').value = data.partlist_revision_count || 0;
        toggleEditDateWrap('partlist');

        // UMH
        document.getElementById('editUmhStatus').value = data.umh === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editUmhDate').value = data.umh_received_date || '';
        document.getElementById('editUmhDocName').textContent = data.umh_doc ? 'File saat ini: ' + data.umh_doc : '';
        document.getElementById('editUmhRevisionCount').value = data.umh_revision_count || 0;
        toggleEditDateWrap('umh');

        // Apply business rules after loading values
        applyBusinessRules('');

        document.getElementById('editDocModal').classList.remove('is-hidden');
    }

    function closeEditDocModal() {
        document.getElementById('editDocModal').classList.add('is-hidden');

        if (shouldReturnToDashboardAfterDocumentModal) {
            showReturnToDashboardLoading();
            window.location.href = dashboardReturnUrl;
        }
    }

    function showReturnToDashboardLoading() {
        let overlay = document.getElementById('returnDashboardLoadingOverlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'returnDashboardLoadingOverlay';
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.zIndex = '100000';
            overlay.style.background = 'rgba(15, 23, 42, 0.42)';
            overlay.style.backdropFilter = 'blur(2px)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.innerHTML = `
                <div style="background:#fff; border-radius:16px; padding:1.35rem 1.65rem; min-width:220px; box-shadow:0 24px 60px rgba(15,23,42,.22); display:grid; justify-items:center; gap:.8rem;">
                    <div style="width:38px; height:38px; border:4px solid #dbeafe; border-top-color:#2563eb; border-radius:999px; animation:returnDashboardSpin .75s linear infinite;"></div>
                    <div style="font-size:.88rem; font-weight:800; color:#334155;">Kembali ke dashboard...</div>
                </div>
            `;

            const style = document.createElement('style');
            style.textContent = '@keyframes returnDashboardSpin{to{transform:rotate(360deg)}}';
            document.head.appendChild(style);
            document.body.appendChild(overlay);
        }

        overlay.style.display = 'flex';
    }

    function openDeleteDocModal(revisionId, name) {
        const baseUrl = '{{ url("database/project-documents") }}';
        document.getElementById('deleteDocForm').action = baseUrl + '/' + revisionId;
        document.getElementById('deleteDocName').textContent = name;
        document.getElementById('deleteDocModal').classList.remove('is-hidden');
    }

    function closeDeleteDocModal() {
        document.getElementById('deleteDocModal').classList.add('is-hidden');
    }

    const dashboardReturnUrl = @json(route('dashboard', absolute: false));
    const shouldReturnToDashboardAfterDocumentModal = @json((bool) session('open_document_revision_id'));


    function validateAndSubmitProjectDocumentForm() {
        const a00 = document.getElementById('editA00Status');
        const a04 = document.getElementById('editA04Status');
        const a05 = document.getElementById('editA05Status');
        const a04Reason = document.getElementById('editA04Reason');
        const a04File = document.getElementById('editA04File');
        const a05File = document.getElementById('editA05File');
        const a04DocName = document.getElementById('editA04DocName');
        const a05DocName = document.getElementById('editA05DocName');
        const partlist = document.getElementById('editPartlistStatus');
        const umh = document.getElementById('editUmhStatus');
        const partlistFile = document.getElementById('editPartlistFile');
        const umhFile = document.getElementById('editUmhFile');
        const partlistDocName = document.getElementById('editPartlistDocName');
        const umhDocName = document.getElementById('editUmhDocName');

        if (a04 && a04.value === 'ada') {
            if (!a04Reason || a04Reason.value.trim() === '') {
                alert('Alasan Canceled/Failed wajib diisi untuk status A04.');
                a04Reason?.focus();
                return false;
            }

            const hasExistingA04Doc = a04DocName && a04DocName.textContent.trim() !== '';
            if ((!a04File || a04File.files.length === 0) && !hasExistingA04Doc) {
                alert('Dokumen A04 wajib diupload.');
                a04File?.focus();
                return false;
            }
        }

        if (a05 && a05.value === 'ada') {
            const hasExistingA05Doc = a05DocName && a05DocName.textContent.trim() !== '';
            if ((!a05File || a05File.files.length === 0) && !hasExistingA05Doc) {
                alert('Dokumen A05 wajib diupload.');
                a05File?.focus();
                return false;
            }
        }

        if (partlist && partlist.value === 'ada') {
            const hasExistingPartlistDoc = partlistDocName && partlistDocName.textContent.trim() !== '';
            if ((!partlistFile || partlistFile.files.length === 0) && !hasExistingPartlistDoc) {
                alert('Dokumen Partlist wajib diupload.');
                partlistFile?.focus();
                return false;
            }
        }

        if (umh && umh.value === 'ada') {
            const hasExistingUmhDoc = umhDocName && umhDocName.textContent.trim() !== '';
            if ((!umhFile || umhFile.files.length === 0) && !hasExistingUmhDoc) {
                alert('Dokumen UMH wajib diupload.');
                umhFile?.focus();
                return false;
            }
        }

        if (a00) {
            a00.disabled = false;
        }

        if (shouldReturnToDashboardAfterDocumentModal) {
            showReturnToDashboardLoading();
        }

        return true;
    }

    function focusTargetDocumentSection(targetStatus) {
        if (targetStatus !== 'A04' && targetStatus !== 'A05') {
            return;
        }

        const lower = targetStatus.toLowerCase();
        const statusSelect = document.getElementById('edit' + targetStatus + 'Status');
        const dateInput = document.getElementById('edit' + targetStatus + 'Date');
        const fileInput = document.getElementById('edit' + targetStatus + 'File');
        const reasonInput = targetStatus === 'A04' ? document.getElementById('editA04Reason') : null;
        const dateWrap = document.getElementById('edit' + targetStatus + 'DateWrap');

        if (statusSelect) {
            statusSelect.value = 'ada';
            toggleEditDateWrap(lower);
        }

        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().slice(0, 10);
        }

        if (dateWrap) {
            dateWrap.style.display = '';
            dateWrap.style.border = '2px solid ' + (targetStatus === 'A04' ? '#fca5a5' : '#86efac');
            dateWrap.style.borderRadius = '12px';
            dateWrap.style.padding = '0.55rem';
            dateWrap.style.background = targetStatus === 'A04' ? '#fff7f7' : '#f0fdf4';
        }

        window.setTimeout(function () {
            if (reasonInput) {
                reasonInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                reasonInput.focus();
                return;
            }

            if (fileInput) {
                fileInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                fileInput.focus();
            }
        }, 250);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const openRevisionId = @json(session('open_document_revision_id'));
        const targetStatus = @json(session('open_document_target_status'));

        if (!openRevisionId) {
            return;
        }

        const editButton = document.querySelector('.js-edit-doc-btn[data-revision-id="' + openRevisionId + '"]');

        if (editButton) {
            editButton.click();
            window.setTimeout(function () {
                focusTargetDocumentSection(targetStatus);
            }, 250);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeEditDocModal();
            closeDeleteDocModal();
        }
    });
</script>
@endsection
