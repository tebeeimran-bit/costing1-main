<div id="partlistImportConfirmModal" class="confirm-modal is-hidden" aria-hidden="true">
        <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="partlistImportConfirmTitle">
            <div class="confirm-modal-head">
                <span class="confirm-modal-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3l-8.47-14.14a2 2 0 0 0-3.42 0z" />
                        <line x1="12" y1="9" x2="12" y2="13" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                </span>
                <h3 id="partlistImportConfirmTitle" class="confirm-modal-title">Konfirmasi Update Partlist</h3>
            </div>
            <div class="confirm-modal-body">
                Yakin ingin mengupdate partlist? Data material yang ada akan digantikan dari file partlist.
            </div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-secondary" id="partlistImportCancelBtn">Batal</button>
                <button type="button" class="btn btn-primary" id="partlistImportOkBtn">Ya, Update Partlist</button>
            </div>
        </div>
    </div>

    <div id="exportRatesConfirmModal" class="confirm-modal is-hidden" aria-hidden="true">
        <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="exportRatesConfirmTitle">
            <div class="confirm-modal-head">
                <span class="confirm-modal-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <h3 id="exportRatesConfirmTitle" class="confirm-modal-title">Konfirmasi Rates</h3>
            </div>
            <div class="confirm-modal-body">Apakah rates sudah sesuai?</div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-secondary" id="exportRatesCancelBtn">Tidak</button>
                <button type="button" class="btn btn-primary" id="exportRatesOkBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <div id="materialDownloadConfirmModal" class="confirm-modal is-hidden" aria-hidden="true">
        <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="materialDownloadConfirmTitle">
            <div class="confirm-modal-head">
                <span class="confirm-modal-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </span>
                <h3 id="materialDownloadConfirmTitle" class="confirm-modal-title">Konfirmasi Download</h3>
            </div>
            <div class="confirm-modal-body">
                Apakah mau download filenya?<br>
                <strong id="materialDownloadFileName" style="display:block;margin-top:6px;overflow-wrap:anywhere;color:#334155"></strong>
            </div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-secondary" id="materialDownloadCancelBtn">Batal</button>
                <button type="button" class="btn btn-primary" id="materialDownloadOkBtn">Download</button>
            </div>
        </div>
    </div>
