<div class="form-section-title">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /></svg>
    Material
    <div class="section-actions">
        <details class="material-action-menu">
            <summary class="btn btn-secondary btn-sm">Export</summary>
            <div class="material-action-menu-panel">
                <button type="button" onclick="exportMaterialEditor()">Export Excel</button>
                <button type="button" onclick="exportMaterialEditor('cogm')">Export COGM</button>
            </div>
        </details>
        <details class="material-action-menu">
            <summary class="btn btn-secondary btn-sm">Import</summary>
            <div class="material-action-menu-panel">
                <button type="button" onclick="document.getElementById('materialEditorFileInput').click()">Import Hasil Edit</button>
                <details class="material-action-submenu">
                    <summary>Import Manual</summary>
                    <div class="material-action-submenu-panel">
                        <button type="button" onclick="chooseManualMaterialImport('replace')">Ganti Material</button>
                        <button type="button" onclick="chooseManualMaterialImport('append')">Tambah Material</button>
                    </div>
                </details>
            </div>
        </details>
        <button type="button" class="btn btn-secondary btn-sm" id="materialUndoBtn" onclick="undoMaterialTable()" disabled aria-label="Undo" title="Undo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20a8 8 0 0 0-8-8H4"></path></svg></button>
        <button type="button" class="btn btn-secondary btn-sm" id="materialRedoBtn" onclick="redoMaterialTable()" disabled aria-label="Redo" title="Redo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 14 20 9 15 4"></polyline><path d="M4 20a8 8 0 0 1 8-8h8"></path></svg></button>
        <button type="button" class="btn btn-secondary btn-sm" id="materialDeleteSelectedBtn" onclick="deleteSelectedMaterialRows()">Hapus Terpilih</button>
        <button type="button" class="btn btn-secondary" onclick="addMaterialRow()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg> Tambah Baris</button>
    </div>
</div>
<style>
.material-action-menu{position:relative}.material-action-menu>summary{list-style:none;cursor:pointer}.material-action-menu>summary::-webkit-details-marker,.material-action-submenu>summary::-webkit-details-marker{display:none}.material-action-menu-panel{position:absolute;z-index:1200;top:calc(100% + 6px);right:0;min-width:170px;padding:5px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;box-shadow:0 12px 28px rgba(15,23,42,.16)}.material-action-menu-panel button,.material-action-submenu>summary{display:flex;width:100%;align-items:center;box-sizing:border-box;border:0;border-radius:6px;padding:8px 10px;background:#fff;color:#334155;font-size:.72rem;font-weight:750;text-align:left;cursor:pointer}.material-action-menu-panel button:hover,.material-action-submenu>summary:hover{background:#eff6ff;color:#1d4ed8}.material-action-submenu{position:relative}.material-action-submenu>summary{justify-content:space-between}.material-action-submenu>summary::after{content:'>';margin-left:12px}.material-action-submenu-panel{position:absolute;z-index:1201;top:0;left:calc(100% + 6px);min-width:160px;padding:5px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;box-shadow:0 12px 28px rgba(15,23,42,.16)}
.form-page #materialFormSection,.form-page #materialFormSection>.form-section-title,.form-page #materialFormSection .section-actions{overflow:visible}.material-action-menu{flex:0 0 auto}
</style>
