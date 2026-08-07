@extends('layouts.app')
@section('title', 'Inbox Costing')
@section('page-title', 'Inbox Costing')
@section('breadcrumb')<a href="{{ route('dashboard', absolute:false) }}">Dashboard</a><span class="breadcrumb-separator">/</span><span>Inbox Costing</span>@endsection

@section('content')
<style>
.ci-card{background:#fff;border:1px solid #d8e2ef;border-radius:12px;padding:16px}.ci-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:13px}.ci-head h3{margin:0 0 4px;color:#0f172a;font-size:15px}.ci-head p{margin:0;color:#64748b;font-size:11px}.ci-search{display:flex;gap:7px}.ci-search input,.ci-search select{height:34px;border:1px solid #cbd8ea;border-radius:7px;padding:0 10px;font-size:11px;background:#fff}.ci-search input{width:260px}.ci-btn{display:inline-flex;align-items:center;justify-content:center;min-height:31px;border:0;border-radius:7px;padding:0 12px;background:#2864e8;color:#fff;font-size:10px;font-weight:800;text-decoration:none;cursor:pointer}.ci-btn.secondary{border:1px solid #b9c9df;background:#fff;color:#17458e}.ci-btn.success{background:#16a34a}.ci-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}.ci-tab{padding:6px 10px;border:1px solid #d6e0ed;border-radius:999px;color:#52647c;text-decoration:none;font-size:10px;font-weight:700}.ci-tab.active{border-color:#2864e8;background:#eaf1ff;color:#1d4ed8}.ci-wrap{overflow:auto;border:1px solid #d8e2ef;border-radius:9px}.ci-table{width:100%;min-width:1100px;border-collapse:collapse;font-size:10px}.ci-table th{padding:9px 10px;background:#f5f8fc;color:#52647c;text-align:left;text-transform:uppercase;font-size:8px}.ci-table td{padding:10px;border-top:1px solid #e1e8f1;color:#334155;vertical-align:middle}.ci-table strong{color:#0f172a}.ci-table small{display:block;margin-top:3px;color:#718198}.ci-status{display:inline-flex;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:800}.ci-status.info{background:#eaf1ff;color:#1d4ed8}.ci-status.warning{background:#fff4dc;color:#b45309}.ci-status.danger{background:#fee8e8;color:#b91c1c}.ci-status.success{background:#e2f8e8;color:#15803d}.ci-actions{display:flex;gap:5px;flex-wrap:wrap}.ci-empty{text-align:center;padding:30px!important;color:#718198!important}.ci-pagination{margin-top:12px}.ci-note{margin-top:4px;color:#b45309;font-size:9px;font-weight:700}@media(max-width:800px){.ci-head{display:block}.ci-search{margin-top:10px}.ci-search input{width:100%}}
.ci-btn.revision{background:#7c3aed}.ci-flash{margin-bottom:12px;padding:9px 12px;border:1px solid #86efac;border-radius:8px;background:#f0fdf4;color:#166534;font-size:11px;font-weight:700}.ci-revision-modal{width:min(560px,calc(100vw - 28px));padding:0;border:0;border-radius:14px;overflow:hidden}.ci-revision-modal::backdrop{background:rgba(15,23,42,.55)}.ci-revision-head{display:flex;justify-content:space-between;padding:14px 16px;background:linear-gradient(135deg,#0b3478,#7c3aed);color:#fff}.ci-revision-head button{border:0;background:transparent;color:#fff;font-size:20px;cursor:pointer}.ci-revision-body{display:grid;gap:12px;padding:16px}.ci-revision-field label{display:block;margin-bottom:5px;color:#475569;font-size:11px;font-weight:800}.ci-revision-field select,.ci-revision-field input,.ci-revision-field textarea{box-sizing:border-box;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px;font-size:11px}.ci-revision-field textarea{min-height:75px}.ci-revision-foot{display:flex;justify-content:flex-end;gap:7px;padding:12px 16px;border-top:1px solid #e2e8f0;background:#f8fafc}
</style>

@if(session('success'))<div class="ci-flash">{{ session('success') }}</div>@endif
<div class="ci-card">
    <div class="ci-head">
        <div><h3>Daftar Progress Costing</h3><p>Costing tetap berstatus sedang proses sampai dikirim untuk approval.</p></div>
        <form class="ci-search" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari customer, model, assy no..."><input type="hidden" name="status" value="{{ $status }}"><button class="ci-btn">Cari</button></form>
    </div>

    <div class="ci-tabs">
        @foreach(['active'=>'Aktif','history'=>'History','all'=>'Semua'] as $key=>$label)
            <a class="ci-tab {{ $status===$key?'active':'' }}" href="{{ route('costing.inbox', ['status'=>$key,'search'=>$search], false) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="ci-wrap"><table class="ci-table"><thead><tr><th>Project</th><th>Customer</th><th>Model</th><th>No. Assy</th><th>Rev.</th><th>Status</th><th>COGM</th><th>Update</th><th>Aksi</th></tr></thead><tbody>
        @foreach($pendingCostingTasks as $task)
            @php($revision=$task->revision)
            @php($project=$task->project)
            <tr>
                <td><strong>{{ $project?->part_name ?: '-' }}</strong><small>{{ $project?->product?->name ?: '-' }}</small></td>
                <td>{{ $project?->customer ?: '-' }}</td>
                <td>{{ $project?->model ?: '-' }}</td>
                <td><strong>{{ $project?->part_number ?: '-' }}</strong></td>
                <td>{{ $revision?->version_label ?: '-' }}</td>
                <td><span class="ci-status info">Menunggu Form Costing</span><small>Dokumen Breakdown telah disimpan.</small>@if($revision?->latestCostingRevision?->revision_type==='price')<div class="ci-note" style="color:#7c3aed">Update Harga · {{ $revision->latestCostingRevision->created_at?->format('d/m/Y H:i') }}</div>@endif</td>
                <td><strong>-</strong></td>
                <td>{{ optional($task->updated_at)->format('d/m/Y') }}<small>{{ optional($task->updated_at)->format('H:i') }}</small></td>
                <td><div class="ci-actions"><a class="ci-btn secondary" href="{{ url('/form') }}?tracking_revision_id={{ $revision?->id }}">Form Costing</a><button class="ci-btn revision" type="button" data-revision="{{ $revision?->id }}" data-project="{{ $project?->part_number }}" onclick="openCostingRevision(this.dataset)">Rev</button></div></td>
            </tr>
        @endforeach
        @forelse($items as $item)
            @php($revision=$item->trackingRevision)
            <tr>
                <td><strong>{{ $item->assy_name ?: $revision?->project?->part_name ?: '-' }}</strong><small>{{ $item->product?->name ?: $revision?->project?->product?->name ?: '-' }}</small></td>
                <td>{{ $item->customer?->name ?: $revision?->project?->customer ?: '-' }}</td>
                <td>{{ $item->model ?: $revision?->project?->model ?: '-' }}</td>
                <td><strong>{{ $item->assy_no ?: $revision?->project?->part_number ?: '-' }}</strong></td>
                <td>{{ $revision?->version_label ?: '-' }}</td>
                <td><span class="ci-status {{ $item->workflow_class }}">{{ $item->workflow_label }}</span>@if($item->open_unpriced_count)<div class="ci-note">{{ $item->open_unpriced_count }} part belum memiliki harga</div>@endif @if($revision?->latestCostingRevision?->revision_type==='price')<div class="ci-note" style="color:#7c3aed">Update Harga · {{ $revision->latestCostingRevision->created_at?->format('d/m/Y H:i') }}</div>@endif @if($revision?->status===\App\Models\DocumentRevision::STATUS_REJECTED_BY_COORDINATOR && $item->approval?->rejection_notes)<small>Catatan: {{ $item->approval->rejection_notes }}</small>@endif</td>
                <td><strong>Rp {{ number_format($item->cogm_value,0,',','.') }}</strong></td>
                <td>{{ optional($item->updated_at)->format('d/m/Y') }}<small>{{ optional($item->updated_at)->format('H:i') }}</small></td>
                <td><div class="ci-actions">
                    <a class="ci-btn secondary" href="{{ url('/form') }}?tracking_revision_id={{ $revision?->id }}{{ $revision?->status === \App\Models\DocumentRevision::STATUS_SUBMITTED_TO_MARKETING ? '&edit_submitted=1' : '' }}">{{ $revision?->status === \App\Models\DocumentRevision::STATUS_SUBMITTED_TO_MARKETING ? 'Edit Form Costing' : 'Form Costing' }}</a>
                    <button class="ci-btn revision" type="button" data-revision="{{ $revision?->id }}" data-project="{{ $item->assy_no ?: $revision?->project?->part_number }}" onclick="openCostingRevision(this.dataset)">Rev</button>
                    @if($item->can_submit_approval)<form method="POST" action="{{ route('costing-approvals.submit',$revision,absolute:false) }}" class="js-confirm-form" data-confirm-title="Submit Costing" data-confirm-message="Costing {{ $item->assy_no }} akan dikirim ke Coordinator Costing untuk diperiksa dan dikunci sementara selama proses approval." data-confirm-button="Ya, Submit Costing" data-confirm-tone="primary">@csrf<button class="ci-btn">Submit Approval</button></form>@endif
                    @if($item->can_approve)<form method="POST" action="{{ route('costing-approvals.approve',$revision,absolute:false) }}" class="js-confirm-form" data-confirm-title="Approve COGM" data-confirm-message="COGM {{ $item->assy_no }} akan disetujui dan dapat dilanjutkan untuk dikirim ke Team Marketing." data-confirm-button="Ya, Approve COGM" data-confirm-tone="primary">@csrf<button class="ci-btn success">Approve</button></form>@endif
                    @if($item->can_send)<form method="POST" action="{{ route('costing-approvals.send-marketing',$revision,absolute:false) }}" class="js-confirm-form" data-confirm-title="Kirim COGM" data-confirm-message="COGM {{ $item->assy_no }} akan dikirim ke Team Marketing dan progress project akan ditandai selesai." data-confirm-button="Ya, Kirim Marketing" data-confirm-tone="primary">@csrf<input type="hidden" name="pic_marketing" value="{{ $revision?->pic_marketing }}"><button class="ci-btn success">Kirim Marketing</button></form>@endif
                </div></td>
            </tr>
        @empty
            @if($pendingCostingTasks->isEmpty())<tr><td colspan="9" class="ci-empty">Tidak ada progress costing pada filter ini.</td></tr>@endif
        @endforelse
    </tbody></table></div>
    <div class="ci-pagination">{{ $items->links() }}</div>
</div>
<dialog class="ci-revision-modal" id="costingRevisionModal"><form id="costingRevisionForm" method="POST" enctype="multipart/form-data">@csrf<div class="ci-revision-head"><div><strong>Upload Revisi Costing</strong><small id="costingRevisionProject" style="display:block;color:#ddd6fe">Project</small></div><button type="button" onclick="costingRevisionModal.close()">&times;</button></div><div class="ci-revision-body"><div class="ci-revision-field"><label>Jenis Update *</label><select name="revision_type" required><option value="">Pilih jenis update</option><option value="price">Update Harga</option><option value="partlist">Update Partlist</option><option value="umh">Update UMH</option></select></div><div class="ci-revision-field"><label>File Excel *</label><input type="file" name="revision_file" accept=".xls,.xlsx" required></div><div class="ci-revision-field"><label>Keterangan</label><textarea name="description" placeholder="Jelaskan perubahan yang dilakukan..."></textarea></div></div><div class="ci-revision-foot"><button class="ci-btn secondary" type="button" onclick="costingRevisionModal.close()">Batal</button><button class="ci-btn" type="submit">Upload Revisi</button></div></form></dialog>
<script>const costingRevisionModal=document.getElementById('costingRevisionModal'),costingRevisionForm=document.getElementById('costingRevisionForm');function openCostingRevision(data){costingRevisionForm.reset();costingRevisionForm.action=@json(url('/costing/revisions'))+'/'+data.revision;document.getElementById('costingRevisionProject').textContent=data.project||'Project';costingRevisionModal.showModal()}</script>
@endsection
