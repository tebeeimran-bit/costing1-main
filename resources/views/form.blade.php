@extends('layouts.app')

@section('title', 'Form Input Costing')
@section('page-title', 'Form Input Costing')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Form Input Costing</span>
@endsection

@section('content')
@if (($a00CostingTabs ?? collect())->count() > 1)
<style>
    .a00-costing-switcher { display:flex; align-items:center; box-sizing:border-box; width:100%; gap:18px; margin:0 0 12px; padding:10px 12px; background:linear-gradient(90deg,#f8fbff,#fff); border:1px solid #d7e3f2; border-radius:11px; box-shadow:0 3px 12px rgba(15,23,42,.035); }
    .a00-costing-switcher-head { display:grid; flex:0 0 175px; gap:2px; padding-left:3px; }
    .a00-costing-switcher-head strong { color:#18345d; font-size:11px; }
    .a00-costing-switcher-head span { color:#7b8ca5; font-size:9px; }
    .a00-costing-tabs { display:flex; flex:1; gap:7px; min-width:0; overflow-x:auto; padding:1px 0 2px; }
    .a00-costing-tab { display:flex; flex:0 0 170px; flex-direction:column; justify-content:center; min-height:44px; padding:7px 10px; border:1px solid #bfd5f3; border-radius:8px; color:#1e40af; text-decoration:none; background:#eff6ff; transition:.15s ease; }
    .a00-costing-tab:hover { border-color:#60a5fa; transform:translateY(-1px); }
    .a00-costing-tab strong { overflow:hidden; font-size:10px; text-overflow:ellipsis; white-space:nowrap; }
    .a00-costing-tab small { overflow:hidden; color:#64748b; font-size:8px; text-overflow:ellipsis; white-space:nowrap; }
    .a00-costing-tab.active { background:#2563eb; border-color:#2563eb; color:#fff; }
    .a00-costing-tab.active small { color:#dbeafe; }
    @media (max-width:760px) { .a00-costing-switcher { align-items:stretch; flex-direction:column; gap:8px; } .a00-costing-switcher-head { flex-basis:auto; } }
</style>
<div class="a00-costing-switcher">
    <div class="a00-costing-switcher-head">
        <strong>Form Costing A00 Gabung</strong>
        <span>{{ $a00CostingTabs->count() }} assy dalam satu dokumen A00</span>
    </div>
    <nav class="a00-costing-tabs" aria-label="Form Costing per Assy">
        @foreach ($a00CostingTabs as $tab)
            <a class="a00-costing-tab {{ (int) $trackingRevisionId === (int) $tab->revision->id ? 'active' : '' }}"
                href="{{ route('form', ['tracking_revision_id' => $tab->revision->id], false) }}">
                <strong>{{ $tab->assy_number }}</strong>
                <small>{{ $tab->assy_name }}</small>
            </a>
        @endforeach
    </nav>
</div>
@endif
        @include('form.partials.styles')

@include('form.partials.alerts')
@include('form.partials.toast-script')

<style>
.readonly-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:12px;padding:12px 14px;border:1px solid #93c5fd;border-radius:10px;background:#eff6ff}.readonly-toolbar strong{display:block;color:#1e3a8a;font-size:.82rem}.readonly-toolbar span{color:#52647c;font-size:.68rem}.readonly-back{padding:7px 11px;border:1px solid #93b4df;border-radius:7px;background:#fff;color:#1d4ed8;text-decoration:none;font-size:.68rem;font-weight:800}.costing-comment-card{margin-bottom:12px;padding:14px;border:1px solid #d8e2ef;border-radius:10px;background:#fff}.costing-comment-card h3{margin:0 0 9px;font-size:.8rem;color:#0f172a}.costing-comment-form{display:flex;gap:8px}.costing-comment-form textarea{flex:1;min-height:62px;padding:9px;border:1px solid #cbd8ea;border-radius:7px;resize:vertical;font:inherit;font-size:.72rem}.costing-comment-form button{align-self:flex-end;padding:9px 13px;border:0;border-radius:7px;background:#2864e8;color:#fff;font-size:.68rem;font-weight:800}.comment-history{display:grid;gap:7px;margin-top:10px}.comment-item{padding:9px 10px;border-radius:7px;background:#f8fafc;color:#475569;font-size:.69rem}.comment-item strong{color:#1e3a8a}.comment-item small{float:right;color:#94a3b8}.readonly-costing input,.readonly-costing select,.readonly-costing textarea{pointer-events:none;background:#f4f7fb!important;color:#475569!important}.readonly-costing button{display:none!important}@media(max-width:700px){.readonly-toolbar,.costing-comment-form{align-items:stretch;flex-direction:column}.costing-comment-form button{align-self:stretch}}
.material-file-info{display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:6px 12px;border-bottom:1px solid #e2e8f0;background:#f8fafc}.material-file-name{display:inline-flex;min-width:0;max-width:300px;align-items:center;gap:5px;padding:3px 8px;border:1px solid #e2e8f0;border-radius:5px;background:#fff;color:#64748b;font-size:.64rem;font-weight:500;white-space:nowrap}.material-file-name strong{min-width:0;overflow:hidden;color:#334155;text-overflow:ellipsis}.material-file-name-link{font-family:inherit;line-height:inherit;text-decoration:none;cursor:pointer}.material-file-name-link:hover{border-color:#93c5fd}.material-file-name-link:hover strong{color:#2563eb}@media(max-width:700px){.material-file-info{justify-content:flex-start;overflow-x:auto}.material-file-name{flex:0 0 auto;max-width:240px}}
</style>
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

        <!-- Section D: Material Breakdown Table -->
        <div class="card form-section" id="materialFormSection">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
                Material
                <div class="section-actions">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="exportMaterialEditor()">
                        Export Excel
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="exportMaterialEditor('cogm')">
                        Export COGM
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('materialEditorFileInput').click()">
                        Import Hasil Edit
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="materialUndoBtn" onclick="undoMaterialTable()" disabled aria-label="Undo" title="Undo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 14 4 9 9 4"></polyline>
                            <path d="M20 20a8 8 0 0 0-8-8H4"></path>
                        </svg>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="materialRedoBtn" onclick="redoMaterialTable()" disabled aria-label="Redo" title="Redo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="15 14 20 9 15 4"></polyline>
                            <path d="M4 20a8 8 0 0 1 8-8h8"></path>
                        </svg>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="materialDeleteSelectedBtn" onclick="deleteSelectedMaterialRows()">
                        Hapus Terpilih
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="addMaterialRow()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Tambah Baris
                    </button>
                </div>
            </div>

            @php
                $importEditFileName = Str::startsWith($trackingRevision?->costing_edit_original_name ?? '', 'Import-Hasil-Edit-')
                    ? Str::after($trackingRevision->costing_edit_original_name, 'Import-Hasil-Edit-')
                    : $trackingRevision?->costing_edit_original_name;
            @endphp
            <div class="material-file-info" aria-label="Informasi file Excel Material">
                <span class="material-file-name" title="Nama file Excel yang terakhir diexport">
                    Export: <strong id="materialExportFileName">Belum diexport</strong>
                </span>
                @if($trackingRevision?->costing_edit_file_path)
                <span role="button" tabindex="0" class="material-file-name material-file-name-link" data-download-url="{{ route('marketing.costing-edit.download', $trackingRevision, absolute:false) }}" title="Download {{ $importEditFileName }}" onclick="openMaterialDownloadConfirm(this)" onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); openMaterialDownloadConfirm(this); }">
                    Import: <strong>{{ $importEditFileName }}</strong>
                </span>
                @else
                <span class="material-file-name" title="File hasil edit belum tersimpan pada submission ini">
                    Import: <strong>Belum tersedia</strong>
                </span>
                @endif
            </div>

            <div class="material-table-container">
                <table class="material-table" id="materialTable">
                    <thead>
                        <tr>
                            <th>
                                <span class="material-row-no-header">
                                    <input type="checkbox" id="materialSelectAllRows" title="Pilih semua baris"
                                        onchange="toggleAllMaterialRowCheckboxes(this.checked)">
                                    <span>No</span>
                                </span>
                            </th>
                            <th>Part No</th>
                            <th>ID Code</th>
                            <th>Part Name</th>
                            <th style="width: 7rem;">Qty Req</th>
                            <th>Unit</th>
                            <th>Pro Code</th>
                            <th>Amount 1</th>
                            <th>Unit Price (Basis)</th>
                            <th>Currency</th>
                            <th style="width: 7rem;">Qty MOQ</th>
                            <th>C/N</th>
                            <th>Supplier</th>
                            <th>Import Tax (%)</th>
                            <th>Multiply Factor</th>
                            <th>Amount 2</th>
                            <th>Currency 2</th>
                            <th>Unit Price 2</th>
                            <th>Total Price (IDR)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="materialTableBody">
                        @php
                            $oldMaterialRows = (!$costingData && $errors->any()) ? old('materials') : null;
                        @endphp

                        @if(is_array($oldMaterialRows) && count($oldMaterialRows) > 0)
                            @foreach($oldMaterialRows as $index => $row)
                            <tr data-row="{{ $index }}">
                                <td>
                                    <span class="material-row-no-cell">
                                        <input type="checkbox" class="material-row-select" title="Pilih baris">
                                        <span class="material-row-number">{{ $index + 1 }}</span>
                                    </span>
                                </td>
                                <td><input type="text" class="form-input part-no" name="materials[{{ $index }}][part_no]"
                                    value="{{ $row['part_no'] ?? '' }}" placeholder="Part No"></td>
                                <td><input type="text" class="form-input id-code" name="materials[{{ $index }}][id_code]"
                                    value="{{ $row['id_code'] ?? '' }}" placeholder="ID Code"></td>
                                <td><input type="text" class="form-input part-name" name="materials[{{ $index }}][part_name]"
                                    value="{{ $row['part_name'] ?? '' }}" placeholder="Part Name"></td>
                                <td><input type="text" class="form-input w-28 qty-req number-format" name="materials[{{ $index }}][qty_req]" autocomplete="off"
                                    value="{{ number_format((float) ($row['qty_req'] ?? 0), 0, ',', '.') }}" data-original-qty-req="{{ intval($row['qty_req'] ?? 0) }}" onchange="calculateRow(this)"></td>
                                <td><input type="text" class="form-input unit" name="materials[{{ $index }}][unit]"
                                    value="{{ isset($row['unit']) ? strtoupper(trim((string) $row['unit'])) : '' }}" placeholder="Unit"></td>
                                <td><input type="text" class="form-input pro-code" name="materials[{{ $index }}][pro_code]"
                                    value="{{ $row['pro_code'] ?? '' }}" placeholder="Pro Code"></td>
                                <td><input type="text" class="form-input amount1 number-format" name="materials[{{ $index }}][amount1]" autocomplete="off" value="{{ rtrim(rtrim(number_format((float) ($row['amount1'] ?? 0), 4, ',', '.'), '0'), ',') }}" data-original-amount1="{{ $row['amount1'] ?? 0 }}"
                                    step="0.0001" onchange="calculateRow(this)"></td>
                                <td><input type="text" class="form-input unit-price-basis" name="materials[{{ $index }}][unit_price_basis]"
                                    value="{{ $row['unit_price_basis_text'] ?? $row['unit_price_basis'] ?? '' }}" placeholder="Unit Price"
                                    onchange="calculateRow(this)"></td>
                                <td>
                                @php $rowCurrency = $row['currency'] ?? 'IDR'; @endphp
                                <select class="form-select currency" name="materials[{{ $index }}][currency]" onchange="calculateRow(this)">
                                    <option value="" {{ $rowCurrency === '' ? 'selected' : '' }}></option>
                                    <option value="IDR" {{ $rowCurrency == 'IDR' ? 'selected' : '' }}>IDR</option>
                                    <option value="USD" {{ $rowCurrency == 'USD' ? 'selected' : '' }}>USD</option>
                                    <option value="JPY" {{ $rowCurrency == 'JPY' ? 'selected' : '' }}>JPY</option>
                                </select>
                                </td>
                                <td><input type="text" class="form-input w-28 qty-moq number-format" name="materials[{{ $index }}][qty_moq]" value="{{ rtrim(rtrim(number_format((float) ($row['qty_moq'] ?? 0), 6, ',', '.'), '0'), ',') }}" data-original-moq="{{ $row['qty_moq'] ?? 0 }}"
                                    step="0.0001" onchange="calculateRow(this)"></td>
                                <td>
                                @php $rowCn = $row['cn_type'] ?? 'N'; @endphp
                                <select class="form-select cn-type" name="materials[{{ $index }}][cn_type]" onchange="calculateRow(this)">
                                    <option value="" {{ $rowCn === '' ? 'selected' : '' }}></option>
                                    <option value="N" {{ $rowCn == 'N' ? 'selected' : '' }}>N</option>
                                    <option value="C" {{ $rowCn == 'C' ? 'selected' : '' }}>C</option>
                                    <option value="E" {{ $rowCn == 'E' ? 'selected' : '' }}>E</option>
                                </select>
                                </td>
                                <td><input type="text" class="form-input supplier" name="materials[{{ $index }}][supplier]"
                                    value="{{ $row['supplier'] ?? '' }}" placeholder="Supplier"></td>
                                <td><input type="text" class="form-input import-tax number-format" name="materials[{{ $index }}][import_tax]"
                                    value="{{ rtrim(rtrim(number_format((float) ($row['import_tax'] ?? 0), 2, ',', '.'), '0'), ',') ?: '0' }}" onchange="calculateRow(this)"></td>
                                <td class="calculated multiply-factor">1</td>
                                <td class="calculated amount2" data-original-amount2="{{ $row['amount2'] ?? 0 }}">{{ rtrim(rtrim(number_format((float) ($row['amount2'] ?? 0), 5, ',', '.'), '0'), ',') ?: '0' }}</td>
                                <td class="calculated currency2">{{ $rowCurrency }}</td>
                                <td class="calculated unit-price2">{{ isset($row['unit']) ? strtoupper(trim((string) $row['unit'])) : '' }}</td>
                                <td class="calculated total-price">Rp {{ rtrim(rtrim(number_format((float) ($row['amount1'] ?? 0), 4, ',', '.'), '0'), ',') }}</td>
                                <td>
                                <button type="button" class="btn btn-secondary" onclick="removeRow(this)"
                                    style="padding: 0.5rem;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <polyline points="3 6 5 6 21 6" />
                                    <path
                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                                </td>
                            </tr>
                            @endforeach
                        @elseif($materialBreakdowns->count() > 0)
                            @foreach($materialBreakdowns as $index => $breakdown)
                                @php
                                    $partNoDisplay = trim((string) ($breakdown->part_no ?? ''));
                                    if ($partNoDisplay === '') {
                                        $partNoDisplay = $breakdown->material->material_code ?? '';
                                        if (str_starts_with((string) $partNoDisplay, '__ROW_') || $partNoDisplay === '__PLACEHOLDER__') {
                                            $partNoDisplay = '-';
                                        }
                                    }
                                    $partNameDisplay = trim((string) ($breakdown->part_name ?? ''));
                                    if ($partNameDisplay === '') {
                                        $partNameDisplay = $breakdown->material->material_description ?? '';
                                    }
                                    $unitDisplay = strtoupper(trim((string) ($breakdown->unit ?? $breakdown->material?->base_uom ?? '')));
                                    $rowCurrencyValue = strtoupper(trim((string) ($breakdown->currency ?? 'IDR')));
                                    $rowExchangeRate = match ($rowCurrencyValue) {
                                        'USD' => (float) ($costingData?->exchange_rate_usd ?? 0),
                                        'JPY' => (float) ($costingData?->exchange_rate_jpy ?? 0),
                                        default => 1.0,
                                    };
                                    $rowServerTotal = (float) ($breakdown->qty_req ?? 0)
                                        * (float) ($breakdown->amount2 ?? 0)
                                        * $rowExchangeRate;
                                @endphp
                                <tr data-row="{{ $index }}" data-server-total="{{ $rowServerTotal }}">
                                    <td>
                                        <span class="material-row-no-cell">
                                            <input type="checkbox" class="material-row-select" title="Pilih baris">
                                            <span class="material-row-number">{{ $index + 1 }}</span>
                                        </span>
                                    </td>
                                    <td><input type="text" class="form-input part-no" name="materials[{{ $index }}][part_no]"
                                    value="{{ $partNoDisplay }}" placeholder="Part No"></td>
                                    <td><input type="text" class="form-input id-code" name="materials[{{ $index }}][id_code]"
                                    value="{{ $breakdown->id_code ?? '' }}" placeholder="ID Code"></td>
                                    <td><input type="text" class="form-input part-name" name="materials[{{ $index }}][part_name]"
                                    value="{{ $partNameDisplay }}" placeholder="Part Name"></td>
                                            <td><input type="text" class="form-input w-28 qty-req number-format" name="materials[{{ $index }}][qty_req]" autocomplete="off"
                                            value="{{ number_format((float) ($breakdown->qty_req), 0, ',', '.') }}" data-original-qty-req="{{ intval($breakdown->qty_req) }}" onchange="calculateRow(this)"></td>
                                    <td><input type="text" class="form-input unit" name="materials[{{ $index }}][unit]"
                                    value="{{ $unitDisplay }}" placeholder="Unit"></td>
                                    <td><input type="text" class="form-input pro-code" name="materials[{{ $index }}][pro_code]"
                                            value="{{ $breakdown->pro_code ?? '' }}" placeholder="Pro Code"></td>
                                            <td><input type="text" class="form-input amount1 number-format" name="materials[{{ $index }}][amount1]" autocomplete="off" value="{{ $breakdown->amount1 === null ? '' : rtrim(rtrim(number_format((float) $breakdown->amount1, 4, ',', '.'), '0'), ',') }}" data-original-amount1="{{ $breakdown->amount1 }}"
                                            step="0.0001" onchange="calculateRow(this)"></td>
                                        <td><input type="text" class="form-input unit-price-basis" name="materials[{{ $index }}][unit_price_basis]"
                                            value="{{ $breakdown->unit_price_basis_text ?? $breakdown->unit_price_basis }}" placeholder="Unit Price"
                                            onchange="calculateRow(this)">
                                    </td>
                                    <td>
                                        <select class="form-select currency" name="materials[{{ $index }}][currency]" onchange="calculateRow(this)">
                                            <option value="" {{ $breakdown->currency === null || $breakdown->currency === '' ? 'selected' : '' }}></option>
                                            <option value="IDR" {{ $breakdown->currency == 'IDR' ? 'selected' : '' }}>IDR</option>
                                            <option value="USD" {{ $breakdown->currency == 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="JPY" {{ $breakdown->currency == 'JPY' ? 'selected' : '' }}>JPY</option>
                                        </select>
                                    </td>
                                        <td><input type="text" class="form-input w-28 qty-moq number-format" name="materials[{{ $index }}][qty_moq]" value="{{ $breakdown->qty_moq === null ? '' : rtrim(rtrim(number_format((float) $breakdown->qty_moq, 6, ',', '.'), '0'), ',') }}" data-original-moq="{{ $breakdown->qty_moq }}"
                                            step="0.0001" onchange="calculateRow(this)"></td>
                                    <td>
                                        <select class="form-select cn-type" name="materials[{{ $index }}][cn_type]" onchange="calculateRow(this)">
                                            <option value="" {{ $breakdown->cn_type === null || $breakdown->cn_type === '' ? 'selected' : '' }}></option>
                                            <option value="N" {{ $breakdown->cn_type == 'N' ? 'selected' : '' }}>N</option>
                                            <option value="C" {{ $breakdown->cn_type == 'C' ? 'selected' : '' }}>C</option>
                                            <option value="E" {{ $breakdown->cn_type == 'E' ? 'selected' : '' }}>E</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-input supplier" name="materials[{{ $index }}][supplier]"
                                            value="{{ $breakdown->supplier ?? '' }}" placeholder="Supplier"></td>
                                    <td><input type="text" class="form-input import-tax number-format" name="materials[{{ $index }}][import_tax]"
                                            value="{{ $breakdown->import_tax_percent === null ? '' : (rtrim(rtrim(number_format((float) $breakdown->import_tax_percent, 2, ',', '.'), '0'), ',') ?: '0') }}" onchange="calculateRow(this)">
                                    </td>
                                    <td class="calculated multiply-factor">1</td>
                                    <td class="calculated amount2" data-original-amount2="{{ $breakdown->amount2 ?? 0 }}">{{ rtrim(rtrim(number_format($breakdown->amount2 ?? 0, 5, ',', '.'), '0'), ',') }}</td>
                                    <td class="calculated currency2">{{ $breakdown->currency ?? 'IDR' }}</td>
                                        <td class="calculated unit-price2">{{ isset($breakdown->material?->base_uom) ? strtoupper(trim((string) $breakdown->material->base_uom)) : '' }}</td>
                                    <td class="calculated total-price">Rp {{ rtrim(rtrim(number_format((float) ($breakdown->amount1 ?? 0), 4, ',', '.'), '0'), ',') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-secondary" onclick="removeRow(this)"
                                            style="padding: 0.5rem;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <!-- Default empty rows -->
                            @for($i = 0; $i < 5; $i++)
                                <tr data-row="{{ $i }}">
                                    <td>
                                        <span class="material-row-no-cell">
                                            <input type="checkbox" class="material-row-select" title="Pilih baris">
                                            <span class="material-row-number">{{ $i + 1 }}</span>
                                        </span>
                                    </td>
                                    <td><input type="text" class="form-input part-no" name="materials[{{ $i }}][part_no]" value=""
                                            placeholder="Part No"></td>
                                    <td><input type="text" class="form-input id-code" name="materials[{{ $i }}][id_code]" value=""
                                            placeholder="ID Code"></td>
                                    <td><input type="text" class="form-input part-name" name="materials[{{ $i }}][part_name]"
                                            value="" placeholder="Part Name"></td>
                                            <td><input type="text" class="form-input w-28 qty-req number-format" name="materials[{{ $i }}][qty_req]" autocomplete="off"
                                            value="0" data-original-qty-req="0" onchange="calculateRow(this)"></td>
                                    <td><input type="text" class="form-input unit" name="materials[{{ $i }}][unit]" value="PCS"
                                            placeholder="Unit"></td>
                                    <td><input type="text" class="form-input pro-code" name="materials[{{ $i }}][pro_code]" value=""
                                            placeholder="Pro Code"></td>
                                            <td><input type="text" class="form-input amount1 number-format" name="materials[{{ $i }}][amount1]" autocomplete="off" value="0" data-original-amount1="0" step="0.0001"
                                            onchange="calculateRow(this)"></td>
                                    <td><input type="text" class="form-input unit-price-basis" name="materials[{{ $i }}][unit_price_basis]" value="" placeholder="Unit Price"
                                            onchange="calculateRow(this)"></td>
                                    <td>
                                        <select class="form-select currency" name="materials[{{ $i }}][currency]" onchange="calculateRow(this)">
                                            <option value="IDR">IDR</option>
                                            <option value="USD">USD</option>
                                            <option value="JPY">JPY</option>
                                        </select>
                                    </td>
                                        <td><input type="text" class="form-input w-28 qty-moq number-format" name="materials[{{ $i }}][qty_moq]" value="0" data-original-moq="0" step="0.0001"
                                            onchange="calculateRow(this)"></td>
                                    <td>
                                        <select class="form-select cn-type" name="materials[{{ $i }}][cn_type]" onchange="calculateRow(this)">
                                            <option value="N">N</option>
                                            <option value="C">C</option>
                                            <option value="E">E</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-input supplier" name="materials[{{ $i }}][supplier]" value=""
                                            placeholder="Supplier"></td>
                                        <td><input type="text" class="form-input import-tax number-format" name="materials[{{ $i }}][import_tax]" value="0"
                                            onchange="calculateRow(this)"></td>
                                    <td class="calculated multiply-factor">1</td>
                                    <td class="calculated amount2" data-original-amount2="0">0.0000</td>
                                    <td class="calculated currency2">IDR</td>
                                    <td class="calculated unit-price2">PCS</td>
                                    <td class="calculated total-price">Rp 0</td>
                                    <td>
                                        <button type="button" class="btn btn-secondary" onclick="removeRow(this)"
                                            style="padding: 0.5rem;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--slate-700);">
                            <td colspan="18" style="text-align: right; font-weight: 600;">Total Material dari Tabel:</td>
                            <td class="calculated" id="tableTotalMaterial"
                                style="font-weight: 700; color: var(--blue-300);">Rp 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>


        </div>

        <div class="card form-section" id="unpricedPartsSection">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3h18v18H3z" />
                    <line x1="3" y1="9" x2="21" y2="9" />
                    <line x1="8" y1="9" x2="8" y2="21" />
                </svg>
                Rekapan Part Tanpa Harga
                <div class="section-actions">
                    <button type="button" class="btn btn-primary btn-sm" id="unpricedAddSelectedBtn" onclick="addSelectedUnpricedRows()">
                        Tambah Terpilih
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="unpricedDeleteSelectedBtn" onclick="deleteSelectedUnpricedRows()">
                        Hapus Terpilih
                    </button>
                    @if(isset($trackingRevision) && $trackingRevision)
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('newPartRequestFileInput').click()">Import New Part Request</button>
                        <button type="button" class="btn btn-secondary" onclick="exportNewPartRequestAndRefresh(@js(route('tracking-documents.export-new-part-request', ['revision' => $trackingRevision->id], absolute: false)), @js(route('tracking-documents.sync-new-part-request', ['revision' => $trackingRevision->id], absolute: false)))">Export New Part Request</button>
                    @endif
                </div>
            </div>

            <div class="material-table-container">
                <table class="material-table">
                    <thead>
                        <tr>
                            <th rowspan="2">
                                <span style="display:inline-flex;align-items:center;gap:0.3rem;">
                                    <input type="checkbox" id="unpricedSelectAll" title="Pilih semua baris"
                                        onchange="toggleAllUnpricedRowCheckboxes(this.checked)">
                                    <span>No</span>
                                </span>
                            </th>
                            <th rowspan="2">Part No</th>
                            <th rowspan="2">ID Code</th>
                            <th rowspan="2">Part Name</th>
                            <th colspan="9">Price</th>
                            <th rowspan="2">Input Harga (Manual)</th>
                            <th rowspan="2">Aksi</th>
                        </tr>
                        <tr>
                            <th>Price</th>
                            <th>Purchase Unit</th>
                            <th>Currency</th>
                            <th>MOQ</th>
                            <th>C/N</th>
                            <th>Maker</th>
                            <th>Add Cost (%)</th>
                            <th>Price Update</th>
                            <th>Price Before</th>
                        </tr>
                    </thead>
                    <tbody id="unpricedRecapBody">
                        @if(isset($openUnpricedParts) && $openUnpricedParts->count() > 0)
                            @foreach($openUnpricedParts as $unpricedIdx => $item)
                                @php
                                    $matchedMaterials = collect($item->matched_materials ?? []);
                                    $matchedWires = collect($item->matched_wires ?? []);
                                    $matchedSource = $item->matched_source ?? null;
                                @endphp
                                <tr data-unpriced-part="{{ $item->part_number }}" data-unpriced-open="{{ is_null($item->resolved_at) ? '1' : '0' }}">
                                    <td>
                                        <span style="display:inline-flex;align-items:center;gap:0.3rem;">
                                            <input type="checkbox" class="unpriced-row-select" data-part-number="{{ $item->part_number }}">
                                            <span>{{ $unpricedIdx + 1 }}</span>
                                        </span>
                                    </td>
                                    <td>
                                        <div>{{ $item->part_number }}</div>
                                        @if(!empty($item->matched_material_description))
                                            <div style="font-size: 0.8rem; color: var(--slate-500); margin-top: 0.25rem;">
                                                {{ $item->matched_material_description }}
                                                @if($matchedSource === 'wire')
                                                    <span style="background: #dbeafe; color: #1e40af; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.7rem; margin-left: 0.3rem;">WIRE</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($item->id_code))
                                            {{ $item->id_code }}
                                        @elseif($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->material_code ?: '-' }}</div>
                                            @endforeach
                                        @elseif($matchedSource === 'wire' && !empty($item->matched_wire_idcode))
                                            {{ $item->matched_wire_idcode }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @php
                                        $displayPartName = trim((string) ($item->part_name ?? ''));
                                        if (isset($materialBreakdowns) && is_iterable($materialBreakdowns)) {
                                            $matBreakdown = collect($materialBreakdowns)->firstWhere('part_no', trim($item->part_number));
                                            if ($matBreakdown && !empty(trim((string) ($matBreakdown->part_name ?? '')))) {
                                                $displayPartName = trim((string) $matBreakdown->part_name);
                                            }
                                        }
                                    @endphp
                                    <td>{{ $displayPartName ?: '-' }}</td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                @php
                                                    $matchedPrice = (float) ($matched->price ?? 0);
                                                    $selectedDetectedPrice = (float) ($item->detected_price ?? 0);
                                                    $isMatchedChecked = $selectedDetectedPrice > 0 && abs($matchedPrice - $selectedDetectedPrice) < 0.0001;
                                                @endphp
                                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                                    <input type="checkbox"
                                                        class="matched-price-select"
                                                        data-part-number="{{ $item->part_number }}"
                                                        data-price="{{ $matchedPrice }}"
                                                        data-currency="{{ $matched->currency ?? '' }}"
                                                        data-unit="{{ $matched->purchase_unit ?? '' }}"
                                                        data-moq="{{ $matched->moq ?? 0 }}"
                                                        data-cn="{{ $matched->cn ?? 'N' }}"
                                                        data-supplier="{{ $matched->maker ?? '' }}"
                                                        data-import-tax="{{ $matched->add_cost_import_tax ?? 0 }}"
                                                        {{ $isMatchedChecked ? 'checked' : '' }}>
                                                    <span>{{ rtrim(rtrim(number_format($matchedPrice, 4, ',', '.'), '0'), ',') }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            @if($matchedSource === 'wire' && isset($item->matched_price))
                                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                                    <input type="checkbox"
                                                        class="matched-price-select"
                                                        data-part-number="{{ $item->part_number }}"
                                                        data-price="{{ (float) $item->matched_price }}"
                                                        data-currency="{{ $item->matched_currency ?? 'IDR' }}"
                                                        data-unit="{{ $matchedSource === 'wire' ? 'm' : ($item->matched_purchase_unit ?? '') }}"
                                                        data-moq="{{ $item->matched_moq ?? 0 }}"
                                                        data-cn="{{ $item->matched_cn ?? 'N' }}"
                                                        data-supplier="{{ $item->matched_maker ?? '' }}"
                                                        data-import-tax="{{ $item->matched_add_cost_import_tax ?? 0 }}"
                                                        checked>
                                                    <span>{{ rtrim(rtrim(number_format((float) $item->matched_price, 4, ',', '.'), '0'), ',') }}</span>
                                                </div>
                                            @else
                                                {{ isset($item->matched_price) && $item->matched_price !== null ? rtrim(rtrim(number_format((float) $item->matched_price, 4, ',', '.'), '0'), ',') : rtrim(rtrim(number_format((float) ($item->detected_price ?? 0), 4, ',', '.'), '0'), ',') }}
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->purchase_unit ?: '-' }}</div>
                                            @endforeach
                                        @elseif($matchedSource === 'wire')
                                            m
                                        @else
                                            {{ $item->matched_purchase_unit ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->currency ?: '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ $item->matched_currency ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ isset($matched->moq) && $matched->moq !== null ? rtrim(rtrim(number_format((float) $matched->moq, 2, ',', '.'), '0'), ',') : '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ isset($item->matched_moq) && $item->matched_moq !== null ? rtrim(rtrim(number_format((float) $item->matched_moq, 2, ',', '.'), '0'), ',') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->cn ?: '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ $item->matched_cn ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->maker ?: '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ $item->matched_maker ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ isset($matched->add_cost_import_tax) && $matched->add_cost_import_tax !== null ? rtrim(rtrim(number_format((float) $matched->add_cost_import_tax, 2, ',', '.'), '0'), ',') : '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ isset($item->matched_add_cost_import_tax) && $item->matched_add_cost_import_tax !== null ? rtrim(rtrim(number_format((float) $item->matched_add_cost_import_tax, 2, ',', '.'), '0'), ',') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ !empty($matched->price_update) ? \Illuminate\Support\Carbon::parse($matched->price_update)->format('Y-m-d') : '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ !empty($item->matched_price_update) ? \Illuminate\Support\Carbon::parse($item->matched_price_update)->format('Y-m-d') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ isset($matched->price_before) && $matched->price_before !== null ? rtrim(rtrim(number_format((float) $matched->price_before, 2, ',', '.'), '0'), ',') : '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ isset($item->matched_price_before) && $item->matched_price_before !== null ? rtrim(rtrim(number_format((float) $item->matched_price_before, 2, ',', '.'), '0'), ',') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" class="form-input unpriced-manual-price number-format"
                                            name="manual_unpriced_prices[{{ $item->part_number }}]"
                                            data-part-number="{{ $item->part_number }}"
                                            value="{{ $item->manual_price ?? '' }}" placeholder="Isi harga jika sudah ada">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm unpriced-add-price-btn"
                                            data-part-number="{{ $item->part_number }}"
                                            data-price="{{ $item->matched_price ?? $item->detected_price ?? 0 }}"
                                            data-unit="{{ $item->matched_purchase_unit ?? '' }}"
                                            data-currency="{{ $item->matched_currency ?? '' }}"
                                            data-moq="{{ $item->matched_moq ?? '' }}"
                                            data-cn="{{ $item->matched_cn ?? '' }}"
                                            data-supplier="{{ $item->matched_maker ?? '' }}"
                                            data-import-tax="{{ $item->matched_add_cost_import_tax ?? '' }}">
                                            Tambah
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm unpriced-delete-btn" data-part-number="{{ $item->part_number }}">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="15" style="text-align: center; color: var(--slate-500);">
                                    Belum ada part tanpa harga untuk versi dokumen ini.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @include('form.partials.cycle-time-section')

        @include('form.partials.costing-resume-section')

        @include('form.partials.form-actions-and-imports')
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const materialBody = document.getElementById('materialTableBody');
            if (!materialBody) return;

            const activateMaterialRow = (target) => {
                const row = target.closest('tr');
                if (!row || !materialBody.contains(row)) return;
                materialBody.querySelectorAll('tr.material-row-active').forEach(activeRow => {
                    if (activeRow !== row) activeRow.classList.remove('material-row-active');
                });
                row.classList.add('material-row-active');
            };

            materialBody.addEventListener('pointerdown', event => activateMaterialRow(event.target));
            materialBody.addEventListener('focusin', event => activateMaterialRow(event.target));
        });

        const initialCostingResumeOverrides = @json($costingData->costing_resume_overrides ?? []);
        const submittedCogmValue = @json($readOnlyMode && $cogmSubmission ? (float) $cogmSubmission->cogm_value : null);
        let costingResumeOverrides = { ...(initialCostingResumeOverrides || {}) };

        // Material ownership is now consolidated under DEM. Discard saved display
        // overrides from the previous split so they cannot restore stale totals.
        Object.keys(costingResumeOverrides).forEach((key) => {
            if (key.startsWith('material.dem.') || key.startsWith('material.customer.')) {
                delete costingResumeOverrides[key];
            }
        });
        syncCostingResumeOverridesInput();

        function triggerUmhImport() {
            const input = document.getElementById('importUmhFileInput');

            if (!input) {
                alert('Input file Import UMH belum ditemukan.');
                return;
            }

            input.value = '';
            input.click();
        }

        function submitUmhImport() {
            const form = document.getElementById('umhImportForm');

            if (!form) {
                alert('Form Import UMH belum ditemukan.');
                return;
            }

            form.submit();
        }

        function triggerMaterialImport() {
            const input = document.getElementById('importCogmFileInput');

            if (!input) {
                alert('Input file Import COGM belum ditemukan.');
                return;
            }

            input.value = '';
            input.click();
        }

        function submitCogmImport() {
            const form = document.getElementById('cogmImportForm');

            if (!form) {
                alert('Form Import COGM belum ditemukan.');
                return;
            }

            syncForecastHidden();

            const forecastHidden = document.getElementById('forecast');
            const projectPeriod = document.getElementById('projectPeriod');
            const wireRateSelector = document.getElementById('wireRateSelector');

            const cogmForecast = document.getElementById('cogmImportForecast');
            const cogmProjectPeriod = document.getElementById('cogmImportProjectPeriod');
            const cogmWireRateId = document.getElementById('cogmImportWireRateId');

            if (cogmForecast && forecastHidden) {
                cogmForecast.value = forecastHidden.value || '0';
            }

            if (cogmProjectPeriod && projectPeriod) {
                cogmProjectPeriod.value = projectPeriod.value || '0';
            }

            if (cogmWireRateId && wireRateSelector) {
                cogmWireRateId.value = wireRateSelector.value || '';
            }

            const syncFields = {
                'cogmImportBusinessCategoryId': 'select[name="business_category_id"]',
                'cogmImportCustomerId': 'select[name="customer_id"]',
                'cogmImportPeriod': '#periodInput',
                'cogmImportLine': 'select[name="line"]',
                'cogmImportModel': 'input[name="model"]',
                'cogmImportAssyNo': 'input[name="assy_no"]',
                'cogmImportAssyName': 'input[name="assy_name"]',
                'cogmImportRateUsd': '#rateUSD',
                'cogmImportRateJpy': '#rateJPY',
                'cogmImportLmeRate': '#lmeRate',
            };

            for (const [hiddenId, mainSelector] of Object.entries(syncFields)) {
                const hidden = document.getElementById(hiddenId);
                const main = document.querySelector('#costingForm ' + mainSelector);

                if (hidden && main) {
                    hidden.value = main.value || '';
                }
            }

            showAppLoading('Mengimport COGM...');

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            form.submit();
        }

        window.COSTING_FORM_FAST_UPDATE_VERSION = 'fire-and-forget-update-material-v1';
        // Global variables
        let rowCounter = {{ (!$costingData && is_array(old('materials')) && count(old('materials')) > 0) ? count(old('materials')) : ($materialBreakdowns->count() > 0 ? $materialBreakdowns->count() : 5) }};
        let cycleRowCounter = {{ $initialCycleCount }};
        let materialUndoHistory = [];
        let materialRedoHistory = [];
        const materialUndoLimit = 50;
        let materialHistoryApplying = false;
        let isMaterialDirty = false;
        let isConfirmingUnsavedMaterial = false;
        const materialFilterState = {};
        const materialFilterableColumns = [1, 2, 3, 5, 6, 9, 11, 12];
        let materialFilterPopup = null;
        let activeMaterialFilterColumn = null;
        let materialSortState = { column: null, direction: null };
        const materialValidationNoticeAcknowledged = {
            missing_price: false,
            estimate_price: false,
        };
        let materialValidationNoticeOpen = false;
        let bypassMaterialValidationNoticeOnce = false;
        let materialInitialRowsSnapshot = [];
        let materialInitialRowsSnapshotJson = '[]';
        let materialStructureDirty = false;

        // Materials data for dynamic selection (slim: only fields needed for JS lookup)
        const rawMaterials = @json($materialsSlim);
        const materials = rawMaterials.map(m => ({
            material_code: m[0],
            material_description: m[1],
            base_uom: m[2],
            currency: m[3],
            price: m[4],
            moq: m[5],
            cn: m[6],
            maker: m[7],
            add_cost_import_tax: m[8]
        }));
        const materialMasterByCode = new Map();
        materials.forEach((item) => {
            const codeKey = String(item?.material_code || '').trim().toUpperCase();
            if (codeKey !== '') {
                materialMasterByCode.set(codeKey, item);
            }
        });
        const cycleProcessOptions = @json(($cycleTimeTemplates ?? collect())->pluck('process')->values());
        const hasServerUnpricedData = {{ (isset($openUnpricedParts) && $openUnpricedParts->count() > 0) ? 'true' : 'false' }};
        const unpricedSyncTimers = {};

        // Format number as Rupiah
        function formatRupiah(value) {
            const number = Number(value) || 0;

            return 'Rp ' + number.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }




        function parseResumeMoneyNumber(value) {
            if (value === null || value === undefined) {
                return 0;
            }

            let raw = String(value).trim();

            if (raw === '') {
                return 0;
            }

            raw = raw.replace(/\s+/g, '');
            raw = raw.replace(/[^0-9,.\-]/g, '');

            if (raw === '' || raw === '-' || raw === '.' || raw === ',') {
                return 0;
            }

            const hasComma = raw.includes(',');
            const hasDot = raw.includes('.');

            if (hasComma && hasDot) {
                const lastComma = raw.lastIndexOf(',');
                const lastDot = raw.lastIndexOf('.');

                if (lastComma > lastDot) {
                    // Format Indonesia: 12.347,13
                    raw = raw.replace(/\./g, '');
                    raw = raw.replace(/,/g, '.');
                } else {
                    // Format international: 12,347.13
                    raw = raw.replace(/,/g, '');
                }
            } else if (hasComma && !hasDot) {
                // Format Indonesia tanpa ribuan: 12347,13
                raw = raw.replace(/,/g, '.');
            } else if (hasDot && !hasComma) {
                /*
                 * Khusus Resume COGM:
                 * Setelah submit, value bisa menjadi raw decimal: 12347.13.
                 * Jangan dianggap ribuan, karena itu yang membuat 12347.13
                 * berubah menjadi 1.234.713,00.
                 */
                raw = raw;
            }

            const numeric = Number(raw);

            return Number.isFinite(numeric) ? numeric : 0;
        }

        function formatResumeMoneyValue(value) {
            const number = Number(value) || 0;

            return number.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function setResumeMoneyValue(inputOrId, value) {
            const input = typeof inputOrId === 'string' ? document.getElementById(inputOrId) : inputOrId;
            if (!input) return;

            input.value = formatResumeMoneyValue(value);
            input.dataset.rawValue = String(Number(value) || 0);
        }

        function getResumeMoneyValue(inputOrId) {
            const input = typeof inputOrId === 'string' ? document.getElementById(inputOrId) : inputOrId;
            if (!input) return 0;

            return parseResumeMoneyNumber(input.value || input.dataset.rawValue || 0);
        }

        function formatResumeMoneyInput(input) {
            setResumeMoneyValue(input, getResumeMoneyValue(input));
        }

        function normalizeResumeMoneyInputsForSubmit() {
            document.querySelectorAll('.resume-money-input').forEach(function(input) {
                input.value = String(parseResumeMoneyNumber(input.value || input.dataset.rawValue || 0));
            });
        }

        
        // Auto-format numbers with dots for thousands and comma for decimal
        
        // Convert JS float (e.g. 4000.25) to input format '4.000,25'
        function floatToInput(num) {
            if (num === null || num === undefined || isNaN(Number(num))) return '0';
            
            let raw = String(Number(num));
            return formatNumberInput(raw.replace('.', ','));
        }

        function formatNumberInput(value) {
            if (value === null || value === undefined) return '';
            let valStr = value.toString();
            if (valStr === '') return '';
            
            let parts = valStr.split(',');
            let integerPart = parts[0].replace(/[^0-9\-]/g, ''); // keep numbers and negative
            let decimalPart = parts.length > 1 ? ',' + parts[1].replace(/[^0-9]/g, '') : '';
            
            // Add thousand separators
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return integerPart + decimalPart;
        }


        function initMaterialValidationHighlightStyles() {
            if (document.getElementById('materialValidationHighlightStyles')) {
                return;
            }

            const style = document.createElement('style');
            style.id = 'materialValidationHighlightStyles';
            style.textContent = `
                #materialTableBody tr.material-row-missing-price > td {
                    background: #fee2e2 !important;
                }

                #materialTableBody tr.material-row-missing-price {
                    outline: 2px solid #dc2626;
                    outline-offset: -2px;
                }

                #materialTableBody tr.material-row-estimate-price > td {
                    background: #fef3c7 !important;
                }

                #materialTableBody tr.material-row-estimate-price {
                    outline: 2px solid #f59e0b;
                    outline-offset: -2px;
                }

                #materialTableBody tr.material-row-missing-price .amount1 {
                    border-color: #dc2626 !important;
                    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.18) !important;
                }

                #materialTableBody tr.material-row-estimate-price .cn-type {
                    border-color: #f59e0b !important;
                    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.22) !important;
                }

                .material-validation-modal-backdrop {
                    position: fixed;
                    inset: 0;
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1.5rem;
                    background: rgba(15, 23, 42, 0.38);
                    backdrop-filter: blur(3px);
                }

                .material-validation-modal-card {
                    width: min(420px, 100%);
                    border-radius: 18px;
                    background: #ffffff;
                    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
                    border: 1px solid rgba(148, 163, 184, 0.22);
                    overflow: hidden;
                    animation: materialValidationModalIn 160ms ease-out;
                }

                .material-validation-modal-body {
                    padding: 1.5rem 1.5rem 1.25rem;
                }

                .material-validation-modal-icon {
                    width: 44px;
                    height: 44px;
                    border-radius: 999px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 0.9rem;
                }

                .material-validation-modal-icon.error {
                    background: #fee2e2;
                    color: #dc2626;
                }

                .material-validation-modal-icon.warning {
                    background: #fef3c7;
                    color: #d97706;
                }

                .material-validation-modal-title {
                    font-size: 1rem;
                    font-weight: 800;
                    color: #0f172a;
                    margin-bottom: 0.35rem;
                }

                .material-validation-modal-message {
                    color: #475569;
                    line-height: 1.5;
                    font-size: 0.92rem;
                }

                .material-validation-modal-actions {
                    display: flex;
                    justify-content: flex-end;
                    gap: 0.75rem;
                    padding: 0 1.5rem 1.5rem;
                }

                .material-validation-modal-ok {
                    border: 0;
                    border-radius: 10px;
                    padding: 0.65rem 1.1rem;
                    font-weight: 700;
                    color: #ffffff;
                    background: #2563eb;
                    cursor: pointer;
                    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.26);
                }

                .material-validation-modal-ok:hover {
                    background: #1d4ed8;
                }

                @keyframes materialValidationModalIn {
                    from {
                        opacity: 0;
                        transform: translateY(8px) scale(0.98);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
            `;

            document.head.appendChild(style);
        }

        function clearMaterialValidationHighlights() {
            document.querySelectorAll('#materialTableBody tr').forEach((row) => {
                row.classList.remove('material-row-missing-price', 'material-row-estimate-price');
            });
        }

        function showMaterialValidationModal(message, type, onOk) {
            initMaterialValidationHighlightStyles();

            if (materialValidationNoticeOpen) {
                return;
            }

            materialValidationNoticeOpen = true;

            const backdrop = document.createElement('div');
            backdrop.className = 'material-validation-modal-backdrop';
            backdrop.setAttribute('role', 'dialog');
            backdrop.setAttribute('aria-modal', 'true');

            const iconSvg = type === 'error'
                ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="7" x2="12" y2="13"></line><circle cx="12" cy="17" r="1"></circle></svg>'
                : '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';

            const detail = type === 'error'
                ? 'Baris yang belum memiliki harga sudah ditandai warna merah. Anda tetap bisa lanjut setelah menekan OK.'
                : 'Baris dengan harga estimate sudah ditandai warna kuning. Anda tetap bisa lanjut setelah menekan OK.';

            backdrop.innerHTML = `
                <div class="material-validation-modal-card">
                    <div class="material-validation-modal-body">
                        <div class="material-validation-modal-icon ${type}">${iconSvg}</div>
                        <div class="material-validation-modal-title">Perhatian Material</div>
                        <div class="material-validation-modal-message">
                            <strong>${message}</strong><br>
                            ${detail}
                        </div>
                    </div>
                    <div class="material-validation-modal-actions">
                        <button type="button" class="material-validation-modal-ok">OK, lanjut</button>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);

            const okButton = backdrop.querySelector('.material-validation-modal-ok');
            const close = () => {
                materialValidationNoticeOpen = false;
                backdrop.remove();

                if (typeof onOk === 'function') {
                    onOk();
                }
            };

            okButton?.addEventListener('click', close);
            setTimeout(() => okButton?.focus(), 30);
        }

        function isMaterialRowActive(row) {
            if (!row) {
                return false;
            }

            const partNo = String(row.querySelector('.part-no')?.value || '').trim();
            const idCode = String(row.querySelector('.id-code')?.value || '').trim();
            const partName = String(row.querySelector('.part-name')?.value || '').trim();
            const supplier = String(row.querySelector('.supplier')?.value || '').trim();
            const qtyReq = parseInputNumber(row.querySelector('.qty-req')?.value || 0);
            const amount1 = parseInputNumber(row.querySelector('.amount1')?.value || 0);

            return partNo !== ''
                || idCode !== ''
                || partName !== ''
                || supplier !== ''
                || qtyReq > 0
                || amount1 > 0;
        }

        function getMaterialSectionValidationResult() {
            initMaterialValidationHighlightStyles();
            clearMaterialValidationHighlights();

            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const activeRows = rows.filter((row) => isMaterialRowActive(row));

            if (activeRows.length === 0) {
                return {
                    ok: true,
                    code: '',
                    message: '',
                    type: 'success',
                    missingRows: [],
                    estimateRows: [],
                };
            }

            const missingRows = [];
            const estimateRows = [];

            activeRows.forEach((row) => {
                const amountInput = row.querySelector('.amount1');
                const rawAmount = String(amountInput?.value || '').trim();
                const cnValue = String(row.querySelector('.cn-type')?.value || '').trim().toUpperCase();

                if (rawAmount === '' || parseInputNumber(rawAmount) <= 0) {
                    missingRows.push(row);
                    row.classList.add('material-row-missing-price');
                }

                if (cnValue === 'E') {
                    estimateRows.push(row);
                    row.classList.add('material-row-estimate-price');
                }
            });

            if (missingRows.length > 0) {
                return {
                    ok: false,
                    code: 'missing_price',
                    message: 'Ada harga yang belum',
                    type: 'error',
                    missingRows,
                    estimateRows,
                };
            }

            if (estimateRows.length > 0) {
                return {
                    ok: false,
                    code: 'estimate_price',
                    message: 'Ada harga yang masih estimate',
                    type: 'warning',
                    missingRows,
                    estimateRows,
                };
            }

            return {
                ok: true,
                code: '',
                message: '',
                type: 'success',
                missingRows: [],
                estimateRows: [],
            };
        }

        function refreshMaterialValidationHighlights() {
            getMaterialSectionValidationResult();
        }

        function shouldShowMaterialValidationNotice(result) {
            if (!result || result.ok || !result.code) {
                return false;
            }

            if (bypassMaterialValidationNoticeOnce) {
                bypassMaterialValidationNoticeOnce = false;
                return false;
            }

            return materialValidationNoticeAcknowledged[result.code] !== true;
        }

        function acknowledgeMaterialValidationNotice(result) {
            if (result && result.code) {
                materialValidationNoticeAcknowledged[result.code] = true;
            }
        }

        document.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('number-format')) {
                let startPos = e.target.selectionStart;
                let oldVal = e.target.value;
                
                // Allow user to type comma if it's the last character and no other commas exist
                if (oldVal.endsWith(',') && (oldVal.match(/,/g) || []).length === 1) {
                    // Do not rewrite immediately to prevent cursor jumping when starting a decimal
                    return;
                }

                let newVal = formatNumberInput(oldVal);
                e.target.value = newVal;
            }
        });
        
        document.addEventListener('input', function(event) {
            if (event.target && event.target.closest && event.target.closest('#materialTableBody')) {
                refreshMaterialValidationHighlights();
            }
        });

        document.addEventListener('change', function(event) {
            if (event.target && event.target.closest && event.target.closest('#materialTableBody')) {
                refreshMaterialValidationHighlights();
            }
        });

        document.addEventListener('blur', function(e) {
            if (e.target && e.target.classList.contains('number-format')) {
                // Formatting on blur cleanly
                if (e.target.value) {
                    let cleaned = e.target.value.replace(/,$/, '');
                    e.target.value = formatNumberInput(cleaned);
                }
                
                // also calculateRow if not already fired by browser
                if (typeof calculateRow === 'function' && e.target.closest('tr')) {
                    calculateRow(e.target);
                }
            }
        }, true);

        function formatWholeNumber(number) {
            return String(Math.round(Number(number) || 0));
        }

        function parsePositiveInteger(value) {
            const digits = String(value || '').replace(/[^\d]/g, '');
            if (!digits) return 0;
            return parseInt(digits, 10) || 0;
        }

        function syncForecastHidden() {
            const forecastDisplay = document.getElementById('forecastDisplay');
            const forecastHidden = document.getElementById('forecast');
            if (!forecastDisplay || !forecastHidden) return 0;

            const numericValue = parsePositiveInteger(forecastDisplay.value);
            forecastHidden.value = numericValue;
            return numericValue;
        }

        function formatForecastDisplay() {
            const forecastDisplay = document.getElementById('forecastDisplay');
            if (!forecastDisplay) return;

            const numericValue = syncForecastHidden();
            forecastDisplay.value = numericValue > 0
                ? new Intl.NumberFormat('id-ID').format(numericValue)
                : '';
        }

        // Get exchange rate based on currency
        function getExchangeRate(currency) {
            switch (currency) {
                case 'USD': return parseRateInputValue(document.getElementById('rateUSD')?.value || 0) || 15500;
                case 'JPY': return parseRateInputValue(document.getElementById('rateJPY')?.value || 0) || 103;
                default: return 1;
            }
        }

        // Calculate Multiply Factor
        // Logika: IF(qtyReq=0,0, IF(OR(cnFlag="C",(moq/(forecast*period*12*qtyReq/unitDivisor))<1), 1, moq/(forecast*period*12*qtyReq/unitDivisor)))
        function calculateMultiplyFactor(row) {
            const qtyReq = parseInputNumber(row.querySelector('.qty-req')?.value || 0);
            const moq = parseInputNumber(row.querySelector('.qty-moq')?.value || 0);
            const quantity = parseInputNumber(document.getElementById('forecast')?.value || document.getElementById('forecastDisplay')?.value || 0);
            const productLife = parseInputNumber(document.getElementById('projectPeriod')?.value || 0);
            const unit = (row.querySelector('.unit')?.value || row.querySelector('.unit')?.textContent || '').trim().toUpperCase();
            const cnFlag = (row.querySelector('.cn-type')?.value || '').trim().toUpperCase();

            // Excel: IF(QTY_REQ=0;0;...)
            if (qtyReq <= 0) {
                return 0;
            }

            // Excel: IF(UNIT="MM";1000;1)
            const unitDivisor = (unit === 'MM') ? 1000 : 1;

            // Excel denominator: QUANTITY * PRODUCT_LIFE * 12 * QTY_REQ / unitDivisor
            let denominator = quantity * productLife * 12 * qtyReq;
            denominator = denominator / unitDivisor;

            if (denominator === 0) {
                return 0;
            }

            const ratio = moq / denominator;

            // Excel: IF(OR(CN="C";ratio<1);1;ratio)
            if (cnFlag === 'C' || ratio < 1) {
                return 1;
            }

            return ratio;
        }

        // Helper to parse input values safely
        // STRATEGI: Asumsi User Indonesia
        // 1. Hapus semua TITIK (.) yang biasanya dipakai sebagai pemisah ribuan
        // 2. Ganti KOMA (,) menjadi TITIK (.) sebagai pemisah desimal
        // Contoh: "1.000,50" -> "1000.50"
        function parseInputNumber(value) {
            if (!value) return 0;
            let str = value.toString();

            str = str.replace(/\s+/g, '');
            str = str.replace(/[^0-9,\.\-]/g, '');

            if (str === '' || str === '-' || str === '.' || str === ',') {
                return 0;
            }

            const hasComma = str.includes(',');
            const hasDot = str.includes('.');

            if (hasComma && hasDot) {
                const lastCommaPos = str.lastIndexOf(',');
                const lastDotPos = str.lastIndexOf('.');

                if (lastCommaPos > lastDotPos) {
                    str = str.replace(/\./g, '');
                    str = str.replace(/,/g, '.');
                } else {
                    str = str.replace(/,/g, '');
                }
            } else if (hasComma && !hasDot) {
                str = str.replace(/,/g, '.');
            } else if (hasDot && !hasComma) {
                // Nilai dari server/import bisa berupa desimal baku (contoh 1138.15),
                // sedangkan input Indonesia memakai titik sebagai pemisah ribuan.
                // Anggap titik sebagai ribuan hanya jika polanya memang kelompok 3 digit.
                const dotCount = (str.match(/\./g) || []).length;
                const lastDotPos = str.lastIndexOf('.');
                const digitsAfterLastDot = str.length - lastDotPos - 1;
                const digitsBeforeLastDot = str.substring(0, lastDotPos).replace('-', '').length;
                const looksLikeLeadingDecimal = /^-?0\.\d+$/.test(str);
                const looksLikeThousands = !looksLikeLeadingDecimal
                    && (dotCount > 1 || (digitsAfterLastDot === 3 && digitsBeforeLastDot > 1));

                if (looksLikeThousands) {
                    str = str.replace(/\./g, '');
                }
            }

            return parseFloat(str) || 0;
        }

        function findMaterialMasterForRow(row) {
            if (!row) return null;

            const partNo = String(row.querySelector('.part-no')?.value || '').trim().toUpperCase();
            const idCode = String(row.querySelector('.id-code')?.value || '').trim().toUpperCase();

            if (partNo && materialMasterByCode.has(partNo)) {
                return materialMasterByCode.get(partNo);
            }

            if (idCode && materialMasterByCode.has(idCode)) {
                return materialMasterByCode.get(idCode);
            }

            return null;
        }

        function applyMasterMaterialToRow(row) {
            const master = findMaterialMasterForRow(row);
            if (!master || !row) {
                return false;
            }

            const partNameInput = row.querySelector('.part-name');
            const unitInput = row.querySelector('.unit');
            const supplierInput = row.querySelector('.supplier');
            const amount1Input = row.querySelector('.amount1');
            const qtyMoqInput = row.querySelector('.qty-moq');
            const importTaxInput = row.querySelector('.import-tax');
            const currencySelect = row.querySelector('.currency');
            const cnTypeSelect = row.querySelector('.cn-type');

            if (partNameInput && String(partNameInput.value || '').trim() === '') {
                partNameInput.value = String(master.material_description || '').toUpperCase();
            }

            if (unitInput && String(unitInput.value || '').trim() === '') {
                unitInput.value = String(master.base_uom || 'PCS').toUpperCase();
            }

            if (supplierInput && String(supplierInput.value || '').trim() === '') {
                supplierInput.value = String(master.maker || '').toUpperCase();
            }

            if (currencySelect && String(currencySelect.value || '').trim() === '') {
                const currency = String(master.currency || 'IDR').toUpperCase();
                if (['IDR', 'USD', 'JPY'].includes(currency)) {
                    currencySelect.value = currency;
                }
            }

            if (cnTypeSelect) {
                const current = String(cnTypeSelect.value || '').toUpperCase();
                if (current !== 'C' && current !== 'N') {
                    const fromMaster = String(master.cn || 'N').toUpperCase();
                    cnTypeSelect.value = fromMaster === 'C' ? 'C' : 'N';
                }
            }

            if (qtyMoqInput) {
                const currentMoq = parseInputNumber(qtyMoqInput.value || 0);
                const masterMoq = Number(master.moq || 0);
                if (currentMoq <= 0 && masterMoq > 0) {
                    qtyMoqInput.value = floatToInput(masterMoq);
                }
            }

            if (importTaxInput && String(importTaxInput.value || '').trim() === '' && master.add_cost_import_tax !== null && master.add_cost_import_tax !== undefined) {
                importTaxInput.value = floatToInput(master.add_cost_import_tax || 0);
            }

            if (amount1Input) {
                const currentAmount1 = parseInputNumber(amount1Input.value || 0);
                const masterPrice = Number(master.price || 0);
                if (currentAmount1 <= 0 && masterPrice > 0) {
                    amount1Input.value = floatToInput(masterPrice);
                }
            }

            return true;
        }

        // Calculate row total
        function calculateRow(element, options = {}) {
            const row = element.closest('tr');
            if (!row) return;

            const multiplyFactor = calculateMultiplyFactor(row);
            const multiplyFactorElement = row.querySelector('.multiply-factor');
            if (multiplyFactorElement) {
                multiplyFactorElement.textContent = floatToInput(Number(multiplyFactor.toFixed(4)));
            }

            const priceBase = parseInputNumber(row.querySelector('.amount1')?.value || 0);
            const uom = (row.querySelector('.unit')?.value || '').trim().toUpperCase();
            const priceBasis = (row.querySelector('.unit-price-basis')?.value || '').trim().toUpperCase();
            const importTax = parseInputNumber(row.querySelector('.import-tax')?.value || 0) || 0;

            const extra = priceBase * (importTax / 100);
            const base = priceBase + extra;
            const numerator = multiplyFactor * base;

            let unitDivisor = 1;
            if (priceBasis === 'METER' || priceBasis === 'M' || priceBasis === 'MTR') {
                unitDivisor = 1000;
            }

            const amount2Raw = (unitDivisor !== 0) ? (numerator / unitDivisor) : 0;
            const amount2Display = Number(amount2Raw.toFixed(5));

            const amount2Element = row.querySelector('.amount2');
            if (amount2Element) {
                amount2Element.textContent = floatToInput(amount2Display);
                amount2Element.setAttribute('data-raw-value', String(amount2Raw));
            }

            const qty = parseInputNumber(row.querySelector('.qty-req')?.value || 0) || 0;
            const currency = row.querySelector('.currency')?.value || 'IDR';
            const exchangeRate = getExchangeRate(currency);

            const currency2Element = row.querySelector('.currency2');
            if (currency2Element) {
                currency2Element.textContent = currency;
            }

            const unitPrice2Element = row.querySelector('.unit-price2');
            if (unitPrice2Element) {
                unitPrice2Element.textContent = uom;
            }

            // PENTING:
            // Total Price (IDR) mengikuti Excel: qty * amount2 mentah/full precision * rate.
            // Amount 2 yang tampil boleh 4 desimal, tetapi total tidak memakai angka tampilan tersebut.
            const total = qty * amount2Raw * exchangeRate;

            const totalPriceElement = row.querySelector('.total-price');
            if (totalPriceElement) {
                totalPriceElement.textContent = formatRupiah(total);
                totalPriceElement.setAttribute('data-value', String(total));
            }

            if (!options.silent) {
                calculateTableTotal();
                refreshUnpricedRecap();
            }
        }

        // Calculate table total

        function parseDataValueNumber(value) {
            if (value === null || value === undefined) {
                return 0;
            }

            const raw = String(value).trim();
            if (raw === '') {
                return 0;
            }

            // data-value biasanya disimpan sebagai angka mentah JS/database, contoh: 1138.15
            // Jangan gunakan parseInputNumber untuk kasus ini, karena titik bisa dianggap ribuan.
            if (/^-?\d+(\.\d+)?$/.test(raw)) {
                return Number(raw) || 0;
            }

            return parseInputNumber(raw);
        }

        function calculateTableTotal(syncMaterialCost = true) {
            let total = 0;
            const rows = document.querySelectorAll('#materialTableBody tr');

            rows.forEach(row => {
                const totalElement = row.querySelector('.total-price');
                const dataValue = totalElement ? parseDataValueNumber(totalElement.getAttribute('data-value') || totalElement.textContent || 0) : 0;
                total += dataValue;
            });

            // Update Footer Total using the rendered totals so it stays aligned with Database Costing
            const materialCostInput = document.getElementById('materialCost');
            if (materialCostInput && syncMaterialCost) {
                setResumeMoneyValue(materialCostInput, total);
                calculateTotals(false);
            }

            document.getElementById('tableTotalMaterial').textContent = formatRupiah(total);

            return total;
        }

        function refreshUnpricedRecap() {
            const tbody = document.getElementById('unpricedRecapBody');
            if (!tbody) return;

            // Only show server-persisted unpriced data. Export New Part Request
            // synchronizes this recap from the latest imported costing-edit file.
            if (hasServerUnpricedData) {
                const visibleRows = tbody.querySelectorAll('tr[data-unpriced-part]').length;
                const openRows = tbody.querySelectorAll('tr[data-unpriced-part][data-unpriced-open="1"]').length;
                const banner = document.getElementById('unpricedTopBanner');
                const bannerText = document.getElementById('unpricedTopBannerText');

                if (banner) {
                    banner.style.display = openRows > 0 ? 'flex' : 'none';
                }
                if (bannerText && openRows > 0) {
                    bannerText.textContent = `Terdapat ${openRows} part yang belum memiliki harga pada versi dokumen ini.`;
                }

                bindUnpricedManualPriceInputs();
                bindUnpricedDeleteButtons();
                bindMatchedPriceSelectors();
                bindUnpricedAddPriceButtons();
                updateUnpricedSelectAllState();
                return;
            }

            // No server data — show empty message
            tbody.innerHTML = '<tr><td colspan="15" style="text-align: center; color: var(--slate-500);">Tidak ada part yang menunggu harga.</td></tr>';
            const banner = document.getElementById('unpricedTopBanner');
            if (banner) banner.style.display = 'none';
        }

        function bindMatchedPriceSelectors() {
            const selectors = document.querySelectorAll('.matched-price-select');
            selectors.forEach((selector) => {
                if (!(selector instanceof HTMLInputElement)) {
                    return;
                }

                if (selector.dataset.boundMatchedPrice === '1') {
                    return;
                }

                selector.dataset.boundMatchedPrice = '1';

                selector.addEventListener('change', function () {
                    const partNumber = this.dataset.partNumber || '';
                    if (!partNumber) {
                        return;
                    }

                    const escapedPart = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function')
                        ? CSS.escape(partNumber)
                        : partNumber.replace(/([\\[\\]\\.\\:\\#\"'])/g, '\\\\$1');

                    const siblingSelectors = document.querySelectorAll(`.matched-price-select[data-part-number="${escapedPart}"]`);

                    if (this.checked) {
                        siblingSelectors.forEach((sibling) => {
                            if (sibling !== this && sibling instanceof HTMLInputElement) {
                                sibling.checked = false;
                            }
                        });
                    }
                });
            });
        }

        function bindUnpricedAddPriceButtons() {
            const buttons = document.querySelectorAll('.unpriced-add-price-btn');
            buttons.forEach((button) => {
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }

                if (button.dataset.boundAddPrice === '1') {
                    return;
                }

                button.dataset.boundAddPrice = '1';

                button.addEventListener('click', function () {
                    const row = this.closest('tr');
                    const item = getUnpricedRowPriceData(row);
                    if (!item?.partNumber) return;

                    if (item.price <= 0) {
                        window.alert('Harga belum tersedia. Import New Part Request terlebih dahulu.');
                        return;
                    }

                    applySelectedMatchedPrice(item.partNumber, item.price, item.currency, item.unit, item.moq, item.cn, item.supplier, item.importTax);
                });
            });
        }

        function getUnpricedRowPriceData(row) {
            if (!(row instanceof HTMLTableRowElement)) return null;

            const button = row.querySelector('.unpriced-add-price-btn');
            if (!(button instanceof HTMLButtonElement)) return null;

            const selectedOption = row.querySelector('.matched-price-select:checked');
            const source = selectedOption instanceof HTMLInputElement ? selectedOption : button;
            const manualInput = row.querySelector('.unpriced-manual-price');
            const manualPrice = manualInput instanceof HTMLInputElement ? parseInputNumber(manualInput.value) : 0;

            return {
                partNumber: button.dataset.partNumber || '',
                price: manualPrice > 0 ? manualPrice : (parseFloat(source.dataset.price || '0') || 0),
                currency: source.dataset.currency || '',
                unit: source.dataset.unit || '',
                moq: source.dataset.moq === '' ? null : (parseFloat(source.dataset.moq || '0') || 0),
                cn: source.dataset.cn || '',
                supplier: source.dataset.supplier || '',
                importTax: source.dataset.importTax === '' ? null : (parseFloat(source.dataset.importTax || '0') || 0),
            };
        }

        function normalizePartKey(value) {
            return String(value || '').trim().toLowerCase();
        }

        async function applySelectedMatchedPrice(partNumber, selectedPrice, selectedCurrency, selectedUnit, selectedMoq, selectedCn, selectedSupplier, selectedImportTax, manageLoading = true, persistPrice = true) {
            const escapedPart = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function')
                ? CSS.escape(partNumber)
                : partNumber.replace(/([\\[\\]\\.\\:\\#\"'])/g, '\\\\$1');

            const manualInput = document.querySelector(`#unpricedRecapBody .unpriced-manual-price[data-part-number="${escapedPart}"]`);
            if (manualInput instanceof HTMLInputElement) {
                manualInput.value = selectedPrice > 0 ? floatToInput(selectedPrice) : '';
            }

            const targetKey = normalizePartKey(partNumber);
            let updatedRows = 0;

            document.querySelectorAll('#materialTableBody tr').forEach((row) => {
                const partInput = row.querySelector('.part-no');
                const amountInput = row.querySelector('.amount1');
                const currencySelect = row.querySelector('.currency');
                const unitInput = row.querySelector('.unit-price-basis');
                const moqInput = row.querySelector('.qty-moq');
                const cnSelect = row.querySelector('.cn-type');
                const supplierInput = row.querySelector('.supplier');
                const importTaxInput = row.querySelector('.import-tax');

                if (!(partInput instanceof HTMLInputElement) || !(amountInput instanceof HTMLInputElement)) {
                    return;
                }

                if (normalizePartKey(partInput.value) !== targetKey) {
                    return;
                }

                amountInput.value = selectedPrice > 0 ? floatToInput(selectedPrice) : '0';

                if (currencySelect instanceof HTMLSelectElement && selectedCurrency) {
                    const hasOption = Array.from(currencySelect.options).some((opt) => opt.value === selectedCurrency);
                    if (hasOption) {
                        currencySelect.value = selectedCurrency;
                    }
                }
                
                if (unitInput && selectedUnit) {
                    unitInput.value = selectedUnit;
                }
                if (moqInput) {
                    moqInput.value = selectedMoq === null ? '' : floatToInput(selectedMoq);
                }
                if (cnSelect instanceof HTMLSelectElement && selectedCn) {
                    const hasOption = Array.from(cnSelect.options).some((opt) => opt.value === selectedCn);
                    if (hasOption) {
                        cnSelect.value = selectedCn;
                    }
                }
                if (supplierInput) {
                    supplierInput.value = selectedSupplier || '';
                }
                if (importTaxInput) {
                    importTaxInput.value = selectedImportTax === null ? '' : floatToInput(selectedImportTax);
                }

                calculateRow(amountInput);
                updatedRows += 1;
            });

            calculateTableTotal();
            if (!persistPrice) return true;

            if (manageLoading) showAppLoading('Menambahkan harga ke Material dan file hasil edit...');
            const saved = await syncManualPriceToServer(partNumber, selectedPrice, {
                purchase_unit: selectedUnit,
                currency: selectedCurrency,
                moq: selectedMoq,
                cn_type: selectedCn,
                maker: selectedSupplier,
                add_cost_percent: selectedImportTax,
                update_costing_edit: true,
            });
            if (saved) {
                const completedRow = document.querySelector(`#unpricedRecapBody tr[data-unpriced-part="${escapedPart}"]`);
                if (completedRow) completedRow.remove();
                renumberUnpricedRows();
                updateUnpricedSelectAllState();
            }

            if (manageLoading) {
                hideAppLoading();
                if (saved) openAppNotify(`Harga ${partNumber} berhasil ditambahkan.`, 'success');
            }

            return saved;
        }

        async function persistMaterialDraftBeforeBulk() {
            const form = document.getElementById('costingForm');
            if (!form) throw new Error('Form Costing tidak ditemukan.');

            const payload = buildMaterialSectionPayload(form);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: payload,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success !== true) {
                throw new Error(data.message || 'Baris Material gagal disimpan sebagai draft.');
            }

            const costingDataId = Number(data?.meta?.costing_data_id || 0);
            const costingDataInput = form.querySelector('[name="costing_data_id"]');
            if (costingDataInput && costingDataId > 0) costingDataInput.value = String(costingDataId);

            return costingDataId;
        }

        async function addSelectedUnpricedRows() {
            const selectedRows = Array.from(document.querySelectorAll('#unpricedRecapBody .unpriced-row-select:checked'))
                .map(checkbox => checkbox.closest('tr'))
                .filter(row => row instanceof HTMLTableRowElement);

            if (selectedRows.length === 0) {
                openAppNotify('Pilih minimal satu part yang akan ditambahkan.', 'warning');
                return;
            }

            const items = selectedRows.map(getUnpricedRowPriceData).filter(Boolean);
            const unavailable = items.filter(item => !item.partNumber || item.price <= 0);
            if (unavailable.length > 0) {
                const labels = unavailable.map(item => item.partNumber || '(tanpa nomor part)').join(', ');
                openAppNotify(`Harga belum tersedia untuk: ${labels}. Import New Part Request atau pilih harga terlebih dahulu.`, 'error');
                return;
            }

            openAppConfirm(`Tambahkan harga untuk ${items.length} part yang dipilih?`, async function () {
                showAppLoading('Menyimpan baris Material sebagai draft...');
                let savedCount = 0;
                const failedParts = [];

                try {
                    // Fill every matching Material row first, then persist the
                    // whole editor in one transaction. This guarantees there
                    // is a real MaterialBreakdown target before a part is
                    // marked resolved.
                    for (const item of items) {
                        await applySelectedMatchedPrice(
                            item.partNumber,
                            item.price,
                            item.currency,
                            item.unit,
                            item.moq,
                            item.cn,
                            item.supplier,
                            item.importTax,
                            false,
                            false
                        );
                    }
                    await persistMaterialDraftBeforeBulk();
                } catch (error) {
                    hideAppLoading();
                    openAppNotify(error.message || 'Material gagal disimpan. Tidak ada part yang dihilangkan.', 'error');
                    return;
                }

                for (const [index, item] of items.entries()) {
                    showAppLoading(`Menambahkan ${index + 1} dari ${items.length} part...`);
                    if (unpricedSyncTimers[item.partNumber]) {
                        clearTimeout(unpricedSyncTimers[item.partNumber]);
                        delete unpricedSyncTimers[item.partNumber];
                    }
                    const saved = await applySelectedMatchedPrice(
                        item.partNumber,
                        item.price,
                        item.currency,
                        item.unit,
                        item.moq,
                        item.cn,
                        item.supplier,
                        item.importTax,
                        false
                    );

                    if (saved) savedCount += 1;
                    else failedParts.push(item.partNumber);
                }

                if (failedParts.length === 0) {
                    hideAppLoading();
                    openAppNotify(`${savedCount} part berhasil ditambahkan.`, 'success');
                    return;
                }

                hideAppLoading();
                openAppNotify(`${savedCount} part berhasil. Gagal: ${failedParts.join(', ')}.`, 'error');
            });
        }

        function bindUnpricedManualPriceInputs() {
            const inputs = document.querySelectorAll('.unpriced-manual-price');
            inputs.forEach((input) => {
                if (input.dataset.boundRealtime === '1') {
                    return;
                }

                input.dataset.boundRealtime = '1';
                input.addEventListener('input', function () {
                    const partNumber = this.dataset.partNumber || '';
                    if (!partNumber) return;

                    if (unpricedSyncTimers[partNumber]) {
                        clearTimeout(unpricedSyncTimers[partNumber]);
                    }

                    unpricedSyncTimers[partNumber] = setTimeout(() => {
                        syncManualPriceToServer(partNumber, this.value);
                    }, 450);
                });
            });
        }

        function bindUnpricedDeleteButtons() {
            const buttons = document.querySelectorAll('.unpriced-delete-btn');
            buttons.forEach((button) => {
                if (button.dataset.boundDelete === '1') {
                    return;
                }

                button.dataset.boundDelete = '1';
                button.addEventListener('click', function () {
                    const partNumber = this.dataset.partNumber || '';
                    if (!partNumber) return;

                    openAppConfirm(`Hapus part tanpa harga "${partNumber}"?`, function() {
                        removeUnpricedRow(partNumber);
                    });
                });
            });

            document.querySelectorAll('#unpricedRecapBody .unpriced-row-select').forEach(cb => {
                if (cb.dataset.boundChange === '1') return;
                cb.dataset.boundChange = '1';
                cb.addEventListener('change', updateUnpricedSelectAllState);
            });
        }

        function removeUnpricedRow(partNumber) {
            const row = document.querySelector(`#unpricedRecapBody tr[data-unpriced-part="${CSS.escape(partNumber)}"]`);
            if (row) {
                row.remove();
            }
            renumberUnpricedRows();
            updateUnpricedSelectAllState();

            // Also sync to server if URL available
            deleteUnpricedPartFromServer(partNumber);
        }

        function deleteUnpricedPartFromServer(partNumber) {
            const trackingRevisionId = document.getElementById('trackingRevisionId')?.value || '';
            const url = document.getElementById('deleteUnpricedPartUrl')?.value || '';
            if (!trackingRevisionId || !url) return Promise.resolve();

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ part_number: partNumber })
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.ok) {
                    updateUnpricedBanner(data.open_unpriced_count || 0);
                }
            })
            .catch(() => {});
        }

        function updateUnpricedBanner(openCount) {
            const banner = document.getElementById('unpricedTopBanner');
            const bannerText = document.getElementById('unpricedTopBannerText');
            if (banner) banner.style.display = openCount > 0 ? 'flex' : 'none';
            if (bannerText) bannerText.textContent = `Terdapat ${openCount} part yang belum memiliki harga pada versi dokumen ini.`;
        }

        function toggleAllUnpricedRowCheckboxes(checked) {
            document.querySelectorAll('#unpricedRecapBody .unpriced-row-select').forEach(cb => {
                cb.checked = checked;
            });
            updateUnpricedSelectAllState();
        }

        function updateUnpricedSelectAllState() {
            const all = document.querySelectorAll('#unpricedRecapBody .unpriced-row-select');
            const checked = document.querySelectorAll('#unpricedRecapBody .unpriced-row-select:checked');
            const selectAll = document.getElementById('unpricedSelectAll');
            if (selectAll) {
                selectAll.checked = all.length > 0 && all.length === checked.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }

            const addButton = document.getElementById('unpricedAddSelectedBtn');
            const deleteButton = document.getElementById('unpricedDeleteSelectedBtn');
            if (addButton) {
                addButton.textContent = checked.length > 0 ? `Tambah Terpilih (${checked.length})` : 'Tambah Terpilih';
                addButton.disabled = checked.length === 0;
            }
            if (deleteButton) {
                deleteButton.textContent = checked.length > 0 ? `Hapus Terpilih (${checked.length})` : 'Hapus Terpilih';
                deleteButton.disabled = checked.length === 0;
            }
        }

        function renumberUnpricedRows() {
            const rows = document.querySelectorAll('#unpricedRecapBody tr[data-unpriced-part]');
            rows.forEach((row, idx) => {
                const numSpan = row.querySelector('.unpriced-row-select')?.parentElement?.querySelector('span');
                if (numSpan) numSpan.textContent = idx + 1;
            });
            if (rows.length === 0) {
                const tbody = document.getElementById('unpricedRecapBody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="15" style="text-align: center; color: var(--slate-500);">Belum ada part tanpa harga untuk versi dokumen ini.</td></tr>';
                }
            }
        }

        function deleteSelectedUnpricedRows() {
            const selectedRows = Array.from(document.querySelectorAll('#unpricedRecapBody .unpriced-row-select:checked'))
                .map(cb => cb.closest('tr'))
                .filter(row => row instanceof HTMLTableRowElement);

            if (selectedRows.length === 0) return;

            openAppConfirm(
                `Hapus ${selectedRows.length} baris yang dipilih?`,
                function() {
                    showAppLoading('Menghapus...');
                    const partNumbers = selectedRows
                        .map(row => row.dataset.unpricedPart || '')
                        .filter(p => p !== '');

                    // Optimistically remove rows from DOM immediately
                    selectedRows.forEach(row => row.remove());
                    renumberUnpricedRows();
                    updateUnpricedSelectAllState();

                    const bulkUrl = document.getElementById('bulkDeleteUnpricedUrl')?.value || '';
                    const deleteUrl = document.getElementById('deleteUnpricedPartUrl')?.value || '';

                    if (bulkUrl && partNumbers.length > 0) {
                        // Single bulk request
                        fetch(bulkUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ part_numbers: partNumbers })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.ok) {
                                updateUnpricedBanner(data.open_unpriced_count || 0);
                            }
                            hideAppLoading();
                        })
                        .catch(() => {
                            hideAppLoading();
                        });
                    } else if (deleteUrl && partNumbers.length > 0) {
                        // Fallback: single delete for each (old endpoint)
                        Promise.all(partNumbers.map(pn =>
                            fetch(deleteUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ part_number: pn })
                            }).catch(() => {})
                        )).then(() => hideAppLoading());
                    } else {
                        hideAppLoading();
                    }
                }
            );
        }

        function syncManualPriceToServer(partNumber, value, details = {}) {
            const trackingRevisionId = document.getElementById('trackingRevisionId')?.value || '';
            const url = document.getElementById('updateUnpricedPriceUrl')?.value || '';

            if (!trackingRevisionId || !url) {
                return Promise.resolve(false);
            }

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    part_number: partNumber,
                    manual_price: value === '' ? null : Number(value),
                    ...details,
                })
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data || data.ok !== true) {
                        throw new Error(data?.message || 'Harga gagal disimpan.');
                    }

                    const banner = document.getElementById('unpricedTopBanner');
                    const bannerText = document.getElementById('unpricedTopBannerText');
                    const openCount = Number(data.open_unpriced_count || 0);

                    if (banner) {
                        banner.style.display = openCount > 0 ? 'flex' : 'none';
                    }

                    if (bannerText) {
                        bannerText.textContent = `Terdapat ${openCount} part yang belum memiliki harga pada versi dokumen ini.`;
                    }
                    return true;
                })
                .catch((error) => {
                    if (details.update_costing_edit) {
                        openAppNotify(error.message || 'Harga gagal disimpan.', 'error');
                    }
                    return false;
                });
        }

        // deleteUnpricedPart replaced by removeUnpricedRow + deleteUnpricedPartFromServer



        // Calculate totals for Resume COGM
        function calculateTotals(recalculateMaterialTable = true) {
            const materialCost = getResumeMoneyValue('materialCost');
            const laborCost = getResumeMoneyValue('laborCost');
            const overheadCost = getResumeMoneyValue('overheadCost');
            const scrapCost = getResumeMoneyValue('scrapCost');
            const cogmTotal = materialCost + laborCost + overheadCost + scrapCost;

            document.getElementById('calcTotalMaterialCost').textContent = formatRupiah(materialCost);
            document.getElementById('calcProcessCost').textContent = formatRupiah(laborCost);
            document.getElementById('calcToolingCost').textContent = formatRupiah(overheadCost);
            document.getElementById('calcAdministrasiCost').textContent = formatRupiah(scrapCost);
            document.getElementById('calcCogsTotal').textContent = formatRupiah(cogmTotal);

            updateCostingResumePreview();

            // Revalidate material cost
            if (recalculateMaterialTable) {
                calculateTableTotal(false);
            }
        }

        function syncCostingResumeOverridesInput() {
            const input = document.getElementById('costingResumeOverridesInput');
            if (input) {
                input.value = JSON.stringify(costingResumeOverrides || {});
            }
        }

        function makeCostingResumeFieldsEditable() {
            document.querySelectorAll('[data-cr-field]').forEach((element) => {
                element.setAttribute('contenteditable', 'true');
                element.setAttribute('spellcheck', 'false');
                element.classList.add('costing-resume-editable');
                element.title = 'Klik untuk edit manual. Nilai manual tersimpan saat Update/Simpan.';

                if (element.dataset.crBound === '1') return;
                element.dataset.crBound = '1';

                element.addEventListener('input', () => {
                    const key = element.dataset.crField;
                    if (!key) return;
                    costingResumeOverrides[key] = element.textContent.trim();
                    syncCostingResumeOverridesInput();
                });
            });
        }

        function applyCostingResumeOverridesToPreview() {
            document.querySelectorAll('[data-cr-field]').forEach((element) => {
                const key = element.dataset.crField;
                if (key && Object.prototype.hasOwnProperty.call(costingResumeOverrides, key)) {
                    element.textContent = costingResumeOverrides[key];
                }
            });

            makeCostingResumeFieldsEditable();
            syncCostingResumeOverridesInput();
        }

        function resetCostingResumeOverrides() {
            if (!confirm('Reset semua edit manual Costing Resume dan kembali ke nilai otomatis?')) {
                return;
            }

            costingResumeOverrides = {};
            formatAllRateInputs();
            syncCostingResumeOverridesInput();
            updateCostingResumePreview();
        }
        function costingResumeSetText(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }

        function costingResumePlainNumber(value, maximumFractionDigits = 2) {
            const number = Number(value) || 0;
            return number.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits
            });
        }

        function costingResumeMoney(value) {
            const formatted = formatRupiah(Number(value) || 0).replace(/^Rp\s?/, '');
            return formatted;
        }

        function costingResumePercent(value, total) {
            const pct = total > 0 ? ((Number(value) || 0) / total) * 100 : 0;
            return `${Math.round(pct)}%`;
        }

        function costingResumeFieldValue(selector) {
            const field = document.querySelector(selector);
            if (!field) return '-';

            if (field.tagName === 'SELECT') {
                const option = field.options[field.selectedIndex];
                const text = option ? option.textContent.trim() : '';
                return text.replace(/^.*?\s-\s/, '').trim() || '-';
            }

            return String(field.value || '').trim() || '-';
        }

        function costingResumeClassifyMaterial(name) {
            const text = String(name || '').toLowerCase();
            if (text.includes('wire') || text.includes('cable')) return 'Wire';
            if (text.includes('connector') || text.includes('conn') || text.includes('housing')) return 'Connector';
            if (text.includes('terminal') || text.includes('term')) return 'Terminal';
            if (text.includes('tube') || text.includes('tubing')) return 'Tube';
            if (text.includes('tape')) return 'Tape';
            return 'Accessories';
        }

        function costingResumeCollectMaterials(ownerType) {
            const categories = ['Wire', 'Connector', 'Terminal', 'Tube', 'Tape', 'Accessories'];

            // Keep the CUSTOMER section available, but consolidate every material
            // row into DEM regardless of its C/N value.
            if (ownerType === 'customer') {
                return [];
            }

            const grouped = categories.reduce((acc, category) => {
                acc[category] = new Map();
                return acc;
            }, {});

            document.querySelectorAll('#materialTableBody tr').forEach((row) => {
                const partName = row.querySelector('.part-name')?.value || row.querySelector('.part-name')?.textContent || '';
                const qty = parseInputNumber(row.querySelector('.qty-req')?.value || 0);
                const unit = (row.querySelector('.unit')?.value || row.querySelector('.unit')?.textContent || '').trim().toLowerCase();
                const unitKey = unit || '-';
                const totalElement = row.querySelector('.total-price');
                const hasServerTotal = row.dataset.serverTotal !== undefined && row.dataset.serverTotal !== '';
                const amount = hasServerTotal
                    ? (Number(row.dataset.serverTotal) || 0)
                    : (totalElement ? parseDataValueNumber(totalElement.getAttribute('data-value') || totalElement.textContent || 0) : 0);

                // Ignore unused input rows; otherwise a blank row is incorrectly
                // classified as an additional Accessories row.
                if (!String(partName).trim() && qty <= 0 && amount <= 0) {
                    return;
                }

                const category = costingResumeClassifyMaterial(partName);

                if (!grouped[category].has(unitKey)) {
                    const safeUnitKey = unitKey.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '') || 'none';
                    grouped[category].set(unitKey, {
                        name: category,
                        fieldKey: `${category}.${safeUnitKey}`,
                        qty: 0,
                        units: new Set(),
                        amount: 0
                    });
                }

                const categoryUnitRow = grouped[category].get(unitKey);
                categoryUnitRow.qty += qty;
                categoryUnitRow.amount += amount;
                if (unit) categoryUnitRow.units.add(unit);
            });

            return categories.flatMap((category) => {
                return Array.from(grouped[category].values())
                    .filter((row) => row.qty > 0 || row.amount > 0);
            });
        }

        function costingResumeMaterialRow(row, materialTotal, ownerType) {
            const unit = row.units.size === 0 ? '-' : Array.from(row.units)[0];
            const qty = row.qty > 0 ? costingResumePlainNumber(row.qty, 3) : '-';
            const amount = row.amount > 0 ? costingResumeMoney(row.amount) : '-';
            const pct = costingResumePercent(row.amount, materialTotal);
            return `
                <tr>
                    <td data-cr-field="material.${ownerType}.${row.fieldKey}.name">${row.name}</td>
                    <td class="text-right" data-cr-field="material.${ownerType}.${row.fieldKey}.qty">${qty}</td>
                    <td data-cr-field="material.${ownerType}.${row.fieldKey}.unit">${unit}</td>
                    <td class="text-right" data-cr-field="material.${ownerType}.${row.fieldKey}.amount">Rp&nbsp;${amount}</td>
                    <td class="text-right" data-cr-field="material.${ownerType}.${row.fieldKey}.pct">${pct}</td>
                </tr>
            `;
        }

        function updateCostingResumePreview() {
            if (!document.getElementById('costingResumeMaterialBody')) return;

            const materialCost = getResumeMoneyValue('materialCost');
            const processCost = getResumeMoneyValue('laborCost');
            const toolingCost = getResumeMoneyValue('overheadCost');
            const adminCost = getResumeMoneyValue('scrapCost');
            const calculatedCogmTotal = materialCost + processCost + toolingCost + adminCost;
            const cogmTotal = submittedCogmValue ?? calculatedCogmTotal;
            const forecast = parsePositiveInteger(document.getElementById('forecast')?.value || document.getElementById('forecastDisplay')?.value || 0);
            const projectLifeYears = parseInputNumber(document.getElementById('projectPeriod')?.value || 0);
            const periodMonths = projectLifeYears > 0 ? projectLifeYears * 12 : 0;
            const totalCycleSec = parseCycleNumber(document.getElementById('cycleTotalSec')?.textContent || 0);
            const totalCycleHour = parseCycleNumber(document.getElementById('cycleTotalHour')?.textContent || 0);
            const selectedRate = document.getElementById('exchangeRateSelector')?.selectedOptions?.[0]?.textContent?.trim() || 'Input manual';
            const rateTitle = selectedRate.split('|')[0]?.trim() || 'Input manual';

            costingResumeSetText('crCustomer', costingResumeFieldValue('select[name="customer_id"]'));
            costingResumeSetText('crModel', costingResumeFieldValue('input[name="model"]'));
            costingResumeSetText('crAssyNo', costingResumeFieldValue('input[name="assy_no"]'));
            costingResumeSetText('crAssyName', costingResumeFieldValue('input[name="assy_name"]'));
            costingResumeSetText('crCct', costingResumePlainNumber(totalCycleSec, 0));
            costingResumeSetText('crForecast', costingResumePlainNumber(forecast, 0));
            costingResumeSetText('crPeriod', costingResumePlainNumber(periodMonths, 0));
            costingResumeSetText('crRateTitle', `Rate Request ${rateTitle}`.trim());
            costingResumeSetText('crRateUsd', costingResumePlainNumber(parseInputNumber(document.getElementById('rateUSD')?.value || 0), 2));
            costingResumeSetText('crRateJpy', costingResumePlainNumber(parseInputNumber(document.getElementById('rateJPY')?.value || 0), 2));
            costingResumeSetText('crRateLme', costingResumePlainNumber(parseInputNumber(document.getElementById('lmeRate')?.value || 0), 2));
            costingResumeSetText('crLabourCost', costingResumeMoney(processCost));

            const demRows = costingResumeCollectMaterials('dem');
            const customerRows = costingResumeCollectMaterials('customer');
            const demTotal = demRows.reduce((sum, row) => sum + row.amount, 0);
            const customerTotal = customerRows.reduce((sum, row) => sum + row.amount, 0);
            const tbody = document.getElementById('costingResumeMaterialBody');
            tbody.innerHTML = `
                <tr class="section-row"><td colspan="5">Material by DEM</td></tr>
                ${demRows.map((row) => costingResumeMaterialRow(row, materialCost, 'dem')).join('')}
                <tr><td><strong data-cr-field="material.dem.total.label">TOTAL MATERIAL DEM</strong></td><td></td><td></td><td class="text-right"><strong data-cr-field="material.dem.total.amount">Rp&nbsp;${costingResumeMoney(demTotal)}</strong></td><td class="text-right"><strong data-cr-field="material.dem.total.pct">${costingResumePercent(demTotal, materialCost)}</strong></td></tr>
                <tr class="section-row"><td colspan="5">Material by CUSTOMER</td></tr>
                ${customerRows.map((row) => costingResumeMaterialRow(row, materialCost, 'customer')).join('')}
                <tr><td><strong data-cr-field="material.customer.total.label">TOTAL MATERIAL CUSTOMER</strong></td><td></td><td></td><td class="text-right"><strong data-cr-field="material.customer.total.amount">Rp&nbsp;${costingResumeMoney(customerTotal)}</strong></td><td class="text-right"><strong data-cr-field="material.customer.total.pct">${costingResumePercent(customerTotal, materialCost)}</strong></td></tr>
            `;

            costingResumeSetText('crTotalMaterialCost', costingResumeMoney(materialCost));
            costingResumeSetText('crTotalMaterialPct', costingResumePercent(materialCost, cogmTotal));
            costingResumeSetText('crProcessHour', `${costingResumePlainNumber(totalCycleHour, 2)} hour`);
            costingResumeSetText('crProcessCost', costingResumeMoney(processCost));
            costingResumeSetText('crProcessPct', costingResumePercent(processCost, cogmTotal));
            costingResumeSetText('crToolingCost', costingResumeMoney(toolingCost));
            costingResumeSetText('crToolingPct', costingResumePercent(toolingCost, cogmTotal));
            costingResumeSetText('crAdminCost', costingResumeMoney(adminCost));
            costingResumeSetText('crAdminPct', costingResumePercent(adminCost, cogmTotal));
            costingResumeSetText('crCogmTotal', costingResumeMoney(cogmTotal));
            costingResumeSetText('crCogmPct', cogmTotal > 0 ? '100%' : '0%');
            costingResumeSetText('crPotentialSalesMonth', costingResumeMoney(cogmTotal * forecast));
            costingResumeSetText('crPotentialSalesLifetime', costingResumeMoney(cogmTotal * forecast * periodMonths));
            costingResumeSetText('crRpPerCct', costingResumeMoney(totalCycleSec > 0 ? cogmTotal / totalCycleSec : 0));

            const estimatedMp = totalCycleSec > 0 && forecast > 0
                ? Math.ceil((totalCycleSec * forecast) / (60 * 60 * 8 * 22))
                : 0;
            costingResumeSetText('crEstimatedMp', costingResumePlainNumber(estimatedMp, 0));
            applyCostingResumeOverridesToPreview();
        }
        // Update material info when dropdown changes
        function updateMaterialInfo(select) {
            const row = select.closest('tr');
            const option = select.options[select.selectedIndex];

            row.querySelector('.id-code').textContent = option.dataset.idcode || '';
            row.querySelector('.part-name').textContent = option.dataset.partname || '';
            row.querySelector('.unit').textContent = option.dataset.unit || 'PCS';
            row.querySelector('.pro-code').textContent = option.dataset.procode || '';
            row.querySelector('.supplier').textContent = option.dataset.supplier || '';

            calculateRow(select);
        }

        // Add new material row
        function addMaterialRow() {
            const beforeSnapshot = getMaterialStateSnapshot();
            const tbody = document.getElementById('materialTableBody');
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-row', rowCounter);

            newRow.innerHTML = `
                                    <td><span class="material-row-no-cell"><input type="checkbox" class="material-row-select" title="Pilih baris"><span class="material-row-number">${rowCounter + 1}</span></span></td>
                                    <td><input type="text" class="form-input part-no" name="materials[${rowCounter}][part_no]" value="" placeholder="Part No"></td>
                                    <td><input type="text" class="form-input id-code" name="materials[${rowCounter}][id_code]" value="" placeholder="ID Code"></td>
                                    <td><input type="text" class="form-input part-name" name="materials[${rowCounter}][part_name]" value="" placeholder="Part Name"></td>
                                    <td><input type="text" class="form-input w-28 qty-req number-format" name="materials[${rowCounter}][qty_req]" value="0" step="1" min="0" onchange="calculateRow(this)"></td>
                                    <td><input type="text" class="form-input unit" name="materials[${rowCounter}][unit]" value="PCS" placeholder="Unit"></td>
                                    <td><input type="text" class="form-input pro-code" name="materials[${rowCounter}][pro_code]" value="" placeholder="Pro Code"></td>
                                    <td><input type="text" class="form-input amount1 number-format" name="materials[${rowCounter}][amount1]" value="0" step="0.0001" onchange="calculateRow(this)"></td>
                                    <td><input type="text" class="form-input unit-price-basis" name="materials[${rowCounter}][unit_price_basis]" value="" placeholder="Unit Price" onchange="calculateRow(this)"></td>
                                    <td><select class="form-select currency" name="materials[${rowCounter}][currency]" onchange="calculateRow(this)"><option value="IDR">IDR</option><option value="USD">USD</option><option value="JPY">JPY</option></select></td>
                                    <td><input type="text" class="form-input w-28 qty-moq number-format" name="materials[${rowCounter}][qty_moq]" value="0" step="0.0001" onchange="calculateRow(this)"></td>
                                    <td><select class="form-select cn-type" name="materials[${rowCounter}][cn_type]" onchange="calculateRow(this)"><option value="N">N</option><option value="C">C</option><option value="E">E</option></select></td>
                                    <td><input type="text" class="form-input supplier" name="materials[${rowCounter}][supplier]" value="" placeholder="Supplier"></td>
                                    <td><input type="text" class="form-input import-tax number-format" name="materials[${rowCounter}][import_tax]" value="0" onchange="calculateRow(this)"></td>
                                    <td class="calculated multiply-factor">1</td>
                        <td class="calculated amount2">0.0000</td>
                        <td class="calculated currency2">IDR</td>
                        <td class="calculated unit-price2">PCS</td>
                                    <td class="calculated total-price">Rp 0</td>
                                    <td><button type="button" class="btn btn-secondary" onclick="removeRow(this)" style="padding: 0.5rem;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></td>
                                `;

            tbody.appendChild(newRow);
            materialStructureDirty = true;
            rowCounter++;
            renumberRows();

            const afterSnapshot = getMaterialStateSnapshot();
            pushMaterialHistoryAction({
                type: 'snapshot',
                before: beforeSnapshot,
                after: afterSnapshot,
            });
            applyMaterialFilters();
            updateMaterialSelectAllRowsState();
        }

        // Remove row
        function removeRow(button) {
            const beforeSnapshot = getMaterialStateSnapshot();
            const row = button.closest('tr');
            row.remove();
            materialStructureDirty = true;
            renumberRows();
            calculateTableTotal();
            refreshUnpricedRecap();

            const afterSnapshot = getMaterialStateSnapshot();
            pushMaterialHistoryAction({
                type: 'snapshot',
                before: beforeSnapshot,
                after: afterSnapshot,
            });
            applyMaterialFilters();
        }

        // Renumber rows
        function renumberRows() {
            const rows = document.querySelectorAll('#materialTableBody tr');
            rows.forEach((row, index) => {
                const numberEl = row.querySelector('.material-row-number');
                if (numberEl) {
                    numberEl.textContent = String(index + 1);
                } else if (row.cells[0]) {
                    row.cells[0].textContent = index + 1;
                }
            });

            updateMaterialSelectAllRowsState();
        }

        function updateMaterialSelectAllRowsState() {
            const master = document.getElementById('materialSelectAllRows');
            if (!(master instanceof HTMLInputElement)) {
                return;
            }

            const rowCheckboxes = Array.from(document.querySelectorAll('#materialTableBody .material-row-select'));
            if (rowCheckboxes.length === 0) {
                master.checked = false;
                master.indeterminate = false;
                return;
            }

            const checkedCount = rowCheckboxes.filter((cb) => cb instanceof HTMLInputElement && cb.checked).length;
            master.checked = checkedCount === rowCheckboxes.length;
            master.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        }

        function deleteSelectedMaterialRows() {
            const selectedRows = Array.from(document.querySelectorAll('#materialTableBody .material-row-select:checked'))
                .map((cb) => cb.closest('tr'))
                .filter((row) => row instanceof HTMLTableRowElement);

            if (selectedRows.length === 0) {
                return;
            }

            openAppConfirm(
                'Hapus ' + selectedRows.length + ' baris yang dipilih?',
                function () {
                    const beforeSnapshot = getMaterialStateSnapshot();
                    selectedRows.forEach((row) => row.remove());
                    materialStructureDirty = true;

                    renumberRows();
                    calculateTableTotal();
                    refreshUnpricedRecap();

                    const afterSnapshot = getMaterialStateSnapshot();
                    pushMaterialHistoryAction({
                        type: 'snapshot',
                        before: beforeSnapshot,
                        after: afterSnapshot,
                    });

                    applyMaterialFilters();
                    updateMaterialSelectAllRowsState();

                    // Auto-save to persist deletion via AJAX, then reload
                    submitMaterialSectionAjax();
                }
            );
        }

        function collectMaterialRowsForPayload(form = document.getElementById('costingForm')) {
            const rows = [];
            const materialRows = form ? form.querySelectorAll('#materialTableBody tr') : [];

            materialRows.forEach((row, visualIndex) => {
                const material = {
                    __row_index: visualIndex,
                    __row_no: visualIndex + 1,
                    __dirty: row.dataset.materialDirty === '1',
                };

                row.querySelectorAll('input, select, textarea').forEach((control) => {
                    const match = (control.name || '').match(/^materials\[(\d+)\]\[(.+)\]$/);
                    if (!match) return;
                    material[match[2]] = control.value;
                });

                if (Object.keys(material).length > 3) {
                    rows.push(material);
                }
            });

            return rows;
        }

        function collectCycleRowsForExcel() {
            return Array.from(document.querySelectorAll('#cycleTimeTableBody tr')).map((row, index) => ({
                no: index + 1,
                process: row.querySelector('.ct-process')?.value || '',
                qty: parseCycleNumber(row.querySelector('.ct-qty')?.value || 0),
                time_hour: parseCycleNumber(row.querySelector('.ct-hour')?.value || 0),
                time_sec: parseCycleNumber(row.querySelector('.ct-sec')?.value || 0),
                time_sec_per_qty: parseCycleNumber(row.querySelector('.ct-sec-per')?.value || 0),
                cost_per_sec: parseCycleNumber(row.querySelector('.ct-cost-sec')?.value || 0),
                cost_per_unit: parseCycleNumber(row.querySelector('.ct-cost-unit')?.value || 0),
            }));
        }

        function safeExportFileName(value, fallback) {
            const cleaned = String(value || '').replace(/[<>:"/\\|?*\u0000-\u001F]/g, '-').trim();
            return cleaned || fallback;
        }

        async function chooseExportDestination(suggestedName) {
            if (typeof window.showSaveFilePicker !== 'function') {
                return { supported: false, handle: null, cancelled: false };
            }

            try {
                const handle = await window.showSaveFilePicker({
                    suggestedName: safeExportFileName(suggestedName, 'export.xlsx'),
                    types: [{
                        description: 'Excel Workbook',
                        accept: { 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['.xlsx'] },
                    }],
                });
                return { supported: true, handle, cancelled: false };
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return { supported: true, handle: null, cancelled: true };
                }
                return { supported: false, handle: null, cancelled: false };
            }
        }

        async function saveExportBlob(blob, filename, destination) {
            if (destination?.handle) {
                const writable = await destination.handle.createWritable();
                await writable.write(blob);
                await writable.close();
                return;
            }

            const downloadUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(downloadUrl);
        }

        async function exportMaterialEditor(exportMode = 'editor') {
            const ratesConfirmed = await showExportRatesConfirmModal();
            if (!ratesConfirmed) return;

            const assyNo = document.querySelector('#costingForm [name="assy_no"]')?.value || '';
            const customerCode = document.querySelector('#costingForm [name="customer_id"]')?.selectedOptions?.[0]?.dataset?.code || '';
            const suggestedName = exportMode === 'cogm'
                ? safeExportFileName(`COGM ${assyNo} - ${customerCode}.xlsx`, 'COGM.xlsx')
                : safeExportFileName(`cogm. ${assyNo} - ${customerCode}.xlsx`, 'Export-costing-edit.xlsx');
            const destination = await chooseExportDestination(suggestedName);
            if (destination.cancelled) return;

            const url = document.getElementById('materialExcelExportUrl')?.value || '';
            const token = document.querySelector('#costingForm input[name="_token"]')?.value || '';
            const rows = collectMaterialRowsForPayload();

            if (!url || rows.length === 0) {
                openAppNotify('Belum ada baris Material yang dapat diexport.', 'info');
                return;
            }

            showAppLoading('Membuat file Excel Material...');
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    },
                    body: JSON.stringify({
                        materials_json: JSON.stringify(rows),
                        cycle_times_json: JSON.stringify(collectCycleRowsForExcel()),
                        tracking_revision_id: document.querySelector('#costingForm [name="tracking_revision_id"]')?.value || '',
                        costing_data_id: document.querySelector('#costingForm [name="costing_data_id"]')?.value || '',
                        assy_no: document.querySelector('#costingForm [name="assy_no"]')?.value || '',
                        assy_name: document.querySelector('#costingForm [name="assy_name"]')?.value || '',
                        customer: (() => {
                            const text = document.querySelector('#costingForm [name="customer_id"]')?.selectedOptions?.[0]?.textContent?.trim() || '';
                            return text.includes(' - ') ? text.split(' - ').slice(1).join(' - ').trim() : text;
                        })(),
                        customer_code: document.querySelector('#costingForm [name="customer_id"]')?.selectedOptions?.[0]?.dataset?.code || '',
                        model: document.querySelector('#costingForm [name="model"]')?.value || '',
                        project_date: document.getElementById('exportProjectDate')?.value || '',
                        sop_mp_date: document.getElementById('exportSopMpDate')?.value || '',
                        forecast: document.getElementById('forecast')?.value || '0',
                        project_period: document.getElementById('projectPeriod')?.value || '0',
                        plant: document.querySelector('#costingForm [name="line"]')?.selectedOptions?.[0]?.textContent?.trim() || '',
                        rate_usd: parseRateInputValue(document.getElementById('rateUSD')?.value || 0),
                        rate_jpy: parseRateInputValue(document.getElementById('rateJPY')?.value || 0),
                        rate_idr: parseRateInputValue(document.getElementById('rateIDR')?.value || 1),
                        rate_lme: parseRateInputValue(document.getElementById('lmeRate')?.value || 0),
                        rate_period: document.getElementById('exchangeRateSelector')?.selectedOptions?.[0]?.dataset?.period
                            || document.getElementById('periodInput')?.value
                            || '',
                        export_mode: exportMode,
                    }),
                });
                if (!response.ok) {
                    const errorText = await response.text();
                    let message = 'File Excel gagal dibuat.';
                    try {
                        const errorData = JSON.parse(errorText);
                        message = errorData.message || message;
                    } catch (error) {
                        if (response.status === 500) {
                            message = 'Proses export dihentikan server. Silakan coba kembali; template besar dapat membutuhkan sekitar 30–60 detik.';
                        }
                    }
                    throw new Error(message);
                }

                const blob = await response.blob();
                const exportedAssyCount = Number(response.headers.get('X-Costing-Assy-Count') || 1);
                const disposition = response.headers.get('Content-Disposition') || '';
                const filenameMatch = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                let filename = exportMode === 'cogm' ? 'COGM.xlsx' : 'Export-costing-edit.xlsx';
                if (filenameMatch != null && filenameMatch[1]) { 
                    filename = filenameMatch[1].replace(/['"]/g, '');
                }
                await saveExportBlob(blob, filename, destination);
                const exportedFileName = document.getElementById('materialExportFileName');
                if (exportedFileName) {
                    exportedFileName.textContent = filename;
                    exportedFileName.parentElement.title = filename;
                }
                openAppNotify(
                    exportedAssyCount > 1
                        ? `Workbook gabungan ${exportedAssyCount} assy berhasil diexport.`
                        : rows.length + ' baris Material berhasil diexport.',
                    'success'
                );
            } catch (error) {
                openAppNotify(error.message || 'Export Excel gagal.', 'error');
            } finally {
                hideAppLoading();
            }
        }

        function showExportRatesConfirmModal() {
            return new Promise((resolve) => {
                const modal = document.getElementById('exportRatesConfirmModal');
                const okBtn = document.getElementById('exportRatesOkBtn');
                const cancelBtn = document.getElementById('exportRatesCancelBtn');
                if (!modal || !okBtn || !cancelBtn) { resolve(false); return; }
                const closeWith = (result) => {
                    modal.classList.add('is-hidden');
                    modal.setAttribute('aria-hidden', 'true');
                    okBtn.removeEventListener('click', handleOk);
                    cancelBtn.removeEventListener('click', handleCancel);
                    modal.removeEventListener('click', handleOverlay);
                    document.removeEventListener('keydown', handleEsc);
                    resolve(result);
                };
                const handleOk = () => closeWith(true);
                const handleCancel = () => closeWith(false);
                const handleOverlay = (event) => { if (event.target === modal) closeWith(false); };
                const handleEsc = (event) => { if (event.key === 'Escape') closeWith(false); };
                modal.classList.remove('is-hidden');
                modal.setAttribute('aria-hidden', 'false');
                okBtn.addEventListener('click', handleOk);
                cancelBtn.addEventListener('click', handleCancel);
                modal.addEventListener('click', handleOverlay);
                document.addEventListener('keydown', handleEsc);
            });
        }

        function openMaterialDownloadConfirm(trigger) {
            const modal = document.getElementById('materialDownloadConfirmModal');
            const downloadBtn = document.getElementById('materialDownloadOkBtn');
            const cancelBtn = document.getElementById('materialDownloadCancelBtn');
            const fileName = document.getElementById('materialDownloadFileName');
            if (!modal || !downloadBtn || !cancelBtn) return;

            const href = trigger.dataset.downloadUrl || '';
            const displayedName = trigger.querySelector('strong')?.textContent?.trim() || 'file hasil edit';
            if (fileName) fileName.textContent = displayedName;

            const close = () => {
                modal.classList.add('is-hidden');
                modal.setAttribute('aria-hidden', 'true');
                downloadBtn.removeEventListener('click', download);
                cancelBtn.removeEventListener('click', cancel);
                modal.removeEventListener('click', closeFromOverlay);
                document.removeEventListener('keydown', closeFromEscape);
            };
            const download = () => {
                close();
                const downloadFrame = document.createElement('iframe');
                downloadFrame.hidden = true;
                downloadFrame.src = href;
                downloadFrame.title = 'Download file hasil edit';
                document.body.appendChild(downloadFrame);
                window.setTimeout(() => downloadFrame.remove(), 60000);
            };
            const cancel = () => close();
            const closeFromOverlay = (overlayEvent) => {
                if (overlayEvent.target === modal) close();
            };
            const closeFromEscape = (keyEvent) => {
                if (keyEvent.key === 'Escape') close();
            };

            modal.classList.remove('is-hidden');
            modal.setAttribute('aria-hidden', 'false');
            downloadBtn.addEventListener('click', download);
            cancelBtn.addEventListener('click', cancel);
            modal.addEventListener('click', closeFromOverlay);
            document.addEventListener('keydown', closeFromEscape);
            cancelBtn.focus();
        }

        async function exportNewPartRequestAndRefresh(url, syncUrl) {
            if (!url) return;

            const assyNo = document.querySelector('#costingForm [name="assy_no"]')?.value || '';
            const customerCode = document.querySelector('#costingForm [name="customer_id"]')?.selectedOptions?.[0]?.dataset?.code || 'CUSTOMER';
            const now = new Date();
            const exportDate = [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('.');
            const safeAssyNo = String(assyNo || 'NEW-PART').replace(/[^A-Za-z0-9_-]/g, '_');
            const destination = await chooseExportDestination(
                safeExportFileName(`${exportDate} ${String(customerCode).toUpperCase()} - ${safeAssyNo}.xlsx`, 'New-Part-Request.xlsx')
            );
            if (destination.cancelled) return;

            showAppLoading('Memperbarui rekapan dan membuat New Part Request...');
            try {
                const materialRows = collectMaterialRowsForPayload().map(row => ({
                    part_no: row.part_no || '', id_code: row.id_code || '',
                    part_name: row.part_name || '', amount1: row.amount1 ?? '',
                }));
                const syncResponse = await fetch(syncUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json', 'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({materials: materialRows}),
                });
                const syncResult = await syncResponse.json().catch(() => ({}));
                if (!syncResponse.ok || syncResult.success === false) {
                    throw new Error(syncResult.message || 'Rekapan part tanpa harga gagal diperbarui.');
                }
                const response = await fetch(url, {
                    method: 'GET',
                    headers: { 'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
                });
                if (!response.ok) {
                    throw new Error('Export New Part Request gagal.');
                }

                const blob = await response.blob();
                const disposition = response.headers.get('Content-Disposition') || '';
                const filenameMatch = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                const filename = filenameMatch?.[1]?.replace(/['"]/g, '') || 'New-Part-Request.xlsx';
                await saveExportBlob(blob, filename, destination);

                window.location.reload();
            } catch (error) {
                hideAppLoading();
                openAppNotify(error.message || 'Export New Part Request gagal.', 'error');
            }
        }

        async function importNewPartRequest(input) {
            const file = input?.files?.[0];
            if (!file) return;

            const url = input.dataset.importUrl || '';
            const token = document.querySelector('#costingForm input[name="_token"]')?.value || '';
            const formData = new FormData();
            formData.append('new_part_request_file', file);

            showAppLoading('Mengimport harga New Part Request...');
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await response.json();
                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Import New Part Request gagal.');
                }
                window.location.reload();
            } catch (error) {
                hideAppLoading();
                openAppNotify(error.message || 'Import New Part Request gagal.', 'error');
            } finally {
                input.value = '';
            }
        }

        async function importMaterialEditor(input) {
            const file = input?.files?.[0];
            if (!file) return;

            const url = document.getElementById('materialExcelImportUrl')?.value || '';
            const token = document.querySelector('#costingForm input[name="_token"]')?.value || '';
            const formData = new FormData();
            formData.append('material_file', file);
            const trackingRevisionId = document.getElementById('trackingRevisionId')?.value || '';
            if (trackingRevisionId) formData.append('tracking_revision_id', trackingRevisionId);
            const costingDataId = document.querySelector('#costingForm [name="costing_data_id"]')?.value || '';
            if (costingDataId) formData.append('costing_data_id', costingDataId);
            formData.append('forecast', document.getElementById('forecast')?.value || '0');
            formData.append('project_period', document.getElementById('projectPeriod')?.value || '0');
            formData.append('exchange_rate_usd', String(parseRateInputValue(document.getElementById('rateUSD')?.value || 0)));
            formData.append('exchange_rate_jpy', String(parseRateInputValue(document.getElementById('rateJPY')?.value || 0)));
            formData.append('lme_rate', String(parseRateInputValue(document.getElementById('lmeRate')?.value || 0)));

            showAppLoading('Memeriksa file Excel Material...');
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await response.json();
                if (!response.ok || data.success === false) {
                    const details = Array.isArray(data.errors) ? '\n' + data.errors.slice(0, 8).join('\n') : '';
                    throw new Error((data.message || 'Import Excel gagal.') + details);
                }

                const currentRows = Array.from(document.querySelectorAll('#materialTableBody tr'));
                const invalidIds = data.rows.filter((row) => Number(row.__row_no) < 1 || Number(row.__row_no) > currentRows.length);
                if (invalidIds.length > 0 || data.rows.length !== currentRows.length) {
                    throw new Error('Jumlah atau Row ID pada Excel tidak sesuai tabel saat ini. Gunakan file export terbaru dan jangan menambah/menghapus baris di Excel.');
                }

                const changedRows = data.rows.filter((incoming) => {
                    const current = currentRows[Number(incoming.__row_no) - 1];
                    return Object.entries(incoming).some(([field, value]) => {
                        if (field === '__row_no') return false;
                        const control = current?.querySelector(`[name$="[${field}]"]`);
                        return control && String(control.value ?? '').trim() !== String(value ?? '').trim();
                    });
                });

                if (data.costing_data_id) {
                    let costingIdInput = document.querySelector('#costingForm [name="costing_data_id"]');
                    if (!costingIdInput) {
                        costingIdInput = document.createElement('input');
                        costingIdInput.type = 'hidden';
                        costingIdInput.name = 'costing_data_id';
                        document.getElementById('costingForm')?.appendChild(costingIdInput);
                    }
                    costingIdInput.value = String(data.costing_data_id);
                }

                hideAppLoading();
                const groupImportNote = data.group_imported
                    ? ` Semua ${data.group_updates?.length || 0} assy dalam grup A00 juga akan diperbarui dari sheet masing-masing.`
                    : '';
                openAppConfirm(
                    `${data.rows.length} baris valid. ${changedRows.length} baris terdeteksi berubah. Terapkan ke tabel Material?${groupImportNote}`,
                    function () { applyMaterialEditorRows(data.rows, Boolean(data.group_imported)); },
                    { title: 'Konfirmasi Import Hasil Edit', buttonLabel: 'Terapkan & Simpan', tone: 'primary' }
                );
            } catch (error) {
                hideAppLoading();
                openAppNotify(error.message || 'Import Excel gagal.', 'error');
            } finally {
                input.value = '';
            }
        }

        async function applyMaterialEditorRows(rows, alreadyPersistedForGroup = false) {
            const tableRows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const beforeSnapshot = getMaterialStateSnapshot();

            rows.forEach((incoming) => {
                const row = tableRows[Number(incoming.__row_no) - 1];
                if (!row) return;

                Object.entries(incoming).forEach(([field, value]) => {
                    if (field === '__row_no') return;
                    const control = row.querySelector(`[name$="[${field}]"]`);
                    if (control) control.value = value ?? '';
                });
                row.dataset.materialDirty = '1';
                const calculatorControl = row.querySelector('.qty-req, .amount1, .currency');
                if (calculatorControl) calculateRow(calculatorControl);
            });

            isMaterialDirty = true;
            const afterSnapshot = getMaterialStateSnapshot();
            pushMaterialHistoryAction({ type: 'snapshot', before: beforeSnapshot, after: afterSnapshot });
            calculateTableTotal();
            refreshUnpricedRecap();
            if (alreadyPersistedForGroup) {
                isMaterialDirty = false;
                refreshMaterialInitialSnapshot();
                markMaterialControlsUndoBase();
                openAppNotify('Hasil edit seluruh assy dalam grup A00 berhasil tersimpan.', 'success');
                window.setTimeout(() => window.location.reload(), 500);
                return;
            }
            await persistImportedMaterialRows();
        }

        async function persistImportedMaterialRows() {
            const mainForm = document.getElementById('costingForm');
            const url = document.getElementById('quickMaterialUpdateUrl')?.value || '';
            const costingDataId = mainForm?.querySelector('[name="costing_data_id"]')?.value || '';
            const token = mainForm?.querySelector('input[name="_token"]')?.value
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '';
            // Import Hasil Edit adalah satu snapshot utuh. Kirim seluruh baris supaya
            // server tidak bergantung pada flag dirty di browser dan hasil refresh
            // selalu identik dengan file yang baru diterapkan.
            const rowsToSave = collectMaterialRowsForPayload();

            if (!mainForm || !url || !costingDataId) {
                openAppNotify('Hasil edit sudah diterapkan, tetapi data costing belum tersedia untuk penyimpanan otomatis.', 'error');
                return;
            }

            showAppLoading('Menyimpan hasil import persis seperti Excel...');
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        costing_data_id: costingDataId,
                        materials_json: JSON.stringify(rowsToSave),
                    }),
                });
                const data = await response.json();
                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Hasil import gagal disimpan.');
                }

                if (Number(data.saved_rows ?? data.updated_rows ?? 0) !== rowsToSave.length) {
                    throw new Error(`Penyimpanan belum lengkap: ${data.saved_rows ?? data.updated_rows ?? 0} dari ${rowsToSave.length} baris tersimpan.`);
                }

                isMaterialDirty = false;
                refreshMaterialInitialSnapshot();
                markMaterialControlsUndoBase();
                openAppNotify(rowsToSave.length + ' baris berhasil diterapkan dan tersimpan permanen.', 'success');
                window.setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                openAppNotify(error.message || 'Hasil import gagal disimpan.', 'error');
            } finally {
                hideAppLoading();
            }
        }

        function normalizeMaterialRowsForCompare(rows) {
            return rows.map((row) => {
                const clone = { ...row };
                delete clone.__dirty;
                return clone;
            });
        }

        function refreshMaterialInitialSnapshot() {
            /*
             * Jangan simpan snapshot seluruh row. Untuk ribuan baris, JSON.stringify
             * seluruh tabel adalah bottleneck. Cukup reset flag dirty per row.
             */
            materialInitialRowsSnapshot = [];
            materialInitialRowsSnapshotJson = '[]';
            materialStructureDirty = false;

            document.querySelectorAll('#materialTableBody tr').forEach((row) => {
                row.dataset.materialDirty = '0';
            });
        }

        function getChangedMaterialRowsForQuickUpdate() {
            /*
             * Mode super cepat: hanya ambil baris yang benar-benar disentuh user.
             * Jangan bandingkan JSON seluruh tabel karena data COGM bisa sangat banyak
             * dan proses stringify semua row membuat tombol Update terasa lama.
             */
            return collectMaterialRowsForPayload().filter((row) => {
                return row.__dirty === true || row.__dirty === '1';
            });
        }

        function submitMaterialQuickUpdateAjax(changedRows, onSuccess) {
            /*
             * EMERGENCY UX FIX:
             * Update Material dibuat fire-and-forget.
             * UI tidak menunggu backend selesai, jadi loading tidak akan muter lama.
             *
             * Backend tetap menerima request quick update di background.
             * Kalau backend gagal, user akan dapat notifikasi error belakangan.
             */
            const mainForm = document.getElementById('costingForm');
            const url = document.getElementById('quickMaterialUpdateUrl')?.value || '';

            if (!mainForm || !url) {
                hideAppLoading();
                openAppNotify('Endpoint Update Material belum tersedia.', 'error');
                return;
            }

            if (!Array.isArray(changedRows) || changedRows.length === 0) {
                hideAppLoading();
                openAppNotify('Tidak ada perubahan Material yang perlu disimpan.', 'info');
                return;
            }

            showAppLoading('Mengirim update Material...');

            const token = mainForm.querySelector('input[name="_token"]')?.value
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '';

            const costingDataId = mainForm.querySelector('[name="costing_data_id"]')?.value || '';
            const trackingRevisionId = mainForm.querySelector('[name="tracking_revision_id"]')?.value || '';

            const requestBody = JSON.stringify({
                costing_data_id: costingDataId,
                tracking_revision_id: trackingRevisionId,
                materials_json: JSON.stringify(changedRows),
                quick_update_version: 'fire-and-forget-v1',
            });

            /*
             * Jangan tunggu response untuk nutup loading.
             * Loading ditutup paksa dalam 600 ms.
             */
            window.setTimeout(function () {
                hideAppLoading();

                isMaterialDirty = false;
                refreshMaterialInitialSnapshot();
                markMaterialControlsUndoBase();

                if (typeof onSuccess === 'function') {
                    onSuccess({
                        success: true,
                        message: 'Update Material dikirim. Sistem memproses di background.',
                        fire_and_forget: true,
                    });
                } else {
                    openAppNotify('Update Material dikirim. Sistem memproses di background.', 'success');
                }
            }, 600);

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: requestBody,
            })
            .then(async function (response) {
                const text = await response.text();
                let data = {};

                try {
                    data = text ? JSON.parse(text) : {};
                } catch (error) {
                    throw new Error('Response server bukan JSON: ' + text.replace(/\s+/g, ' ').slice(0, 200));
                }

                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Backend gagal menyimpan Material.');
                }

                return data;
            })
            .then(function (data) {
                if (data && data.material_cost !== undefined && !data.save_only) {
                    setResumeMoneyValue('materialCost', Number(data.material_cost || 0));
                    calculateTotals(false);
                }
            })
            .catch(function (error) {
                /*
                 * Error backend muncul setelah UI sudah bebas.
                 */
                hideAppLoading();
                openAppNotify(error.message || 'Backend gagal menyimpan Material.', 'error');
            });
        }

        function buildMaterialSectionPayload(form) {
            const payload = new FormData();
            const appendIfPresent = (name, value) => {
                if (value !== null && value !== undefined) {
                    payload.append(name, value);
                }
            };

            [
                '_token',
                'costing_data_id',
                'tracking_revision_id',
                'update_section',
                'forecast',
                'project_period',
                'material_cost',
                'labor_cost',
                'overhead_cost',
                'scrap_cost',
                'revenue',
                'qty_good',
            ].forEach((name) => {
                const el = form.querySelector(`[name="${name}"]`);
                if (el) {
                    appendIfPresent(name, el.value);
                }
            });

            appendIfPresent('update_section', 'material');

            const materials = collectMaterialRowsForPayload(form).map((row) => {
                const clone = { ...row };
                delete clone.__row_index;
                delete clone.__row_no;
                delete clone.__dirty;
                return clone;
            });
            appendIfPresent('materials_json', JSON.stringify(materials));

            const manualPrices = {};
            form.querySelectorAll('input[name^="manual_unpriced_prices["]').forEach((control) => {
                const match = (control.name || '').match(/^manual_unpriced_prices\[(.+)\]$/);
                if (!match) return;
                manualPrices[match[1]] = control.value;
            });
            appendIfPresent('manual_unpriced_prices_json', JSON.stringify(manualPrices));

            return payload;
        }

        function submitMaterialSectionAjax(onSuccess) {
            const form = document.getElementById('costingForm');
            if (!form) return;

            showAppLoading('Menyimpan perubahan...');

            const payload = buildMaterialSectionPayload(form);
            const controller = new AbortController();

            const timeout = window.setTimeout(function () {
                try {
                    controller.abort();
                } catch (error) {}
            }, 5000);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: controller.signal,
                body: payload,
            })
            .then(function (resp) {
                window.clearTimeout(timeout);

                if (resp.ok) {
                    isMaterialDirty = false;
                    return resp.json().then(function (data) {
                        if (data.redirect && data.redirect !== window.location.href) {
                            window.history.replaceState(null, '', data.redirect);
                        }

                        if (typeof onSuccess === 'function') {
                            onSuccess(data);
                        } else {
                            hideAppLoading();
                            openAppNotify('Update berhasil disimpan.', 'success');
                        }
                    }).catch(function() {
                        if (typeof onSuccess === 'function') {
                            onSuccess();
                        } else {
                            hideAppLoading();
                            openAppNotify('Update berhasil disimpan.', 'success');
                        }
                    });
                }

                if (resp.status === 302 || resp.redirected) {
                    isMaterialDirty = false;
                    if (typeof onSuccess === 'function') {
                        onSuccess();
                    } else {
                        hideAppLoading();
                        openAppNotify('Update berhasil disimpan.', 'success');
                    }
                    return;
                }

                return resp.text().then(function (text) {
                    hideAppLoading();
                    const shortMessage = text ? text.replace(/\s+/g, ' ').slice(0, 250) : '';
                    openAppNotify('Gagal menyimpan: ' + resp.status + (shortMessage ? ' - ' + shortMessage : ''), 'error');
                });
            })
            .catch(function (error) {
                window.clearTimeout(timeout);
                hideAppLoading();

                if (error.name === 'AbortError') {
                    openAppNotify('Update melebihi batas 5 detik dan dihentikan agar halaman tidak stuck. Simpan sedikit perubahan dulu, lalu klik Update lagi.', 'error');
                    return;
                }

                openAppNotify('Gagal menghubungi server. Data mungkin sudah tersimpan, silakan muat ulang.', 'error');
            });

        }

        function submitMaterialSection() {
            const form = document.getElementById('costingForm');
            const materialUpdateBtn = document.querySelector('.section-update-btn[data-section="material"]');
            if (!form || !materialUpdateBtn) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(materialUpdateBtn);
                return;
            }

            materialUpdateBtn.click();
        }

        function getMaterialStateSnapshot() {
            const tbody = document.getElementById('materialTableBody');
            if (!tbody) {
                return null;
            }

            const clone = tbody.cloneNode(true);
            const liveControls = Array.from(tbody.querySelectorAll('input, select, textarea'));
            const cloneControls = Array.from(clone.querySelectorAll('input, select, textarea'));

            cloneControls.forEach((control, index) => {
                const liveControl = liveControls[index];
                if (!liveControl) {
                    return;
                }

                if (control instanceof HTMLInputElement) {
                    control.value = liveControl.value;
                    if (control.type === 'checkbox' || control.type === 'radio') {
                        control.checked = liveControl.checked;
                    }
                } else if (control instanceof HTMLSelectElement) {
                    control.value = liveControl.value;
                    Array.from(control.options).forEach((option) => {
                        option.selected = option.value === liveControl.value;
                    });
                } else if (control instanceof HTMLTextAreaElement) {
                    control.value = liveControl.value;
                    control.textContent = liveControl.value;
                }
            });

            return {
                html: clone.innerHTML,
                rowCounter,
            };
        }

        function updateMaterialUndoButtonState() {
            const undoBtn = document.getElementById('materialUndoBtn');
            const redoBtn = document.getElementById('materialRedoBtn');
            if (!undoBtn) return;
            undoBtn.disabled = materialUndoHistory.length === 0;
            if (redoBtn) {
                redoBtn.disabled = materialRedoHistory.length === 0;
            }
        }

        function pushMaterialHistoryAction(action) {
            if (!action || materialHistoryApplying) {
                return;
            }

            materialUndoHistory.push(action);
            if (materialUndoHistory.length > materialUndoLimit) {
                materialUndoHistory.shift();
            }

            // Flag as dirty to stop section jumping
            isMaterialDirty = true;
            materialRedoHistory = [];

            updateMaterialUndoButtonState();
        }

        function commitActiveMaterialFieldChange() {
            const active = document.activeElement;
            if (!(active instanceof HTMLElement)) {
                return;
            }

            if (!active.matches('#materialTableBody input.form-input, #materialTableBody select.form-select')) {
                return;
            }

            const previousValue = active.dataset.undoValue ?? '';
            const currentValue = active.value ?? '';
            if (previousValue === currentValue) {
                return;
            }

            pushMaterialHistoryAction({
                type: 'field',
                name: active.name,
                oldValue: previousValue,
                newValue: currentValue,
            });

            active.dataset.undoValue = currentValue;
        }

        function applyMaterialFieldValueByName(name, value) {
            if (!name) {
                return;
            }

            const escapedName = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function')
                ? CSS.escape(name)
                : name.replace(/([\[\]\.\:\#])/g, '\\$1');

            const target = document.querySelector(`#materialTableBody [name="${escapedName}"]`);
            if (!(target instanceof HTMLElement)) {
                return;
            }

            target.value = value ?? '';

            if (target instanceof HTMLInputElement && target.type === 'text') {
                target.value = String(target.value || '').toUpperCase();
            }

            target.dataset.undoValue = target.value ?? '';

            recalculateAllRows();
            refreshUnpricedRecap();

            const focused = document.activeElement;
            if (focused instanceof HTMLElement && focused.matches('#materialTableBody input.form-input, #materialTableBody select.form-select')) {
                focused.dataset.undoValue = focused.value ?? '';
            }
        }

        function markMaterialControlsUndoBase() {
            const controls = document.querySelectorAll('#materialTableBody input.form-input, #materialTableBody select.form-select');
            controls.forEach((control) => {
                control.dataset.undoValue = control.value ?? '';
            });
        }

        function applyMaterialAction(action, direction) {
            if (!action) {
                return;
            }

            materialHistoryApplying = true;

            if (action.type === 'field') {
                const targetValue = direction === 'undo' ? action.oldValue : action.newValue;
                applyMaterialFieldValueByName(action.name, targetValue);
            } else if (action.type === 'snapshot') {
                const snapshot = direction === 'undo' ? action.before : action.after;
                restoreMaterialSnapshot(snapshot);
            }

            materialHistoryApplying = false;

            markMaterialControlsUndoBase();

            updateMaterialUndoButtonState();
        }

        function restoreMaterialSnapshot(snapshot) {
            if (!snapshot) {
                return;
            }

            const tbody = document.getElementById('materialTableBody');
            if (!tbody) {
                return;
            }

            tbody.innerHTML = snapshot.html;
            rowCounter = snapshot.rowCounter;

            renumberRows();
            normalizeMaterialTextInputs();
            recalculateAllRows();
            refreshUnpricedRecap();
            bindUnpricedManualPriceInputs();
            bindUnpricedDeleteButtons();
            bindMatchedPriceSelectors();
            bindUnpricedAddPriceButtons();
            applyMaterialFilters();
        }

        function getMaterialRowFilterValue(row, columnIndex) {
            const cell = row.cells[columnIndex];
            if (!cell) return '';

            const control = cell.querySelector('input, select, textarea');
            if (control) {
                return String(control.value ?? '').trim();
            }

            return String(cell.textContent ?? '').trim();
        }

        function getMaterialColumnValues(columnIndex) {
            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const values = new Set();

            rows.forEach((row) => {
                const value = getMaterialRowFilterValue(row, columnIndex);
                values.add(value === '' ? '(Blanks)' : value);
            });

            return Array.from(values).sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));
        }

        function applyMaterialFilters() {
            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));

            rows.forEach((row) => {
                let visible = true;

                for (const [columnKey, selectedValues] of Object.entries(materialFilterState)) {
                    if (!(selectedValues instanceof Set) || selectedValues.size === 0) {
                        continue;
                    }

                    const columnIndex = Number(columnKey);
                    const rawValue = getMaterialRowFilterValue(row, columnIndex);
                    const normalizedValue = rawValue === '' ? '(Blanks)' : rawValue;

                    if (!selectedValues.has(normalizedValue)) {
                        visible = false;
                        break;
                    }
                }

                row.style.display = visible ? '' : 'none';
            });

            if (materialSortState.column !== null && materialSortState.direction) {
                const columnIndex = Number(materialSortState.column);
                const direction = materialSortState.direction === 'desc' ? -1 : 1;
                const tbody = document.getElementById('materialTableBody');

                if (tbody) {
                    rows.sort((a, b) => {
                        const va = getMaterialRowFilterValue(a, columnIndex);
                        const vb = getMaterialRowFilterValue(b, columnIndex);

                        const na = Number(va);
                        const nb = Number(vb);
                        const bothNumeric = !Number.isNaN(na) && !Number.isNaN(nb) && va !== '' && vb !== '';

                        if (bothNumeric) {
                            if (na === nb) return 0;
                            return (na < nb ? -1 : 1) * direction;
                        }

                        return va.localeCompare(vb, undefined, { sensitivity: 'base', numeric: true }) * direction;
                    });

                    rows.forEach((row) => tbody.appendChild(row));
                    renumberRows();
                }
            }

            updateMaterialFilterButtonsState();
        }

        function updateMaterialFilterButtonsState() {
            const table = document.getElementById('materialTable');
            if (!table) return;

            table.querySelectorAll('.material-filter-btn').forEach((btn) => {
                const col = Number(btn.dataset.col || -1);
                const activeSet = materialFilterState[col];
                const hasFilter = activeSet instanceof Set && activeSet.size > 0;
                const hasSort = materialSortState.column === col && !!materialSortState.direction;
                btn.classList.toggle('is-active', hasFilter || hasSort);
            });
        }

        function setMaterialSort(columnIndex, direction) {
            if (materialSortState.column === columnIndex && materialSortState.direction === direction) {
                materialSortState = { column: null, direction: null };
            } else {
                materialSortState = { column: columnIndex, direction };
            }

            applyMaterialFilters();

            if (activeMaterialFilterColumn !== null) {
                const sortAscBtn = materialFilterPopup?.querySelector('.material-filter-sort-asc');
                const sortDescBtn = materialFilterPopup?.querySelector('.material-filter-sort-desc');
                const ascActive = materialSortState.column === activeMaterialFilterColumn && materialSortState.direction === 'asc';
                const descActive = materialSortState.column === activeMaterialFilterColumn && materialSortState.direction === 'desc';
                sortAscBtn?.classList.toggle('is-active', ascActive);
                sortDescBtn?.classList.toggle('is-active', descActive);
            }
        }

        function closeMaterialFilterPopup() {
            if (!materialFilterPopup) return;
            materialFilterPopup.classList.remove('show');
            activeMaterialFilterColumn = null;
        }

        function renderMaterialFilterOptions(columnIndex, keyword = '') {
            if (!materialFilterPopup) return;

            const list = materialFilterPopup.querySelector('.material-filter-popup-list');
            if (!list) return;

            const selected = materialFilterState[columnIndex] instanceof Set
                ? new Set(materialFilterState[columnIndex])
                : null;
            const values = getMaterialColumnValues(columnIndex).filter((v) => v.toLowerCase().includes(keyword.toLowerCase()));

            list.innerHTML = '';

            const selectAllItem = document.createElement('label');
            selectAllItem.className = 'material-filter-popup-item';
            const selectAllCheckbox = document.createElement('input');
            selectAllCheckbox.type = 'checkbox';
            selectAllCheckbox.className = 'material-filter-select-all-checkbox';
            selectAllCheckbox.checked = values.length > 0 && values.every((v) => !selected || selected.has(v));
            const selectAllText = document.createElement('span');
            selectAllText.textContent = '(Select All)';
            selectAllItem.appendChild(selectAllCheckbox);
            selectAllItem.appendChild(selectAllText);
            list.appendChild(selectAllItem);

            selectAllCheckbox.addEventListener('change', function () {
                const checked = this.checked;
                list.querySelectorAll('.material-filter-value-checkbox').forEach((cb) => {
                    cb.checked = checked;
                });
            });

            values.forEach((value) => {
                const item = document.createElement('label');
                item.className = 'material-filter-popup-item';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'material-filter-value-checkbox';
                checkbox.value = value;
                checkbox.checked = !selected || selected.has(value);

                const text = document.createElement('span');
                text.textContent = value;

                item.appendChild(checkbox);
                item.appendChild(text);
                list.appendChild(item);
            });
        }

        function openMaterialFilterPopup(columnIndex, title, anchorElement) {
            if (!materialFilterPopup || !anchorElement) return;

            activeMaterialFilterColumn = columnIndex;
            materialFilterPopup.querySelector('.material-filter-popup-head').textContent = `Filter: ${title}`;

            const searchInput = materialFilterPopup.querySelector('.material-filter-search-input');
            if (searchInput) {
                searchInput.value = '';
            }

            const sortAscBtn = materialFilterPopup.querySelector('.material-filter-sort-asc');
            const sortDescBtn = materialFilterPopup.querySelector('.material-filter-sort-desc');
            const clearLineBtn = materialFilterPopup.querySelector('.material-filter-clear-line-btn');
            const ascActive = materialSortState.column === columnIndex && materialSortState.direction === 'asc';
            const descActive = materialSortState.column === columnIndex && materialSortState.direction === 'desc';
            sortAscBtn?.classList.toggle('is-active', ascActive);
            sortDescBtn?.classList.toggle('is-active', descActive);
            if (clearLineBtn) {
                clearLineBtn.textContent = `Clear Filter From "${title.toUpperCase()}"`;
            }

            renderMaterialFilterOptions(columnIndex, '');

            const rect = anchorElement.getBoundingClientRect();
            materialFilterPopup.style.top = `${Math.min(window.innerHeight - 380, rect.bottom + 8)}px`;
            materialFilterPopup.style.left = `${Math.max(8, Math.min(window.innerWidth - 280, rect.left))}px`;
            materialFilterPopup.classList.add('show');
        }

        function initMaterialFilterPopup() {
            if (materialFilterPopup) return;

            materialFilterPopup = document.createElement('div');
            materialFilterPopup.className = 'material-filter-popup';
            materialFilterPopup.innerHTML = `
                <div class="material-filter-popup-head">Filter</div>
                <div class="material-filter-popup-sort">
                    <button type="button" class="btn btn-secondary btn-sm material-filter-sort-asc">Sort A to Z</button>
                    <button type="button" class="btn btn-secondary btn-sm material-filter-sort-desc">Sort Z to A</button>
                </div>
                <div class="material-filter-separator"></div>
                <div class="material-filter-clear-line">
                    <button type="button" class="btn btn-secondary btn-sm material-filter-clear-line-btn">Clear Filter</button>
                </div>
                <div class="material-filter-popup-search">
                    <input type="text" class="material-filter-search-input" placeholder="Search...">
                </div>
                <div class="material-filter-popup-list"></div>
                <div class="material-filter-popup-actions">
                    <button type="button" class="btn btn-secondary btn-sm material-filter-cancel-btn">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm material-filter-apply-btn">OK</button>
                </div>
            `;

            document.body.appendChild(materialFilterPopup);

            const searchInput = materialFilterPopup.querySelector('.material-filter-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    if (activeMaterialFilterColumn === null) return;
                    renderMaterialFilterOptions(activeMaterialFilterColumn, this.value || '');
                });
            }

            const sortAscBtn = materialFilterPopup.querySelector('.material-filter-sort-asc');
            sortAscBtn?.addEventListener('click', function () {
                if (activeMaterialFilterColumn === null) return;
                setMaterialSort(activeMaterialFilterColumn, 'asc');
            });

            const sortDescBtn = materialFilterPopup.querySelector('.material-filter-sort-desc');
            sortDescBtn?.addEventListener('click', function () {
                if (activeMaterialFilterColumn === null) return;
                setMaterialSort(activeMaterialFilterColumn, 'desc');
            });

            const clearLineBtn = materialFilterPopup.querySelector('.material-filter-clear-line-btn');
            clearLineBtn?.addEventListener('click', function () {
                if (activeMaterialFilterColumn === null) return;
                delete materialFilterState[activeMaterialFilterColumn];
                applyMaterialFilters();
                closeMaterialFilterPopup();
            });

            const cancelBtn = materialFilterPopup.querySelector('.material-filter-cancel-btn');
            cancelBtn?.addEventListener('click', function () {
                closeMaterialFilterPopup();
            });

            const applyBtn = materialFilterPopup.querySelector('.material-filter-apply-btn');
            applyBtn?.addEventListener('click', function () {
                if (activeMaterialFilterColumn === null) return;

                const checked = Array.from(materialFilterPopup.querySelectorAll('.material-filter-popup-list .material-filter-value-checkbox:checked'))
                    .map((el) => el.value);

                const allValues = getMaterialColumnValues(activeMaterialFilterColumn);
                if (checked.length === 0 || checked.length === allValues.length) {
                    delete materialFilterState[activeMaterialFilterColumn];
                } else {
                    materialFilterState[activeMaterialFilterColumn] = new Set(checked);
                }

                applyMaterialFilters();
                closeMaterialFilterPopup();
            });

            document.addEventListener('click', function (event) {
                if (!materialFilterPopup || !materialFilterPopup.classList.contains('show')) return;
                const target = event.target;
                if (!(target instanceof Node)) return;

                const clickedFilterBtn = target instanceof Element && target.closest('.material-filter-btn');
                if (materialFilterPopup.contains(target) || clickedFilterBtn) {
                    return;
                }

                closeMaterialFilterPopup();
            });
        }

        function initMaterialHeaderFilters() {
            const table = document.getElementById('materialTable');
            if (!table) return;

            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach((th, index) => {
                if (!materialFilterableColumns.includes(index)) {
                    return;
                }

                if (th.dataset.filterReady === '1') {
                    return;
                }

                const title = (th.textContent || '').trim();
                th.dataset.filterReady = '1';
                th.dataset.filterTitle = title;

                const wrap = document.createElement('span');
                wrap.className = 'material-header-filter';

                const label = document.createElement('span');
                label.textContent = title;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'material-filter-btn';
                btn.dataset.col = String(index);
                btn.title = `Filter ${title}`;
                btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 4 21 4 14 12 14 19 10 21 10 12 3 4"></polygon></svg>';

                btn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    if (materialFilterPopup?.classList.contains('show') && activeMaterialFilterColumn === index) {
                        closeMaterialFilterPopup();
                        return;
                    }
                    openMaterialFilterPopup(index, title, btn);
                });

                th.textContent = '';
                wrap.appendChild(label);
                wrap.appendChild(btn);
                th.appendChild(wrap);
            });

            updateMaterialFilterButtonsState();
        }

        function undoMaterialTable() {
            // Capture pending active-cell edit first, then revert exactly one action.
            commitActiveMaterialFieldChange();

            if (materialUndoHistory.length === 0) {
                return;
            }

            const action = materialUndoHistory.pop();
            if (!action) {
                updateMaterialUndoButtonState();
                return;
            }

            applyMaterialAction(action, 'undo');

            materialRedoHistory.push(action);
            if (materialRedoHistory.length > materialUndoLimit) {
                materialRedoHistory.shift();
            }

            updateMaterialUndoButtonState();
        }

        function redoMaterialTable() {
            if (materialRedoHistory.length === 0) {
                return;
            }

            const next = materialRedoHistory.pop();
            if (!next) {
                updateMaterialUndoButtonState();
                return;
            }

            applyMaterialAction(next, 'redo');

            materialUndoHistory.push(next);
            if (materialUndoHistory.length > materialUndoLimit) {
                materialUndoHistory.shift();
            }

            updateMaterialUndoButtonState();
        }

        function normalizeMaterialTextInputs(scope = document) {
            const textInputs = scope.querySelectorAll('#materialTableBody input[type="text"]');
            textInputs.forEach((input) => {
                const value = String(input.value || '');
                const upper = value.toUpperCase();
                if (upper !== value) {
                    input.value = upper;
                }
            });
        }

        function moveMaterialFocusByArrow(currentElement, key) {
            const currentRow = currentElement.closest('tr');
            if (!currentRow) return;

            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const currentRowIndex = rows.indexOf(currentRow);
            if (currentRowIndex < 0) return;

            const getEditableCells = (row) => Array.from(row.querySelectorAll('input.form-input, select.form-select'));
            const currentCells = getEditableCells(currentRow);
            const currentCellIndex = currentCells.indexOf(currentElement);
            if (currentCellIndex < 0) return;

            let nextRowIndex = currentRowIndex;
            let nextCellIndex = currentCellIndex;

            if (key === 'ArrowLeft') nextCellIndex -= 1;
            if (key === 'ArrowRight') nextCellIndex += 1;
            if (key === 'ArrowUp') nextRowIndex -= 1;
            if (key === 'ArrowDown') nextRowIndex += 1;

            if (key === 'ArrowLeft' || key === 'ArrowRight') {
                if (nextCellIndex < 0 || nextCellIndex >= currentCells.length) {
                    return;
                }

                const target = currentCells[nextCellIndex];
                if (!target) return;

                target.focus();
                if (target.tagName === 'INPUT') {
                    target.select();
                }
                return;
            }

            if (nextRowIndex < 0 || nextRowIndex >= rows.length) {
                return;
            }

            const nextRow = rows[nextRowIndex];
            const nextRowCells = getEditableCells(nextRow);
            if (!nextRowCells.length) return;

            const target = nextRowCells[Math.min(currentCellIndex, nextRowCells.length - 1)];
            if (!target) return;

            target.focus();
            if (target.tagName === 'INPUT') {
                target.select();
            }
        }

        function moveMaterialFocusLinear(currentElement, step) {
            const currentRow = currentElement.closest('tr');
            if (!currentRow) return;

            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const currentRowIndex = rows.indexOf(currentRow);
            if (currentRowIndex < 0) return;

            const getEditableCells = (row) => Array.from(row.querySelectorAll('input.form-input, select.form-select'));
            const currentCells = getEditableCells(currentRow);
            const currentCellIndex = currentCells.indexOf(currentElement);
            if (currentCellIndex < 0) return;

            let nextRowIndex = currentRowIndex;
            let nextCellIndex = currentCellIndex + step;

            if (nextCellIndex >= currentCells.length) {
                nextRowIndex += 1;
                if (nextRowIndex >= rows.length) {
                    return;
                }
                nextCellIndex = 0;
            } else if (nextCellIndex < 0) {
                nextRowIndex -= 1;
                if (nextRowIndex < 0) {
                    return;
                }
                const prevCells = getEditableCells(rows[nextRowIndex]);
                nextCellIndex = Math.max(prevCells.length - 1, 0);
            }

            const nextCells = getEditableCells(rows[nextRowIndex]);
            if (!nextCells.length) return;

            const target = nextCells[Math.min(nextCellIndex, nextCells.length - 1)];
            if (!target) return;

            target.focus();
            if (target.tagName === 'INPUT') {
                target.select();
            }
        }

        function bindMaterialTableBehaviors() {
            const materialBody = document.getElementById('materialTableBody');
            if (!materialBody || materialBody.dataset.boundBehavior === '1') {
                return;
            }

            materialBody.dataset.boundBehavior = '1';

            materialBody.addEventListener('input', function (event) {
                isMaterialDirty = true;
                const dirtyRow = event.target?.closest ? event.target.closest('tr') : null;
                if (dirtyRow && dirtyRow.closest('#materialTableBody')) {
                    dirtyRow.dataset.materialDirty = '1';
                }
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) {
                    return;
                }

                if (target.type !== 'text') {
                    return;
                }

                const currentValue = String(target.value || '');
                const upperValue = currentValue.toUpperCase();
                if (upperValue === currentValue) {
                    return;
                }

                const start = target.selectionStart;
                const end = target.selectionEnd;
                target.value = upperValue;
                if (start !== null && end !== null) {
                    target.setSelectionRange(start, end);
                }
            });

            materialBody.addEventListener('focusin', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (!target.matches('input.form-input, select.form-select')) {
                    return;
                }

                target.dataset.undoValue = target.value ?? '';
            });

            materialBody.addEventListener('change', function (event) {
                const target = event.target;
                const dirtyRow = target?.closest ? target.closest('tr') : null;
                if (dirtyRow && dirtyRow.closest('#materialTableBody')) {
                    dirtyRow.dataset.materialDirty = '1';
                }
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.matches('.part-no, .id-code')) {
                    const row = target.closest('tr');
                    if (row instanceof HTMLTableRowElement) {
                        const updated = applyMasterMaterialToRow(row);
                        if (updated) {
                            const input = row.querySelector('.qty-req') || row.querySelector('.amount1') || row.querySelector('.unit-price-basis');
                            if (input) {
                                calculateRow(input, { silent: true });
                            }
                        }
                    }
                }

                if (target.matches('.material-row-select')) {
                    updateMaterialSelectAllRowsState();
                    return;
                }

                if (!target.matches('input.form-input, select.form-select')) {
                    return;
                }

                const previousValue = target.dataset.undoValue ?? '';
                const currentValue = target.value ?? '';
                if (previousValue === currentValue) {
                    return;
                }

                pushMaterialHistoryAction({
                    type: 'field',
                    name: target.name,
                    oldValue: previousValue,
                    newValue: currentValue,
                });

                target.dataset.undoValue = currentValue;
                applyMaterialFilters();
            });

            materialBody.addEventListener('keydown', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (!target.matches('input.form-input, select.form-select')) {
                    return;
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    moveMaterialFocusLinear(target, event.shiftKey ? -1 : 1);
                    return;
                }

                // Excel-like F2 behavior: enter edit mode
                if (event.key === 'F2') {
                    event.preventDefault();
                    if (target.tagName === 'INPUT') {
                        target.dataset.isEditing = '1';
                        // Move cursor to the end of the text
                        const len = target.value.length;
                        target.setSelectionRange(len, len);
                    }
                    return;
                }

                // If in edit mode, allow Left/Right arrows to move cursor naturally
                if (['ArrowLeft', 'ArrowRight'].includes(event.key) && target.dataset.isEditing === '1') {
                    return;
                }

                if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
                    return;
                }

                event.preventDefault();
                moveMaterialFocusByArrow(target, event.key);
            });

            // Double click to also enter edit mode like Excel
            materialBody.addEventListener('dblclick', function(event) {
                const target = event.target;
                if (target instanceof HTMLInputElement && target.matches('input.form-input')) {
                    target.dataset.isEditing = '1';
                }
            });

            // Clear edit mode when leaving the cell
            materialBody.addEventListener('focusout', function(event) {
                const target = event.target;
                if (target instanceof HTMLInputElement) {
                    target.dataset.isEditing = '0';
                }
            });

            const masterSelectAll = document.getElementById('materialSelectAllRows');
            if (masterSelectAll && masterSelectAll.dataset.boundSelectAll !== '1') {
                masterSelectAll.dataset.boundSelectAll = '1';
                masterSelectAll.addEventListener('change', function () {
                    const checked = !!this.checked;
                    document.querySelectorAll('#materialTableBody .material-row-select').forEach((cb) => {
                        if (cb instanceof HTMLInputElement) {
                            cb.checked = checked;
                        }
                    });
                    updateMaterialSelectAllRowsState();
                });
            }
        }




        function parseRateInputValue(value) {
            if (typeof parseCycleNumber === 'function') {
                return parseCycleNumber(value || 0);
            }

            return parseInputNumber(value || 0);
        }

        function formatRateDisplayValue(value) {
            const number = Number(value) || 0;
            return number.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatRateInput(inputOrId) {
            const input = typeof inputOrId === 'string' ? document.getElementById(inputOrId) : inputOrId;
            if (!input) return;

            input.value = formatRateDisplayValue(parseRateInputValue(input.value || 0));
        }

        function formatAllRateInputs() {
            ['rateUSD', 'rateJPY', 'lmeRate'].forEach(formatRateInput);
            const idrInput = document.getElementById('rateIDR');
            if (idrInput) {
                idrInput.value = '1,00';
            }
        }
        function normalizeRateInputsForSubmit() {
            ['rateUSD', 'rateJPY', 'rateIDR', 'lmeRate'].forEach(function(id) {
                const input = document.getElementById(id);
                if (!input) return;

                input.value = String(parseRateInputValue(input.value || 0));
            });
        }


        function normalizeCycleTimeInputsForSubmit() {
            document.querySelectorAll('#cycleTimeTableBody tr').forEach(function(row) {
                ['ct-qty', 'ct-hour', 'ct-sec', 'ct-sec-per', 'ct-cost-sec', 'ct-cost-unit'].forEach(function(className) {
                    const input = row.querySelector('.' + className);
                    if (!input) return;

                    input.value = String(parseCycleNumber(input.value || 0));
                });
            });
        }

        function normalizeCycleCostUnitInputsForSubmit() {
            document.querySelectorAll('.ct-cost-unit').forEach(function(input) {
                input.value = String(parseCycleNumber(input.value || 0));
            });
        }

        function formatCycleCostUnitValue(value) {
            const number = Number(value) || 0;

            return number.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }


        function parseCycleNumber(value) {
            if (value === null || value === undefined) {
                return 0;
            }

            let raw = String(value).trim();

            if (raw === '') {
                return 0;
            }

            raw = raw.replace(/\s+/g, '');
            raw = raw.replace(/[^0-9,.\-]/g, '');

            if (raw === '' || raw === '-' || raw === '.' || raw === ',') {
                return 0;
            }

            const hasComma = raw.includes(',');
            const hasDot = raw.includes('.');

            if (hasComma && hasDot) {
                const lastComma = raw.lastIndexOf(',');
                const lastDot = raw.lastIndexOf('.');

                if (lastComma > lastDot) {
                    // Format Indonesia: 1.234,56
                    raw = raw.replace(/\./g, '');
                    raw = raw.replace(/,/g, '.');
                } else {
                    // Format international: 1,234.56
                    raw = raw.replace(/,/g, '');
                }
            } else if (hasComma && !hasDot) {
                // Koma sebagai desimal
                raw = raw.replace(/,/g, '.');
            } else if (hasDot && !hasComma) {
                // Untuk Cycle Time, titik adalah desimal. Jangan dihapus.
                raw = raw;
            }

            const numeric = Number(raw);

            return Number.isFinite(numeric) ? numeric : 0;
        }

        function formatCycleHourValue(value) {
            const number = Number(value) || 0;

            return Number(number.toFixed(9)).toString();
        }

        function formatCycleIntegerValue(value) {
            const number = Number(value) || 0;

            return String(Math.round(number));
        }

        function calculateCycleRow(element) {
            const row = element.closest('tr');
            if (!row) return;

            const qtyInput = row.querySelector('.ct-qty');
            const hourInput = row.querySelector('.ct-hour');
            const secInput = row.querySelector('.ct-sec');
            const secPerInput = row.querySelector('.ct-sec-per');
            const costSecInput = row.querySelector('.ct-cost-sec');
            const costUnitInput = row.querySelector('.ct-cost-unit');

            const qty = parseCycleNumber(qtyInput?.value || 0);
            let hour = parseCycleNumber(hourInput?.value || 0);
            let sec = parseCycleNumber(secInput?.value || 0);
            const costPerSec = parseCycleNumber(costSecInput?.value || 0);

            if (element.classList.contains('ct-hour')) {
                sec = hour * 3600;
                if (secInput) {
                    secInput.value = formatCycleIntegerValue(sec);
                }
            } else if (element.classList.contains('ct-sec')) {
                hour = sec / 3600;
                if (hourInput) {
                    hourInput.value = formatCycleHourValue(hour);
                }
            } else {
                // Rapikan format saat kalkulasi awal / import.
                if (hourInput && hour > 0) {
                    hourInput.value = formatCycleHourValue(hour);
                }
                if (secInput && sec > 0) {
                    secInput.value = formatCycleIntegerValue(sec);
                }
            }

            const secPerQty = qty > 0 ? (sec / qty) : 0;
            const costPerUnit = sec * costPerSec;

            if (secPerInput) {
                secPerInput.value = formatCycleIntegerValue(secPerQty);
            }

            if (costUnitInput) {
                const shouldRecalculateCostUnit =
                    !costUnitInput.value ||
                    element.classList.contains('ct-qty') ||
                    element.classList.contains('ct-hour') ||
                    element.classList.contains('ct-sec') ||
                    element.classList.contains('ct-cost-sec');

                if (shouldRecalculateCostUnit) {
                    costUnitInput.value = formatCycleCostUnitValue(costPerUnit);
                } else {
                    costUnitInput.value = formatCycleCostUnitValue(parseCycleNumber(costUnitInput.value || 0));
                }
            }

            calculateCycleTotals();

        }


        function formatCycleTotalNumber(value, maxDecimals = 0) {
            const number = Number(value) || 0;

            return number.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: maxDecimals
            });
        }

        function calculateCycleTotals() {
            let totalHour = 0;
            let totalSec = 0;
            let totalCostUnit = 0;
            const rows = document.querySelectorAll('#cycleTimeTableBody tr');

            rows.forEach((row) => {
                totalHour += parseCycleNumber(row.querySelector('.ct-hour')?.value || 0);
                totalSec += parseCycleNumber(row.querySelector('.ct-sec')?.value || 0);
                totalCostUnit += parseCycleNumber(row.querySelector('.ct-cost-unit')?.value || 0);
            });

            const totalHourEl = document.getElementById('cycleTotalHour');
            const totalSecEl = document.getElementById('cycleTotalSec');
            const totalCostUnitEl = document.getElementById('cycleTotalCostUnit');

            if (totalHourEl) {
                totalHourEl.textContent = formatCycleTotalNumber(totalHour, 4);
            }
            if (totalSecEl) {
                totalSecEl.textContent = formatCycleTotalNumber(totalSec, 0);
            }
            if (totalCostUnitEl) {
                totalCostUnitEl.textContent = formatCycleTotalNumber(totalCostUnit, 2);
            }

            // Sync process cost in Resume COGM from total cycle time cost
            const laborCostInput = document.getElementById('laborCost');
            if (laborCostInput) {
                setResumeMoneyValue(laborCostInput, totalCostUnit);
            }

            calculateTotals(false);
        }

        function addCycleTimeRow() {
            const tbody = document.getElementById('cycleTimeTableBody');
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-cycle-row', cycleRowCounter);

            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const processOptionsHtml = ['<option value="">-- Pilih Process --</option>']
                .concat(cycleProcessOptions.map((process) => {
                    const escaped = escapeHtml(process);
                    return `<option value="${escaped}">${escaped}</option>`;
                }))
                .join('');

            newRow.innerHTML = `
                <td>${cycleRowCounter + 1}</td>
                <td><select class="form-select ct-process" name="cycle_times[${cycleRowCounter}][process]">${processOptionsHtml}</select></td>
                <td><input type="text" inputmode="decimal" class="form-input ct-qty" name="cycle_times[${cycleRowCounter}][qty]" value="" onchange="calculateCycleRow(this)"></td>
                <td><input type="text" inputmode="decimal" class="form-input ct-hour" name="cycle_times[${cycleRowCounter}][time_hour]" value="" onchange="calculateCycleRow(this)"></td>
                <td><input type="text" inputmode="decimal" class="form-input ct-sec" name="cycle_times[${cycleRowCounter}][time_sec]" value="" onchange="calculateCycleRow(this)"></td>
                <td><input type="text" inputmode="decimal" class="form-input ct-sec-per" name="cycle_times[${cycleRowCounter}][time_sec_per_qty]" value="" onchange="calculateCycleRow(this)"></td>
                <td><input type="text" inputmode="decimal" class="form-input ct-cost-sec" name="cycle_times[${cycleRowCounter}][cost_per_sec]" value="10.33" onchange="calculateCycleRow(this)"></td>
                <td><input type="text" inputmode="decimal" class="form-input ct-cost-unit" name="cycle_times[${cycleRowCounter}][cost_per_unit]" value="" onchange="calculateCycleRow(this)"></td>
                <td><button type="button" class="btn btn-secondary" onclick="removeCycleTimeRow(this)" style="padding: 0.5rem;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></td>
            `;

            tbody.appendChild(newRow);
            cycleRowCounter++;
            renumberCycleRows();
        }

        function removeCycleTimeRow(button) {
            const row = button.closest('tr');
            row.remove();
            renumberCycleRows();
            calculateCycleTotals();
        }

        function renumberCycleRows() {
            const rows = document.querySelectorAll('#cycleTimeTableBody tr');
            rows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
        }

        function setRateInputsLocked(locked) {
            ['rateUSD', 'rateJPY', 'lmeRate'].forEach((id) => {
                const input = document.getElementById(id);
                if (!input) return;

                input.readOnly = locked;
                input.setAttribute('aria-readonly', locked ? 'true' : 'false');
                input.style.backgroundColor = locked ? '#f1f5f9' : '';
                input.style.cursor = locked ? 'not-allowed' : '';
            });
        }

        function rememberSelectedExchangeRate(select) {
            const selectionKey = select?.dataset?.selectionKey || 'new';
            const selectedId = select?.value || '';
            const storageKey = `costing-rate-selection:${selectionKey}`;

            try {
                window.localStorage.setItem(storageKey, selectedId);
            } catch (error) {}

            const url = select?.dataset?.rememberUrl || '';
            const token = document.querySelector('#costingForm input[name="_token"]')?.value || '';
            if (!url) return;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    exchange_rate_id: selectedId || null,
                    selection_key: selectionKey,
                }),
            }).catch(() => {});
        }

        function restoreSelectedExchangeRate(select) {
            if (!select) return;
            const selectionKey = select.dataset.selectionKey || 'new';
            try {
                const rememberedId = window.localStorage.getItem(`costing-rate-selection:${selectionKey}`);
                if (rememberedId !== null && Array.from(select.options).some((option) => option.value === rememberedId)) {
                    select.value = rememberedId;
                }
            } catch (error) {}
        }

        function updateRatesFromExchangeRate(select, shouldRemember = false) {
            if (!select) return;

            if (shouldRemember) {
                rememberSelectedExchangeRate(select);
            }

            const option = select.options[select.selectedIndex];
            if (!option) return;

            // Pilihan kosong berarti pengguna ingin mempertahankan/mengisi rate secara manual.
            if (!option.value) {
                setRateInputsLocked(false);
                return;
            }

            const usd = option.getAttribute('data-usd');
            const jpy = option.getAttribute('data-jpy');
            const lme = option.getAttribute('data-lme');

            const usdInput = document.getElementById('rateUSD');
            const jpyInput = document.getElementById('rateJPY');
            const idrInput = document.getElementById('rateIDR');
            const lmeInput = document.getElementById('lmeRate');

            if (usdInput && usd !== null && usd !== '') {
                usdInput.value = formatRateDisplayValue(usd);
            }

            if (jpyInput && jpy !== null && jpy !== '') {
                jpyInput.value = formatRateDisplayValue(jpy);
            }

            if (idrInput) {
                idrInput.value = '1,00';
            }

            if (lmeInput && lme !== null && lme !== '') {
                lmeInput.value = formatRateDisplayValue(lme);
            }

            setRateInputsLocked(true);

            // Setelah rate berubah, hitung ulang Material karena Total Price (IDR)
            // bergantung pada USD/JPY.
            if (typeof recalculateAllRows === 'function') {
                recalculateAllRows();
            } else if (typeof calculateTableTotal === 'function') {
                calculateTableTotal();
            }
        }

        function toggleAllMaterialRowCheckboxes(checked) {
            document.querySelectorAll('#materialTableBody .material-row-select').forEach(function (cb) {
                if (cb instanceof HTMLInputElement) {
                    cb.checked = checked;
                }
            });
        }

        function initSectionToggles() {
            const sections = document.querySelectorAll('.form-page .form-section');

            sections.forEach((section, index) => {
                const title = section.querySelector('.form-section-title');
                if (!title) return;

                const toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'section-toggle';
                toggleBtn.setAttribute('aria-expanded', 'true');
                toggleBtn.setAttribute('aria-controls', `section-content-${index}`);
                toggleBtn.title = 'Hide/Show bagian ini';
                toggleBtn.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                `;

                toggleBtn.addEventListener('click', () => {
                    const isCollapsed = section.classList.toggle('is-collapsed');
                    toggleBtn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                });

                title.appendChild(toggleBtn);
            });
        }

        function shouldKeepFieldForSection(fieldName, section) {
            if (!fieldName) return false;

            const alwaysKeep = [
                '_token',
                'costing_data_id',
                'tracking_revision_id',
                'update_section',
                'import_partlist',
                'import_partlist_file'
            ];

            if (alwaysKeep.includes(fieldName)) {
                return true;
            }

            const sectionExactFields = {
                informasi_project: ['business_category_id', 'customer_id', 'period', 'line', 'model', 'assy_no', 'assy_name', 'pic_engineering', 'pic_marketing', 'forecast', 'forecast_uom', 'forecast_basis', 'project_period'],
                rates: ['exchange_rate_usd', 'exchange_rate_jpy', 'lme_rate'],
                material: ['forecast', 'project_period', 'material_cost', 'labor_cost', 'overhead_cost', 'scrap_cost', 'revenue', 'qty_good', 'import_partlist'],
                unpriced_parts: ['tracking_revision_id'],
                cycle_time: ['cycle_times'],
                resume_cogm: ['material_cost', 'labor_cost', 'overhead_cost', 'scrap_cost', 'revenue', 'qty_good']
            };

            const sectionPrefixes = {
                material: ['materials[', 'manual_unpriced_prices['],
                unpriced_parts: ['materials[', 'manual_unpriced_prices['],
                cycle_time: ['cycle_times[']
            };

            const exact = sectionExactFields[section] || [];
            if (exact.includes(fieldName)) {
                return true;
            }

            const prefixes = sectionPrefixes[section] || [];
            return prefixes.some(prefix => fieldName.startsWith(prefix));
        }

        function submitResumeCogmSectionFromEnter(event) {
            event.preventDefault();
            event.stopPropagation();

            const target = event.target;
            if (target && typeof formatResumeMoneyInput === 'function') {
                formatResumeMoneyInput(target);
            }

            if (typeof calculateTotals === 'function') {
                calculateTotals(false);
            }

            if (typeof normalizeResumeMoneyInputsForSubmit === 'function') {
                /*
                 * Jangan panggil normalize di sini. Normalisasi akan dipanggil oleh
                 * submit handler utama tepat sebelum submit. Di sini tetap biarkan
                 * tampilan format Indonesia supaya nilai tidak terlihat hilang/berubah.
                 */
            }

            const form = document.getElementById('costingForm');
            const resumeButton = form?.querySelector('.section-update-btn[data-section="resume_cogm"]');

            if (!form || !resumeButton) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(resumeButton);
            } else {
                const updateSectionInput = document.getElementById('updateSectionInput');
                if (updateSectionInput) {
                    updateSectionInput.value = 'resume_cogm';
                }

                resumeButton.click();
            }
        }

        function bindResumeCogmEnterSave() {
            ['overheadCost', 'scrapCost'].forEach(function (inputId) {
                const input = document.getElementById(inputId);
                if (!input || input.dataset.enterSaveBound === '1') {
                    return;
                }

                input.dataset.enterSaveBound = '1';

                input.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    submitResumeCogmSectionFromEnter(event);
                });

                input.addEventListener('blur', function () {
                    formatResumeMoneyInput(input);
                    calculateTotals(false);
                });
            });
        }

        function prepareSectionOnlySubmit(section, submitter) {
            if (!section) return;

            const form = document.getElementById('costingForm');
            if (!form) return;

            form.querySelectorAll('input, select, textarea, button').forEach((el) => {
                if (el === submitter) {
                    return;
                }

                if (!el.name) {
                    return;
                }

                if (shouldKeepFieldForSection(el.name, section)) {
                    return;
                }

                if (!el.disabled) {
                    el.dataset.sectionDisabled = '1';
                    el.disabled = true;
                }
            });
        }

        function showPartlistImportConfirmModal() {
            return new Promise((resolve) => {
                const modal = document.getElementById('partlistImportConfirmModal');
                const okBtn = document.getElementById('partlistImportOkBtn');
                const cancelBtn = document.getElementById('partlistImportCancelBtn');

                if (!modal || !okBtn || !cancelBtn) {
                    resolve(false);
                    return;
                }

                const closeWith = (result) => {
                    modal.classList.add('is-hidden');
                    modal.setAttribute('aria-hidden', 'true');
                    okBtn.removeEventListener('click', handleOk);
                    cancelBtn.removeEventListener('click', handleCancel);
                    modal.removeEventListener('click', handleOverlay);
                    document.removeEventListener('keydown', handleEsc);
                    resolve(result);
                };

                const handleOk = () => closeWith(true);
                const handleCancel = () => closeWith(false);
                const handleOverlay = (event) => {
                    if (event.target === modal) {
                        closeWith(false);
                    }
                };
                const handleEsc = (event) => {
                    if (event.key === 'Escape') {
                        closeWith(false);
                    }
                };

                modal.classList.remove('is-hidden');
                modal.setAttribute('aria-hidden', 'false');

                okBtn.addEventListener('click', handleOk);
                cancelBtn.addEventListener('click', handleCancel);
                modal.addEventListener('click', handleOverlay);
                document.addEventListener('keydown', handleEsc);
            });
        }

        async function triggerPartlistImport() {
            const fileInput = document.getElementById('importPartlistFileInput');
            if (!fileInput) return;

            const hasFilledMaterial = Array.from(document.querySelectorAll('#materialTableBody tr')).some((row) => {
                const partNo = (row.querySelector('.part-no')?.value || '').trim();
                const partName = (row.querySelector('.part-name')?.value || '').trim();
                const amount1 = parseInputNumber(row.querySelector('.amount1')?.value || 0);
                const qtyReq = parseInputNumber(row.querySelector('.qty-req')?.value || 0);
                return partNo !== '' || partName !== '' || amount1 > 0 || qtyReq > 0;
            });

            if (hasFilledMaterial) {
                const confirmed = await showPartlistImportConfirmModal();
                if (!confirmed) {
                    return;
                }
            }

            fileInput.value = '';
            fileInput.click();
        }

        function submitPartlistImport() {
            const form = document.getElementById('partlistImportForm');
            const importForecast = document.getElementById('importForecast');
            const importProjectPeriod = document.getElementById('importProjectPeriod');
            const importWireRateId = document.getElementById('importWireRateId');
            const forecastHidden = document.getElementById('forecast');
            const projectPeriod = document.getElementById('projectPeriod');
            const wireRateSelector = document.getElementById('wireRateSelector');

            if (!form) return;

            syncForecastHidden();

            if (importForecast && forecastHidden) {
                importForecast.value = forecastHidden.value || '0';
            }

            if (importProjectPeriod && projectPeriod) {
                importProjectPeriod.value = projectPeriod.value || '0';
            }

            if (importWireRateId && wireRateSelector) {
                importWireRateId.value = wireRateSelector.value || '';
            }

            // Sync main form fields to import form
            const syncFields = {
                'importBusinessCategoryId': 'select[name="business_category_id"]',
                'importCustomerId': 'select[name="customer_id"]',
                'importPeriod': '#periodInput',
                'importLine': 'select[name="line"]',
                'importModel': 'input[name="model"]',
                'importAssyNo': 'input[name="assy_no"]',
                'importAssyName': 'input[name="assy_name"]',
                'importRateUsd': '#rateUSD',
                'importRateJpy': '#rateJPY',
                'importLmeRate': '#lmeRate',
            };
            for (const [hiddenId, mainSelector] of Object.entries(syncFields)) {
                const hidden = document.getElementById(hiddenId);
                const main = document.querySelector('#costingForm ' + mainSelector);
                if (hidden && main) hidden.value = main.value || '';
            }

            // Submit the import form
            showAppLoading('Mengimport partlist...');

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            form.submit();
        }

        function bindMaterialTableColumnResizer() {
            const table = document.getElementById('materialTable');
            if (!table) return;

            const headers = table.querySelectorAll('thead th');
            headers.forEach((th, index) => {
                th.title = 'Klik dua kali untuk menyesuaikan lebar kolom secara otomatis (Auto-fit)';
                th.style.cursor = 'col-resize';
                
                th.addEventListener('dblclick', function() {
                    let maxChars = th.textContent.trim().length;

                    // Iterate over visible rows to find longest text content or input value
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        // Skip deleted form rows (hidden visually)
                        if (row.style.display === 'none') return;
                        
                        const cell = row.children[index];
                        if (!cell) return;

                        let text = cell.textContent.trim();
                        const input = cell.querySelector('input:not([type="hidden"]), select');
                        
                        if (input) {
                            if (input.tagName === 'SELECT') {
                                text = input.options[input.selectedIndex]?.text || '';
                            } else {
                                text = input.value || '';
                            }
                        }

                        if (text.length > maxChars) {
                            maxChars = text.length;
                        }
                    });

                    // Base width approximation using ch unit (adding buffer for padding/dropdown arrow)
                    let estimatedCh = maxChars + 6; 
                    
                    // Constrain min/max width limits to prevent breaking table
                    if (estimatedCh > 65) estimatedCh = 65; 
                    if (estimatedCh < 8) estimatedCh = 8;
                    
                    const newWidth = estimatedCh + 'ch';

                    // Optional: remove hardcoded classes and apply inline style
                    th.style.width = newWidth;
                    th.style.minWidth = newWidth;
                    
                    rows.forEach(row => {
                        const cell = row.children[index];
                        if (!cell) return;
                        
                        // Inputs need to resize themselves accordingly
                        const input = cell.querySelector('.form-input, .form-select');
                        if (input) {
                            input.classList.remove('w-28');
                            input.style.width = '100%';
                            input.style.minWidth = newWidth;
                        }
                    });
                });
            });
        }

        // Initialize calculations on page load
        document.addEventListener('DOMContentLoaded', function () {
            bindMaterialTableColumnResizer();
            initSectionToggles();
            bindMaterialTableBehaviors();
            initMaterialFilterPopup();
            initMaterialHeaderFilters();
            normalizeMaterialTextInputs();
            markMaterialControlsUndoBase();
            applyMaterialFilters();
            updateMaterialSelectAllRowsState();
            formatForecastDisplay();

            const exchangeRateSelector = document.getElementById('exchangeRateSelector');
            if (exchangeRateSelector) {
                restoreSelectedExchangeRate(exchangeRateSelector);
                updateRatesFromExchangeRate(exchangeRateSelector);
            }

            // Recalculate material rows on load so Multiply Factor follows the Excel formula.
            // This is needed because rendered HTML may still show default "1".
            recalculateAllRows();
            calculateTotals();

            const serverMaterialCost = Number(document.getElementById('serverMaterialCost')?.value || 0);
            if (serverMaterialCost > 0) {
                const tableTotal = document.getElementById('tableTotalMaterial');
                if (tableTotal) tableTotal.textContent = formatRupiah(serverMaterialCost);
                const materialCostInput = document.getElementById('materialCost');
                if (materialCostInput) setResumeMoneyValue(materialCostInput, serverMaterialCost);
                calculateTotals(false);
            }

            refreshUnpricedRecap();
            bindUnpricedManualPriceInputs();
            bindUnpricedDeleteButtons();
            bindUnpricedAddPriceButtons();

            const cycleRows = document.querySelectorAll('#cycleTimeTableBody tr');
            cycleRows.forEach(row => {
                const input = row.querySelector('.ct-hour') || row.querySelector('.ct-sec');
                if (input) calculateCycleRow(input);
            });

            calculateCycleTotals();

            const forecastDisplay = document.getElementById('forecastDisplay');
            if (forecastDisplay) {
                forecastDisplay.addEventListener('input', function () {
                    syncForecastHidden();
                    recalculateAllRows();
                });

                forecastDisplay.addEventListener('blur', function () {
                    formatForecastDisplay();
                    recalculateAllRows();
                });
            }

            bindResumeCogmEnterSave();

            const costingForm = document.getElementById('costingForm');
            if (costingForm) {
                costingForm.addEventListener('submit', function (event) {
                    normalizeMaterialTextInputs();
                    syncForecastHidden();

                    const submitter = event.submitter;
                    const updateSectionInput = document.getElementById('updateSectionInput');
                    const section = submitter?.dataset?.section || '';

                    if (section === 'resume_cogm' && typeof calculateTotals === 'function') {
                        calculateTotals(false);
                    }

                    if (typeof normalizeResumeMoneyInputsForSubmit === 'function') {
                        normalizeResumeMoneyInputsForSubmit();
                    }
                    if (typeof normalizeRateInputsForSubmit === 'function') {
                        normalizeRateInputsForSubmit();
                    }
                    if (typeof normalizeCycleTimeInputsForSubmit === 'function') {
                        normalizeCycleTimeInputsForSubmit();
                    }

                    refreshUnpricedRecap();

                    if (updateSectionInput) {
                        updateSectionInput.value = section;
                    }

                    if (section === 'material') {
                        const validationResult = getMaterialSectionValidationResult();

                        if (shouldShowMaterialValidationNotice(validationResult)) {
                            event.preventDefault();

                            showMaterialValidationModal(validationResult.message, validationResult.type, function () {
                                acknowledgeMaterialValidationNotice(validationResult);
                                bypassMaterialValidationNoticeOnce = true;

                                if (typeof costingForm.requestSubmit === 'function') {
                                    costingForm.requestSubmit(submitter || undefined);
                                } else {
                                    costingForm.submit();
                                }
                            });

                            return;
                        }
                    }

                    if (section === 'material') {
                        event.preventDefault();

                        /*
                         * HARD FIX:
                         * Tombol Update Material tidak boleh lagi memanggil full section save
                         * karena full save memproses semua row dan membuat loading lama.
                         *
                         * Mulai sekarang tombol Update hanya menyimpan row yang berubah.
                         */
                        refreshMaterialValidationHighlights();
                        commitActiveMaterialFieldChange();

                        const changedRows = getChangedMaterialRowsForQuickUpdate();

                        if (changedRows.length === 0) {
                            hideAppLoading();

                            if (materialStructureDirty) {
                                openAppNotify('Ada tambah/hapus baris Material. Simpan cepat hanya mendukung edit row existing. Untuk sementara reload halaman lalu lakukan perubahan tanpa tambah/hapus baris dulu.', 'warning');
                            } else {
                                openAppNotify('Tidak ada perubahan Material yang perlu disimpan.', 'info');
                            }

                            isMaterialDirty = false;
                            refreshMaterialInitialSnapshot();
                            return;
                        }

                        const afterSave = function(data) {
                            hideAppLoading();
                            openAppNotify((data && data.message) ? data.message : 'Update Material dikirim.', 'success');
                            markMaterialControlsUndoBase();
                            isMaterialDirty = false;
                            refreshMaterialInitialSnapshot();
                        };

                        submitMaterialQuickUpdateAjax(changedRows, afterSave);
                        return;
                    }

                    if (section) {
                        prepareSectionOnlySubmit(section, submitter);
                    }
                });
            }
        });

        // Recalculate when exchange rates change
        document.getElementById('rateUSD').addEventListener('change', function() { formatRateInput(this); recalculateAllRows(); });
        document.getElementById('rateJPY').addEventListener('change', function() { formatRateInput(this); recalculateAllRows(); });
        document.getElementById('lmeRate').addEventListener('change', function() { formatRateInput(this); updateCostingResumePreview(); });
        document.getElementById('forecastDisplay').addEventListener('change', function () {
            formatForecastDisplay();
            recalculateAllRows();
        });
        document.getElementById('projectPeriod').addEventListener('change', recalculateAllRows);

        function recalculateAllRows() {
            const rows = document.querySelectorAll('#materialTableBody tr');
            rows.forEach(row => {
                const input = row.querySelector('.qty-req');
                if (input) calculateRow(input);
            });

            const cycleRows = document.querySelectorAll('#cycleTimeTableBody tr');
            cycleRows.forEach(row => {
                const input = row.querySelector('.ct-hour') || row.querySelector('.ct-sec');
                if (input) calculateCycleRow(input);
            });
        }

        function normalizeMaterialRowsToReasonableValues() {
            const rows = document.querySelectorAll('#materialTableBody tr');

            rows.forEach((row) => {
                const qtyReqInput = row.querySelector('.qty-req');
                const amount1Input = row.querySelector('.amount1');
                const qtyMoqInput = row.querySelector('.qty-moq');
                const currencySelect = row.querySelector('.currency');
                const importTaxInput = row.querySelector('.import-tax');
                const amount2Element = row.querySelector('.amount2');
                const totalPriceElement = row.querySelector('.total-price');
                const multiplyFactorElement = row.querySelector('.multiply-factor');
                const unitInput = row.querySelector('.unit');

                if (!qtyReqInput || !amount1Input || !qtyMoqInput || !currencySelect || !amount2Element || !totalPriceElement) {
                    return;
                }

                let qtyReq = parseInputNumber(qtyReqInput.value || 0);
                let moq = parseInputNumber(qtyMoqInput.value || 0);
                const currency = String(currencySelect.value || 'IDR').toUpperCase();
                const importTax = parseInputNumber(importTaxInput?.value || 0);
                const rate = getExchangeRate(currency);
                const taxFactor = Math.max(1e-9, 1 + (importTax / 100));
                const multiplyFactor = Math.max(1e-9, parseInputNumber(multiplyFactorElement?.textContent || 1));
                const unit = String(unitInput?.value || '').toUpperCase();
                const unitDivisor = (unit === 'METER' || unit === 'M' || unit === 'MTR' || unit === 'MM') ? 1000 : 1;
                const currentTotal = parseDataValueNumber(totalPriceElement.getAttribute('data-value') || totalPriceElement.textContent || 0);

                // Normalize unreasonable qty values from corrupted inputs/imports.
                if (qtyReq > 1000) {
                    qtyReq = 1;
                }
                if (qtyReq < 0) {
                    qtyReq = 0;
                }

                // Normalize MOQ so multiply factor stays realistic.
                if (moq <= 0 || moq > (qtyReq * 20)) {
                    moq = Math.max(qtyReq, qtyReq * 5);
                }

                const denom = Math.max(1e-9, qtyReq * Math.max(1, rate));
                const normalizedAmount2 = currentTotal > 0
                    ? (currentTotal / denom)
                    : parseInputNumber(amount2Element.textContent || 0);

                const normalizedAmount1 = (normalizedAmount2 * Math.max(1, unitDivisor)) / (multiplyFactor * taxFactor);

                qtyReqInput.value = floatToInput(Math.round(qtyReq));
                amount1Input.value = floatToInput(normalizedAmount1.toFixed(4));
                qtyMoqInput.value = floatToInput(Number(moq.toFixed(4)));
                amount2Element.textContent = floatToInput(Number(normalizedAmount2.toFixed(5)));
            });
        }

        function restoreMaterialRowsFromDatabase() {
            const rows = document.querySelectorAll('#materialTableBody tr');

            rows.forEach(row => {
                const qtyReqInput = row.querySelector('.qty-req');
                const amount1Input = row.querySelector('.amount1');
                const qtyMoqInput = row.querySelector('.qty-moq');
                const amount2Element = row.querySelector('.amount2');
                const totalPriceElement = row.querySelector('.total-price');

                if (qtyReqInput && qtyReqInput.dataset.originalQtyReq !== undefined) {
                    qtyReqInput.value = floatToInput(qtyReqInput.dataset.originalQtyReq || 0);
                }

                if (amount1Input && amount1Input.dataset.originalAmount1 !== undefined) {
                    amount1Input.value = floatToInput(amount1Input.dataset.originalAmount1 || 0);
                }

                if (qtyMoqInput && qtyMoqInput.dataset.originalMoq !== undefined) {
                    qtyMoqInput.value = floatToInput(qtyMoqInput.dataset.originalMoq || 0);
                }

                if (amount2Element && amount2Element.dataset.originalAmount2 !== undefined) {
                    amount2Element.textContent = floatToInput(Number(amount2Element.dataset.originalAmount2 || 0));
                }

                if (amount1Input && totalPriceElement && amount1Input.dataset.originalAmount1 !== undefined) {
                    const amount1Value = parseInputNumber(amount1Input.dataset.originalAmount1 || 0);
                    totalPriceElement.textContent = formatRupiah(amount1Value);
                    totalPriceElement.setAttribute('data-value', amount1Value);
                }
            });

            calculateTableTotal(false);
        }

        function syncMaterialTableFromRenderedValues() {
            const rows = document.querySelectorAll('#materialTableBody tr');
            rows.forEach((row) => {
                const input = row.querySelector('.qty-req') || row.querySelector('.amount1') || row.querySelector('.unit-price-basis');
                if (input && typeof calculateRow === 'function') {
                    calculateRow(input);
                }
            });

            calculateTableTotal(false);
        }


        window.debugMaterialRowTotal = function(rowNumber) {
            const row = document.querySelectorAll('#materialTableBody tr')[Number(rowNumber) - 1];
            if (!row) return null;

            const input = row.querySelector('.qty-req') || row.querySelector('.amount1') || row.querySelector('.unit-price-basis');
            if (input) {
                calculateRow(input);
            }

            return {
                row: rowNumber,
                qtyReq: row.querySelector('.qty-req')?.value || '',
                amount1: row.querySelector('.amount1')?.value || '',
                amount2Display: row.querySelector('.amount2')?.textContent || '',
                amount2Raw: row.querySelector('.amount2')?.getAttribute('data-raw-value') || '',
                currency: row.querySelector('.currency')?.value || '',
                totalPrice: row.querySelector('.total-price')?.textContent || '',
                totalRaw: row.querySelector('.total-price')?.getAttribute('data-value') || ''
            };
        };


        window.recalculateMaterialTotalsSafe = function() {
            if (typeof recalculateAllRows === 'function') {
                recalculateAllRows();
                return 'Material totals recalculated';
            }
            return 'recalculateAllRows is not available';
        };



        function normalizeAllCycleTimeDisplayValues() {
            document.querySelectorAll('#cycleTimeTableBody tr').forEach(function(row) {
                const hourInput = row.querySelector('.ct-hour');
                const secInput = row.querySelector('.ct-sec');
                const secPerInput = row.querySelector('.ct-sec-per');
                const costUnitInput = row.querySelector('.ct-cost-unit');

                if (hourInput && hourInput.value !== '') {
                    hourInput.value = formatCycleHourValue(parseCycleNumber(hourInput.value));
                }

                if (secInput && secInput.value !== '') {
                    secInput.value = formatCycleIntegerValue(parseCycleNumber(secInput.value));
                }

                if (secPerInput && secPerInput.value !== '') {
                    secPerInput.value = formatCycleIntegerValue(parseCycleNumber(secPerInput.value));
                }

                if (costUnitInput && costUnitInput.value !== '') {
                    costUnitInput.value = formatCycleCostUnitValue(parseCycleNumber(costUnitInput.value));
                }
            });
        }

        function formatAllCycleCostUnitInputs() {
            document.querySelectorAll('.ct-cost-unit').forEach(function(input) {
                input.value = formatCycleCostUnitValue(parseCycleNumber(input.value || 0));
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            normalizeAllCycleTimeDisplayValues();
            formatAllCycleCostUnitInputs();
            calculateCycleTotals();
            formatAllRateInputs();
            syncCostingResumeOverridesInput();
            updateCostingResumePreview();

            const costingResumeRefreshTargets = [
                '#costingForm input',
                '#costingForm select',
                '#materialTableBody input',
                '#materialTableBody select',
                '#cycleTimeTableBody input',
                '#cycleTimeTableBody select'
            ].join(',');

            document.querySelectorAll(costingResumeRefreshTargets).forEach(function(element) {
                element.addEventListener('input', updateCostingResumePreview);
                element.addEventListener('change', updateCostingResumePreview);
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function(formElement) {
                formElement.addEventListener('submit', normalizeRateInputsForSubmit);
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function(formElement) {
                formElement.addEventListener('submit', normalizeCycleTimeInputsForSubmit);
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function(formElement) {
                formElement.addEventListener('submit', function() {
                    if (typeof normalizeResumeMoneyInputsForSubmit === 'function') {
                        normalizeResumeMoneyInputsForSubmit();
                    }
                    if (typeof normalizeRateInputsForSubmit === 'function') {
                        normalizeRateInputsForSubmit();
                    }
                    if (typeof normalizeCycleTimeInputsForSubmit === 'function') {
                        normalizeCycleTimeInputsForSubmit();
                    }
                });
            });
        });

    </script>
    @if(!empty($readOnlyMode))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.readonly-costing input, .readonly-costing select, .readonly-costing textarea, .readonly-costing button').forEach(function (control) {
                control.disabled = true;
                control.setAttribute('aria-disabled', 'true');
            });
        });
    </script>
    @endif
@endsection
