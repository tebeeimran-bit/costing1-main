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

