        // Calculate totals for Resume COGM
        function applyResumeCostFormula(input, useStoredFormula = false) {
            if (!input) return 0;

            const enteredValue = useStoredFormula && input.dataset.resumeFormula
                ? input.dataset.resumeFormula
                : String(input.value || '').trim();
            const isFormula = enteredValue.startsWith('=') || /[+*/()×]/.test(enteredValue)
                || /\d\s*-\s*\d/.test(enteredValue) || /\d\s*[xX]\s*\d/.test(enteredValue);

            if (!isFormula) {
                delete input.dataset.resumeFormula;
                input.setCustomValidity('');
                formatResumeMoneyInput(input);
                return getResumeMoneyValue(input);
            }

            try {
                const materialCost = getResumeMoneyValue('materialCost');
                let expression = enteredValue.replace(/^\s*=\s*/, '');
                expression = expression.replace(/TOTAL[ _]MATERIAL[ _]COST|MATERIAL[ _]COST/gi, `(${materialCost})`);
                expression = expression.replace(/[xX×]/g, '*').replace(/\s+/g, '');
                expression = expression.replace(/\d[\d.,]*/g, (numberText) => String(parseResumeMoneyNumber(numberText)));

                if (!expression || !/^[0-9+\-*/().]+$/.test(expression)) {
                    throw new Error('Formula hanya boleh berisi angka dan operator +, -, *, /.');
                }

                const result = Function(`"use strict"; return (${expression});`)();
                if (!Number.isFinite(result)) {
                    throw new Error('Hasil formula tidak valid atau terjadi pembagian dengan nol.');
                }

                input.dataset.resumeFormula = enteredValue;
                input.setCustomValidity('');
                setResumeMoneyValue(input, result);
                return result;
            } catch (error) {
                input.setCustomValidity(error.message || 'Formula Depresiasi Tooling Cost tidak valid.');
                input.reportValidity();
                return getResumeMoneyValue(input);
            }
        }

        function calculateTotals(recalculateMaterialTable = true) {
            const materialCost = getResumeMoneyValue('materialCost');
            const laborCost = getResumeMoneyValue('laborCost');
            const toolingInput = document.getElementById('overheadCost');
            if (toolingInput?.dataset.resumeFormula) {
                applyResumeCostFormula(toolingInput, true);
            }
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

                if (element.dataset.crField === 'summary.tooling_cost') {
                    element.title = 'Masukkan angka atau formula, lalu tekan Enter untuk menghitung.';
                    element.addEventListener('keydown', (event) => {
                        if (event.key !== 'Enter') return;
                        event.preventDefault();
                        if (commitToolingFormulaFromPreview(element)) {
                            submitResumeCogmSectionFromEnter(event);
                        }
                    });
                    element.addEventListener('blur', () => {
                        if (/^\s*=/.test(element.textContent || '')) {
                            commitToolingFormulaFromPreview(element);
                        }
                    });
                }
            });
        }

        function commitToolingFormulaFromPreview(element) {
            const toolingInput = document.getElementById('overheadCost');
            if (!toolingInput || !element) return false;

            toolingInput.value = String(element.textContent || '').trim();
            const result = applyResumeCostFormula(toolingInput);
            if (toolingInput.validationMessage) return false;

            [
                'summary.material_pct', 'summary.process_pct',
                'summary.tooling_cost', 'summary.tooling_pct',
                'summary.admin_pct', 'summary.cogm_total', 'summary.cogm_pct',
                'metrics.potential_sales_month', 'metrics.potential_sales_lifetime',
                'metrics.rp_per_cct', 'metrics.estimated_mp'
            ].forEach((key) => delete costingResumeOverrides[key]);
            syncCostingResumeOverridesInput();
            calculateTotals(false);
            element.textContent = costingResumeMoney(result);
            return true;
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
