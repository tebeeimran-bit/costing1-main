<script>
    function toggleEditDateWrap(prefix) {
        const status = document.getElementById('edit' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'Status').value;
        const wrap = document.getElementById('edit' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'DateWrap');
        wrap.style.display = status === 'ada' ? '' : 'none';

        applyBusinessRules(prefix);
    }

    function applyBusinessRules(changedPrefix) {
        const a00 = document.getElementById('editA00Status');
        const a04 = document.getElementById('editA04Status');
        const a05 = document.getElementById('editA05Status');

        // Rule: A04 and A05 are mutually exclusive
        if (changedPrefix === 'a04' && a04.value === 'ada') {
            a05.value = 'belum_ada';
            toggleEditDateWrap('a05');
        }
        if (changedPrefix === 'a05' && a05.value === 'ada') {
            a04.value = 'belum_ada';
            toggleEditDateWrap('a04');
        }

        // Rule: If A04 or A05 = ada, force A00 = ada
        if (a04.value === 'ada' || a05.value === 'ada') {
            a00.value = 'ada';
            a00.disabled = true;
            a00.title = 'A00 otomatis "Ada" karena A04/A05 sudah ada';
            a00.style.opacity = '0.6';
            document.getElementById('editA00DateWrap').style.display = '';
        } else {
            a00.disabled = false;
            a00.title = '';
            a00.style.opacity = '1';
        }
    }

    function openEditDocModal(revisionId, data) {
        const baseUrl = '{{ url("database/project-documents") }}';
        document.getElementById('editDocForm').action = baseUrl + '/' + revisionId;
        document.getElementById('editDocLabel').textContent = data.customer + ' — ' + data.model + ' — ' + data.part_name;

        // A00
        document.getElementById('editA00Status').value = data.a00 === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editA00Date').value = data.a00_received_date || '';
        document.getElementById('editA00DocName').textContent = data.a00_doc ? 'File saat ini: ' + data.a00_doc : '';
        toggleEditDateWrap('a00');

        // A04
        document.getElementById('editA04Status').value = data.a04 === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editA04Date').value = data.a04_received_date || '';
        document.getElementById('editA04DocName').textContent = data.a04_doc ? 'File saat ini: ' + data.a04_doc : '';
        document.getElementById('editA04Reason').value = data.a04_reason || '';
        toggleEditDateWrap('a04');

        // A05
        document.getElementById('editA05Status').value = data.a05 === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editA05Date').value = data.a05_received_date || '';
        document.getElementById('editA05DocName').textContent = data.a05_doc ? 'File saat ini: ' + data.a05_doc : '';
        toggleEditDateWrap('a05');

        // Partlist
        document.getElementById('editPartlistStatus').value = data.partlist === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editPartlistDate').value = data.partlist_received_date || '';
        document.getElementById('editPartlistDocName').textContent = data.partlist_doc ? 'File saat ini: ' + data.partlist_doc : '';
        document.getElementById('editPartlistRevisionCount').value = data.partlist_revision_count || 0;
        toggleEditDateWrap('partlist');

        // UMH
        document.getElementById('editUmhStatus').value = data.umh === 'ada' ? 'ada' : 'belum_ada';
        document.getElementById('editUmhDate').value = data.umh_received_date || '';
        document.getElementById('editUmhDocName').textContent = data.umh_doc ? 'File saat ini: ' + data.umh_doc : '';
        document.getElementById('editUmhRevisionCount').value = data.umh_revision_count || 0;
        toggleEditDateWrap('umh');

        // Apply business rules after loading values
        applyBusinessRules('');

        document.getElementById('editDocModal').classList.remove('is-hidden');
    }

    function closeEditDocModal() {
        document.getElementById('editDocModal').classList.add('is-hidden');

        if (shouldReturnToDashboardAfterDocumentModal) {
            showReturnToDashboardLoading();
            window.location.href = dashboardReturnUrl;
        }
    }

    function showReturnToDashboardLoading() {
        let overlay = document.getElementById('returnDashboardLoadingOverlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'returnDashboardLoadingOverlay';
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.zIndex = '100000';
            overlay.style.background = 'rgba(15, 23, 42, 0.42)';
            overlay.style.backdropFilter = 'blur(2px)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.innerHTML = `
                <div style="background:#fff; border-radius:16px; padding:1.35rem 1.65rem; min-width:220px; box-shadow:0 24px 60px rgba(15,23,42,.22); display:grid; justify-items:center; gap:.8rem;">
                    <div style="width:38px; height:38px; border:4px solid #dbeafe; border-top-color:#2563eb; border-radius:999px; animation:returnDashboardSpin .75s linear infinite;"></div>
                    <div style="font-size:.88rem; font-weight:800; color:#334155;">Kembali ke dashboard...</div>
                </div>
            `;

            const style = document.createElement('style');
            style.textContent = '@keyframes returnDashboardSpin{to{transform:rotate(360deg)}}';
            document.head.appendChild(style);
            document.body.appendChild(overlay);
        }

        overlay.style.display = 'flex';
    }

    function openDeleteDocModal(revisionId, name) {
        const baseUrl = '{{ url("database/project-documents") }}';
        document.getElementById('deleteDocForm').action = baseUrl + '/' + revisionId;
        document.getElementById('deleteDocName').textContent = name;
        document.getElementById('deleteDocModal').classList.remove('is-hidden');
    }

    function closeDeleteDocModal() {
        document.getElementById('deleteDocModal').classList.add('is-hidden');
    }

    const dashboardReturnUrl = @json(route('dashboard', absolute: false));
    const shouldReturnToDashboardAfterDocumentModal = @json((bool) session('open_document_revision_id'));


    function validateAndSubmitProjectDocumentForm() {
        const a00 = document.getElementById('editA00Status');
        const a04 = document.getElementById('editA04Status');
        const a05 = document.getElementById('editA05Status');
        const a04Reason = document.getElementById('editA04Reason');
        const a04File = document.getElementById('editA04File');
        const a05File = document.getElementById('editA05File');
        const a04DocName = document.getElementById('editA04DocName');
        const a05DocName = document.getElementById('editA05DocName');
        const partlist = document.getElementById('editPartlistStatus');
        const umh = document.getElementById('editUmhStatus');
        const partlistFile = document.getElementById('editPartlistFile');
        const umhFile = document.getElementById('editUmhFile');
        const partlistDocName = document.getElementById('editPartlistDocName');
        const umhDocName = document.getElementById('editUmhDocName');

        if (a04 && a04.value === 'ada') {
            if (!a04Reason || a04Reason.value.trim() === '') {
                alert('Alasan Canceled/Failed wajib diisi untuk status A04.');
                a04Reason?.focus();
                return false;
            }

            const hasExistingA04Doc = a04DocName && a04DocName.textContent.trim() !== '';
            if ((!a04File || a04File.files.length === 0) && !hasExistingA04Doc) {
                alert('Dokumen A04 wajib diupload.');
                a04File?.focus();
                return false;
            }
        }

        if (a05 && a05.value === 'ada') {
            const hasExistingA05Doc = a05DocName && a05DocName.textContent.trim() !== '';
            if ((!a05File || a05File.files.length === 0) && !hasExistingA05Doc) {
                alert('Dokumen A05 wajib diupload.');
                a05File?.focus();
                return false;
            }
        }

        if (partlist && partlist.value === 'ada') {
            const hasExistingPartlistDoc = partlistDocName && partlistDocName.textContent.trim() !== '';
            if ((!partlistFile || partlistFile.files.length === 0) && !hasExistingPartlistDoc) {
                alert('Dokumen Partlist wajib diupload.');
                partlistFile?.focus();
                return false;
            }
        }

        if (umh && umh.value === 'ada') {
            const hasExistingUmhDoc = umhDocName && umhDocName.textContent.trim() !== '';
            if ((!umhFile || umhFile.files.length === 0) && !hasExistingUmhDoc) {
                alert('Dokumen UMH wajib diupload.');
                umhFile?.focus();
                return false;
            }
        }

        if (a00) {
            a00.disabled = false;
        }

        if (shouldReturnToDashboardAfterDocumentModal) {
            showReturnToDashboardLoading();
        }

        return true;
    }

    function focusTargetDocumentSection(targetStatus) {
        if (targetStatus !== 'A04' && targetStatus !== 'A05') {
            return;
        }

        const lower = targetStatus.toLowerCase();
        const statusSelect = document.getElementById('edit' + targetStatus + 'Status');
        const dateInput = document.getElementById('edit' + targetStatus + 'Date');
        const fileInput = document.getElementById('edit' + targetStatus + 'File');
        const reasonInput = targetStatus === 'A04' ? document.getElementById('editA04Reason') : null;
        const dateWrap = document.getElementById('edit' + targetStatus + 'DateWrap');

        if (statusSelect) {
            statusSelect.value = 'ada';
            toggleEditDateWrap(lower);
        }

        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().slice(0, 10);
        }

        if (dateWrap) {
            dateWrap.style.display = '';
            dateWrap.style.border = '2px solid ' + (targetStatus === 'A04' ? '#fca5a5' : '#86efac');
            dateWrap.style.borderRadius = '12px';
            dateWrap.style.padding = '0.55rem';
            dateWrap.style.background = targetStatus === 'A04' ? '#fff7f7' : '#f0fdf4';
        }

        window.setTimeout(function () {
            if (reasonInput) {
                reasonInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                reasonInput.focus();
                return;
            }

            if (fileInput) {
                fileInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                fileInput.focus();
            }
        }, 250);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const openRevisionId = @json(session('open_document_revision_id'));
        const targetStatus = @json(session('open_document_target_status'));

        if (!openRevisionId) {
            return;
        }

        const editButton = document.querySelector('.js-edit-doc-btn[data-revision-id="' + openRevisionId + '"]');

        if (editButton) {
            editButton.click();
            window.setTimeout(function () {
                focusTargetDocumentSection(targetStatus);
            }, 250);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeEditDocModal();
            closeDeleteDocModal();
        }
    });
</script>
