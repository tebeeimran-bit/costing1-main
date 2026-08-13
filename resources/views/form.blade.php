@extends('layouts.app')

@section('title', 'Form Input Costing')
@section('page-title', 'Form Input Costing')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Form Input Costing</span>
@endsection

@section('content')
@include('form.partials.a00-costing-switcher')
        @include('form.partials.styles')

@include('form.partials.alerts')
@include('form.partials.toast-script')

@if(!empty($readOnlyMode))
<div class="readonly-toolbar"><div><strong>Mode Lihat Saja — Form Costing</strong><span>Data telah dikirim ke Marketing dan tidak dapat diubah dari halaman ini.</span></div><a class="readonly-back" href="{{ route('marketing.cogm-inbox', absolute:false) }}">Kembali ke Inbox</a></div>
@endif
@if(!empty($editSubmittedMode))
<div class="readonly-toolbar" style="border-color:#fbbf24;background:#fffbeb"><div><strong style="color:#92400e">Edit COGM yang Sudah Dikirim</strong><span>Setiap perubahan yang disimpan akan langsung memperbarui COGM di Inbox Marketing dan tercatat sebagai update.</span></div><a class="readonly-back" href="{{ route('costing.inbox', ['status'=>'history'], false) }}">Kembali ke History</a></div>
@endif
@if($cogmSubmission && (!empty($readOnlyMode) || $cogmSubmission->comments->isNotEmpty()))
<div class="costing-comment-card">
    <h3>Komentar untuk Team Costing</h3>
    @if(!empty($readOnlyMode) && in_array(auth()->user()->role, ['admin','marketing'], true))
    <form class="costing-comment-form" method="POST" action="{{ route('marketing.cogm-comments.store',$cogmSubmission,absolute:false) }}">@csrf<textarea name="comment" required maxlength="2000" placeholder="Tulis catatan atau pertanyaan untuk Team Costing..."></textarea><button type="submit">Kirim Komentar</button></form>
    @endif
    <div class="comment-history">@forelse($cogmSubmission->comments as $comment)<div class="comment-item"><strong>{{ $comment->user?->name ?? 'User' }}</strong><small>{{ $comment->created_at->format('d/m/Y H:i') }}</small><div>{{ $comment->comment }}</div></div>@empty<div class="comment-item">Belum ada komentar.</div>@endforelse</div>
</div>
@endif

@if(!empty($partlistAutoImportMessage))
<div style="margin-bottom:1rem;padding:.75rem 1rem;border:1px solid #93c5fd;border-radius:9px;background:#eff6ff;color:#1e40af;font-size:.75rem;font-weight:700">
    {{ $partlistAutoImportMessage }}
</div>
@endif

    @include('form.partials.unpriced-top-banner')

<div class="form-page {{ !empty($readOnlyMode) ? 'readonly-costing' : '' }}">
    <form action="{{ route('costing.store', absolute: false) }}" method="POST" id="costingForm" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <input type="hidden" name="update_section" id="updateSectionInput" value="">
        @if(!empty($editSubmittedMode))<input type="hidden" name="edit_submitted" value="1">@endif
        @if(isset($costingData) && $costingData)
            <input type="hidden" name="costing_data_id" value="{{ $costingData->id }}">
        @endif
        @if(isset($trackingRevisionId) && $trackingRevisionId)
            <input type="hidden" name="tracking_revision_id" value="{{ $trackingRevisionId }}">
        @endif
        <input type="hidden" id="trackingRevisionId" value="{{ $trackingRevisionId ?? '' }}">
        <input type="hidden" id="updateUnpricedPriceUrl"
            value="{{ isset($trackingRevision) && $trackingRevision ? route('tracking-documents.update-unpriced-price', ['revision' => $trackingRevision->id], absolute: false) : '' }}">
        <input type="hidden" id="deleteUnpricedPartUrl"
            value="{{ isset($trackingRevision) && $trackingRevision ? route('tracking-documents.delete-unpriced-part', ['revision' => $trackingRevision->id], absolute: false) : '' }}">
        <input type="hidden" id="bulkDeleteUnpricedUrl"
            value="{{ isset($trackingRevision) && $trackingRevision ? route('tracking-documents.bulk-delete-unpriced-parts', ['revision' => $trackingRevision->id], absolute: false) : '' }}">
        <input type="hidden" id="quickMaterialUpdateUrl"
            value="{{ route('costing.material-quick-update', absolute: false) }}">
        <input type="hidden" id="materialExcelExportUrl" value="{{ route('costing.material-excel.export', absolute: false) }}">
        <input type="hidden" id="materialExcelImportUrl" value="{{ route('costing.material-excel.import', absolute: false) }}">
        <input type="hidden" id="exportSopMpDate" value="{{ $trackingRevision?->project?->a00Form?->resolvedMassProductionDate()?->format('Y-m-d') ?? '' }}">
        <input type="hidden" id="exportProjectDate" value="{{ $trackingRevision?->received_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
        <input type="hidden" id="serverMaterialCost" value="{{ $costingData?->material_cost ?? 0 }}">

        @include('form.partials.project-info-section')

        @include('form.partials.rates-section')

        @include('form.partials.material-section')
        @include('form.partials.cycle-time-section')

        @include('form.partials.costing-resume-section')

        @include('form.partials.form-actions-and-imports')
@endsection

@section('scripts')
    @include('form.partials.scripts')
@endsection
