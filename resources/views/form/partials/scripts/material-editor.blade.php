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

