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
        min-width: 1450px;
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
    .shared-a00-note{display:grid;grid-template-columns:24px minmax(0,1fr) auto;width:min(100%,270px);align-items:center;gap:.42rem;margin-top:.3rem;padding:.4rem .48rem;border:1px solid #bfdbfe;border-radius:9px;background:linear-gradient(135deg,#eff6ff,#f8fbff);box-shadow:0 2px 6px rgba(37,99,235,.06)}
    .shared-a00-icon{display:flex;width:24px;height:24px;align-items:center;justify-content:center;border-radius:7px;background:#dbeafe;color:#2563eb}.shared-a00-icon svg{width:14px;height:14px}
    .shared-a00-copy{min-width:0;display:grid;gap:.04rem}.shared-a00-copy span{color:#2563eb;font-size:.54rem;font-weight:900;letter-spacing:.035em;text-transform:uppercase}.shared-a00-copy strong{overflow:hidden;color:#1e3a8a;font-size:.58rem;font-weight:800;text-overflow:ellipsis;white-space:nowrap}
    .shared-a00-count{display:grid;min-width:40px;justify-items:center;padding:.2rem .35rem;border-radius:7px;background:#fff;color:#1d4ed8;box-shadow:inset 0 0 0 1px #dbeafe}.shared-a00-count b{font-size:.68rem;line-height:1}.shared-a00-count small{color:#64748b!important;font-size:.48rem!important;font-weight:750!important;white-space:nowrap}
    .part-summary { white-space: nowrap; }
    .assy-number-list { margin: 0; padding-left: 1.15rem; color: #0f172a; font-weight: 750; line-height: 1.65; }
    .assy-number-list li { padding-left: .12rem; }
    .pic-compact { display: grid; gap: .24rem; min-width: 155px; }
    .pic-compact > div { display: grid; grid-template-columns: 66px minmax(0, 1fr); gap: .35rem; align-items: start; line-height: 1.25; }
    .pic-compact span { color: #64748b; font-size: .62rem; font-weight: 700; }
    .pic-compact strong { color: #334155; font-size: .68rem; font-weight: 750; }
    .pic-compact .pic-list { display: flex; flex-direction: column; align-items: flex-start; gap: .12rem; }
    .pic-compact .pic-list > div { display: block; width: 100%; }
    .group-row .status-stack { display: flex; flex-wrap: wrap; gap: .25rem; }
    .group-row .status-pill { padding: .25rem .48rem; font-size: .62rem; }
    .updated-compact { color: #475569; white-space: nowrap; line-height: 1.35; }
    .updated-compact small { display: block; color: #94a3b8; font-size: .62rem; }
    .updated-compact .update-note{display:block;min-width:150px;max-width:190px;margin-top:.28rem;color:#64748b;font-size:.60rem;font-weight:650;line-height:1.45;white-space:normal;overflow-wrap:anywhere}
    .row-actions { position: relative; display: flex; justify-content: center; }
    .row-actions summary { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #334155; cursor: pointer; font-size: 1.05rem; font-weight: 900; list-style: none; }
    .row-actions summary::-webkit-details-marker { display: none; }
    .row-actions[open] summary { border-color: #93c5fd; background: #eff6ff; color: #2563eb; }
    .row-action-menu { position: absolute; z-index: 20; top: 36px; right: 0; width: 180px; padding: .35rem; border: 1px solid #dbe5f2; border-radius: 9px; background: #fff; box-shadow: 0 12px 28px rgba(15,23,42,.16); }
    .row-action-menu a,.row-action-menu button { box-sizing: border-box; display: flex; width: 100%; align-items: center; padding: .55rem .65rem; border: 0; border-radius: 6px; background: transparent; color: #334155; font-size: .69rem; font-weight: 750; text-align: left; text-decoration: none; cursor: pointer; }
    .row-action-menu a:hover,.row-action-menu button:hover { background: #eff6ff; color: #1d4ed8; }
    .progress-trigger{display:block;width:100%;min-width:300px;padding:.2rem 0;border:0;background:transparent;text-align:left;cursor:pointer}.progress-track{display:grid;grid-template-columns:repeat(6,1fr);align-items:start}.progress-step{position:relative;display:grid;justify-items:center;gap:.28rem;color:#94a3b8;font-size:.55rem;font-weight:750}.progress-step:not(:first-child)::before{content:"";position:absolute;top:9px;right:50%;width:100%;height:2px;background:#dbe3ef;z-index:0}.progress-dot{position:relative;z-index:1;display:flex;align-items:center;justify-content:center;width:19px;height:19px;border:2px solid #cbd5e1;border-radius:50%;background:#fff;color:#94a3b8;font-size:.58rem;font-weight:900}.progress-step.done{color:#159447}.progress-step.done .progress-dot{border-color:#22b45b;background:#22b45b;color:#fff}.progress-step.done::before{background:#22b45b}.progress-step.active{color:#2563eb}.progress-step.active .progress-dot{border-color:#2563eb;background:#2563eb;color:#fff}.progress-step.active::before{background:linear-gradient(90deg,#22b45b,#2563eb)}.progress-caption{margin-top:.38rem;color:#64748b;font-size:.61rem}.progress-dialog{width:min(620px,calc(100vw - 30px));padding:0;border:0;border-radius:13px;box-shadow:0 24px 70px rgba(15,23,42,.28)}.progress-dialog::backdrop{background:rgba(15,23,42,.42)}.progress-dialog-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1rem;border-bottom:1px solid #e2e8f0}.progress-dialog-head strong{color:#0f172a;font-size:.86rem}.progress-dialog-close{width:30px;height:30px;border:0;border-radius:7px;background:#f1f5f9;color:#475569;cursor:pointer;font-size:1.1rem}.progress-dialog-body{padding:.25rem 1rem .8rem}.progress-detail-row{display:grid;grid-template-columns:28px 100px 1fr 130px;gap:.6rem;align-items:center;padding:.7rem 0;border-bottom:1px solid #e2e8f0;font-size:.7rem}.progress-detail-row:last-child{border-bottom:0}.progress-detail-state{font-weight:800}.progress-detail-state.done{color:#159447}.progress-detail-state.active{color:#2563eb}.progress-detail-state.pending{color:#64748b}.progress-detail-meta{display:grid;gap:.14rem;color:#475569}.progress-detail-meta small{color:#94a3b8;font-size:.6rem}@media(max-width:1100px){.progress-trigger{min-width:260px}.project-table{min-width:1080px}}
    .progress-step.skipped{color:#64748b}.progress-step.skipped .progress-dot{border-color:#94a3b8;background:#e2e8f0;color:#475569}.progress-step.skipped::before{background:#cbd5e1}.progress-detail-state.skipped{color:#64748b}

     .assy-progress-list { padding-top: .35rem !important; padding-bottom: .35rem !important; }
     .assy-progress-item { padding: .28rem 0; }
     .assy-progress-item + .assy-progress-item { border-top: 1px solid #e2e8f0; }
     .assy-progress-title { display: block; margin-bottom: .08rem; color: #0f2f64; font-size: .62rem; }
     .assy-progress-item .progress-trigger { padding: 0; }
     .assy-progress-item .progress-caption { margin-top: .2rem; }
     .progress-track { grid-template-columns: repeat(7, 1fr); }
     /* Keep the seven workflow stages visible after adding New Part Request. */
     .progress-track { grid-template-columns: repeat(7, minmax(0, 1fr)) !important; }
     .progress-step { min-width: 0; }
     .progress-step > span:last-child { text-align: center; white-space: normal; }
     .progress-dialog {
        position: fixed !important;
        inset: 0 !important;
        width: min(620px, calc(100vw - 30px));
        max-height: min(720px, calc(100vh - 30px));
        margin: auto !important;
        overflow: auto;
    }
    .new-project-dialog{position:fixed;inset:0;width:min(980px,calc(100vw - 30px));height:min(540px,calc(100vh - 30px));margin:auto;padding:0;border:0;border-radius:14px;overflow:hidden;background:#fff;box-shadow:0 24px 70px rgba(15,23,42,.3)}
    .new-project-dialog::backdrop{background:rgba(15,23,42,.55)}.new-project-frame{display:block;width:100%;height:100%;border:0;background:#f8fafc}
    .edit-project-dialog{position:fixed;inset:0;width:min(880px,calc(100vw - 28px));max-height:calc(100vh - 28px);margin:auto;padding:0;border:0;border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 28px 80px rgba(15,23,42,.32)}
    .edit-project-dialog::backdrop{background:rgba(15,23,42,.58);backdrop-filter:blur(2px)}.edit-project-form{display:flex;max-height:calc(100vh - 28px);flex-direction:column}
    .edit-project-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.2rem;background:linear-gradient(135deg,#123f86,#2563eb);color:#fff}.edit-project-head h3{margin:0;font-size:1rem}.edit-project-head p{margin:.18rem 0 0;color:#dbeafe;font-size:.69rem;font-weight:600}
    .edit-project-close{display:grid;width:32px;height:32px;place-items:center;border:1px solid rgba(255,255,255,.35);border-radius:8px;background:rgba(255,255,255,.12);color:#fff;font-size:1.2rem;cursor:pointer}.edit-project-body{overflow-y:auto;padding:1.15rem 1.2rem;background:#f8fafc}
    .edit-project-section{padding:1rem;border:1px solid #dbe5f2;border-radius:12px;background:#fff}.edit-project-section+.edit-project-section{margin-top:.85rem}.edit-project-section-title{margin:0 0 .8rem;color:#0f172a;font-size:.78rem;font-weight:850}
    .edit-project-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.8rem}.edit-project-grid .span-2{grid-column:span 2}.edit-project-field{display:grid;min-width:0;gap:.35rem}.edit-project-field label{color:#475569;font-size:.68rem;font-weight:800}.edit-project-field label span{color:#dc2626}
    .edit-project-field input,.edit-project-field select{box-sizing:border-box;width:100%;height:40px;padding:0 .7rem;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#0f172a;font:inherit;font-size:.75rem;outline:none}.edit-project-field input:focus,.edit-project-field select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.11)}
    .edit-project-quantity{display:grid;grid-template-columns:1.1fr .8fr 1.1fr;gap:.4rem}.edit-project-actions{display:flex;justify-content:flex-end;gap:.6rem;padding:.85rem 1.2rem;border-top:1px solid #e2e8f0;background:#fff}.edit-project-actions button{min-height:38px;padding:.5rem 1rem;border-radius:8px;font-size:.72rem;font-weight:800;cursor:pointer}.edit-cancel{border:1px solid #cbd5e1;background:#fff;color:#475569}.edit-save{border:1px solid #2563eb;background:#2563eb;color:#fff}
    .edit-forecast-option{grid-column:1/-1}.edit-forecast-toggle{border:1px solid #2563eb;border-radius:7px;background:#fff;color:#1d4ed8;padding:.48rem .7rem;font-size:.7rem;font-weight:800;cursor:pointer}.edit-forecast-panel{margin-top:.65rem;padding:.75rem;border:1px solid #bfdbfe;border-radius:9px;background:#f8fbff}.edit-forecast-panel[hidden]{display:none}.edit-forecast-head{display:grid;grid-template-columns:180px 1fr;align-items:end;gap:.7rem;margin-bottom:.55rem}.edit-forecast-head label{display:grid;gap:.3rem;color:#475569;font-size:.68rem;font-weight:800}.edit-forecast-head select{height:36px;border:1px solid #cbd5e1;border-radius:7px;background:#fff}.edit-forecast-note{color:#64748b;font-size:.68rem}.edit-forecast-row{display:grid;grid-template-columns:90px 1fr 130px;align-items:center;gap:.55rem;margin-top:.35rem;color:#334155;font-size:.7rem}.edit-forecast-row input{height:34px!important}.edit-forecast-average{text-align:right;color:#64748b}.edit-forecast-summary{display:flex;justify-content:space-between;margin-top:.65rem;padding-top:.55rem;border-top:1px solid #bfdbfe;color:#1d4ed8;font-size:.72rem;font-weight:850}
    @media(max-width:760px){.edit-project-grid{grid-template-columns:1fr}.edit-project-grid .span-2{grid-column:auto}.edit-project-quantity{grid-template-columns:1fr}.edit-project-actions button{flex:1}}

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
                    <th style="width:105px;">Tanggal</th><th style="width:145px;">Customer</th><th style="width:90px;">Model</th><th style="width:175px;">No. Assy</th><th style="width:210px;">Assy Name</th><th style="width:210px;">PIC</th><th style="width:340px;">Progress</th><th style="width:190px;">Update Terakhir</th><th style="width:64px;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pagedGroups as $group)
                    @php
                        $rowId = 'groupRow' . md5($group->key);
                    @endphp
                    <tr class="group-row" id="{{ $rowId }}Main">
                        <td class="updated-compact">@if($group->created_at){{ \Carbon\Carbon::parse($group->created_at)->format('d/m/Y') }}@else - @endif</td>
                        <td>{{ $group->customer }}</td><td>{{ $group->model }}</td>
                        <td class="part-summary">
                            @php
                                $assyNumberList = $group->items->pluck('part_number')->filter()->unique()->values();
                            @endphp
                            @if($assyNumberList->count()>1)
                                <ol class="assy-number-list">@foreach($assyNumberList as $assyNumber)<li>{{ $assyNumber }}</li>@endforeach</ol>
                            @else
                                <strong>{{ $assyNumberList->first() ?: '-' }}</strong>
                            @endif
                        </td>
                        <td><div class="project-main"><strong>{{ $group->project_name }}</strong><small>{{ $group->business_category }}</small>@foreach($group->shared_a00_labels as $sharedA00)<div class="shared-a00-note" title="Digunakan bersama oleh {{ $sharedA00->project_count }} project"><span class="shared-a00-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></span><span class="shared-a00-copy"><span>A00 Gabung</span><strong>{{ $sharedA00->number }}</strong></span><span class="shared-a00-count"><b>{{ $sharedA00->project_count }}</b><small>Project</small></span></div>@endforeach</div></td>
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
                        @if($group->grouped_by_a00 && $group->items->count() > 1)
                        <td class="assy-progress-list">
                            @foreach($group->items as $assyItem)
                                @php
                                    $itemProgress = collect($assyItem->progress)->values();
                                    $itemProgressId = $rowId.'Item'.$assyItem->revision->id;
                                    $itemCurrentStep = $itemProgress->firstWhere('state', 'active');
                                    $itemAllDone = $itemProgress->every(fn ($step) => $step['state'] === 'done');
                                    $itemAllPending = $itemProgress->every(fn ($step) => $step['state'] === 'pending');
                                    $itemSkipped = $itemProgress->filter(fn ($step) => str_starts_with((string) ($step['status'] ?? ''), 'Dilewati'));
                                    $itemProgressCaption = $itemCurrentStep
                                        ? 'Sedang '.$itemCurrentStep['label']
                                        : ($itemAllDone ? 'Semua tahapan selesai'
                                            : ($itemAllPending ? 'Belum ada tahapan dimulai'
                                                : ($itemSkipped->isNotEmpty() ? 'Tahap '.$itemSkipped->pluck('label')->join(', ').' dilewati' : 'Menunggu kelanjutan workflow')));
                                @endphp
                                <div class="assy-progress-item">
                                    <strong class="assy-progress-title">{{ $assyItem->part_number }}</strong>
                                    <button type="button" class="progress-trigger" onclick="openProjectProgress('{{ $itemProgressId }}')"><span class="progress-track">@foreach($itemProgress as $step)<span class="progress-step {{ ($step['skipped'] ?? false)?'skipped':$step['state'] }}"><span class="progress-dot">{{ ($step['skipped'] ?? false)?'−':($step['state']==='done'?'✓':$loop->iteration) }}</span><span>{{ $step['label'] }}</span></span>@endforeach</span><span class="progress-caption">{{ $itemCurrentStep ? 'Sedang '.$itemCurrentStep['label'] : 'Semua tahapan selesai' }} · klik untuk detail</span></button>
                                    <template id="{{ $itemProgressId }}Progress"><div class="progress-dialog-head"><strong>Progress {{ $assyItem->part_number }} — {{ $assyItem->part_name }}</strong><button type="button" class="progress-dialog-close" onclick="closeProjectProgress()">×</button></div><div class="progress-dialog-body">@foreach($itemProgress as $step)<div class="progress-detail-row"><span class="progress-dot" @if($step['skipped'] ?? false) style="border-color:#94a3b8;background:#e2e8f0;color:#475569" @elseif($step['state']==='done') style="border-color:#22b45b;background:#22b45b;color:#fff" @elseif($step['state']==='active') style="border-color:#2563eb;background:#2563eb;color:#fff" @endif>{{ ($step['skipped'] ?? false)?'−':($step['state']==='done'?'✓':$loop->iteration) }}</span><strong>{{ $step['label'] }}</strong><span class="progress-detail-state {{ ($step['skipped'] ?? false)?'skipped':$step['state'] }}">{{ $step['status'] }}</span><span class="progress-detail-meta"><span>{{ $step['date'] ?: '—' }}</span>@if($step['time'])<small>{{ $step['time'] }}</small>@endif<small>{{ $step['pic'] ?: '—' }}</small></span></div>@endforeach</div></template>
                                </div>
                            @endforeach
                        </td>
                        @else
                        @php
                            // Normalize old progress payloads so this page always shows the new stage.
                            $progress = collect($group->progress)->values();
                            if (!$progress->contains('key', 'new-part-request')) {
                                $costingIndex = $progress->search(fn ($step) => ($step['key'] ?? null) === 'costing');
                                $newPartStep = ['key' => 'new-part-request', 'label' => 'New Part Request', 'state' => 'pending', 'status' => 'Belum dimulai', 'date' => null, 'time' => null, 'pic' => '-'];
                                $progress->splice($costingIndex === false ? $progress->count() : $costingIndex + 1, 0, [$newPartStep]);
                            }
                            $currentStep = $progress->firstWhere('state','active');
                            $allStepsDone = $progress->every(fn ($step) => $step['state'] === 'done');
                            $allStepsPending = $progress->every(fn ($step) => $step['state'] === 'pending');
                            $skippedSteps = $progress->filter(fn ($step) => str_starts_with((string) ($step['status'] ?? ''), 'Dilewati'));
                            $progressCaption = $currentStep
                                ? 'Sedang '.$currentStep['label']
                                : ($allStepsDone ? 'Semua tahapan selesai'
                                    : ($allStepsPending ? 'Belum ada tahapan dimulai'
                                        : ($skippedSteps->isNotEmpty() ? 'Tahap '.$skippedSteps->pluck('label')->join(', ').' dilewati' : 'Menunggu kelanjutan workflow')));
                        @endphp
                        <td><button type="button" class="progress-trigger" onclick="openProjectProgress('{{ $rowId }}')"><span class="progress-track">@foreach($progress as $step)<span class="progress-step {{ ($step['skipped'] ?? false)?'skipped':$step['state'] }}"><span class="progress-dot">{{ ($step['skipped'] ?? false)?'−':($step['state']==='done'?'✓':$loop->iteration) }}</span><span>{{ $step['label'] }}</span></span>@endforeach</span>@php $currentStep=$progress->firstWhere('state','active'); @endphp<span class="progress-caption">{{ $currentStep ? 'Sedang '.$currentStep['label'] : 'Semua tahapan selesai' }} · klik untuk detail</span></button>
                        <template id="{{ $rowId }}Progress"><div class="progress-dialog-head"><strong>Progress Project — {{ $group->project_name }}</strong><button type="button" class="progress-dialog-close" onclick="closeProjectProgress()">×</button></div><div class="progress-dialog-body">@foreach($progress as $step)<div class="progress-detail-row"><span class="progress-dot" @if($step['skipped'] ?? false) style="border-color:#94a3b8;background:#e2e8f0;color:#475569" @elseif($step['state']==='done') style="border-color:#22b45b;background:#22b45b;color:#fff" @elseif($step['state']==='active') style="border-color:#2563eb;background:#2563eb;color:#fff" @endif>{{ ($step['skipped'] ?? false)?'−':($step['state']==='done'?'✓':$loop->iteration) }}</span><strong>{{ $step['label'] }}</strong><span class="progress-detail-state {{ ($step['skipped'] ?? false)?'skipped':$step['state'] }}">{{ $step['status'] }}</span><span class="progress-detail-meta"><span>{{ $step['date'] ?: '—' }}</span>@if($step['time'])<small>{{ $step['time'] }}</small>@endif<small>{{ $step['pic'] ?: '—' }}</small></span></div>@endforeach</div></template></td>
                        @endif
                        <td class="updated-compact">@if($group->updated_at){{ \Carbon\Carbon::parse($group->updated_at)->format('d/m/Y') }}<small>{{ \Carbon\Carbon::parse($group->updated_at)->format('H:i') }}</small><span class="update-note" title="{{ $group->update_note }}">{{ $group->update_note }}</span>@else - @endif</td>
                        <td><details class="row-actions"><summary aria-label="Buka aksi">⋮</summary><div class="row-action-menu">
                                @foreach($group->items as $editItem)
                                    @php
                                        $editCategory = $businessCategories->first(fn ($category) => strcasecmp((string) $category->code, (string) $editItem->project?->product?->code) === 0);
                                        $editCustomer = $customers->first(fn ($customer) => strcasecmp(trim((string) $customer->name), trim((string) $editItem->customer)) === 0);
                                        $editForecasts = \App\Models\ProjectQuantityForecast::where('document_revision_id',$editItem->revision->id)->orderBy('year_number')->orderBy('month_number')->get();
                                        $editPayload = [
                                            'action' => route('tracking-documents.update-project-info', ['project' => $editItem->project->id], false),
                                            'business_category_id' => $editCategory?->id,
                                            'customer_id' => $editItem->costing?->customer_id ?? $editCustomer?->id,
                                            'model' => $editItem->model,
                                            'part_number' => $editItem->part_number,
                                            'part_name' => $editItem->part_name,
                                            'forecast' => $editItem->costing?->forecast,
                                            'forecast_uom' => $editItem->costing?->forecast_uom ?? 'PCE',
                                            'forecast_basis' => $editItem->costing?->forecast_basis ?? 'per_month',
                                            'project_period' => $editItem->costing?->project_period,
                                            'quantity_forecast_enabled' => $editForecasts->isNotEmpty(),
                                            'quantity_forecast_period_type' => $editForecasts->first()?->period_type ?? 'year',
                                            'quantity_forecasts' => $editForecasts->map->only(['year_number','calendar_year','month_number','quantity'])->values()->all(),
                                            'line' => $editItem->costing?->line,
                                            'period' => $editItem->costing?->period,
                                            'received_date' => optional($editItem->revision->received_date)->format('Y-m-d'),
                                            'pic_engineering' => $editItem->pic_engineering === '-' ? '' : $editItem->pic_engineering,
                                            'pic_marketing' => $editItem->pic_marketing === '-' ? '' : $editItem->pic_marketing,
                                        ];
                                    @endphp
                                    <button type="button" onclick="openEditProjectModal({{ Illuminate\Support\Js::from($editPayload) }})">{{ $group->items->count() > 1 ? 'Edit '.$editItem->part_number : 'Edit Project' }}</button>
                                @endforeach
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
            Menampilkan {{ $pagedGroups->firstItem() ?? 0 }} - {{ $pagedGroups->lastItem() ?? 0 }} dari {{ $pagedGroups->total() }} project
        </div>
        <div>
            {{ $pagedGroups->onEachSide(1)->links() }}
        </div>
    </div>
</div>

<dialog class="progress-dialog" id="projectProgressDialog"><div id="projectProgressContent"></div></dialog>
<dialog class="new-project-dialog" id="newProjectDialog"><iframe id="newProjectFrame" class="new-project-frame" title="Form New Project"></iframe></dialog>
<dialog class="edit-project-dialog" id="editProjectDialog">
    <form id="editProjectForm" class="edit-project-form" method="POST">
        @csrf
        <div class="edit-project-head"><div><h3>Edit Project</h3><p id="editProjectSubtitle">Perbarui informasi utama project</p></div><button type="button" class="edit-project-close" onclick="closeEditProjectModal()" aria-label="Tutup">&times;</button></div>
        <div class="edit-project-body">
            <section class="edit-project-section"><h4 class="edit-project-section-title">Informasi Project</h4><div class="edit-project-grid">
                <div class="edit-project-field"><label>Business Category <span>*</span></label><select name="business_category_id" required><option value="">Pilih kategori</option>@foreach($businessCategories as $category)<option value="{{ $category->id }}">{{ $category->code }} - {{ $category->name }}</option>@endforeach</select></div>
                <div class="edit-project-field"><label>Customer <span>*</span></label><select name="customer_id" required><option value="">Pilih customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->code }} - {{ $customer->name }}</option>@endforeach</select></div>
                <div class="edit-project-field"><label>Model <span>*</span></label><input name="model" maxlength="255" required></div>
                <div class="edit-project-field"><label>Assy No. <span>*</span></label><input name="part_number" maxlength="255" required></div>
                <div class="edit-project-field span-2"><label>Assy Name <span>*</span></label><input name="part_name" maxlength="255" required></div>
            </div></section>
            <section class="edit-project-section"><div class="edit-project-grid">
                <div class="edit-project-field span-2"><label>Quantity</label><div class="edit-project-quantity"><input type="number" name="forecast" min="0" placeholder="Jumlah"><select name="forecast_uom"><option value="PCE">PCE</option><option value="Set">Set</option></select><select name="forecast_basis"><option value="per_month">Per Bulan</option><option value="per_year">Per Tahun</option></select></div></div>
                <div class="edit-project-field"><label>Product's Life</label><input type="number" name="project_period" min="0" step="any"></div>
                <div class="edit-forecast-option"><input type="hidden" name="quantity_forecast_enabled" value="0" data-edit-forecast-enabled><button type="button" class="edit-forecast-toggle" data-edit-forecast-toggle>+ Quantity berbeda tiap periode</button><div class="edit-forecast-panel" data-edit-forecast-panel hidden><div class="edit-forecast-head"><label>Detail forecast<select name="quantity_forecast_period_type" data-edit-forecast-type><option value="year">Per Tahun</option><option value="month">Per Bulan</option></select></label><label>Tahun Mulai<input type="number" min="2000" max="2200" data-edit-forecast-start placeholder="2026"></label><span class="edit-forecast-note" data-edit-forecast-note></span></div><div data-edit-forecast-rows></div><div class="edit-forecast-summary"><span>Total Product Life</span><span data-edit-forecast-total>0</span></div></div></div>
                <div class="edit-project-field"><label>Plant</label><select name="line"><option value="">Pilih plant</option>@foreach($plants as $plant)<option value="{{ $plant->code }}">{{ $plant->code }} - {{ $plant->name }}</option>@endforeach</select></div>
                <div class="edit-project-field"><label>Periode</label><input type="month" name="period"></div>
                <div class="edit-project-field"><label>Tanggal Diterima</label><input type="date" name="received_date"></div>
                <div class="edit-project-field"><label>PIC Engineering <span>*</span></label><select name="pic_engineering" required><option value="">Pilih PIC Engineering</option>@foreach($picsEngineering as $pic)<option value="{{ $pic->name }}">{{ $pic->name }}</option>@endforeach</select></div>
                <div class="edit-project-field"><label>PIC Marketing <span>*</span></label><select name="pic_marketing" required><option value="">Pilih PIC Marketing</option>@foreach($picsMarketing as $pic)<option value="{{ $pic->name }}">{{ $pic->name }}</option>@endforeach</select></div>
            </div></section>
        </div>
        <div class="edit-project-actions"><button type="button" class="edit-cancel" onclick="closeEditProjectModal()">Batal</button><button type="submit" class="edit-save">Simpan Perubahan</button></div>
    </form>
</dialog>

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

    function openNewProjectModal(){const dialog=document.getElementById('newProjectDialog'),frame=document.getElementById('newProjectFrame');dialog.style.height='min(540px, calc(100vh - 30px))';frame.src=@json(route('tracking-documents.create',['embedded'=>1],false));dialog.showModal()}
    function closeNewProjectModal(reload=false){const dialog=document.getElementById('newProjectDialog'),frame=document.getElementById('newProjectFrame');dialog.close();frame.src='';if(reload)location.reload()}
    function openEditProjectModal(data){
        const dialog=document.getElementById('editProjectDialog'),form=document.getElementById('editProjectForm');
        if(!dialog||!form)return;
        form.action=data.action;
        Object.entries(data).forEach(([name,value])=>{
            const field=form.elements.namedItem(name);
            if(!field||name==='action')return;
            const normalizedValue=value??'';
            if(field instanceof HTMLSelectElement&&normalizedValue!==''&&!Array.from(field.options).some(option=>option.value===String(normalizedValue))){
                field.add(new Option(String(normalizedValue),String(normalizedValue)));
            }
            field.value=normalizedValue;
        });
        document.getElementById('editProjectSubtitle').textContent=(data.part_number||'Project')+' - '+(data.part_name||'');
        configureEditForecast(form,data);
        document.querySelectorAll('.row-actions[open]').forEach(menu=>menu.removeAttribute('open'));
        dialog.showModal();
    }
    function closeEditProjectModal(){document.getElementById('editProjectDialog')?.close()}
    function editForecastRaw(value){const normalized=String(value??'').replace(/\.0+$/,'').replace(/[^0-9]/g,'');return normalized===''?'':String(Number(normalized))}function editForecastDisplay(value){const raw=editForecastRaw(value);return raw===''?'':new Intl.NumberFormat('id-ID').format(Number(raw))}function formatEditForecastQuantity(input){const raw=editForecastRaw(input.value);input.value=editForecastDisplay(raw);const hidden=input.closest('[data-edit-forecast-row]')?.querySelector('[data-qty-raw]');if(hidden)hidden.value=raw}
    function renderEditForecast(form,seed=[]){const years=Math.max(0,Math.min(99,Number(form.elements.project_period.value)||0)),type=form.querySelector('[data-edit-forecast-type]').value,startYear=Number(form.querySelector('[data-edit-forecast-start]').value)||0,container=form.querySelector('[data-edit-forecast-rows]'),previous=new Map(seed.map(row=>[`${row.year_number}-${row.month_number||0}`,editForecastRaw(row.quantity)]));if(!seed.length)container.querySelectorAll('[data-edit-forecast-row]').forEach(row=>previous.set(`${row.querySelector('[data-year]').value}-${row.querySelector('[data-month]')?.value||0}`,row.querySelector('[data-qty-raw]').value));container.innerHTML='';let index=0;for(let year=1;year<=years;year++){for(let month=1;month<=(type==='month'?12:1);month++){const key=`${year}-${type==='month'?month:0}`,calendarYear=startYear?startYear+year-1:0,row=document.createElement('div'),raw=previous.get(key)??'';row.className='edit-forecast-row';row.dataset.editForecastRow='';row.innerHTML=`<span>${type==='month'?`${calendarYear||'-'} · Bulan ${month}`:`${calendarYear||'-'}`}</span><input type="hidden" data-year name="quantity_forecasts[${index}][year_number]" value="${year}"><input type="hidden" data-calendar-year name="quantity_forecasts[${index}][calendar_year]" value="${calendarYear}">${type==='month'?`<input type="hidden" data-month name="quantity_forecasts[${index}][month_number]" value="${month}">`:''}<input type="text" inputmode="numeric" data-qty value="${editForecastDisplay(raw)}" placeholder="Quantity"><input type="hidden" data-qty-raw name="quantity_forecasts[${index}][quantity]" value="${raw}"><span class="edit-forecast-average">${type==='year'?'rata-rata ÷ 12':''}</span>`;container.appendChild(row);index++}}form.querySelector('[data-edit-forecast-note]').textContent=years?`${years} tahun · ${index} periode`:'Isi Product\'s Life terlebih dahulu';updateEditForecastSummary(form)}
    function updateEditForecastSummary(form){const quantities=[...form.querySelectorAll('[data-edit-forecast-row] [data-qty-raw]')].map(input=>Number(input.value)||0),total=quantities.reduce((sum,value)=>sum+value,0),type=form.querySelector('[data-edit-forecast-type]').value,years=Number(form.elements.project_period.value)||0,average=type==='month'?(quantities.length?total/quantities.length:0):(years?total/years:0);form.querySelector('[data-edit-forecast-total]').textContent=`${new Intl.NumberFormat('id-ID').format(total)} ${form.elements.forecast_uom.value}`;form.elements.forecast.value=Math.round(average);form.elements.forecast_basis.value=type==='month'?'per_month':'per_year'}
    function configureEditForecast(form,data){const panel=form.querySelector('[data-edit-forecast-panel]'),toggle=form.querySelector('[data-edit-forecast-toggle]'),enabled=form.querySelector('[data-edit-forecast-enabled]'),type=form.querySelector('[data-edit-forecast-type]');type.value=data.quantity_forecast_period_type||'year';form.querySelector('[data-edit-forecast-start]').value=data.quantity_forecasts?.[0]?.calendar_year||new Date().getFullYear();const setEnabled=value=>{enabled.value=value?'1':'0';panel.hidden=!value;toggle.textContent=value?'Gunakan quantity biasa':'+ Quantity berbeda tiap periode';if(value)renderEditForecast(form,data.quantity_forecasts||[])};toggle.onclick=()=>setEnabled(panel.hidden);type.onchange=()=>renderEditForecast(form);form.querySelector('[data-edit-forecast-start]').oninput=()=>renderEditForecast(form);form.elements.project_period.oninput=()=>{if(!panel.hidden)renderEditForecast(form)};form.querySelector('[data-edit-forecast-rows]').oninput=event=>{if(event.target.matches('[data-qty]'))formatEditForecastQuantity(event.target);updateEditForecastSummary(form)};form.elements.forecast_uom.onchange=()=>updateEditForecastSummary(form);setEnabled(Boolean(data.quantity_forecast_enabled))}
    document.getElementById('newProjectFrame')?.addEventListener('load',function(){try{const dialog=document.getElementById('newProjectDialog'),contentHeight=this.contentDocument?.documentElement?.scrollHeight||540;dialog.style.height=Math.max(430,Math.min(contentHeight,window.innerHeight-30))+'px'}catch(error){}});
    window.addEventListener('message',event=>{if(event.origin!==location.origin)return;if(event.data?.type==='new-project-cancel')closeNewProjectModal(false);if(event.data?.type==='new-project-created')closeNewProjectModal(true)});

    document.getElementById('projectProgressDialog')?.addEventListener('click', function (event) {
        if (event.target === this) this.close();
    });
    document.getElementById('editProjectDialog')?.addEventListener('click',function(event){if(event.target===this)this.close()});

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

    function correctProjectProgressCaptions() {
        document.querySelectorAll('.progress-trigger').forEach(function (button) {
            const steps = Array.from(button.querySelectorAll('.progress-step'));
            const caption = button.querySelector('.progress-caption');
            if (!caption || steps.some(step => step.classList.contains('active'))) return;

            const allDone = steps.length > 0 && steps.every(step => step.classList.contains('done'));
            const allPending = steps.length > 0 && steps.every(step => step.classList.contains('pending'));
            if (allDone) {
                caption.textContent = 'Semua tahapan selesai · klik untuk detail';
            } else if (allPending) {
                caption.textContent = 'Belum ada tahapan dimulai · klik untuk detail';
            } else {
                caption.textContent = 'Ada tahapan dilewati atau belum diselesaikan · klik untuk detail';
            }
        });
    }

    correctProjectProgressCaptions();
</script>
@endsection
