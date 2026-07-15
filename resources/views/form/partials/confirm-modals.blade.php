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

    <!-- Modal Konfirmasi Unsaved Material -->
    <div id="unsavedMaterialConfirmModal" class="confirm-modal is-hidden" style="z-index: 1000;" aria-hidden="true">
        <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="unsavedMaterialConfirmTitle">
            <div class="confirm-modal-head">
                <span class="confirm-modal-icon" style="background: #fef08a; color: #b45309;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                </span>
                <h3 id="unsavedMaterialConfirmTitle" class="confirm-modal-title" style="color: #b45309;">Perubahan Belum Disimpan</h3>
            </div>
            <div class="confirm-modal-body" style="font-size: 0.8rem;">
                Anda memiliki perubahan pada section Material yang belum di-Update. Apakah Anda ingin meng-Update menyimpannya terlebih dahulu sebelum berpindah bagian? <br><br>
                <em>Jika Anda memilih Abaikan & Pindah, maka data yang barusan diketik berpotensi hilang saat Reload/Update section lain.</em>
            </div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-secondary" id="unsavedMaterialIgnoreBtn">Abaikan & Pindah</button>
                <button type="button" class="btn btn-primary" id="unsavedMaterialSaveBtn" style="background: #eab308; border-color: #ca8a04; color: white;">Ya, Update Sekarang</button>
            </div>
        </div>
    </div>
    </div>
