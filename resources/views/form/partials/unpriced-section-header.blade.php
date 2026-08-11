<div class="form-section-title">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z" /><line x1="3" y1="9" x2="21" y2="9" /><line x1="8" y1="9" x2="8" y2="21" /></svg>
    Rekapan Part Tanpa Harga
    <div class="section-actions">
        <button type="button" class="btn btn-primary btn-sm" id="unpricedAddSelectedBtn" onclick="addSelectedUnpricedRows()">Tambah Terpilih</button>
        <button type="button" class="btn btn-secondary btn-sm" id="unpricedDeleteSelectedBtn" onclick="deleteSelectedUnpricedRows()">Hapus Terpilih</button>
        @if(isset($trackingRevision) && $trackingRevision)
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('newPartRequestFileInput').click()">Import New Part Request</button>
            <button type="button" class="btn btn-secondary" onclick="exportNewPartRequestAndRefresh(@js(route('tracking-documents.export-new-part-request', ['revision' => $trackingRevision->id], absolute: false)), @js(route('tracking-documents.sync-new-part-request', ['revision' => $trackingRevision->id], absolute: false)))">Export New Part Request</button>
        @endif
    </div>
</div>
