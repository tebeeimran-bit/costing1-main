@extends('layouts.app')

@section('title', 'Template Excel')
@section('page-title', 'Template Excel')
@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Database Template Excel</span>
@endsection

@section('content')
@php
    $typeDescriptions = [
        'costing' => 'Template export Costing/COGM dipilih otomatis berdasarkan jumlah assy dalam satu A00.',
        'partlist' => 'Template standar untuk proses import dan export dokumen Partlist.',
        'umh' => 'Template standar untuk proses import dan export dokumen UMH.',
        'a00' => 'Template standar untuk pembuatan dan export dokumen A00.',
    ];
    $typeShortLabels = ['costing' => 'Costing', 'partlist' => 'Partlist', 'umh' => 'UMH', 'a00' => 'A00'];
@endphp
<style>
    .tpl-page { display:grid; gap:16px; }
    .tpl-menu { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:10px; padding:10px; background:#fff; border:1px solid #d7e2f0; border-radius:14px; }
    .tpl-menu-item { display:flex; align-items:center; gap:11px; min-height:58px; padding:10px 14px; border:1px solid #d5e0ef; border-radius:10px; color:#334155; text-decoration:none; background:#f8fbff; transition:.15s ease; }
    .tpl-menu-item:hover { border-color:#93b4ef; transform:translateY(-1px); }
    .tpl-menu-item.active { border-color:#2563eb; background:linear-gradient(135deg,#2563eb,#2454d4); color:#fff; box-shadow:0 7px 16px rgba(37,99,235,.2); }
    .tpl-menu-icon { display:grid; place-items:center; flex:0 0 34px; height:34px; border-radius:9px; background:rgba(37,99,235,.1); font-size:13px; font-weight:900; }
    .tpl-menu-item.active .tpl-menu-icon { background:rgba(255,255,255,.18); }
    .tpl-menu-copy strong { display:block; font-size:12px; }
    .tpl-menu-copy small { display:block; margin-top:4px; color:#718096; font-size:9px; }
    .tpl-menu-item.active small { color:#dbeafe; }
    .tpl-card { overflow:hidden; background:#fff; border:1px solid #d7e2f0; border-radius:14px; box-shadow:0 5px 18px rgba(15, 23, 42, .04); }
    .tpl-card-head { padding:17px 20px 14px; border-bottom:1px solid #e7eef8; background:linear-gradient(90deg, #f8fbff 0%, #fff 60%); }
    .tpl-card-head h3 { margin:0 0 5px; color:#172033; font-size:16px; }
    .tpl-help { margin:0; color:#64748b; font-size:11px; line-height:1.55; }
    .tpl-card-body { padding:18px 20px 20px; }
    .tpl-form { display:grid; grid-template-columns:minmax(240px,.9fr) minmax(320px,1.3fr) 150px; gap:14px; align-items:end; }
    .tpl-form.with-assy { grid-template-columns:130px minmax(240px,.9fr) minmax(320px,1.3fr) 150px; }
    .tpl-field label { display:block; margin-bottom:7px; font-size:10px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.025em; }
    .tpl-field input { box-sizing:border-box; width:100%; height:40px; border:1px solid #c8d6e8; border-radius:8px; padding:0 11px; color:#24324a; background:#fff; outline:none; transition:.15s ease; }
    .tpl-field input:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37, 99, 235, .1); }
    .tpl-field input[type=file] { padding:4px; color:#64748b; }
    .tpl-field input[type=file]::file-selector-button { height:30px; margin-right:10px; padding:0 12px; border:0; border-radius:6px; background:#edf4ff; color:#1d4ed8; font-size:11px; font-weight:800; cursor:pointer; }
    .tpl-btn { display:inline-flex; align-items:center; justify-content:center; box-sizing:border-box; height:40px; border:1px solid #2563eb; border-radius:8px; padding:0 15px; background:#2563eb; color:#fff; font-size:11px; font-weight:800; text-decoration:none; white-space:nowrap; cursor:pointer; transition:.15s ease; }
    .tpl-btn:hover { filter:brightness(.96); transform:translateY(-1px); }
    .tpl-btn.secondary { background:#fff; color:#2563eb; }
    .tpl-btn.danger { background:#dc2626; border-color:#dc2626; }
    .tpl-table-title { margin:0; font-size:14px; color:#172033; }
    .tpl-table-wrap { overflow-x:auto; padding:0 20px 15px; }
    .tpl-table { width:100%; min-width:780px; border-collapse:collapse; }
    .tpl-table th, .tpl-table td { padding:12px 10px; border-bottom:1px solid #e2e8f0; text-align:left; font-size:11px; vertical-align:middle; }
    .tpl-table th { background:#f4f7fb; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:.025em; }
    .tpl-table tbody tr:hover { background:#f8fbff; }
    .tpl-empty { height:76px; text-align:center !important; color:#7b8aa0; }
    .tpl-actions { display:flex; gap:7px; }
    .tpl-errors { margin:0 0 14px; padding:10px 14px; border:1px solid #fecaca; border-radius:9px; background:#fef2f2; color:#b91c1c; font-size:12px; }
    @media (max-width:1100px) { .tpl-menu { grid-template-columns:repeat(2,1fr); } .tpl-form,.tpl-form.with-assy { grid-template-columns:1fr 1fr; } }
    @media (max-width:700px) { .tpl-menu,.tpl-form,.tpl-form.with-assy { grid-template-columns:1fr; } .tpl-card-head,.tpl-card-body { padding-left:15px; padding-right:15px; } }
</style>

<div class="tpl-page">
<nav class="tpl-menu" aria-label="Jenis Template Excel">
    @foreach ($templateTypes as $type => $label)
        <a class="tpl-menu-item {{ $activeType === $type ? 'active' : '' }}" href="?type={{ $type }}">
            <span class="tpl-menu-icon">{{ strtoupper(substr($typeShortLabels[$type], 0, 2)) }}</span>
            <span class="tpl-menu-copy"><strong>{{ $label }}</strong><small>Kelola file {{ $typeShortLabels[$type] }}</small></span>
        </a>
    @endforeach
</nav>
<div class="tpl-card">
    <div class="tpl-card-head">
        <h3>Upload {{ $templateTypes[$activeType] }}</h3>
        <p class="tpl-help">{{ $typeDescriptions[$activeType] }}</p>
    </div>
    <div class="tpl-card-body">

    @if ($errors->any())
        <div class="tpl-errors">{{ $errors->first() }}</div>
    @endif

    <form class="tpl-form {{ $activeType === 'costing' ? 'with-assy' : '' }}" method="POST" enctype="multipart/form-data" action="{{ route('database.costing-excel-templates.store', absolute: false) }}">
        @csrf
        <input type="hidden" name="template_type" value="{{ $activeType }}">
        @if ($activeType === 'costing')
        <div class="tpl-field">
            <label for="assy_count">Jumlah Assy *</label>
            <input id="assy_count" type="number" name="assy_count" min="1" max="20" value="{{ old('template_type') === $activeType ? old('assy_count') : '' }}" required>
        </div>
        @endif
        <div class="tpl-field">
            <label for="template_name">Nama Template *</label>
            <input id="template_name" name="name" value="{{ old('template_type') === $activeType ? old('name') : '' }}" placeholder="Contoh: {{ $templateTypes[$activeType] }}" required>
        </div>
        <div class="tpl-field">
            <label for="template_file">File Excel (.xlsx) *</label>
            <input id="template_file" type="file" name="template_file" accept=".xlsx" required>
        </div>
        <button class="tpl-btn" type="submit">Upload Template</button>
    </form>
    </div>
</div>

<div class="tpl-card">
    <div class="tpl-card-head">
        <h3 class="tpl-table-title">Daftar {{ $templateTypes[$activeType] }}</h3>
        <p class="tpl-help">{{ $typeDescriptions[$activeType] }}</p>
    </div>
    <div class="tpl-table-wrap">
    <table class="tpl-table">
        <thead>
            <tr>@if ($activeType === 'costing')<th>Jumlah Assy</th>@endif<th>Nama</th><th>File</th><th>Upload</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @forelse ($templates as $template)
                <tr>
                    @if ($activeType === 'costing')<td><strong>{{ $template->assy_count }} Assy</strong></td>@endif
                    <td>{{ $template->name }}</td>
                    <td>{{ $template->original_name }}</td>
                    <td>{{ $template->uploader?->name ?: '-' }}<br><small>{{ $template->updated_at->format('d/m/Y H:i') }}</small></td>
                    <td>{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        <div class="tpl-actions">
                            <a class="tpl-btn secondary" href="{{ route('database.costing-excel-templates.download', $template, absolute: false) }}">Download</a>
                            <form method="POST" action="{{ route('database.costing-excel-templates.destroy', $template, absolute: false) }}" class="js-confirm-form" data-confirm-message="Hapus {{ $template->name }}?">
                                @csrf
                                @method('DELETE')
                                <button class="tpl-btn danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td class="tpl-empty" colspan="{{ $activeType === 'costing' ? 6 : 5 }}">Belum ada {{ strtolower($templateTypes[$activeType]) }} yang diupload.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
</div>
@endsection
