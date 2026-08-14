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
        border: 1px solid #d8e2ef;
        border-radius: 9px;
    }

    .marketing-inbox-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
    }

    .marketing-inbox-table th,
    .marketing-inbox-table td {
        padding: 9px 10px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: middle;
        font-size: 10px;
    }

    .marketing-inbox-table th {
        background: #f5f8fc;
        color: #52647c;
        font-size: 8px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0;
        white-space: nowrap;
    }

    .marketing-inbox-table th:last-child {
        text-align: left !important;
    }

    .marketing-inbox-table td {
        color: #334155;
        font-weight: 700;
    }

    .marketing-inbox-table th:nth-child(1),
    .marketing-inbox-table td:nth-child(1) {
        min-width: 115px;
        white-space: nowrap;
    }

    .marketing-inbox-table th:nth-child(2),
    .marketing-inbox-table td:nth-child(2) {
        min-width: 155px;
        white-space: nowrap;
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
        font-size: 9px;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .inbox-action:hover {
        border-color: #93c5fd;
        background: #dbeafe;
        color: #1e40af;
    }

    .marketing-inbox-table tbody tr[data-href] > td:last-child{min-width:140px;vertical-align:middle}.inbox-actions{display:flex;width:140px;margin-left:auto;align-items:stretch;flex-direction:column;gap:4px}.inbox-actions .inbox-action{box-sizing:border-box;width:100%}
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
        padding: 5px 8px;
        background: #dcfce7;
        color: #15803d;
        font-size: 9px;
        font-weight: 900;
        white-space: nowrap;
    }
    .inbox-status-summary{display:flex;max-width:285px;align-items:flex-start;flex-direction:column;gap:4px}.inbox-update{display:inline-flex;align-items:center;gap:4px;margin:0;padding:4px 6px;border:1px solid #fde68a;border-radius:6px;background:#fffbeb;color:#92400e;font-size:9px;font-weight:800;white-space:nowrap}.inbox-update small{color:#a16207;font-size:8px;font-weight:700}.inbox-update small+small{width:100%;white-space:normal}

    .inbox-empty {
        padding: 2rem;
        text-align: center;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 750;
    }
    .marketing-status{display:inline-flex;margin:0;padding:.25rem .5rem;border-radius:999px;font-size:.62rem;font-weight:900;white-space:nowrap}.marketing-status.waiting{background:#fef3c7;color:#92400e}.marketing-status.cancel{background:#fee2e2;color:#b91c1c}.marketing-status.die_go{background:#dcfce7;color:#15803d}.marketing-status.unset{background:#e2e8f0;color:#475569}.marketing-detail-row{display:none}.marketing-detail-row.open{display:table-row}.marketing-detail{display:grid;grid-template-columns:minmax(230px,.8fr) minmax(260px,1fr) minmax(300px,1.3fr);gap:12px;padding:12px;background:#f8fafc}.marketing-panel{padding:12px;border:1px solid #dbe5f2;border-radius:9px;background:#fff}.marketing-panel h4{margin:0 0 9px;color:#173b75;font-size:.72rem}.marketing-panel form{display:grid;gap:7px}.marketing-panel select,.marketing-panel textarea{box-sizing:border-box;width:100%;padding:8px;border:1px solid #cbd8ea;border-radius:7px;font:inherit;font-size:.68rem}.marketing-panel textarea{min-height:66px;resize:vertical}.marketing-panel button{justify-self:end;border:0;border-radius:7px;background:#2864e8;color:#fff;padding:7px 10px;font-size:.64rem;font-weight:850;cursor:pointer}.marketing-timeline{display:grid;gap:7px;max-height:240px;overflow:auto}.marketing-event{padding:8px;border-left:3px solid #60a5fa;border-radius:5px;background:#f8fafc;color:#475569;font-size:.63rem}.marketing-event strong,.marketing-event small{display:block}.marketing-event small{margin-top:2px;color:#94a3b8}.marketing-warning{margin:0;padding:.28rem .45rem;border-radius:6px;background:#fff7ed;color:#c2410c;font-size:.6rem;font-weight:800;white-space:nowrap}@media(max-width:900px){.marketing-detail{grid-template-columns:1fr}}
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
        <h3>COGM Per Item</h3>
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
                    <th>Progress</th>
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
                        $openUnpricedCount = $revision?->pricing_status === 'full_price'
                            ? 0
                            : ($revision?->pricing_status === 'incomplete'
                                ? (int) ($revision?->manual_missing_price_count ?? 0)
                                : ($revision?->unpricedParts?->count() ?? 0));
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
                        <td><x-project-progress :revision="$revision" /></td>
                        <td style="text-align:right;"><span class="inbox-cogm">Rp {{ number_format((float) $submission->cogm_value, 0, ',', '.') }}</span></td>
                        <td>{{ $submission->submitted_by ?? '-' }}</td>
                        <td>{{ $submission->submitted_at ? $submission->submitted_at->format('d/m/Y H:i') : '-' }}</td>
                        <td><div class="inbox-status-summary"><span class="inbox-pill">Submitted to Marketing</span>@if($openUnpricedCount > 0)<span class="marketing-warning">Belum Full Price · {{ $openUnpricedCount }} part belum memiliki harga</span>@else<span class="marketing-status die_go">Full Price · Harga lengkap</span>@endif @if($submission->marketing_status)<span class="marketing-status {{ $submission->marketing_status }}">{{ ['waiting'=>'Waiting','cancel'=>'Cancel','die_go'=>'Die Go (Berhasil)'][$submission->marketing_status] }}</span>@endif @if($submission->last_updated_at)<span class="inbox-update">{{ $costingUpdateLabel }} {{ $submission->update_count }}x</span>@endif</div></td>
                        <td style="text-align:right;">
                            <div class="inbox-actions">
                                @if($revision?->cogm_import_file_path)
                                    <a class="inbox-action download" data-no-row-open href="{{ route('marketing.cogm-import.download', $submission, absolute: false) }}" title="{{ $revision->cogm_import_original_name }}">Download COGM Manual</a>
                                @elseif($revision?->costing_edit_file_path)
                                    <a class="inbox-action download" data-no-row-open href="{{ route('marketing.costing-edit.download', $revision, absolute: false) }}" title="{{ $revision->costing_edit_original_name }}">Download File Import</a>
                                @elseif($latestCostingRevision?->file_path)
                                    <a class="inbox-action download" data-no-row-open href="{{ route('marketing.cogm-update.download', $submission, absolute: false) }}" title="{{ $latestCostingRevision->original_name }}">Download {{ $costingUpdateLabel }}</a>
                                @else
                                    <span class="inbox-file-missing">File tidak tersedia</span>
                                @endif
                                <a class="inbox-action" data-no-row-open href="{{ route('marketing.cogm-costing.show', $submission, absolute: false) }}">Lihat Form Costing</a>
                                <button class="inbox-action marketing-detail-toggle" type="button" data-no-row-open data-target="marketing-detail-{{ $submission->id }}">Detail</button>
                            </div>
                        </td>
                    </tr>
                    @php($waitingOverdue=$submission->marketing_status==='waiting'&&$submission->waiting_since?->lte(now()->subMonth()))
                    <tr class="marketing-detail-row" id="marketing-detail-{{ $submission->id }}"><td colspan="11"><div class="marketing-detail">
                        <section class="marketing-panel"><h4>Status Kelanjutan Project</h4>@if(in_array(auth()->user()->role,['admin','marketing'],true))<form method="POST" action="{{ route('marketing.cogm-status.update',$submission,absolute:false) }}">@csrf<select name="marketing_status" required><option value="">Pilih status wajib</option><option value="waiting" @selected($submission->marketing_status==='waiting')>Waiting</option><option value="cancel" @selected($submission->marketing_status==='cancel')>Cancel</option><option value="die_go" @selected($submission->marketing_status==='die_go')>Die Go (Berhasil)</option></select><textarea name="reason" placeholder="Alasan wajib untuk Cancel atau Waiting lebih dari 1 bulan">{{ $submission->marketing_status_reason }}</textarea><button>Simpan Status</button></form>@else<div>{{ $submission->marketing_status_reason ?: 'Belum ada alasan/status.' }}</div>@endif @if($waitingOverdue)<div class="marketing-warning">Waiting lebih dari 1 bulan. Marketing wajib memberikan alasan atau perkembangan terbaru.</div>@endif</section>
                        <section class="marketing-panel"><h4>Komentar Marketing ke Costing</h4>@if(in_array(auth()->user()->role,['admin','marketing'],true))<form method="POST" action="{{ route('marketing.cogm-comments.store',$submission,absolute:false) }}">@csrf<textarea name="comment" required maxlength="2000" placeholder="Tulis pertanyaan atau catatan untuk Team Costing..."></textarea><button>Kirim Komentar</button></form>@endif<div class="marketing-timeline" style="margin-top:9px">@forelse($submission->comments as $comment)<div class="marketing-event"><strong>{{ $comment->user?->name ?? 'User' }}</strong>{{ $comment->comment }}<small>{{ $comment->created_at->format('d/m/Y H:i') }}</small></div>@empty<div class="marketing-event">Belum ada komentar.</div>@endforelse</div></section>
                        <section class="marketing-panel"><h4>Riwayat COGM & Project</h4><div class="marketing-timeline">@forelse($submission->events as $event)<div class="marketing-event"><strong>{{ $event->title }}</strong>@if($event->description)<div>{{ $event->description }}</div>@endif @if($event->cogm_value!==null)<div>COGM: Rp {{ number_format((float)$event->cogm_value,0,',','.') }}</div>@endif<small>{{ $event->user?->name ?? ucfirst(str_replace('_',' ',$event->source ?: 'sistem')) }} · {{ $event->created_at->format('d/m/Y H:i') }}</small></div>@empty<div class="marketing-event">Belum ada riwayat update baru.</div>@endforelse</div></section>
                    </div></td></tr>
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
document.querySelectorAll('.marketing-detail-toggle').forEach(button=>button.addEventListener('click',()=>document.getElementById(button.dataset.target)?.classList.toggle('open')));
</script>
@endsection
