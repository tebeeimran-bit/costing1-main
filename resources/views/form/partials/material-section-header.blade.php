<div class="form-section-title">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /></svg>
    Material
    <div class="section-actions">
        <button type="button" class="btn btn-secondary btn-sm" onclick="exportMaterialEditor()">Export Excel</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="exportMaterialEditor('cogm')">Export COGM</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('materialEditorFileInput').click()">Import Hasil Edit</button>
        <button type="button" class="btn btn-secondary btn-sm" id="materialUndoBtn" onclick="undoMaterialTable()" disabled aria-label="Undo" title="Undo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20a8 8 0 0 0-8-8H4"></path></svg></button>
        <button type="button" class="btn btn-secondary btn-sm" id="materialRedoBtn" onclick="redoMaterialTable()" disabled aria-label="Redo" title="Redo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 14 20 9 15 4"></polyline><path d="M4 20a8 8 0 0 1 8-8h8"></path></svg></button>
        <button type="button" class="btn btn-secondary btn-sm" id="materialDeleteSelectedBtn" onclick="deleteSelectedMaterialRows()">Hapus Terpilih</button>
        <button type="button" class="btn btn-secondary" onclick="addMaterialRow()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg> Tambah Baris</button>
    </div>
</div>
