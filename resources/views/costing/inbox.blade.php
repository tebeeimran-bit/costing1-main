@extends('layouts.app')
@section('title', 'Inbox Costing')
@section('page-title', 'Inbox Costing')
@section('breadcrumb')<a href="{{ route('dashboard', absolute:false) }}">Dashboard</a><span class="breadcrumb-separator">/</span><span>Inbox Costing</span>@endsection

@section('content')
<style>
.ci-card{background:#fff;border:1px solid #d8e2ef;border-radius:12px;padding:16px}.ci-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:13px}.ci-head h3{margin:0 0 4px;color:#0f172a;font-size:15px}.ci-head p{margin:0;color:#64748b;font-size:11px}.ci-search{display:flex;gap:7px}.ci-search input,.ci-search select{height:34px;border:1px solid #cbd8ea;border-radius:7px;padding:0 10px;font-size:11px;background:#fff}.ci-search input{width:260px}.ci-btn{display:inline-flex;align-items:center;justify-content:center;min-height:31px;border:0;border-radius:7px;padding:0 12px;background:#2864e8;color:#fff;font-size:10px;font-weight:800;text-decoration:none;cursor:pointer}.ci-btn.secondary{border:1px solid #b9c9df;background:#fff;color:#17458e}.ci-btn.success{background:#16a34a}.ci-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}.ci-tab{padding:6px 10px;border:1px solid #d6e0ed;border-radius:999px;color:#52647c;text-decoration:none;font-size:10px;font-weight:700}.ci-tab.active{border-color:#2864e8;background:#eaf1ff;color:#1d4ed8}.ci-wrap{overflow:auto;border:1px solid #d8e2ef;border-radius:9px}.ci-table{width:100%;min-width:1100px;border-collapse:collapse;font-size:10px}.ci-table th{padding:9px 10px;background:#f5f8fc;color:#52647c;text-align:left;text-transform:uppercase;font-size:8px}.ci-table td{padding:10px;border-top:1px solid #e1e8f1;color:#334155;vertical-align:middle}.ci-table strong{color:#0f172a}.ci-table small{display:block;margin-top:3px;color:#718198}.ci-status{display:inline-flex;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:800}.ci-status.info{background:#eaf1ff;color:#1d4ed8}.ci-status.warning{background:#fff4dc;color:#b45309}.ci-status.danger{background:#fee8e8;color:#b91c1c}.ci-status.success{background:#e2f8e8;color:#15803d}.ci-actions{display:flex;gap:5px;flex-wrap:wrap}.ci-flow{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:14px 0}.ci-flow div{padding:9px;border:1px solid #dbe5f1;border-radius:8px;background:#f8fafc;color:#475569;font-size:10px}.ci-flow strong{display:block;margin-bottom:3px;color:#18468e}.ci-empty{text-align:center;padding:30px!important;color:#718198!important}.ci-pagination{margin-top:12px}.ci-note{margin-top:4px;color:#b45309;font-size:9px;font-weight:700}@media(max-width:800px){.ci-head{display:block}.ci-search{margin-top:10px}.ci-search input{width:100%}.ci-flow{grid-template-columns:1fr 1fr}}
</style>

<div class="ci-card">
    <div class="ci-head">
        <div><h3>Daftar Progress Costing</h3><p>Costing tetap berstatus sedang proses sampai dikirim untuk approval.</p></div>
        <form class="ci-search" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari customer, model, assy no..."><input type="hidden" name="status" value="{{ $status }}"><button class="ci-btn">Cari</button></form>
    </div>

    <div class="ci-flow">
        <div><strong>1. Kerjakan Costing</strong>Lengkapi material, rates, cycle time, dan resume.</div>
        <div><strong>2. New Part Request</strong>Isi form harga baru untuk part yang belum memiliki harga.</div>
        <div><strong>3. Submit Approval</strong>Dikirim ke Coordinator Costing setelah tidak ada unpriced part.</div>
        <div><strong>4. Approval</strong>Coordinator menyetujui atau mengembalikan untuk revisi.</div>
        <div><strong>5. Kirim COGM</strong>COGM approved dikirim ke Marketing.</div>
    </div>

    <div class="ci-tabs">
        @foreach(['active'=>'Aktif','draft'=>'Dikerjakan','pricing'=>'Menunggu Harga','rejected'=>'Perlu Revisi','waiting'=>'Menunggu Approval','approved'=>'Siap Dikirim','sent'=>'Selesai','history'=>'History','all'=>'Semua'] as $key=>$label)
            <a class="ci-tab {{ $status===$key?'active':'' }}" href="{{ route('costing.inbox', ['status'=>$key,'search'=>$search], false) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="ci-wrap"><table class="ci-table"><thead><tr><th>Project</th><th>Customer</th><th>Model</th><th>No. Assy</th><th>Rev.</th><th>Status</th><th>COGM</th><th>Update</th><th>Aksi</th></tr></thead><tbody>
        @forelse($items as $item)
            @php($revision=$item->trackingRevision)
            <tr>
                <td><strong>{{ $item->assy_name ?: $revision?->project?->part_name ?: '-' }}</strong><small>{{ $item->product?->name ?: $revision?->project?->product?->name ?: '-' }}</small></td>
                <td>{{ $item->customer?->name ?: $revision?->project?->customer ?: '-' }}</td>
                <td>{{ $item->model ?: $revision?->project?->model ?: '-' }}</td>
                <td><strong>{{ $item->assy_no ?: $revision?->project?->part_number ?: '-' }}</strong></td>
                <td>{{ $revision?->version_label ?: '-' }}</td>
                <td><span class="ci-status {{ $item->workflow_class }}">{{ $item->workflow_label }}</span>@if($item->open_unpriced_count)<div class="ci-note">{{ $item->open_unpriced_count }} part belum memiliki harga</div>@endif @if($revision?->status===\App\Models\DocumentRevision::STATUS_REJECTED_BY_COORDINATOR && $item->approval?->rejection_notes)<small>Catatan: {{ $item->approval->rejection_notes }}</small>@endif</td>
                <td><strong>Rp {{ number_format($item->cogm_value,0,',','.') }}</strong></td>
                <td>{{ optional($item->updated_at)->format('d/m/Y') }}<small>{{ optional($item->updated_at)->format('H:i') }}</small></td>
                <td><div class="ci-actions">
                    <a class="ci-btn secondary" href="{{ url('/form') }}?tracking_revision_id={{ $revision?->id }}">Form Costing</a>
                    @if($item->can_submit_approval)<form method="POST" action="{{ route('costing-approvals.submit',$revision,absolute:false) }}" class="js-confirm-form" data-confirm-title="Submit Costing" data-confirm-message="Costing {{ $item->assy_no }} akan dikirim ke Coordinator Costing untuk diperiksa dan dikunci sementara selama proses approval." data-confirm-button="Ya, Submit Costing" data-confirm-tone="primary">@csrf<button class="ci-btn">Submit Approval</button></form>@endif
                    @if($item->can_approve)<form method="POST" action="{{ route('costing-approvals.approve',$revision,absolute:false) }}" class="js-confirm-form" data-confirm-title="Approve COGM" data-confirm-message="COGM {{ $item->assy_no }} akan disetujui dan dapat dilanjutkan untuk dikirim ke Team Marketing." data-confirm-button="Ya, Approve COGM" data-confirm-tone="primary">@csrf<button class="ci-btn success">Approve</button></form>@endif
                    @if($item->can_send)<form method="POST" action="{{ route('costing-approvals.send-marketing',$revision,absolute:false) }}" class="js-confirm-form" data-confirm-title="Kirim COGM" data-confirm-message="COGM {{ $item->assy_no }} akan dikirim ke Team Marketing dan progress project akan ditandai selesai." data-confirm-button="Ya, Kirim Marketing" data-confirm-tone="primary">@csrf<input type="hidden" name="pic_marketing" value="{{ $revision?->pic_marketing }}"><button class="ci-btn success">Kirim Marketing</button></form>@endif
                </div></td>
            </tr>
        @empty<tr><td colspan="9" class="ci-empty">Tidak ada progress costing pada filter ini.</td></tr>@endforelse
    </tbody></table></div>
    <div class="ci-pagination">{{ $items->links() }}</div>
</div>
@endsection
