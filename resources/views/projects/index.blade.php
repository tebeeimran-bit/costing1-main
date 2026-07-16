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
        overflow-x: auto;
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
        min-width: 1500px;
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

    .workflow-summary {
        display: grid;
        gap: 0.42rem;
        width: 100%;
        min-width: 0;
        margin-top: 0.55rem;
        padding-top: 0.55rem;
        border-top: 1px solid #e2e8f0;
    }

    .workflow-summary-head,
    .workflow-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        color: #334155;
        font-size: 0.68rem;
        font-weight: 850;
    }

    .workflow-progress-track {
        height: 7px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .workflow-progress-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #0ea5e9);
    }

    .workflow-summary-meta {
        color: #64748b;
        font-size: 0.64rem;
        font-weight: 750;
        line-height: 1.35;
    }

    .workflow-summary-meta.complete { color: #15803d; }
    .workflow-summary-meta.attention { color: #b45309; }

    .workflow-card {
        display: grid;
        gap: 0.65rem;
        min-width: 330px;
        padding: 0.72rem;
        border: 1px solid #dbe5f2;
        border-radius: 10px;
        background: #f8fafc;
    }

    .workflow-steps {
        display: grid;
        grid-template-columns: repeat(6, minmax(42px, 1fr));
        gap: 0.25rem;
    }

    .workflow-step {
        display: grid;
        justify-items: center;
        gap: 0.25rem;
        position: relative;
        color: #94a3b8;
        font-size: 0.56rem;
        font-weight: 800;
        text-align: center;
    }

    .workflow-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 10px;
        left: calc(50% + 12px);
        width: calc(100% - 20px);
        height: 2px;
        background: #dbe5f2;
    }

    .workflow-step-dot {
        width: 21px;
        height: 21px;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #cbd5e1;
        border-radius: 999px;
        background: #fff;
        color: #94a3b8;
        font-size: 0.62rem;
        font-weight: 950;
    }

    .workflow-step.complete { color: #15803d; }
    .workflow-step.complete .workflow-step-dot { border-color: #22c55e; background: #dcfce7; color: #15803d; }
    .workflow-step.complete:not(:last-child)::after { background: #86efac; }
    .workflow-step.current { color: #1d4ed8; }
    .workflow-step.current .workflow-step-dot { border-color: #3b82f6; background: #dbeafe; color: #1d4ed8; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
    .workflow-step.issue { color: #b45309; }
    .workflow-step.issue .workflow-step-dot { border-color: #f59e0b; background: #fef3c7; color: #b45309; }

    .workflow-next {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        padding-top: 0.55rem;
        border-top: 1px solid #e2e8f0;
    }

    .workflow-next-copy { min-width: 0; }
    .workflow-next-label { color: #0f172a; font-size: 0.68rem; font-weight: 900; }
    .workflow-next-description { color: #64748b; font-size: 0.60rem; font-weight: 650; line-height: 1.35; margin-top: 0.12rem; }
    .workflow-next-link {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 29px;
        padding: 0.3rem 0.55rem;
        border: 1px solid #bfdbfe;
        border-radius: 7px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.62rem;
        font-weight: 850;
        text-decoration: none;
        white-space: nowrap;
    }
    .workflow-next-link.issue { border-color: #fed7aa; background: #fff7ed; color: #c2410c; }
    .workflow-next-link.complete { border-color: #bbf7d0; background: #f0fdf4; color: #15803d; }
    .workflow-next-waiting { color: #64748b; font-size: 0.62rem; font-weight: 800; white-space: nowrap; }

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


</style>

<div class="project-card">
    <div class="project-card-header">
        <h3 class="project-card-title">List Project</h3>

        <form method="GET" action="{{ url()->current() }}" class="project-toolbar">
            <input
                type="text"
                name="search"
                class="project-search"
                value="{{ $search ?? '' }}"
                placeholder="Cari project, customer, model, atau part number..."
            >
            <button type="submit" class="btn-project">Search</button>
            <a href="{{ url('/tracking-documents/new') }}" class="btn-project primary">+ New Project</a>
        </form>
    </div>

    <div class="project-table-wrap">
        <table class="project-table">
            <thead>
                <tr>
                    <th style="width:44px;"></th>
                    <th style="width:115px;">Tanggal</th>
                    <th>Informasi Project</th>
                    <th style="width:130px;">Total Part Number</th>
                    <th style="width:130px;">PIC Engineering</th>
                    <th style="width:130px;">PIC Marketing</th>
                    <th style="width:200px;">Status & Workflow</th>
                    <th style="width:120px;">Last Updated</th>
                    <th style="width:220px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pagedGroups as $group)
                    @php
                        $rowId = 'groupRow' . md5($group->key);
                    @endphp
                    <tr class="group-row" id="{{ $rowId }}Main">
                        <td class="expand-cell">
                            <button type="button" class="expand-btn" onclick="toggleProjectGroup('{{ $rowId }}')" aria-label="Expand project group">›</button>
                        </td>
                        <td>
                            {{ $group->created_at ? \Carbon\Carbon::parse($group->created_at)->format('d/m/Y') : '-' }}
                        </td>
                        <td>
                            <div class="project-info-box">
                                <div>
                                    <div class="info-label">Business Categories</div>
                                    <div class="info-value">{{ $group->business_category }}</div>
                                </div>
                                <div>
                                    <div class="info-label">Customer</div>
                                    <div class="info-value">{{ $group->customer }}</div>
                                </div>
                                <div>
                                    <div class="info-label">Model</div>
                                    <div class="info-value">{{ $group->model }}</div>
                                </div>
                                <div>
                                    <div class="info-label">Nama Project</div>
                                    <div class="info-value">{{ $group->project_name }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $group->total_part_number }}</strong> Part Number
                            @if($group->total_items !== $group->total_part_number)
                                <div style="font-size:.7rem;color:#64748b;margin-top:.18rem;">{{ $group->total_items }} item/revisi</div>
                            @endif
                        </td>
                        <td>
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
                        </td>
                        <td>
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
                        </td>
                        <td>
                            <div class="status-stack">
                                @foreach($group->status_summary as $status)
                                    <span class="status-pill {{ $status->class }}">{{ $status->count }} {{ $status->label }}</span>
                                @endforeach
                            </div>
                            <div class="workflow-summary">
                                <div class="workflow-summary-head">
                                    <span>Progress</span>
                                    <strong>{{ $group->workflow_progress }}%</strong>
                                </div>
                                <div class="workflow-progress-track">
                                    <div class="workflow-progress-fill" style="width: {{ $group->workflow_progress }}%;"></div>
                                </div>
                                @if($group->workflow_attention > 0)
                                    <div class="workflow-summary-meta attention">
                                        {{ $group->workflow_attention }} item perlu tindakan berikutnya
                                    </div>
                                @else
                                    <div class="workflow-summary-meta complete">Semua item sudah dikirim ke Marketing</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            {{ $group->updated_at ? \Carbon\Carbon::parse($group->updated_at)->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td>
                            <div class="action-stack">
                                <a class="action-link" href="{{ route('tracking-documents.create', [
                                    'business_category' => $group->business_category,
                                    'customer' => $group->customer,
                                    'model' => $group->model,
                                ], false) }}">
                                    + New Project
                                </a>
                                <button type="button" class="action-link" onclick="toggleProjectGroup('{{ $rowId }}')">
                                    Lihat Semua Part
                                </button>
                                <a class="action-link" href="{{ route('database.project-documents', ['search' => $group->customer . ' ' . $group->model], false) }}">
                                    Lihat Dokumen Group
                                </a>
                            </div>
                        </td>
                    </tr>

                    <tr class="child-row" id="{{ $rowId }}Child">
                        <td colspan="9" class="child-cell">
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
                                            <th style="width:380px;">Workflow & Langkah Berikutnya</th>
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
                                                <td>
                                                    <div class="workflow-card">
                                                        <div class="workflow-card-head">
                                                            <span>{{ $item->workflow['completed_count'] }}/{{ $item->workflow['total_count'] }} tahap selesai</span>
                                                            <strong>{{ $item->workflow['progress'] }}%</strong>
                                                        </div>
                                                        <div class="workflow-progress-track">
                                                            <div class="workflow-progress-fill" style="width: {{ $item->workflow['progress'] }}%;"></div>
                                                        </div>
                                                        <div class="workflow-steps">
                                                            @foreach($item->workflow['steps'] as $workflowStep)
                                                                <div class="workflow-step {{ $workflowStep['state'] }}">
                                                                    <span class="workflow-step-dot">
                                                                        {{ $workflowStep['complete'] ? '✓' : $loop->iteration }}
                                                                    </span>
                                                                    <span>{{ $workflowStep['label'] }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @php($nextAction = $item->workflow['next_action'])
                                                        <div class="workflow-next">
                                                            <div class="workflow-next-copy">
                                                                <div class="workflow-next-label">{{ $nextAction['label'] }}</div>
                                                                <div class="workflow-next-description">{{ $nextAction['description'] }}</div>
                                                            </div>
                                                            @if($nextAction['url'])
                                                                <a href="{{ $nextAction['url'] }}" class="workflow-next-link {{ $nextAction['type'] === 'issue' ? 'issue' : '' }}">
                                                                    Buka
                                                                </a>
                                                            @elseif($nextAction['type'] === 'complete')
                                                                <span class="workflow-next-link complete">Selesai</span>
                                                            @else
                                                                <span class="workflow-next-waiting">Menunggu</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('d/m/Y H:i') : '-' }}</td>
                                                <td>
                                                    <div class="action-stack" id="workflow-actions-{{ $item->revision->id }}">
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
                                                        <a class="action-link" href="{{ route('project-collaboration.show', $item->revision, false) }}">
                                                            Activity &amp; Comments
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
                        <td colspan="9">
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

<script>
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
</script>
@endsection
