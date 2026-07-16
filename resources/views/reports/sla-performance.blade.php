@extends('layouts.app')

@section('title', 'SLA Performance')
@section('page-title', 'SLA Performance')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a><span class="breadcrumb-separator">/</span><span>SLA Performance</span>
@endsection

@section('content')
<div class="sla-page">
    <section class="sla-hero">
        <div><span>OPERATIONAL CONTROL</span><h2>SLA Performance Dashboard</h2><p>Pantau kepatuhan deadline, aging pekerjaan aktif, dan area yang perlu segera ditindaklanjuti.</p></div>
        <a href="{{ route('help-center', absolute: false) }}#sla-performance-help">Pelajari cara membaca dashboard</a>
    </section>

    <section class="sla-kpis">
        <article><span>Pekerjaan Aktif</span><strong>{{ $kpis['active'] }}</strong><small>Seluruh tahap yang belum selesai</small></article>
        <article class="good"><span>Sesuai SLA</span><strong>{{ $kpis['compliance'] }}%</strong><small>{{ $kpis['on_time'] }} pekerjaan belum terlambat</small></article>
        <article class="danger"><span>Overdue</span><strong>{{ $kpis['overdue'] }}</strong><small>Perlu tindakan segera</small></article>
        <article><span>Rata-rata Aging</span><strong>{{ $kpis['average_aging'] }} hari</strong><small>Sejak pembaruan terakhir</small></article>
    </section>

    <section class="sla-panel">
        <div class="sla-section-head"><div><h3>Performa per Tahap</h3><p>Snapshot pekerjaan aktif berdasarkan tahap workflow saat ini.</p></div></div>
        <div class="sla-stage-grid">
            @foreach($stageSummary as $stage)
                <article class="{{ $stage->overdue > 0 ? 'has-risk' : '' }}">
                    <header><b>{{ $stage->label }}</b><span>{{ $stage->total }} aktif</span></header>
                    <strong>{{ $stage->compliance }}%</strong><small>Kepatuhan SLA</small>
                    <div class="sla-bar"><i style="width:{{ $stage->compliance }}%"></i></div>
                    <footer><span>{{ $stage->overdue }} overdue</span><span>Aging {{ $stage->average_aging }} hari</span></footer>
                </article>
            @endforeach
        </div>
    </section>

    <section class="sla-panel sla-history">
        <div class="sla-section-head"><div><h3>Historical SLA Trend</h3><p>Snapshot kepatuhan 30 hari terakhir agar perubahan performa dapat dipantau.</p></div></div>
        <div class="sla-history-chart">
            @forelse($history as $point)<div title="{{ \Carbon\Carbon::parse($point->date)->format('d M Y') }} · {{ $point->overdue }} overdue"><span>{{ $point->compliance }}%</span><i style="height:{{ max(5,$point->compliance) }}%"></i><small>{{ \Carbon\Carbon::parse($point->date)->format('d/m') }}</small></div>@empty<div class="sla-empty">Snapshot pertama akan dibuat saat dashboard ini dibuka.</div>@endforelse
        </div>
    </section>

    <section class="sla-two-column">
        <div class="sla-panel">
            <div class="sla-section-head"><div><h3>Daftar Pekerjaan</h3><p>Urutan pertama menampilkan pekerjaan paling mendesak.</p></div></div>
            <form class="sla-filters" method="GET">
                <select name="stage">
                    <option value="all">Semua Tahap</option>
                    @foreach(['documents'=>'Dokumen','pricing'=>'Harga Part','costing'=>'Costing','approval'=>'Approval','marketing'=>'Marketing'] as $key=>$label)
                        <option value="{{ $key }}" @selected($stageFilter===$key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status">
                    <option value="all">Semua Status</option>
                    <option value="overdue" @selected($statusFilter==='overdue')>Overdue</option>
                    <option value="on_time" @selected($statusFilter==='on_time')>Sesuai SLA</option>
                </select>
                <button>Terapkan</button>
            </form>
            <div class="sla-task-list">
                @forelse($filteredRows as $item)
                    <article>
                        <div class="sla-task-main"><span>{{ $item->stage_label }} · {{ $item->revision->version_label }}</span><a href="{{ route('project-collaboration.show', $item->revision, false) }}">{{ $item->part_number }} — {{ $item->project_name }}</a><small>{{ $item->customer }} · {{ $item->model }} · PIC {{ $item->pic }}</small></div>
                        <div class="sla-task-progress"><b>{{ $item->progress }}%</b><div><i style="width:{{ $item->progress }}%"></i></div></div>
                        <div class="sla-task-deadline {{ $item->is_overdue ? 'overdue' : '' }}"><b>{{ $item->is_overdue ? abs($item->days_remaining).' hari terlambat' : $item->days_remaining.' hari tersisa' }}</b><small>{{ $item->due_at->format('d M Y') }}</small></div>
                    </article>
                @empty
                    <div class="sla-empty">Tidak ada pekerjaan untuk filter ini.</div>
                @endforelse
            </div>
        </div>

        <aside class="sla-panel">
            <div class="sla-section-head"><div><h3>Performa PIC</h3><p>Fokuskan koordinasi pada PIC dengan overdue tertinggi.</p></div></div>
            <div class="sla-pic-list">
                @forelse($picSummary as $item)
                    <article><div><b>{{ $item->pic }}</b><small>{{ $item->total }} pekerjaan · aging {{ $item->average_aging }} hari</small></div><div class="{{ $item->overdue ? 'risk' : '' }}"><strong>{{ $item->compliance }}%</strong><small>{{ $item->overdue }} overdue</small></div></article>
                @empty
                    <div class="sla-empty">Belum ada pekerjaan aktif.</div>
                @endforelse
            </div>
        </aside>
    </section>
</div>

<style>
.sla-page{max-width:1500px;margin:0 auto;color:#183550}.sla-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:24px 27px;border-radius:16px;background:linear-gradient(120deg,#073b7d,#0874e9);color:#fff}.sla-hero span{color:#bfdbfe;font-size:10px;font-weight:900;letter-spacing:.12em}.sla-hero h2{margin:5px 0;font-size:25px}.sla-hero p{margin:0;color:#dbeafe;font-size:12px}.sla-hero a{padding:10px 13px;border:1px solid #7eb8f6;border-radius:9px;color:#fff;font-size:11px;font-weight:800;text-decoration:none}.sla-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:15px 0}.sla-kpis article{padding:18px;border:1px solid #d9e5ef;border-radius:13px;background:#fff}.sla-kpis span,.sla-kpis small,.sla-kpis strong{display:block}.sla-kpis span{color:#60758a;font-size:10px;font-weight:800}.sla-kpis strong{margin:8px 0 4px;font-size:24px}.sla-kpis small{color:#7a8ca0;font-size:9px}.sla-kpis .good strong{color:#07855b}.sla-kpis .danger strong{color:#dc3b35}.sla-panel{padding:18px;border:1px solid #d9e5ef;border-radius:14px;background:#fff}.sla-section-head h3{margin:0;font-size:14px}.sla-section-head p{margin:4px 0 0;color:#708297;font-size:10px}.sla-stage-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:14px}.sla-stage-grid article{padding:14px;border:1px solid #dce7f0;border-radius:11px;background:#f8fbfe}.sla-stage-grid article.has-risk{border-color:#f4cbc6;background:#fffafa}.sla-stage-grid header,.sla-stage-grid footer{display:flex;justify-content:space-between;gap:7px}.sla-stage-grid header b{font-size:11px}.sla-stage-grid header span,.sla-stage-grid small,.sla-stage-grid footer{color:#72859a;font-size:9px}.sla-stage-grid>article>strong{display:block;margin-top:13px;font-size:20px}.sla-bar{height:6px;margin:8px 0;border-radius:99px;background:#e1eaf2;overflow:hidden}.sla-bar i{display:block;height:100%;border-radius:inherit;background:#0783e9}.has-risk .sla-bar i{background:#ef6a5b}.sla-two-column{display:grid;grid-template-columns:minmax(0,2.1fr) minmax(280px,.9fr);gap:12px;margin-top:12px}.sla-filters{display:flex;justify-content:flex-end;gap:7px;margin:-31px 0 14px}.sla-filters select,.sla-filters button{min-height:34px;padding:0 10px;border:1px solid #ccdbe8;border-radius:8px;background:#fff;color:#31516f;font-size:10px}.sla-filters button{border-color:#0871df;background:#0871df;color:#fff;font-weight:800}.sla-task-list{border-top:1px solid #e4ebf2}.sla-task-list>article{display:grid;grid-template-columns:minmax(0,1fr) 105px 115px;align-items:center;gap:15px;padding:13px 2px;border-bottom:1px solid #edf1f5}.sla-task-main span,.sla-task-main small,.sla-task-main a{display:block}.sla-task-main span{color:#0871df;font-size:9px;font-weight:900;text-transform:uppercase}.sla-task-main a{margin:3px 0;color:#173b60;font-size:11px;font-weight:900;text-decoration:none}.sla-task-main small{color:#76889b;font-size:9px}.sla-task-progress b{font-size:10px}.sla-task-progress div{height:5px;margin-top:5px;border-radius:99px;background:#e4ebf2}.sla-task-progress i{display:block;height:100%;border-radius:inherit;background:#1383e8}.sla-task-deadline{text-align:right}.sla-task-deadline b,.sla-task-deadline small{display:block}.sla-task-deadline b{color:#167a55;font-size:10px}.sla-task-deadline.overdue b{color:#d63a32}.sla-task-deadline small{margin-top:3px;color:#718499;font-size:9px}.sla-pic-list{margin-top:10px}.sla-pic-list article{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 2px;border-bottom:1px solid #e8eef4}.sla-pic-list b,.sla-pic-list small,.sla-pic-list strong{display:block}.sla-pic-list b{font-size:11px}.sla-pic-list small{margin-top:3px;color:#74879a;font-size:9px}.sla-pic-list article>div:last-child{text-align:right}.sla-pic-list strong{color:#13805d;font-size:13px}.sla-pic-list .risk strong{color:#d53b34}.sla-empty{padding:28px;text-align:center;color:#75879a;font-size:11px}@media(max-width:1050px){.sla-kpis{grid-template-columns:repeat(2,1fr)}.sla-stage-grid{grid-template-columns:repeat(3,1fr)}.sla-two-column{grid-template-columns:1fr}}@media(max-width:650px){.sla-hero{align-items:flex-start;flex-direction:column}.sla-kpis,.sla-stage-grid{grid-template-columns:1fr}.sla-filters{margin:12px 0;justify-content:flex-start;flex-wrap:wrap}.sla-task-list>article{grid-template-columns:1fr}.sla-task-deadline{text-align:left}}
.sla-history{margin-top:12px}.sla-history-chart{display:flex;align-items:flex-end;gap:7px;height:150px;margin-top:16px;padding:20px 4px 0;border-bottom:1px solid #dce6ef;overflow-x:auto}.sla-history-chart>div{display:grid;grid-template-rows:18px 1fr 18px;align-items:end;min-width:34px;height:100%;text-align:center}.sla-history-chart span,.sla-history-chart small{color:#718599;font-size:7px}.sla-history-chart i{display:block;min-height:5px;border-radius:5px 5px 0 0;background:linear-gradient(#19b8d8,#0878df);box-shadow:0 0 14px #0a8adb25}
</style>
@endsection
