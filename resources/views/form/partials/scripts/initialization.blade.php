        // Initialize calculations on page load
        document.addEventListener('DOMContentLoaded', function () {
            bindMaterialTableColumnResizer();
            initSectionToggles();
            bindMaterialTableBehaviors();
            initMaterialFilterPopup();
            initMaterialHeaderFilters();
            normalizeMaterialTextInputs();
            markMaterialControlsUndoBase();
            applyMaterialFilters();
            updateMaterialSelectAllRowsState();
            formatForecastDisplay();

            const exchangeRateSelector = document.getElementById('exchangeRateSelector');
            if (exchangeRateSelector) {
                restoreSelectedExchangeRate(exchangeRateSelector);
                updateRatesFromExchangeRate(exchangeRateSelector);
            }

            // Recalculate material rows on load so Multiply Factor follows the Excel formula.
            // This is needed because rendered HTML may still show default "1".
            recalculateAllRows();
            calculateTotals();

            const serverMaterialCost = Number(document.getElementById('serverMaterialCost')?.value || 0);
            if (serverMaterialCost > 0) {
                const tableTotal = document.getElementById('tableTotalMaterial');
                if (tableTotal) tableTotal.textContent = formatRupiah(serverMaterialCost);
                const materialCostInput = document.getElementById('materialCost');
                if (materialCostInput) setResumeMoneyValue(materialCostInput, serverMaterialCost);
                calculateTotals(false);
            }

            refreshUnpricedRecap();
            bindUnpricedManualPriceInputs();
            bindUnpricedDeleteButtons();
            bindUnpricedAddPriceButtons();

            const cycleRows = document.querySelectorAll('#cycleTimeTableBody tr');
            cycleRows.forEach(row => {
                const input = row.querySelector('.ct-hour') || row.querySelector('.ct-sec');
                if (input) calculateCycleRow(input);
            });

            calculateCycleTotals();

            const forecastDisplay = document.getElementById('forecastDisplay');
            if (forecastDisplay) {
                forecastDisplay.addEventListener('input', function () {
                    syncForecastHidden();
                    recalculateAllRows();
                });

                forecastDisplay.addEventListener('blur', function () {
                    formatForecastDisplay();
                    recalculateAllRows();
                });
            }

            bindResumeCogmEnterSave();

            const costingForm = document.getElementById('costingForm');
            if (costingForm) {
                costingForm.addEventListener('submit', function (event) {
                    normalizeMaterialTextInputs();
                    syncForecastHidden();

                    const submitter = event.submitter;
                    const updateSectionInput = document.getElementById('updateSectionInput');
                    const section = submitter?.dataset?.section || '';

                    if (section === 'resume_cogm' && typeof calculateTotals === 'function') {
                        calculateTotals(false);
                    }

                    if (typeof normalizeResumeMoneyInputsForSubmit === 'function') {
                        normalizeResumeMoneyInputsForSubmit();
                    }
                    if (typeof normalizeRateInputsForSubmit === 'function') {
                        normalizeRateInputsForSubmit();
                    }
                    if (typeof normalizeCycleTimeInputsForSubmit === 'function') {
                        normalizeCycleTimeInputsForSubmit();
                    }

                    refreshUnpricedRecap();

                    if (updateSectionInput) {
                        updateSectionInput.value = section;
                    }

                    if (section === 'material') {
                        const validationResult = getMaterialSectionValidationResult();

                        if (shouldShowMaterialValidationNotice(validationResult)) {
                            event.preventDefault();

                            showMaterialValidationModal(validationResult.message, validationResult.type, function () {
                                acknowledgeMaterialValidationNotice(validationResult);
                                bypassMaterialValidationNoticeOnce = true;

                                if (typeof costingForm.requestSubmit === 'function') {
                                    costingForm.requestSubmit(submitter || undefined);
                                } else {
                                    costingForm.submit();
                                }
                            });

                            return;
                        }
                    }

                    if (section === 'material') {
                        event.preventDefault();

                        /*
                         * HARD FIX:
                         * Tombol Update Material tidak boleh lagi memanggil full section save
                         * karena full save memproses semua row dan membuat loading lama.
                         *
                         * Mulai sekarang tombol Update hanya menyimpan row yang berubah.
                         */
                        refreshMaterialValidationHighlights();
                        commitActiveMaterialFieldChange();

                        const changedRows = getChangedMaterialRowsForQuickUpdate();

                        if (changedRows.length === 0) {
                            hideAppLoading();

                            if (materialStructureDirty) {
                                openAppNotify('Ada tambah/hapus baris Material. Simpan cepat hanya mendukung edit row existing. Untuk sementara reload halaman lalu lakukan perubahan tanpa tambah/hapus baris dulu.', 'warning');
                            } else {
                                openAppNotify('Tidak ada perubahan Material yang perlu disimpan.', 'info');
                            }

                            isMaterialDirty = false;
                            refreshMaterialInitialSnapshot();
                            return;
                        }

                        const afterSave = function(data) {
                            hideAppLoading();
                            openAppNotify((data && data.message) ? data.message : 'Update Material dikirim.', 'success');
                            markMaterialControlsUndoBase();
                            isMaterialDirty = false;
                            refreshMaterialInitialSnapshot();
                        };

                        submitMaterialQuickUpdateAjax(changedRows, afterSave);
                        return;
                    }

                    if (section) {
                        prepareSectionOnlySubmit(section, submitter);
                    }
                });
            }
        });

        // Recalculate when exchange rates change
        document.getElementById('rateUSD').addEventListener('change', function() { formatRateInput(this); recalculateAllRows(); });
        document.getElementById('rateJPY').addEventListener('change', function() { formatRateInput(this); recalculateAllRows(); });
        document.getElementById('lmeRate').addEventListener('change', function() { formatRateInput(this); updateCostingResumePreview(); });
        document.getElementById('forecastDisplay').addEventListener('change', function () {
            formatForecastDisplay();
            recalculateAllRows();
        });
        document.getElementById('projectPeriod').addEventListener('change', recalculateAllRows);

        function recalculateAllRows() {
            const rows = document.querySelectorAll('#materialTableBody tr');
            rows.forEach(row => {
                const input = row.querySelector('.qty-req');
                if (input) calculateRow(input);
            });

            const cycleRows = document.querySelectorAll('#cycleTimeTableBody tr');
            cycleRows.forEach(row => {
                const input = row.querySelector('.ct-hour') || row.querySelector('.ct-sec');
                if (input) calculateCycleRow(input);
            });
        }

        function normalizeMaterialRowsToReasonableValues() {
            const rows = document.querySelectorAll('#materialTableBody tr');

            rows.forEach((row) => {
                const qtyReqInput = row.querySelector('.qty-req');
                const amount1Input = row.querySelector('.amount1');
                const qtyMoqInput = row.querySelector('.qty-moq');
                const currencySelect = row.querySelector('.currency');
                const importTaxInput = row.querySelector('.import-tax');
                const amount2Element = row.querySelector('.amount2');
                const totalPriceElement = row.querySelector('.total-price');
                const multiplyFactorElement = row.querySelector('.multiply-factor');
                const unitInput = row.querySelector('.unit');

                if (!qtyReqInput || !amount1Input || !qtyMoqInput || !currencySelect || !amount2Element || !totalPriceElement) {
                    return;
                }

                let qtyReq = parseInputNumber(qtyReqInput.value || 0);
                let moq = parseInputNumber(qtyMoqInput.value || 0);
                const currency = String(currencySelect.value || 'IDR').toUpperCase();
                const importTax = parseInputNumber(importTaxInput?.value || 0);
                const rate = getExchangeRate(currency);
                const taxFactor = Math.max(1e-9, 1 + (importTax / 100));
                const multiplyFactor = Math.max(1e-9, parseInputNumber(multiplyFactorElement?.textContent || 1));
                const unit = String(unitInput?.value || '').toUpperCase();
                const unitDivisor = (unit === 'METER' || unit === 'M' || unit === 'MTR' || unit === 'MM') ? 1000 : 1;
                const currentTotal = parseDataValueNumber(totalPriceElement.getAttribute('data-value') || totalPriceElement.textContent || 0);

                // Normalize unreasonable qty values from corrupted inputs/imports.
                if (qtyReq > 1000) {
                    qtyReq = 1;
                }
                if (qtyReq < 0) {
                    qtyReq = 0;
                }

                // Normalize MOQ so multiply factor stays realistic.
                if (moq <= 0 || moq > (qtyReq * 20)) {
                    moq = Math.max(qtyReq, qtyReq * 5);
                }

                const denom = Math.max(1e-9, qtyReq * Math.max(1, rate));
                const normalizedAmount2 = currentTotal > 0
                    ? (currentTotal / denom)
                    : parseInputNumber(amount2Element.textContent || 0);

                const normalizedAmount1 = (normalizedAmount2 * Math.max(1, unitDivisor)) / (multiplyFactor * taxFactor);

                qtyReqInput.value = floatToInput(Math.round(qtyReq));
                amount1Input.value = floatToInput(normalizedAmount1.toFixed(4));
                qtyMoqInput.value = floatToInput(Number(moq.toFixed(4)));
                amount2Element.textContent = floatToInput(Number(normalizedAmount2.toFixed(5)));
            });
        }

        function restoreMaterialRowsFromDatabase() {
            const rows = document.querySelectorAll('#materialTableBody tr');

            rows.forEach(row => {
                const qtyReqInput = row.querySelector('.qty-req');
                const amount1Input = row.querySelector('.amount1');
                const qtyMoqInput = row.querySelector('.qty-moq');
                const amount2Element = row.querySelector('.amount2');
                const totalPriceElement = row.querySelector('.total-price');

                if (qtyReqInput && qtyReqInput.dataset.originalQtyReq !== undefined) {
                    qtyReqInput.value = floatToInput(qtyReqInput.dataset.originalQtyReq || 0);
                }

                if (amount1Input && amount1Input.dataset.originalAmount1 !== undefined) {
                    amount1Input.value = floatToInput(amount1Input.dataset.originalAmount1 || 0);
                }

                if (qtyMoqInput && qtyMoqInput.dataset.originalMoq !== undefined) {
                    qtyMoqInput.value = floatToInput(qtyMoqInput.dataset.originalMoq || 0);
                }

                if (amount2Element && amount2Element.dataset.originalAmount2 !== undefined) {
                    amount2Element.textContent = floatToInput(Number(amount2Element.dataset.originalAmount2 || 0));
                }

                if (amount1Input && totalPriceElement && amount1Input.dataset.originalAmount1 !== undefined) {
                    const amount1Value = parseInputNumber(amount1Input.dataset.originalAmount1 || 0);
                    totalPriceElement.textContent = formatRupiah(amount1Value);
                    totalPriceElement.setAttribute('data-value', amount1Value);
                }
            });

            calculateTableTotal(false);
        }

        function syncMaterialTableFromRenderedValues() {
            const rows = document.querySelectorAll('#materialTableBody tr');
            rows.forEach((row) => {
                const input = row.querySelector('.qty-req') || row.querySelector('.amount1') || row.querySelector('.unit-price-basis');
                if (input && typeof calculateRow === 'function') {
                    calculateRow(input);
                }
            });

            calculateTableTotal(false);
        }


        window.debugMaterialRowTotal = function(rowNumber) {
            const row = document.querySelectorAll('#materialTableBody tr')[Number(rowNumber) - 1];
            if (!row) return null;

            const input = row.querySelector('.qty-req') || row.querySelector('.amount1') || row.querySelector('.unit-price-basis');
            if (input) {
                calculateRow(input);
            }

            return {
                row: rowNumber,
                qtyReq: row.querySelector('.qty-req')?.value || '',
                amount1: row.querySelector('.amount1')?.value || '',
                amount2Display: row.querySelector('.amount2')?.textContent || '',
                amount2Raw: row.querySelector('.amount2')?.getAttribute('data-raw-value') || '',
                currency: row.querySelector('.currency')?.value || '',
                totalPrice: row.querySelector('.total-price')?.textContent || '',
                totalRaw: row.querySelector('.total-price')?.getAttribute('data-value') || ''
            };
        };


        window.recalculateMaterialTotalsSafe = function() {
            if (typeof recalculateAllRows === 'function') {
                recalculateAllRows();
                return 'Material totals recalculated';
            }
            return 'recalculateAllRows is not available';
        };



        function normalizeAllCycleTimeDisplayValues() {
            document.querySelectorAll('#cycleTimeTableBody tr').forEach(function(row) {
                const hourInput = row.querySelector('.ct-hour');
                const secInput = row.querySelector('.ct-sec');
                const secPerInput = row.querySelector('.ct-sec-per');
                const costUnitInput = row.querySelector('.ct-cost-unit');

                if (hourInput && hourInput.value !== '') {
                    hourInput.value = formatCycleHourValue(parseCycleNumber(hourInput.value));
                }

                if (secInput && secInput.value !== '') {
                    secInput.value = formatCycleIntegerValue(parseCycleNumber(secInput.value));
                }

                if (secPerInput && secPerInput.value !== '') {
                    secPerInput.value = formatCycleIntegerValue(parseCycleNumber(secPerInput.value));
                }

                if (costUnitInput && costUnitInput.value !== '') {
                    costUnitInput.value = formatCycleCostUnitValue(parseCycleNumber(costUnitInput.value));
                }
            });
        }

        function formatAllCycleCostUnitInputs() {
            document.querySelectorAll('.ct-cost-unit').forEach(function(input) {
                input.value = formatCycleCostUnitValue(parseCycleNumber(input.value || 0));
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            normalizeAllCycleTimeDisplayValues();
            formatAllCycleCostUnitInputs();
            calculateCycleTotals();
            formatAllRateInputs();
            syncCostingResumeOverridesInput();
            updateCostingResumePreview();

            const costingResumeRefreshTargets = [
                '#costingForm input',
                '#costingForm select',
                '#materialTableBody input',
                '#materialTableBody select',
                '#cycleTimeTableBody input',
                '#cycleTimeTableBody select'
            ].join(',');

            document.querySelectorAll(costingResumeRefreshTargets).forEach(function(element) {
                element.addEventListener('input', updateCostingResumePreview);
                element.addEventListener('change', updateCostingResumePreview);
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function(formElement) {
                formElement.addEventListener('submit', normalizeRateInputsForSubmit);
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function(formElement) {
                formElement.addEventListener('submit', normalizeCycleTimeInputsForSubmit);
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function(formElement) {
                formElement.addEventListener('submit', function() {
                    if (typeof normalizeResumeMoneyInputsForSubmit === 'function') {
                        normalizeResumeMoneyInputsForSubmit();
                    }
                    if (typeof normalizeRateInputsForSubmit === 'function') {
                        normalizeRateInputsForSubmit();
                    }
                    if (typeof normalizeCycleTimeInputsForSubmit === 'function') {
                        normalizeCycleTimeInputsForSubmit();
                    }
                });
            });
        });

