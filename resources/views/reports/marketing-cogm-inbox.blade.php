@extends('layouts.app')

@section('title', 'Marketing COGM Inbox')
@section('page-title', 'Marketing COGM Inbox')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Marketing COGM Inbox</span>
@endsection

@section('content')
<style>
    .marketing-inbox-card {
        background: #ffffff;
        border: 1px solid #dbe5f2;
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.045);
        overflow: hidden;
    }
    .marketing-search-bar{display:flex;align-items:center;justify-content:flex-end;gap:7px;margin-bottom:12px}.marketing-search{display:flex;gap:7px}.marketing-search input{width:290px;height:34px;box-sizing:border-box;padding:0 10px;border:1px solid #cbd8ea;border-radius:7px;font-size:11px}.marketing-search-btn{display:inline-flex;min-height:32px;align-items:center;justify-content:center;padding:0 12px;border:0;border-radius:7px;background:#2864e8;color:#fff;text-decoration:none;font-size:10px;font-weight:800;cursor:pointer;white-space:nowrap}.marketing-search-btn.secondary{border:1px solid #b9c9df;background:#fff;color:#17458e}@media(max-width:700px){.marketing-search-bar,.marketing-search{align-items:stretch;flex-direction:column}.marketing-search input{width:100%}}

    .marketing-inbox-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .marketing-inbox-header h3 {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 900;
        color: #0f172a;
    }

    .marketing-inbox-header span {
        color: #64748b;
        font-size: 0.74rem;
        font-weight: 800;
    }

    .marketing-inbox-table-wrap {
        overflow-x: auto;
    }

    .marketing-inbox-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
    }

    .marketing-inbox-table th,
    .marketing-inbox-table td {
        padding: 0.78rem 0.9rem;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
        font-size: 0.78rem;
    }

    .marketing-inbox-table th {
        background: #ffffff;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .marketing-inbox-table td {
        color: #334155;
        font-weight: 700;
    }

    .marketing-inbox-table tr:last-child td {
        border-bottom: 0;
    }
    .marketing-inbox-table tbody tr[data-href]{cursor:pointer;transition:background .15s}.marketing-inbox-table tbody tr[data-href]:hover{background:#eff6ff}

    .inbox-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.42rem 0.72rem;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.68rem;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .inbox-action:hover {
        border-color: #93c5fd;
        background: #dbeafe;
        color: #1e40af;
    }

    .inbox-actions { display:flex;justify-content:flex-end;gap:.4rem;align-items:center; }
    .inbox-action.download { border-color:#bbf7d0;background:#f0fdf4;color:#15803d; }
    .inbox-action.download:hover { border-color:#86efac;background:#dcfce7;color:#166534; }
    .inbox-file-missing { color:#94a3b8;font-size:.64rem;font-weight:800;white-space:nowrap; }

    .inbox-cogm {
        color: #047857;
        font-weight: 950;
        white-space: nowrap;
    }

    .inbox-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.28rem 0.6rem;
        background: #dcfce7;
        color: #15803d;
        font-size: 0.68rem;
        font-weight: 900;
        white-space: nowrap;
    }
    .inbox-update{display:grid;gap:.16rem;margin-top:.35rem;padding:.35rem .5rem;border:1px solid #fde68a;border-radius:7px;background:#fffbeb;color:#92400e;font-size:.61rem;font-weight:800}.inbox-update small{color:#a16207;font-size:.56rem;font-weight:700}

    .inbox-empty {
        padding: 2rem;
        text-align: center;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 750;
    }
</style>

<div class="marketing-search-bar"><form class="marketing-search" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari customer, model, part, atau PIC..."><button class="marketing-search-btn">Cari</button>@if($search !== '')<a class="marketing-search-btn secondary" href="{{ route('marketing.cogm-inbox',absolute:false) }}">Reset</a>@endif</form></div>
<div class="marketing-inbox-card">
    @if($groupSubmissions->isNotEmpty())
    <div class="marketing-inbox-header"><h3>Bulky COGM per A00</h3><span>{{ $groupSubmissions->count() }} submission group</span></div>
    <div class="marketing-inbox-table-wrap"><table class="marketing-inbox-table"><thead><tr><th>A00</th><th>Item</th><th>PIC Marketing</th><th style="text-align:right">Total Extended COGM</th><th>Versi</th><th>Submitted At</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead><tbody>
    @foreach($groupSubmissions as $version) @php $group=$version->group; @endphp
    <tr><td><strong>{{ $group->a00Form?->document_number }}</strong><br><small>{{ $group->a00Form?->customer }}</small></td><td>{{ $version->items()->count() }} item<br><small>{{ $group->activeItems->pluck('a00Item.assy_number')->filter()->implode(', ') }}</small></td><td>{{ collect([$group->pic_marketing])->merge($group->activeItems->pluck('pic_marketing'))->filter()->unique()->implode(', ') ?: '-' }}</td><td style="text-align:right"><span class="inbox-cogm">{{ $version->total_extended_cogm!==null?'Rp '.number_format((float)$version->total_extended_cogm,0,',','.'):'-' }}</span></td><td>V{{ $version->version_number }}</td><td>{{ $version->submitted_at?->format('d/m/Y H:i') }}</td><td><span class="inbox-pill">Submitted to Marketing</span></td><td style="text-align:right"><a class="inbox-action download" href="{{ route('marketing.bulky-cogm.download',$version,absolute:false) }}">Download COGM</a></td></tr>
    @endforeach
    </tbody></table></div>
    @endif
    <div class="marketing-inbox-header">
        <h3>COGM Per Item / Legacy</h3>
        <span>{{ $submissions->total() }} submission</span>
    </div>

    <div class="marketing-inbox-table-wrap">
        <table class="marketing-inbox-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Customer</th>
                    <th>Model</th>
                    <th>PIC Engineering</th>
                    <th>PIC Marketing</th>
                    <th style="text-align:right;">COGM</th>
                    <th>Submitted By</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                    @php
                        $revision = $submission->revision;
                        $project = $revision?->project;
                        $latestCostingRevision = $revision?->latestCostingRevision;
                        $costingUpdateLabel = match($latestCostingRevision?->revision_type) {
                            'price' => 'Update Harga', 'partlist' => 'Update Partlist',
                            'umh' => 'Update UMH', default => 'COGM diperbarui',
                        };
                    @endphp
                    <tr data-href="{{ route('marketing.cogm-costing.show',$submission,absolute:false) }}" tabindex="0" aria-label="Lihat Form Costing {{ $project?->part_number }}">
                        <td>
                            <strong>{{ $project?->part_number ?? '-' }}</strong><br>
                            <span style="color:#64748b;">{{ $project?->part_name ?? '-' }}</span>
                        </td>
                        <td>{{ $project?->customer ?? '-' }}</td>
                        <td>{{ $project?->model ?? '-' }}</td>
                        <td>{{ $revision?->pic_engineering ?? '-' }}</td>
                        <td>{{ $submission->pic_marketing ?? '-' }}</td>
                        <td style="text-align:right;"><span class="inbox-cogm">Rp {{ number_format((float) $submission->cogm_value, 0, ',', '.') }}</span></td>
                        <td>{{ $submission->submitted_by ?? '-' }}</td>
                        <td>{{ $submission->submitted_at ? $submission->submitted_at->format('d/m/Y H:i') : '-' }}</td>
                        <td><span class="inbox-pill">Submitted to Marketing</span>@if($submission->last_updated_at)<div class="inbox-update">{{ $costingUpdateLabel }} ({{ $submission->update_count }}x)<small>{{ $submission->last_updated_by }} · {{ $submission->last_updated_at->format('d/m/Y H:i') }}</small>@if($latestCostingRevision?->description)<small>{{ $latestCostingRevision->description }}</small>@endif</div>@endif</td>
                        <td style="text-align:right;">
                            <div class="inbox-actions">
                                @if($revision?->costing_edit_file_path)
                                    <a class="inbox-action download" data-no-row-open href="{{ route('marketing.costing-edit.download', $revision, absolute: false) }}" title="{{ $revision->costing_edit_original_name }}">Download File Import</a>
                                @elseif($revision?->cogm_import_file_path)
                                    <a class="inbox-action download" data-no-row-open href="{{ route('marketing.cogm-import.download', $submission, absolute: false) }}" title="{{ $revision->cogm_import_original_name }}">Download File Import</a>
                                @else
                                    <span class="inbox-file-missing">File tidak tersedia</span>
                                @endif
                                <a class="inbox-action" data-no-row-open href="{{ route('marketing.cogm-costing.show', $submission, absolute: false) }}">Lihat Form Costing</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10"><div class="inbox-empty">Belum ada COGM approved yang dikirim ke Marketing.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 1rem;">
    {{ $submissions->onEachSide(1)->links() }}
</div>
<script>
document.querySelectorAll('.marketing-inbox-table tbody tr[data-href]').forEach(function(row){
    const open=function(){ window.location.href=row.dataset.href; };
    row.addEventListener('click',function(event){if(!event.target.closest('[data-no-row-open]'))open();});
    row.addEventListener('keydown',function(event){if(event.key==='Enter'||event.key===' '){event.preventDefault();open();}});
});
</script>
@endsection
