@extends('layouts.app')

@section('title', 'Rekap Dokumen')
@section('page-title', 'Rekap Dokumen')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="{{ route('database.project-documents', absolute: false) }}">Project Document</a>
    <span class="breadcrumb-separator">/</span>
    <span>Rekap Dokumen</span>
@endsection

@section('content')
<style>
    .recap-path-card {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        flex-wrap: wrap;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.95rem 1.15rem;
        margin-bottom: 1.2rem;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
        color: #475569;
        font-size: 0.86rem;
        font-weight: 800;
    }

    .recap-path-card .folder-root {
        width: 22px;
        height: 22px;
        color: #2563eb;
        flex: 0 0 auto;
    }

    .recap-path-card .path-separator {
        color: #94a3b8;
        font-size: 1.1rem;
        font-weight: 950;
    }

    .recap-grid {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 1rem;
        align-items: stretch;
    }

    .recap-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .recap-tree-card {
        padding: 1.1rem;
    }

    .recap-card-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin: 0 0 0.9rem;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
    }

    .folder-tree,
    .folder-tree ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .folder-tree ul {
        margin-left: 0.72rem;
        padding-left: 0.85rem;
        border-left: 1px dashed #cbd5e1;
    }

    .folder-tree li {
        margin: 0.24rem 0;
    }

    .tree-item {
        display: flex;
        align-items: center;
        gap: 0.48rem;
        min-height: 32px;
        width: 100%;
        padding: 0.42rem 0.58rem;
        border-radius: 9px;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 800;
        text-decoration: none;
        box-sizing: border-box;
        transition: .15s ease;
    }

    .tree-item:hover {
        background: #f8fafc;
        color: #1d4ed8;
    }

    .tree-item.is-trail {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .tree-item.is-selected {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
    }

    .tree-icon {
        width: 17px;
        height: 17px;
        color: #f59e0b;
        flex: 0 0 auto;
    }

    .tree-item.is-trail .tree-icon {
        color: #2563eb;
    }

    .tree-item.is-selected .tree-icon {
        color: #fff;
    }

    .tree-text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tree-count {
        margin-left: auto;
        min-width: 22px;
        padding: 0.1rem 0.38rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 950;
        text-align: center;
    }

    .tree-item.is-trail .tree-count {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .tree-item.is-selected .tree-count {
        background: rgba(255,255,255,.18);
        color: #fff;
    }

    .folder-content-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.05rem 1.15rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .folder-content-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 950;
    }

    .folder-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .folder-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        min-height: 38px;
        padding: 0.5rem 0.78rem;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
    }

    .folder-action-btn.primary {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.18);
    }

    .recap-table-wrap {
        overflow-x: auto;
        padding: 0 1.15rem 1.05rem;
    }

    .recap-table {
        width: 100%;
        min-width: 880px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .recap-table th {
        padding: 0.9rem 0.85rem;
        border-bottom: 1px solid #e2e8f0;
        color: #64748b;
        background: #fff;
        font-size: 0.78rem;
        font-weight: 950;
        text-align: left;
        white-space: nowrap;
    }

    .recap-table td {
        padding: 0.95rem 0.85rem;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.87rem;
        font-weight: 750;
        vertical-align: middle;
    }

    .document-name {
        display: inline-flex;
        align-items: center;
        gap: 0.7rem;
        color: #1e293b;
        font-weight: 900;
        text-decoration: none;
    }

    .document-name:hover {
        color: #2563eb;
    }

    .excel-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dcfce7;
        color: #15803d;
        font-weight: 950;
        font-size: 0.95rem;
        flex: 0 0 auto;
    }

    .doc-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 92px;
        padding: 0.34rem 0.65rem;
        border-radius: 999px;
        font-size: 0.73rem;
        font-weight: 950;
        white-space: nowrap;
    }

    .doc-status-pill.green {
        color: #15803d;
        background: #dcfce7;
    }

    .doc-status-pill.orange {
        color: #ea580c;
        background: #ffedd5;
    }

    .doc-status-pill.red {
        color: #dc2626;
        background: #fee2e2;
    }

    .action-cell {
        position: relative;
        text-align: center;
    }

    .row-menu-btn {
        width: 32px;
        height: 32px;
        border: 1px solid transparent;
        background: transparent;
        color: #64748b;
        font-size: 1.2rem;
        cursor: pointer;
        line-height: 1;
        border-radius: 9px;
        font-weight: 950;
    }

    .row-menu-btn:hover,
    .row-menu-btn.is-open {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #2563eb;
    }

    .action-dropdown {
        position: absolute;
        right: 0.35rem;
        top: 2.45rem;
        z-index: 50;
        min-width: 178px;
        padding: 0.35rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fff;
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.18);
        display: none;
        text-align: left;
    }

    .action-dropdown.is-open {
        display: grid;
        gap: 0.25rem;
    }

    .action-dropdown a,
    .action-dropdown button {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        width: 100%;
        min-height: 34px;
        padding: 0.45rem 0.55rem;
        border: 0;
        background: transparent;
        border-radius: 9px;
        color: #334155;
        text-decoration: none;
        font-size: 0.78rem;
        font-weight: 850;
        cursor: pointer;
        box-sizing: border-box;
    }

    .action-dropdown a:hover,
    .action-dropdown button:hover {
        background: #f8fafc;
        color: #2563eb;
    }

    .action-dropdown .disabled-action {
        opacity: .55;
        cursor: not-allowed;
    }

    .recap-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.95rem 1.15rem 1.15rem;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 750;
    }

    .recap-page-btn {
        width: 38px;
        height: 34px;
        border-radius: 9px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        font-weight: 950;
    }

    .recap-page-btn.active {
        color: #fff;
        background: #2563eb;
        border-color: #2563eb;
    }

    .empty-folder {
        padding: 2.5rem 1rem;
        text-align: center;
        color: #64748b;
        font-weight: 800;
    }

    @media (max-width: 1180px) {
        .recap-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    $folderIcon = '<svg class="tree-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1"><path d="M3 7h7l2 2h9v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"></path></svg>';

    $routeParams = function (array $override = []) use ($businessCategory, $customer, $model, $revisionId) {
        return array_filter(array_merge([
            'business_category' => $businessCategory,
            'customer' => $customer,
            'model' => $model,
            'revision_id' => $revisionId,
        ], $override), fn ($value) => $value !== null && $value !== '');
    };

    $selectedRevisionLabel = $revisionOptions[$revisionId] ?? 'Rev. 00';
@endphp

<div class="recap-path-card">
    <svg class="folder-root" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v1H3V7Z"></path>
        <path d="M3 10h18v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7Z"></path>
    </svg>
    <span>Business Categories</span>
    <span class="path-separator">›</span>
    <span>{{ $businessCategory ?: '-' }}</span>
    <span class="path-separator">›</span>
    <span>{{ $customer ?: '-' }}</span>
    <span class="path-separator">›</span>
    <span>{{ $model ?: '-' }}</span>
    <span class="path-separator">›</span>
    <span>{{ $selectedRevisionLabel }}</span>
</div>

<div class="recap-grid">
    <div class="recap-card recap-tree-card">
        <h3 class="recap-card-title">Struktur Folder</h3>

        <ul class="folder-tree">
            <li>
                <span class="tree-item is-trail">
                    {!! $folderIcon !!}
                    <span class="tree-text">Business Categories</span>
                    <span class="tree-count">{{ $businessCategories->count() }}</span>
                </span>
                <ul>
                    @forelse($businessCategories as $businessName => $businessItems)
                        <li>
                            <a class="tree-item {{ $businessName === $businessCategory ? 'is-trail' : '' }}"
                               href="{{ route('database.document-recap', ['business_category' => $businessName], false) }}"
                               title="{{ $businessName }}">
                                {!! $folderIcon !!}
                                <span class="tree-text">{{ $businessName }}</span>
                                <span class="tree-count">{{ $businessItems->count() }}</span>
                            </a>

                            @if($businessName === $businessCategory)
                                <ul>
                                    @foreach($customers as $customerName => $customerItems)
                                        <li>
                                            <a class="tree-item {{ $customerName === $customer ? 'is-trail' : '' }}"
                                               href="{{ route('database.document-recap', $routeParams(['customer' => $customerName, 'model' => null, 'revision_id' => null]), false) }}"
                                               title="{{ $customerName }}">
                                                {!! $folderIcon !!}
                                                <span class="tree-text">{{ $customerName }}</span>
                                                <span class="tree-count">{{ $customerItems->count() }}</span>
                                            </a>

                                            @if($customerName === $customer)
                                                <ul>
                                                    @foreach($models as $modelName => $modelItems)
                                                        <li>
                                                            <a class="tree-item {{ $modelName === $model ? 'is-trail' : '' }}"
                                                               href="{{ route('database.document-recap', $routeParams(['model' => $modelName, 'revision_id' => null]), false) }}"
                                                               title="{{ $modelName }}">
                                                                {!! $folderIcon !!}
                                                                <span class="tree-text">{{ $modelName }}</span>
                                                                <span class="tree-count">{{ $modelItems->count() }}</span>
                                                            </a>

                                                            @if($modelName === $model)
                                                                <ul>
                                                                    @foreach($revisionOptions as $revId => $revLabel)
                                                                        <li>
                                                                            <a class="tree-item {{ (int) $revId === (int) $revisionId ? 'is-selected' : '' }}"
                                                                               href="{{ route('database.document-recap', $routeParams(['revision_id' => $revId]), false) }}"
                                                                               title="{{ $revLabel }}">
                                                                                {!! $folderIcon !!}
                                                                                <span class="tree-text">{{ $revLabel }}</span>
                                                                            </a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @empty
                        <li><span class="tree-item">Belum ada data folder.</span></li>
                    @endforelse
                </ul>
            </li>
        </ul>
    </div>

    <div class="recap-card">
        <div class="folder-content-head">
            <h3 class="folder-content-title">Isi Folder: {{ $selectedRevisionLabel }}</h3>
            <div class="folder-actions">
                <a href="{{ route('database.project-documents', ['search' => $customer], false) }}" class="folder-action-btn primary">
                    Upload / Edit Dokumen
                </a>
                <a href="{{ route('database.project-documents', false) }}" class="folder-action-btn">
                    Kembali ke Project Document
                </a>
            </div>
        </div>

        @if($documents->isEmpty())
            <div class="empty-folder">Belum ada dokumen untuk folder ini.</div>
        @else
            <div class="recap-table-wrap">
                <table class="recap-table">
                    <thead>
                        <tr>
                            <th>Nama Dokumen</th>
                            <th>Tanggal Update</th>
                            <th>Jenis Dokumen</th>
                            <th>Status</th>
                            <th style="width:70px;text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $document)
                            <tr>
                                <td>
                                    @if($document->downloadable)
                                        <a href="{{ $document->download_url }}" class="document-name">
                                            <span class="excel-icon">X</span>
                                            {{ $document->name }}
                                        </a>
                                    @else
                                        <span class="document-name">
                                            <span class="excel-icon">X</span>
                                            {{ $document->name }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($document->date)
                                        {{ \Carbon\Carbon::parse($document->date)->format('d/m/Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $document->label }}</td>
                                <td>
                                    <span class="doc-status-pill {{ $document->status_class }}">
                                        {{ $document->status_label }}
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <button type="button" class="row-menu-btn" onclick="toggleRecapActionMenu(event, 'actionMenu{{ $loop->index }}')" aria-label="Aksi dokumen">⋮</button>
                                    <div id="actionMenu{{ $loop->index }}" class="action-dropdown">
                                        @if($document->downloadable)
                                            <a href="{{ $document->download_url }}">
                                                Download Dokumen
                                            </a>
                                        @else
                                            <button type="button" class="disabled-action" disabled>
                                                Download Belum Tersedia
                                            </button>
                                        @endif

                                        <a href="{{ route('database.project-documents', ['search' => $customer], false) }}">
                                            Upload / Edit Dokumen
                                        </a>

                                        <a href="{{ route('database.document-recap', $routeParams(), false) }}">
                                            Refresh Folder
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="recap-footer">
                <div>Menampilkan 1 - {{ $documents->count() }} dari {{ $documents->count() }} dokumen</div>
                <div>
                    <button class="recap-page-btn">‹</button>
                    <button class="recap-page-btn active">1</button>
                    <button class="recap-page-btn">›</button>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    function closeAllRecapActionMenus() {
        document.querySelectorAll('.action-dropdown.is-open').forEach(function(menu) {
            menu.classList.remove('is-open');
        });

        document.querySelectorAll('.row-menu-btn.is-open').forEach(function(button) {
            button.classList.remove('is-open');
        });
    }

    function toggleRecapActionMenu(event, menuId) {
        event.preventDefault();
        event.stopPropagation();

        const menu = document.getElementById(menuId);
        const button = event.currentTarget;
        const wasOpen = menu && menu.classList.contains('is-open');

        closeAllRecapActionMenus();

        if (!wasOpen && menu) {
            menu.classList.add('is-open');
            button.classList.add('is-open');
        }
    }

    document.addEventListener('click', function () {
        closeAllRecapActionMenus();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllRecapActionMenus();
        }
    });
</script>
@endsection
