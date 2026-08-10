@extends('layouts.app')
@section('title', 'Inbox New Part Request')
@section('page-title', 'Inbox New Part Request')
@section('breadcrumb')<a href="{{ route('dashboard', absolute:false) }}">Dashboard</a><span class="breadcrumb-separator">/</span><span>Inbox New Part Request</span>@endsection

@section('content')
<style>
.npr-card{padding:16px;border:1px solid #d8e2ef;border-radius:12px;background:#fff}.npr-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:12px}.npr-title h3{margin:0 0 4px;color:#0f172a;font-size:15px}.npr-title p{margin:0;color:#64748b;font-size:11px}.npr-summary{display:inline-flex;align-items:center;gap:7px;margin-top:8px;padding:6px 9px;border-radius:7px;background:#fff4dc;color:#b45309;font-size:10px;font-weight:800}.npr-tabs{display:flex;gap:6px;margin-bottom:12px}.npr-tab{padding:6px 11px;border:1px solid #d6e0ed;border-radius:999px;color:#52647c;text-decoration:none;font-size:10px;font-weight:800}.npr-tab.active{border-color:#2864e8;background:#eaf1ff;color:#1d4ed8}.npr-search{display:flex;gap:7px}.npr-search input{width:290px;height:34px;padding:0 10px;border:1px solid #cbd8ea;border-radius:7px;font-size:11px}.npr-btn{display:inline-flex;min-height:32px;align-items:center;justify-content:center;padding:0 12px;border:0;border-radius:7px;background:#2864e8;color:#fff;text-decoration:none;font-size:10px;font-weight:800;cursor:pointer;white-space:nowrap}.npr-btn.secondary,.npr-btn.import{border:1px solid #b9c9df;background:#fff;color:#17458e}.npr-btn.export{background:#475569}.npr-btn.submit{background:#16a34a}.npr-btn:disabled{cursor:not-allowed;opacity:.45}.npr-wrap{overflow:auto;border:1px solid #d8e2ef;border-radius:9px}.npr-table{width:100%;min-width:1100px;border-collapse:collapse;font-size:10px}.npr-table th{padding:9px 10px;background:#f5f8fc;color:#52647c;text-align:left;text-transform:uppercase;font-size:8px}.npr-table td{padding:11px 10px;border-top:1px solid #e1e8f1;color:#334155;vertical-align:middle}.npr-table strong{color:#0f172a}.npr-table small{display:block;margin-top:3px;color:#718198}.npr-count{display:inline-flex;min-width:28px;justify-content:center;padding:5px 8px;border-radius:999px;background:#fee8e8;color:#b91c1c;font-weight:800}.npr-count.done{background:#e2f8e8;color:#15803d}.npr-ready{display:block;margin-top:4px;color:#15803d;font-size:9px;font-weight:800}.npr-actions{display:flex;align-items:center;gap:6px}.npr-actions form{margin:0}.npr-empty{padding:34px!important;text-align:center;color:#718198!important}.npr-pagination{margin-top:12px}@media(max-width:760px){.npr-head{align-items:stretch;flex-direction:column}.npr-search input{width:100%}}
</style>

<div class="npr-card">
    <div class="npr-head">
        <div class="npr-title"><h3>Project dengan Part Tanpa Harga</h3><p>Download Excel, isi harga pada kolom F–L, lalu upload kembali. Harga otomatis diterapkan ke Form Costing.</p><div class="npr-summary">{{ $projects->total() }} project · {{ $totalOpenParts }} part masih kosong</div></div>
        <form class="npr-search" method="GET"><input type="hidden" name="status" value="{{ $status }}"><input name="search" value="{{ $search }}" placeholder="Cari project, customer, model, atau part..."><button class="npr-btn">Cari</button>@if($search !== '')<a class="npr-btn secondary" href="{{ route('new-part-request.inbox', ['status'=>$status], false) }}">Reset</a>@endif</form>
    </div>

    <div class="npr-tabs">
        @foreach(['active'=>'Aktif','history'=>'History','all'=>'Semua'] as $key=>$label)<a class="npr-tab {{ $status===$key?'active':'' }}" href="{{ route('new-part-request.inbox',['status'=>$key,'search'=>$search],false) }}">{{ $label }}</a>@endforeach
    </div>

    <div class="npr-wrap"><table class="npr-table"><thead><tr><th>Project</th><th>Customer</th><th>Model</th><th>No. Assy</th><th>Rev.</th><th>Progress</th><th>Part Kosong</th><th>Update</th><th>Aksi</th></tr></thead><tbody>
        @forelse($projects as $revision)
            @php
                $project = $revision->project;
            @endphp
            <tr>
                <td><strong>{{ $project?->part_name ?: '-' }}</strong><small>{{ $project?->product?->name ?: '-' }}</small></td>
                <td>{{ $project?->customer ?: $revision->costingData?->customer?->name ?: '-' }}</td>
                <td>{{ $project?->model ?: $revision->costingData?->model ?: '-' }}</td>
                <td><strong>{{ $project?->part_number ?: $revision->costingData?->assy_no ?: '-' }}</strong></td>
                <td>{{ $revision->version_label }}</td>
                <td><x-project-progress :revision="$revision" /></td>
                <td>@if($revision->open_unpriced_count > 0)<span class="npr-count">{{ $revision->open_unpriced_count }}</span>@if($revision->ready_to_submit_count > 0)<span class="npr-ready">{{ $revision->ready_to_submit_count }} siap submit</span>@endif @elseif($revision->completed_npr_count > 0)<span class="npr-count done">Selesai</span><span class="npr-ready">{{ $revision->completed_npr_count }} part diproses</span>@else <span class="npr-count">0</span><span class="npr-ready" style="color:#b45309">Menunggu upload harga</span>@endif</td>
                <td>{{ optional($revision->unpriced_parts_max_updated_at ? \Carbon\Carbon::parse($revision->unpriced_parts_max_updated_at) : $revision->updated_at)->format('d/m/Y') }}<small>{{ optional($revision->unpriced_parts_max_updated_at ? \Carbon\Carbon::parse($revision->unpriced_parts_max_updated_at) : $revision->updated_at)->format('H:i') }}</small></td>
                <td><div class="npr-actions">
                    @if($revision->open_unpriced_count > 0 || ($revision->new_part_request_exported_at && $revision->completed_npr_count < 1))
                    <button type="button" class="npr-btn import" onclick="document.getElementById('nprFile{{ $revision->id }}').click()">Upload Harga</button>
                    <input id="nprFile{{ $revision->id }}" type="file" accept=".xls,.xlsx" hidden onchange="importInboxNewPartRequest(this, {{ $revision->id }})">
                    @php
                        $projectCustomerKey = mb_strtolower(trim((string) $project?->customer));
                        $downloadCustomerCode = strtoupper(trim((string) ($revision->costingData?->customer?->code ?: $customerCodes->get($projectCustomerKey) ?: 'CUSTOMER')));
                        $downloadAssy = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($project?->part_number ?: $revision->costingData?->assy_no ?: 'NEW-PART'));
                        $downloadFilename = now()->format('Y.m.d').' '.$downloadCustomerCode.' - '.$downloadAssy.'.xlsx';
                    @endphp
                    <button type="button" class="npr-btn export" data-url="{{ route('tracking-documents.export-new-part-request', $revision, false) }}" data-filename="{{ $downloadFilename }}" onclick="downloadInboxNewPartRequest(this)">Download Excel</button>
                    @else
                    <a class="npr-btn secondary" href="{{ route('form',['tracking_revision_id'=>$revision->id],false) }}">Lihat Hasil</a>
                    @endif
                </div></td>
            </tr>
        @empty<tr><td colspan="8" class="npr-empty">Tidak ada project dengan harga part yang masih kosong.</td></tr>@endforelse
    </tbody></table></div>
    <div class="npr-pagination">{{ $projects->links() }}</div>
</div>

<script>
async function importInboxNewPartRequest(input, revisionId) {
    const file = input.files?.[0];
    if (!file) return;
    const body = new FormData();
    body.append('new_part_request_file', file);
    showAppLoading('Memeriksa dan menerapkan harga ke Form Costing...');
    try {
        const response = await fetch(`{{ url('/tracking-documents') }}/${revisionId}/import-new-part-request`, {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body});
        const data = await response.json();
        if (!response.ok || data.success === false) throw new Error(data.message || 'Import gagal.');
        hideAppLoading();
        openAppNotify(data.message || 'Harga berhasil diterapkan ke Form Costing.', () => window.location.reload());
    } catch (error) {
        hideAppLoading();
        openAppNotify(error.message || 'File New Part Request tidak dapat diimport.');
    } finally { input.value = ''; }
}

async function downloadInboxNewPartRequest(button) {
    const url = button?.dataset?.url || '';
    const filename = button?.dataset?.filename || 'New-Part-Request.xlsx';
    if (!url) return;

    let handle = null;
    if (typeof window.showSaveFilePicker === 'function') {
        try {
            handle = await window.showSaveFilePicker({
                suggestedName: filename,
                types: [{
                    description: 'Excel Workbook',
                    accept: {'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['.xlsx']},
                }],
            });
        } catch (error) {
            if (error?.name === 'AbortError') return;
        }
    }

    showAppLoading('Menyiapkan file New Part Request...');
    try {
        const response = await fetch(url, {headers: {'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}});
        if (!response.ok) throw new Error('File New Part Request gagal dibuat.');
        const blob = await response.blob();

        if (handle) {
            const writable = await handle.createWritable();
            await writable.write(blob);
            await writable.close();
        } else {
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
        }
        hideAppLoading();
    } catch (error) {
        hideAppLoading();
        openAppNotify(error.message || 'Download Excel gagal.', 'error');
    }
}
</script>
@endsection
