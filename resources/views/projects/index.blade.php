@extends('layouts.app')

@section('title', 'Project')
@section('page-title', 'Project')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Project</span>
@endsection

@section('content')
<style>
    .project-card,
    .project-card * {
        font-family: inherit !important;
    }

    .project-card {
        background: #fff;
        border: 1px solid #dbe5f2;
        padding: 1.25rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
    }

    .project-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .project-card-title {
        margin: 0;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 800;
    }

    .project-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.55rem;
        flex-wrap: wrap;
    }

    .project-search {
        width: 380px;
        max-width: 100%;
        height: 38px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        padding: 0 0.85rem;
        color: #334155;
        font-size: 0.72rem;
        outline: none;
        background: #fff;
    }

    .project-search:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .btn-project {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        min-height: 38px;
        padding: 0.52rem 0.85rem;
        border-radius: 9px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        font-size: 0.70rem;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-project.primary {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .18);
    }

    .project-table-wrap {
        overflow-x: auto;
        border: 1px solid #dbe5f2;
        border-radius: 12px;
    }

    .project-table {
        width: 100%;
        min-width: 1280px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .project-table th {
        padding: 0.85rem 0.75rem;
        border-bottom: 1px solid #dbe5f2;
        color: #475569;
        background: #f8fafc;
        font-size: 0.68rem;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .project-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        color: #0f172a;
        font-size: 0.76rem;
        font-weight: 600;
        vertical-align: top;
        background: #fff;
    }

    .group-row.is-open td {
        background: #fbfdff;
    }

    .expand-cell {
        width: 42px;
        text-align: center;
        vertical-align: middle !important;
    }

    .expand-btn {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #2563eb;
        font-size: 1.05rem;
        font-weight: 950;
        cursor: pointer;
        line-height: 1;
        transition: .15s ease;
    }

    .expand-btn.is-open {
        background: #eff6ff;
        border-color: #93c5fd;
        transform: rotate(90deg);
    }

    .project-info-box {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.7rem 2rem;
        width: 100%;
        max-width: 560px;
        border: 1px solid #dbe5f2;
        border-radius: 11px;
        padding: 0.85rem;
        background: #fff;
    }

    .info-label {
        color: #64748b;
        font-size: 0.64rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .info-value {
        color: #0f172a;
        font-size: 0.75rem;
        font-weight: 800;
        margin-top: 0.16rem;
    }

    .status-stack {
        display: grid;
        gap: 0.32rem;
        align-items: start;
        justify-items: start;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.3rem 0.58rem;
        border-radius: 999px;
        font-size: 0.70rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .status-pill.orange { background: #ffedd5; color: #ea580c; }
    .status-pill.blue { background: #dbeafe; color: #2563eb; }
    .status-pill.green { background: #dcfce7; color: #15803d; }
    .status-pill.red { background: #fee2e2; color: #dc2626; }
    .status-pill.gray { background: #e2e8f0; color: #475569; }

    .child-row {
        display: none;
    }

    .child-row.is-open {
        display: table-row;
    }

    .child-cell {
        padding: 0 !important;
        background: #f8fafc !important;
    }

    .child-panel {
        margin: 0.3rem 0.75rem 0.75rem 3.2rem;
        border: 1px solid #dbe5f2;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
    }

    .child-panel-head {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 0.85rem;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 850;
        background: #f8fafc;
        border-bottom: 1px solid #dbe5f2;
    }

    .child-table {
        width: 100%;
        border-collapse: collapse;
    }

    .child-table th {
        background: #fff;
        color: #475569;
        font-size: 0.68rem;
        font-weight: 800;
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .child-table td {
        padding: 0.62rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.74rem;
        font-weight: 600;
        background: #fff;
        vertical-align: top;
    }

    .child-table tr:last-child td {
        border-bottom: 0;
    }

    .action-stack {
        display: grid;
        gap: 0.35rem;
        justify-items: stretch;
    }

    .action-stack form {
        margin: 0;
        width: 100%;
    }

    .action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-height: 30px;
        padding: 0.35rem 0.55rem;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        font-size: 0.70rem;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
    }

    .action-link:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #2563eb;
    }

    .action-delete,
    .action-submit,
    .action-approve,
    .action-reject,
    .action-send {
        width: 100%;
    }

    .action-submit {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .action-approve,
    .action-send {
        color: #047857;
        border-color: #a7f3d0;
        background: #ecfdf5;
    }

    .action-reject,
    .action-delete:hover {
        color: #dc2626;
        border-color: #fecaca;
        background: #fef2f2;
    }

    .approval-meta {
        display: grid;
        gap: 0.26rem;
        margin-top: 0.45rem;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 750;
        line-height: 1.35;
    }

    .approval-meta strong {
        color: #334155;
        font-weight: 900;
    }

    .costing-health {
        display: grid;
        gap: 0.32rem;
        margin-top: 0.42rem;
        justify-items: start;
    }

    .costing-health-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.32rem;
        width: fit-content;
        max-width: 100%;
        border-radius: 999px;
        padding: 0.22rem 0.55rem;
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 1.25;
        letter-spacing: 0.02em;
        white-space: normal;
        text-align: left;
    }

    .costing-health-badge::before {
        content: '';
        width: 0.42rem;
        height: 0.42rem;
        border-radius: 999px;
        flex: 0 0 auto;
        background: currentColor;
    }

    .costing-health-badge.info {
        background: rgba(37, 99, 235, 0.10);
        color: #1d4ed8;
    }

    .costing-health-badge.warning {
        background: rgba(245, 158, 11, 0.14);
        color: #b45309;
    }

    .costing-health-badge.danger {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }

    .empty-state {
        padding: 2rem;
        text-align: center;
        color: #64748b;
        font-weight: 850;
    }

    .project-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 1rem;
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .project-pagination nav > div:first-child {
        display: none;
    }

    .project-pagination nav > div:last-child {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    @media (max-width: 1024px) {
        .project-card-header {
            align-items: stretch;
            flex-direction: column;
        }

        .project-toolbar {
            justify-content: flex-start;
        }

        .project-info-box {
            grid-template-columns: 1fr;
        }
    }

    .pic-list {
        display: grid;
        gap: 0.18rem;
        line-height: 1.35;
    }

    /* Compact project list */
    .project-card { padding: 1rem; border-radius: 10px; }
    .project-card-header { margin-bottom: .8rem; }
    .project-card-title { font-size: 1.05rem; }
    .project-table { min-width: 980px; }
    .project-table th { padding: .72rem .65rem; font-size: .64rem; }
    .project-table .group-row td { padding: .7rem .65rem; vertical-align: middle; font-size: .73rem; }
    .project-main { display: grid; gap: .18rem; }
    .project-main strong { color: #0f172a; font-size: .76rem; font-weight: 850; }
    .project-main small { color: #64748b; font-size: .65rem; font-weight: 650; }
    .part-summary { white-space: nowrap; }
    .pic-compact { display: grid; gap: .24rem; min-width: 155px; }
    .pic-compact div { display: grid; grid-template-columns: 66px 1fr; gap: .35rem; line-height: 1.25; }
    .pic-compact span { color: #64748b; font-size: .62rem; font-weight: 700; }
    .pic-compact strong { color: #334155; font-size: .68rem; font-weight: 750; }
    .group-row .status-stack { display: flex; flex-wrap: wrap; gap: .25rem; }
    .group-row .status-pill { padding: .25rem .48rem; font-size: .62rem; }
    .updated-compact { color: #475569; white-space: nowrap; line-height: 1.35; }
    .updated-compact small { display: block; color: #94a3b8; font-size: .62rem; }
    .row-actions { position: relative; display: flex; justify-content: center; }
    .row-actions summary { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #334155; cursor: pointer; font-size: 1.05rem; font-weight: 900; list-style: none; }
    .row-actions summary::-webkit-details-marker { display: none; }
    .row-actions[open] summary { border-color: #93c5fd; background: #eff6ff; color: #2563eb; }
    .row-action-menu { position: absolute; z-index: 20; top: 36px; right: 0; width: 180px; padding: .35rem; border: 1px solid #dbe5f2; border-radius: 9px; background: #fff; box-shadow: 0 12px 28px rgba(15,23,42,.16); }
    .row-action-menu a,.row-action-menu button { box-sizing: border-box; display: flex; width: 100%; align-items: center; padding: .55rem .65rem; border: 0; border-radius: 6px; background: transparent; color: #334155; font-size: .69rem; font-weight: 750; text-align: left; text-decoration: none; cursor: pointer; }
    .row-action-menu a:hover,.row-action-menu button:hover { background: #eff6ff; color: #1d4ed8; }
    .progress-trigger{display:block;width:100%;min-width:300px;padding:.2rem 0;border:0;background:transparent;text-align:left;cursor:pointer}.progress-track{display:grid;grid-template-columns:repeat(6,1fr);align-items:start}.progress-step{position:relative;display:grid;justify-items:center;gap:.28rem;color:#94a3b8;font-size:.55rem;font-weight:750}.progress-step:not(:first-child)::before{content:"";position:absolute;top:9px;right:50%;width:100%;height:2px;background:#dbe3ef;z-index:0}.progress-dot{position:relative;z-index:1;display:flex;align-items:center;justify-content:center;width:19px;height:19px;border:2px solid #cbd5e1;border-radius:50%;background:#fff;color:#94a3b8;font-size:.58rem;font-weight:900}.progress-step.done{color:#159447}.progress-step.done .progress-dot{border-color:#22b45b;background:#22b45b;color:#fff}.progress-step.done::before{background:#22b45b}.progress-step.active{color:#2563eb}.progress-step.active .progress-dot{border-color:#2563eb;background:#2563eb;color:#fff}.progress-step.active::before{background:linear-gradient(90deg,#22b45b,#2563eb)}.progress-caption{margin-top:.38rem;color:#64748b;font-size:.61rem}.progress-dialog{width:min(620px,calc(100vw - 30px));padding:0;border:0;border-radius:13px;box-shadow:0 24px 70px rgba(15,23,42,.28)}.progress-dialog::backdrop{background:rgba(15,23,42,.42)}.progress-dialog-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1rem;border-bottom:1px solid #e2e8f0}.progress-dialog-head strong{color:#0f172a;font-size:.86rem}.progress-dialog-close{width:30px;height:30px;border:0;border-radius:7px;background:#f1f5f9;color:#475569;cursor:pointer;font-size:1.1rem}.progress-dialog-body{padding:.25rem 1rem .8rem}.progress-detail-row{display:grid;grid-template-columns:28px 100px 1fr 130px;gap:.6rem;align-items:center;padding:.7rem 0;border-bottom:1px solid #e2e8f0;font-size:.7rem}.progress-detail-row:last-child{border-bottom:0}.progress-detail-state{font-weight:800}.progress-detail-state.done{color:#159447}.progress-detail-state.active{color:#2563eb}.progress-detail-state.pending{color:#64748b}.progress-detail-meta{display:grid;gap:.14rem;color:#475569}.progress-detail-meta small{color:#94a3b8;font-size:.6rem}@media(max-width:1100px){.progress-trigger{min-width:260px}.project-table{min-width:1080px}}

    .progress-dialog {
        position: fixed !important;
        inset: 0 !important;
        width: min(620px, calc(100vw - 30px));
        max-height: min(720px, calc(100vh - 30px));
        margin: auto !important;
        overflow: auto;
    }
    .new-project-dialog{position:fixed;inset:0;width:min(1180px,calc(100vw - 30px));height:min(820px,calc(100vh - 30px));margin:auto;padding:0;border:0;border-radius:14px;overflow:hidden;background:#fff;box-shadow:0 24px 70px rgba(15,23,42,.3)}
    .new-project-dialog::backdrop{background:rgba(15,23,42,.55)}.new-project-frame{display:block;width:100%;height:100%;border:0;background:#f8fafc}

</style>

<div class="project-card">
    <div class="project-card-header">
        <h3 class="project-card-title">Project</h3>

        <form method="GET" action="{{ url()->current() }}" class="project-toolbar">
            <input
                type="text"
                name="search"
                class="project-search"
                value="{{ $search ?? '' }}"
                placeholder="Cari project, customer, model, atau part number..."
            >
            <button type="submit" class="btn-project">Cari</button>
            <button type="button" class="btn-project primary" onclick="openNewProjectModal()">+ Project Baru</button>
        </form>
    </div>

    <div class="project-table-wrap">
        <table class="project-table">
            <thead>
                <tr>
                    <th>Project</th><th style="width:130px;">Customer</th><th style="width:100px;">Model</th><th style="width:140px;">No. Assy</th><th style="width:210px;">PIC</th><th style="width:340px;">Progress</th><th style="width:125px;">Update</th><th style="width:64px;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pagedGroups as $group)
                    @php
                        $rowId = 'groupRow' . md5($group->key);
                    @endphp
                    <tr class="group-row" id="{{ $rowId }}Main">
                        <td><div class="project-main"><strong>{{ $group->project_name }}</strong><small>{{ $group->business_category }}</small></div></td>
                        <td>{{ $group->customer }}</td><td>{{ $group->model }}</td>
                        <td class="part-summary"><strong>{{ $group->assy_numbers ?: '-' }}</strong></td>
                        <td><div class="pic-compact"><div><span>Engineering</span><strong>
                            @php
                                $picEngineeringList = collect(explode(',', (string) $group->pic_engineering))
                                    ->map(fn ($pic) => trim($pic))
                                    ->filter(fn ($pic) => $pic !== '' && $pic !== '-')
                                    ->values();
                            @endphp

                            @if($picEngineeringList->count() > 1)
                                <div class="pic-list">
                                    @foreach($picEngineeringList as $picEngineering)
                                        <div>- {{ $picEngineering }}</div>
                                    @endforeach
                                </div>
                            @elseif($picEngineeringList->count() === 1)
                                {{ $picEngineeringList->first() }}
                            @else
                                -
                            @endif
                        </strong></div><div><span>Marketing</span><strong>
                            @php
                                $picMarketingList = collect(explode(',', (string) $group->pic_marketing))
                                    ->map(fn ($pic) => trim($pic))
                                    ->filter(fn ($pic) => $pic !== '' && $pic !== '-')
                                    ->values();
                            @endphp

                            @if($picMarketingList->count() > 1)
                                <div class="pic-list">
                                    @foreach($picMarketingList as $picMarketing)
                                        <div>- {{ $picMarketing }}</div>
                                    @endforeach
                                </div>
                            @elseif($picMarketingList->count() === 1)
                                {{ $picMarketingList->first() }}
                            @else
                                -
                            @endif
                        </strong></div></div></td>
                        <td><button type="button" class="progress-trigger" onclick="openProjectProgress('{{ $rowId }}')"><span class="progress-track">@foreach($group->progress as $step)<span class="progress-step {{ $step['state'] }}"><span class="progress-dot">{{ $step['state']==='done'?'✓':$loop->iteration }}</span><span>{{ $step['label'] }}</span></span>@endforeach</span>@php $currentStep=collect($group->progress)->firstWhere('state','active'); @endphp<span class="progress-caption">{{ $currentStep ? 'Sedang '.$currentStep['label'] : 'Semua tahapan selesai' }} · klik untuk detail</span></button>
                        <template id="{{ $rowId }}Progress"><div class="progress-dialog-head"><strong>Progress Project — {{ $group->project_name }}</strong><button type="button" class="progress-dialog-close" onclick="closeProjectProgress()">×</button></div><div class="progress-dialog-body">@foreach($group->progress as $step)<div class="progress-detail-row"><span class="progress-dot" @if($step['state']==='done') style="border-color:#22b45b;background:#22b45b;color:#fff" @elseif($step['state']==='active') style="border-color:#2563eb;background:#2563eb;color:#fff" @endif>{{ $step['state']==='done'?'✓':$loop->iteration }}</span><strong>{{ $step['label'] }}</strong><span class="progress-detail-state {{ $step['state'] }}">{{ $step['status'] }}</span><span class="progress-detail-meta"><span>{{ $step['date'] ?: '—' }}</span><small>{{ $step['pic'] ?: '—' }}</small></span></div>@endforeach</div></template></td>
                        <td class="updated-compact">@if($group->updated_at){{ \Carbon\Carbon::parse($group->updated_at)->format('d/m/Y') }}<small>{{ \Carbon\Carbon::parse($group->updated_at)->format('H:i') }}</small>@else - @endif</td>
                        <td><details class="row-actions"><summary aria-label="Buka aksi">⋮</summary><div class="row-action-menu">
                                <a href="{{ route('tracking-documents.create', [
                                    'business_category' => $group->business_category,
                                    'customer' => $group->customer,
                                    'model' => $group->model,
                                ], false) }}">
                                    + Tambah Project
                                </a>
                                <button type="button" onclick="toggleProjectGroup('{{ $rowId }}'); this.closest('details').removeAttribute('open')">
                                    Lihat Semua Part
                                </button>
                                <a href="{{ route('database.project-documents', ['search' => $group->customer . ' ' . $group->model], false) }}">
                                    Lihat Dokumen Group
                                </a>
                                <form method="POST" action="{{ route('project.group.destroy', absolute:false) }}" class="js-confirm-form" data-confirm-message="Apakah yakin akan hapus project?">
                                    @csrf
                                    @method('DELETE')
                                    @foreach($group->items->pluck('project.id')->filter()->unique() as $projectId)
                                        <input type="hidden" name="project_ids[]" value="{{ $projectId }}">
                                    @endforeach
                                    <button type="submit" style="color:#dc2626">Hapus Project</button>
                                </form>
                        </div></details></td>
                    </tr>

                    <tr class="child-row" id="{{ $rowId }}Child">
                        <td colspan="8" class="child-cell">
                            <div class="child-panel">
                                <div class="child-panel-head">
                                    <span>ⓘ</span>
                                    <span>
                                        Child project dalam group
                                        <strong>{{ $group->business_category }} / {{ $group->customer }} / {{ $group->model }}</strong>:
                                        {{ $group->total_items }} item
                                    </span>
                                </div>
                                <table class="child-table">
                                    <thead>
                                        <tr>
                                            <th>Part Number</th>
                                            <th>Part Name</th>
                                            <th>Rev</th>
                                            <th>PIC Engineering</th>
                                            <th>PIC Marketing</th>
                                            <th>Status Dokumen</th>
                                            <th>Last Updated</th>
                                            <th style="width:220px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($group->items as $item)
                                            <tr>
                                                <td>{{ $item->part_number }}</td>
                                                <td>{{ $item->part_name }}</td>
                                                <td>{{ $item->revision_label }} ({{ $item->revision_count }} revisi)</td>
                                                <td>{{ $item->pic_engineering }}</td>
                                                <td>{{ $item->pic_marketing }}</td>
                                                <td>
                                                    <span class="status-pill {{ $item->status_class }}">{{ $item->status_label }}</span>

                                                    @if(!empty($item->health_messages))
                                                        <div class="costing-health">
                                                            @foreach($item->health_messages as $healthMessage)
                                                                <span class="costing-health-badge {{ $healthMessage['type'] }}">
                                                                    {{ $healthMessage['label'] }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <div class="approval-meta">
                                                        @if($item->cogm_value !== null)
                                                            <span>COGM: <strong>Rp {{ number_format($item->cogm_value, 0, ',', '.') }}</strong></span>
                                                        @endif
                                                        @if($item->approval_submitted_at)
                                                            <span>Submitted by: <strong>{{ $item->approval_submitter }}</strong></span>
                                                        @endif
                                                        @if($item->approval_approved_at)
                                                            <span>Approved by: <strong>{{ $item->approval_approver }}</strong></span>
                                                        @endif
                                                        @if($item->approval_rejection_notes)
                                                            <span>Reject note: <strong>{{ $item->approval_rejection_notes }}</strong></span>
                                                        @endif
                                                        @if($item->marketing_submitted_at)
                                                            <span>Marketing sent: <strong>{{ \Carbon\Carbon::parse($item->marketing_submitted_at)->format('d/m/Y H:i') }}</strong></span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('d/m/Y H:i') : '-' }}</td>
                                                <td>
                                                    <div class="action-stack">
                                                        @if($item->project)
                                                            <form action="{{ route('tracking-documents.destroy-project', ['project' => $item->project->id], false) }}" method="POST"
                                                                onsubmit="return confirm('Hapus semua data project {{ $item->customer }} / {{ $item->model }} / {{ $item->part_number }}?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="action-link action-delete">
                                                                    Hapus Semua
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <a class="action-link" href="{{ route('database.project-documents', ['search' => $item->part_number], false) }}">
                                                            Lihat Dokumen
                                                        </a>
                                                        <a class="action-link" href="{{ url('/form') }}?tracking_revision_id={{ $item->revision->id }}">
                                                            Form Costing
                                                        </a>

                                                        @if($item->can_submit_approval)
                                                            <form action="{{ route('costing-approvals.submit', ['revision' => $item->revision->id], false) }}" method="POST"
                                                                onsubmit="return confirm('Submit costing {{ e($item->part_number) }} ke Coordinator Costing? Data akan dikunci sampai approval selesai.');">
                                                                @csrf
                                                                <button type="submit" class="action-link action-submit">Submit Approval</button>
                                                            </form>
                                                        @endif

                                                        @if($item->can_approve_approval)
                                                            <form action="{{ route('costing-approvals.approve', ['revision' => $item->revision->id], false) }}" method="POST"
                                                                onsubmit="return confirm('Approve COGM {{ e($item->part_number) }}?');">
                                                                @csrf
                                                                <button type="submit" class="action-link action-approve">Approve COGM</button>
                                                            </form>
                                                        @endif

                                                        @if($item->can_reject_approval)
                                                            <form action="{{ route('costing-approvals.reject', ['revision' => $item->revision->id], false) }}" method="POST"
                                                                onsubmit="const note = prompt('Masukkan catatan reject untuk Admin Costing:'); if (note === null) return false; this.querySelector('[name=rejection_notes]').value = note.trim(); return note.trim().length > 0;">
                                                                @csrf
                                                                <input type="hidden" name="rejection_notes" value="">
                                                                <button type="submit" class="action-link action-reject">Reject</button>
                                                            </form>
                                                        @endif

                                                        @if($item->can_send_marketing)
                                                            <form action="{{ route('costing-approvals.send-marketing', ['revision' => $item->revision->id], false) }}" method="POST"
                                                                onsubmit="return confirm('Kirim COGM approved {{ e($item->part_number) }} ke Team Marketing?');">
                                                                @csrf
                                                                <input type="hidden" name="pic_marketing" value="{{ $item->pic_marketing }}">
                                                                <button type="submit" class="action-link action-send">Send Marketing</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">Belum ada project yang bisa ditampilkan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="project-pagination">
        <div>
            Menampilkan {{ $pagedGroups->firstItem() ?? 0 }} - {{ $pagedGroups->lastItem() ?? 0 }} dari {{ $pagedGroups->total() }} project group
        </div>
        <div>
            {{ $pagedGroups->onEachSide(1)->links() }}
        </div>
    </div>
</div>

<dialog class="progress-dialog" id="projectProgressDialog"><div id="projectProgressContent"></div></dialog>
<dialog class="new-project-dialog" id="newProjectDialog"><iframe id="newProjectFrame" class="new-project-frame" title="Form New Project"></iframe></dialog>

<script>
    function openProjectProgress(rowId) {
        const template = document.getElementById(rowId + 'Progress');
        const dialog = document.getElementById('projectProgressDialog');
        const content = document.getElementById('projectProgressContent');
        if (!template || !dialog || !content) return;
        content.replaceChildren(template.content.cloneNode(true));
        dialog.showModal();
    }

    function closeProjectProgress() {
        document.getElementById('projectProgressDialog')?.close();
    }

    function openNewProjectModal(){const dialog=document.getElementById('newProjectDialog'),frame=document.getElementById('newProjectFrame');frame.src=@json(route('tracking-documents.create',['embedded'=>1],false));dialog.showModal()}
    function closeNewProjectModal(reload=false){const dialog=document.getElementById('newProjectDialog'),frame=document.getElementById('newProjectFrame');dialog.close();frame.src='';if(reload)location.reload()}
    window.addEventListener('message',event=>{if(event.origin!==location.origin)return;if(event.data?.type==='new-project-cancel')closeNewProjectModal(false);if(event.data?.type==='new-project-created')closeNewProjectModal(true)});

    document.getElementById('projectProgressDialog')?.addEventListener('click', function (event) {
        if (event.target === this) this.close();
    });

    function toggleProjectGroup(rowId) {
        const child = document.getElementById(rowId + 'Child');
        const main = document.getElementById(rowId + 'Main');
        const button = main ? main.querySelector('.expand-btn') : null;

        if (!child) {
            return;
        }

        child.classList.toggle('is-open');
        main?.classList.toggle('is-open');
        button?.classList.toggle('is-open');
    }

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.row-actions[open]').forEach(function (menu) {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });

    document.querySelectorAll('.row-actions').forEach(function (menu) {
        menu.addEventListener('toggle', function () {
            if (!menu.open) return;
            document.querySelectorAll('.row-actions[open]').forEach(function (other) {
                if (other !== menu) other.removeAttribute('open');
            });
        });
    });
</script>
@endsection
