<!-- Submit Buttons -->
<div class="form-actions">
    <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('dashboard', absolute: false) }}'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        Batal
    </button>
    <button type="submit" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" /><polyline points="17 21 17 13 7 13 7 21" /><polyline points="7 3 7 8 15 8" /></svg>
        Simpan Data Costing
    </button>
</div>
</form>

<input type="file" id="materialEditorFileInput" accept=".xls,.xlsx" hidden onchange="importMaterialEditor(this)">
<input type="file" id="manualMaterialFileInput" accept=".xls,.xlsx" hidden data-import-url="{{ route('costing.material-excel.import-manual', absolute: false) }}" onchange="importManualMaterialExcel(this)">
@if(isset($trackingRevision) && $trackingRevision)
<input type="file" id="newPartRequestFileInput" accept=".xls,.xlsx" hidden data-import-url="{{ route('tracking-documents.import-new-part-request', ['revision' => $trackingRevision->id], absolute: false) }}" onchange="importNewPartRequest(this)">
@endif

<form action="{{ route('costing.import-partlist', absolute: false) }}" method="POST" id="partlistImportForm" enctype="multipart/form-data" style="position:absolute; width:0; height:0; overflow:hidden;">
    @csrf
    @if(isset($costingData) && $costingData)<input type="hidden" name="costing_data_id" value="{{ $costingData->id }}">@endif
    @if(isset($trackingRevisionId) && $trackingRevisionId)<input type="hidden" name="tracking_revision_id" value="{{ $trackingRevisionId }}">@endif
    <input type="hidden" name="wire_rate_id" id="importWireRateId" value="{{ $selectedWireRateId }}">
    <input type="hidden" name="business_category_id" id="importBusinessCategoryId" value="{{ $costingData->product->line ?? ($trackingProjectPrefill['business_category_id'] ?? '') }}">
    <input type="hidden" name="customer_id" id="importCustomerId" value="{{ $costingData->customer_id ?? ($trackingProjectPrefill['customer_id'] ?? '') }}">
    <input type="hidden" name="period" id="importPeriod" value="{{ $costingData->period ?? '' }}">
    <input type="hidden" name="line" id="importLine" value="{{ $costingData->line ?? '' }}">
    <input type="hidden" name="model" id="importModel" value="{{ $costingData->model ?? ($trackingProjectPrefill['model'] ?? '') }}">
    <input type="hidden" name="assy_no" id="importAssyNo" value="{{ $costingData->assy_no ?? ($trackingProjectPrefill['assy_no'] ?? '') }}">
    <input type="hidden" name="assy_name" id="importAssyName" value="{{ $costingData->assy_name ?? ($trackingProjectPrefill['assy_name'] ?? '') }}">
    <input type="hidden" name="exchange_rate_usd" id="importRateUsd" value="{{ $costingData->exchange_rate_usd ?? ($activeWireRate->usd_rate ?? 15500) }}">
    <input type="hidden" name="exchange_rate_jpy" id="importRateJpy" value="{{ $costingData->exchange_rate_jpy ?? ($activeWireRate->jpy_rate ?? 103) }}">
    <input type="hidden" name="lme_rate" id="importLmeRate" value="{{ $costingData->lme_rate ?? ($activeWireRate->lme_active ?? '') }}">
    <input type="hidden" name="forecast" id="importForecast" value="{{ $forecastValue ?? 0 }}">
    <input type="hidden" name="project_period" id="importProjectPeriod" value="{{ $costingData->project_period ?? 2 }}">
    <input type="file" name="import_partlist_file" id="importPartlistFileInput" accept=".xls,.xlsx" onchange="if(this.files && this.files.length){ submitPartlistImport(); }">
</form>

<form action="{{ route('costing.import-cogm', absolute: false) }}" method="POST" id="cogmImportForm" enctype="multipart/form-data" style="position:absolute; width:0; height:0; overflow:hidden;">
    @csrf
    @if(isset($costingData) && $costingData)<input type="hidden" name="costing_data_id" value="{{ $costingData->id }}">@endif
    @if(isset($trackingRevisionId) && $trackingRevisionId)<input type="hidden" name="tracking_revision_id" value="{{ $trackingRevisionId }}">@endif
    <input type="hidden" name="wire_rate_id" id="cogmImportWireRateId" value="{{ $selectedWireRateId }}">
    <input type="hidden" name="business_category_id" id="cogmImportBusinessCategoryId" value="{{ $costingData->product->line ?? ($trackingProjectPrefill['business_category_id'] ?? '') }}">
    <input type="hidden" name="customer_id" id="cogmImportCustomerId" value="{{ $costingData->customer_id ?? ($trackingProjectPrefill['customer_id'] ?? '') }}">
    <input type="hidden" name="period" id="cogmImportPeriod" value="{{ $costingData->period ?? '' }}">
    <input type="hidden" name="line" id="cogmImportLine" value="{{ $costingData->line ?? '' }}">
    <input type="hidden" name="model" id="cogmImportModel" value="{{ $costingData->model ?? ($trackingProjectPrefill['model'] ?? '') }}">
    <input type="hidden" name="assy_no" id="cogmImportAssyNo" value="{{ $costingData->assy_no ?? ($trackingProjectPrefill['assy_no'] ?? '') }}">
    <input type="hidden" name="assy_name" id="cogmImportAssyName" value="{{ $costingData->assy_name ?? ($trackingProjectPrefill['assy_name'] ?? '') }}">
    <input type="hidden" name="exchange_rate_usd" id="cogmImportRateUsd" value="{{ $costingData->exchange_rate_usd ?? ($activeWireRate->usd_rate ?? 15500) }}">
    <input type="hidden" name="exchange_rate_jpy" id="cogmImportRateJpy" value="{{ $costingData->exchange_rate_jpy ?? ($activeWireRate->jpy_rate ?? 103) }}">
    <input type="hidden" name="lme_rate" id="cogmImportLmeRate" value="{{ $costingData->lme_rate ?? ($activeWireRate->lme_active ?? '') }}">
    <input type="hidden" name="forecast" id="cogmImportForecast" value="{{ $forecastValue ?? 0 }}">
    <input type="hidden" name="project_period" id="cogmImportProjectPeriod" value="{{ $costingData->project_period ?? 2 }}">
    <input type="file" name="import_cogm_file" id="importCogmFileInput" accept=".xls,.xlsx" onchange="if(this.files && this.files.length){ submitCogmImport(); }">
</form>

<form action="{{ route('costing.import-umh', absolute: false) }}" method="POST" id="umhImportForm" enctype="multipart/form-data" style="position:absolute; width:0; height:0; overflow:hidden;">
    @csrf
    @if(isset($costingData) && $costingData)<input type="hidden" name="costing_data_id" value="{{ $costingData->id }}">@endif
    @if(isset($trackingRevisionId) && $trackingRevisionId)<input type="hidden" name="tracking_revision_id" value="{{ $trackingRevisionId }}">@endif
    <input type="file" name="import_umh_file" id="importUmhFileInput" accept=".xls,.xlsx" onchange="if(this.files && this.files.length){ submitUmhImport(); }">
</form>

@include('form.partials.confirm-modals')
