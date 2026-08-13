        // Global variables
        let rowCounter = {{ (!$costingData && is_array(old('materials')) && count(old('materials')) > 0) ? count(old('materials')) : ($materialBreakdowns->count() > 0 ? $materialBreakdowns->count() : 5) }};
        let cycleRowCounter = {{ $initialCycleCount }};
        let materialUndoHistory = [];
        let materialRedoHistory = [];
        const materialUndoLimit = 50;
        let materialHistoryApplying = false;
        let isMaterialDirty = false;
        let isConfirmingUnsavedMaterial = false;
        const materialFilterState = {};
        const materialFilterableColumns = [1, 2, 3, 5, 6, 9, 11, 12];
        let materialFilterPopup = null;
        let activeMaterialFilterColumn = null;
        let materialSortState = { column: null, direction: null };
        const materialValidationNoticeAcknowledged = {
            missing_price: false,
            estimate_price: false,
        };
        let materialValidationNoticeOpen = false;
        let bypassMaterialValidationNoticeOnce = false;
        let materialInitialRowsSnapshot = [];
        let materialInitialRowsSnapshotJson = '[]';
        let materialStructureDirty = false;

        // Materials data for dynamic selection (slim: only fields needed for JS lookup)
        const rawMaterials = @json($materialsSlim);
        const materials = rawMaterials.map(m => ({
            material_code: m[0],
            material_description: m[1],
            base_uom: m[2],
            currency: m[3],
            price: m[4],
            moq: m[5],
            cn: m[6],
            maker: m[7],
            add_cost_import_tax: m[8]
        }));
        const materialMasterByCode = new Map();
        materials.forEach((item) => {
            const codeKey = String(item?.material_code || '').trim().toUpperCase();
            if (codeKey !== '') {
                materialMasterByCode.set(codeKey, item);
            }
        });
        const cycleProcessOptions = @json(($cycleTimeTemplates ?? collect())->pluck('process')->values());
        const hasServerUnpricedData = {{ (isset($openUnpricedParts) && $openUnpricedParts->count() > 0) ? 'true' : 'false' }};
        const unpricedSyncTimers = {};

        // Format number as Rupiah
        function formatRupiah(value) {
            const number = Number(value) || 0;

            return 'Rp ' + number.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }




        function parseResumeMoneyNumber(value) {
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
                    // Format Indonesia: 12.347,13
                    raw = raw.replace(/\./g, '');
                    raw = raw.replace(/,/g, '.');
                } else {
                    // Format international: 12,347.13
                    raw = raw.replace(/,/g, '');
                }
            } else if (hasComma && !hasDot) {
                // Format Indonesia tanpa ribuan: 12347,13
                raw = raw.replace(/,/g, '.');
            } else if (hasDot && !hasComma) {
                /*
                 * Khusus Resume COGM:
                 * Setelah submit, value bisa menjadi raw decimal: 12347.13.
                 * Jangan dianggap ribuan, karena itu yang membuat 12347.13
                 * berubah menjadi 1.234.713,00.
                 */
                raw = raw;
            }

            const numeric = Number(raw);

            return Number.isFinite(numeric) ? numeric : 0;
        }

        function formatResumeMoneyValue(value) {
            const number = Number(value) || 0;

            return number.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function setResumeMoneyValue(inputOrId, value) {
            const input = typeof inputOrId === 'string' ? document.getElementById(inputOrId) : inputOrId;
            if (!input) return;

            input.value = formatResumeMoneyValue(value);
            input.dataset.rawValue = String(Number(value) || 0);
        }

        function getResumeMoneyValue(inputOrId) {
            const input = typeof inputOrId === 'string' ? document.getElementById(inputOrId) : inputOrId;
            if (!input) return 0;

            return parseResumeMoneyNumber(input.value || input.dataset.rawValue || 0);
        }

        function formatResumeMoneyInput(input) {
            setResumeMoneyValue(input, getResumeMoneyValue(input));
        }

        function normalizeResumeMoneyInputsForSubmit() {
            document.querySelectorAll('.resume-money-input').forEach(function(input) {
                input.value = String(parseResumeMoneyNumber(input.value || input.dataset.rawValue || 0));
            });
        }

        
        // Auto-format numbers with dots for thousands and comma for decimal
        
        // Convert JS float (e.g. 4000.25) to input format '4.000,25'
        function floatToInput(num) {
            if (num === null || num === undefined || isNaN(Number(num))) return '0';
            
            let raw = String(Number(num));
            return formatNumberInput(raw.replace('.', ','));
        }

        function formatNumberInput(value) {
            if (value === null || value === undefined) return '';
            let valStr = value.toString();
            if (valStr === '') return '';
            
            let parts = valStr.split(',');
            let integerPart = parts[0].replace(/[^0-9\-]/g, ''); // keep numbers and negative
            let decimalPart = parts.length > 1 ? ',' + parts[1].replace(/[^0-9]/g, '') : '';
            
            // Add thousand separators
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return integerPart + decimalPart;
        }


        function initMaterialValidationHighlightStyles() {
            if (document.getElementById('materialValidationHighlightStyles')) {
                return;
            }

            const style = document.createElement('style');
            style.id = 'materialValidationHighlightStyles';
            style.textContent = `
                #materialTableBody tr.material-row-missing-price > td {
                    background: #fee2e2 !important;
                }

                #materialTableBody tr.material-row-missing-price {
                    outline: 2px solid #dc2626;
                    outline-offset: -2px;
                }

                #materialTableBody tr.material-row-estimate-price > td {
                    background: #fef3c7 !important;
                }

                #materialTableBody tr.material-row-estimate-price {
                    outline: 2px solid #f59e0b;
                    outline-offset: -2px;
                }

                #materialTableBody tr.material-row-missing-price .amount1 {
                    border-color: #dc2626 !important;
                    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.18) !important;
                }

                #materialTableBody tr.material-row-estimate-price .cn-type {
                    border-color: #f59e0b !important;
                    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.22) !important;
                }

                .material-validation-modal-backdrop {
                    position: fixed;
                    inset: 0;
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1.5rem;
                    background: rgba(15, 23, 42, 0.38);
                    backdrop-filter: blur(3px);
                }

                .material-validation-modal-card {
                    width: min(420px, 100%);
                    border-radius: 18px;
                    background: #ffffff;
                    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
                    border: 1px solid rgba(148, 163, 184, 0.22);
                    overflow: hidden;
                    animation: materialValidationModalIn 160ms ease-out;
                }

                .material-validation-modal-body {
                    padding: 1.5rem 1.5rem 1.25rem;
                }

                .material-validation-modal-icon {
                    width: 44px;
                    height: 44px;
                    border-radius: 999px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 0.9rem;
                }

                .material-validation-modal-icon.error {
                    background: #fee2e2;
                    color: #dc2626;
                }

                .material-validation-modal-icon.warning {
                    background: #fef3c7;
                    color: #d97706;
                }

                .material-validation-modal-title {
                    font-size: 1rem;
                    font-weight: 800;
                    color: #0f172a;
                    margin-bottom: 0.35rem;
                }

                .material-validation-modal-message {
                    color: #475569;
                    line-height: 1.5;
                    font-size: 0.92rem;
                }

                .material-validation-modal-actions {
                    display: flex;
                    justify-content: flex-end;
                    gap: 0.75rem;
                    padding: 0 1.5rem 1.5rem;
                }

                .material-validation-modal-ok {
                    border: 0;
                    border-radius: 10px;
                    padding: 0.65rem 1.1rem;
                    font-weight: 700;
                    color: #ffffff;
                    background: #2563eb;
                    cursor: pointer;
                    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.26);
                }

                .material-validation-modal-ok:hover {
                    background: #1d4ed8;
                }

                @keyframes materialValidationModalIn {
                    from {
                        opacity: 0;
                        transform: translateY(8px) scale(0.98);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
            `;

            document.head.appendChild(style);
        }

        function clearMaterialValidationHighlights() {
            document.querySelectorAll('#materialTableBody tr').forEach((row) => {
                row.classList.remove('material-row-missing-price', 'material-row-estimate-price');
            });
        }

        function showMaterialValidationModal(message, type, onOk) {
            initMaterialValidationHighlightStyles();

            if (materialValidationNoticeOpen) {
                return;
            }

            materialValidationNoticeOpen = true;

            const backdrop = document.createElement('div');
            backdrop.className = 'material-validation-modal-backdrop';
            backdrop.setAttribute('role', 'dialog');
            backdrop.setAttribute('aria-modal', 'true');

            const iconSvg = type === 'error'
                ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="7" x2="12" y2="13"></line><circle cx="12" cy="17" r="1"></circle></svg>'
                : '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';

            const detail = type === 'error'
                ? 'Baris yang belum memiliki harga sudah ditandai warna merah. Anda tetap bisa lanjut setelah menekan OK.'
                : 'Baris dengan harga estimate sudah ditandai warna kuning. Anda tetap bisa lanjut setelah menekan OK.';

            backdrop.innerHTML = `
                <div class="material-validation-modal-card">
                    <div class="material-validation-modal-body">
                        <div class="material-validation-modal-icon ${type}">${iconSvg}</div>
                        <div class="material-validation-modal-title">Perhatian Material</div>
                        <div class="material-validation-modal-message">
                            <strong>${message}</strong><br>
                            ${detail}
                        </div>
                    </div>
                    <div class="material-validation-modal-actions">
                        <button type="button" class="material-validation-modal-ok">OK, lanjut</button>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);

            const okButton = backdrop.querySelector('.material-validation-modal-ok');
            const close = () => {
                materialValidationNoticeOpen = false;
                backdrop.remove();

                if (typeof onOk === 'function') {
                    onOk();
                }
            };

            okButton?.addEventListener('click', close);
            setTimeout(() => okButton?.focus(), 30);
        }

        function isMaterialRowActive(row) {
            if (!row) {
                return false;
            }

            const partNo = String(row.querySelector('.part-no')?.value || '').trim();
            const idCode = String(row.querySelector('.id-code')?.value || '').trim();
            const partName = String(row.querySelector('.part-name')?.value || '').trim();
            const supplier = String(row.querySelector('.supplier')?.value || '').trim();
            const qtyReq = parseInputNumber(row.querySelector('.qty-req')?.value || 0);
            const amount1 = parseInputNumber(row.querySelector('.amount1')?.value || 0);

            return partNo !== ''
                || idCode !== ''
                || partName !== ''
                || supplier !== ''
                || qtyReq > 0
                || amount1 > 0;
        }

        function getMaterialSectionValidationResult() {
            initMaterialValidationHighlightStyles();
            clearMaterialValidationHighlights();

            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const activeRows = rows.filter((row) => isMaterialRowActive(row));

            if (activeRows.length === 0) {
                return {
                    ok: true,
                    code: '',
                    message: '',
                    type: 'success',
                    missingRows: [],
                    estimateRows: [],
                };
            }

            const missingRows = [];
            const estimateRows = [];

            activeRows.forEach((row) => {
                const amountInput = row.querySelector('.amount1');
                const rawAmount = String(amountInput?.value || '').trim();
                const cnValue = String(row.querySelector('.cn-type')?.value || '').trim().toUpperCase();

                if (rawAmount === '' || parseInputNumber(rawAmount) <= 0) {
                    missingRows.push(row);
                    row.classList.add('material-row-missing-price');
                }

                if (cnValue === 'E') {
                    estimateRows.push(row);
                    row.classList.add('material-row-estimate-price');
                }
            });

            if (missingRows.length > 0) {
                return {
                    ok: false,
                    code: 'missing_price',
                    message: 'Ada harga yang belum',
                    type: 'error',
                    missingRows,
                    estimateRows,
                };
            }

            if (estimateRows.length > 0) {
                return {
                    ok: false,
                    code: 'estimate_price',
                    message: 'Ada harga yang masih estimate',
                    type: 'warning',
                    missingRows,
                    estimateRows,
                };
            }

            return {
                ok: true,
                code: '',
                message: '',
                type: 'success',
                missingRows: [],
                estimateRows: [],
            };
        }

        function refreshMaterialValidationHighlights() {
            getMaterialSectionValidationResult();
        }

        function shouldShowMaterialValidationNotice(result) {
            if (!result || result.ok || !result.code) {
                return false;
            }

            if (bypassMaterialValidationNoticeOnce) {
                bypassMaterialValidationNoticeOnce = false;
                return false;
            }

            return materialValidationNoticeAcknowledged[result.code] !== true;
        }

        function acknowledgeMaterialValidationNotice(result) {
            if (result && result.code) {
                materialValidationNoticeAcknowledged[result.code] = true;
            }
        }

        document.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('number-format')) {
                let startPos = e.target.selectionStart;
                let oldVal = e.target.value;
                
                // Allow user to type comma if it's the last character and no other commas exist
                if (oldVal.endsWith(',') && (oldVal.match(/,/g) || []).length === 1) {
                    // Do not rewrite immediately to prevent cursor jumping when starting a decimal
                    return;
                }

                let newVal = formatNumberInput(oldVal);
                e.target.value = newVal;
            }
        });
        
        document.addEventListener('input', function(event) {
            if (event.target && event.target.closest && event.target.closest('#materialTableBody')) {
                refreshMaterialValidationHighlights();
            }
        });

        document.addEventListener('change', function(event) {
            if (event.target && event.target.closest && event.target.closest('#materialTableBody')) {
                refreshMaterialValidationHighlights();
            }
        });

        document.addEventListener('blur', function(e) {
            if (e.target && e.target.classList.contains('number-format')) {
                // Formatting on blur cleanly
                if (e.target.value) {
                    let cleaned = e.target.value.replace(/,$/, '');
                    e.target.value = formatNumberInput(cleaned);
                }
                
                // also calculateRow if not already fired by browser
                if (typeof calculateRow === 'function' && e.target.closest('tr')) {
                    calculateRow(e.target);
                }
            }
        }, true);

        function formatWholeNumber(number) {
            return String(Math.round(Number(number) || 0));
        }

        function parsePositiveInteger(value) {
            const digits = String(value || '').replace(/[^\d]/g, '');
            if (!digits) return 0;
            return parseInt(digits, 10) || 0;
        }

        function syncForecastHidden() {
            const forecastDisplay = document.getElementById('forecastDisplay');
            const forecastHidden = document.getElementById('forecast');
            if (!forecastDisplay || !forecastHidden) return 0;

            const numericValue = parsePositiveInteger(forecastDisplay.value);
            forecastHidden.value = numericValue;
            return numericValue;
        }

        function formatForecastDisplay() {
            const forecastDisplay = document.getElementById('forecastDisplay');
            if (!forecastDisplay) return;

            const numericValue = syncForecastHidden();
            forecastDisplay.value = numericValue > 0
                ? new Intl.NumberFormat('id-ID').format(numericValue)
                : '';
        }

        // Get exchange rate based on currency
        function getExchangeRate(currency) {
            switch (currency) {
                case 'USD': return parseRateInputValue(document.getElementById('rateUSD')?.value || 0) || 15500;
                case 'JPY': return parseRateInputValue(document.getElementById('rateJPY')?.value || 0) || 103;
                default: return 1;
            }
        }

        // Calculate Multiply Factor
        // Logika: IF(qtyReq=0,0, IF(OR(cnFlag="C",(moq/(forecast*period*12*qtyReq/unitDivisor))<1), 1, moq/(forecast*period*12*qtyReq/unitDivisor)))
        function calculateMultiplyFactor(row) {
            const qtyReq = parseInputNumber(row.querySelector('.qty-req')?.value || 0);
            const moq = parseInputNumber(row.querySelector('.qty-moq')?.value || 0);
            const quantity = parseInputNumber(document.getElementById('forecast')?.value || document.getElementById('forecastDisplay')?.value || 0);
            const productLife = parseInputNumber(document.getElementById('projectPeriod')?.value || 0);
            const unit = (row.querySelector('.unit')?.value || row.querySelector('.unit')?.textContent || '').trim().toUpperCase();
            const cnFlag = (row.querySelector('.cn-type')?.value || '').trim().toUpperCase();

            // Excel: IF(QTY_REQ=0;0;...)
            if (qtyReq <= 0) {
                return 0;
            }

            // Excel: IF(UNIT="MM";1000;1)
            const unitDivisor = (unit === 'MM') ? 1000 : 1;

            // Excel denominator: QUANTITY * PRODUCT_LIFE * 12 * QTY_REQ / unitDivisor
            let denominator = quantity * productLife * 12 * qtyReq;
            denominator = denominator / unitDivisor;

            if (denominator === 0) {
                return 0;
            }

            const ratio = moq / denominator;

            // Excel: IF(OR(CN="C";ratio<1);1;ratio)
            if (cnFlag === 'C' || ratio < 1) {
                return 1;
            }

            return ratio;
        }

        // Helper to parse input values safely
        // STRATEGI: Asumsi User Indonesia
        // 1. Hapus semua TITIK (.) yang biasanya dipakai sebagai pemisah ribuan
        // 2. Ganti KOMA (,) menjadi TITIK (.) sebagai pemisah desimal
        // Contoh: "1.000,50" -> "1000.50"
        function parseInputNumber(value) {
            if (!value) return 0;
            let str = value.toString();

            str = str.replace(/\s+/g, '');
            str = str.replace(/[^0-9,\.\-]/g, '');

            if (str === '' || str === '-' || str === '.' || str === ',') {
                return 0;
            }

            const hasComma = str.includes(',');
            const hasDot = str.includes('.');

            if (hasComma && hasDot) {
                const lastCommaPos = str.lastIndexOf(',');
                const lastDotPos = str.lastIndexOf('.');

                if (lastCommaPos > lastDotPos) {
                    str = str.replace(/\./g, '');
                    str = str.replace(/,/g, '.');
                } else {
                    str = str.replace(/,/g, '');
                }
            } else if (hasComma && !hasDot) {
                str = str.replace(/,/g, '.');
            } else if (hasDot && !hasComma) {
                // Nilai dari server/import bisa berupa desimal baku (contoh 1138.15),
                // sedangkan input Indonesia memakai titik sebagai pemisah ribuan.
                // Anggap titik sebagai ribuan hanya jika polanya memang kelompok 3 digit.
                const dotCount = (str.match(/\./g) || []).length;
                const lastDotPos = str.lastIndexOf('.');
                const digitsAfterLastDot = str.length - lastDotPos - 1;
                const digitsBeforeLastDot = str.substring(0, lastDotPos).replace('-', '').length;
                const looksLikeLeadingDecimal = /^-?0\.\d+$/.test(str);
                const looksLikeThousands = !looksLikeLeadingDecimal
                    && (dotCount > 1 || (digitsAfterLastDot === 3 && digitsBeforeLastDot > 1));

                if (looksLikeThousands) {
                    str = str.replace(/\./g, '');
                }
            }

            return parseFloat(str) || 0;
        }

        function findMaterialMasterForRow(row) {
            if (!row) return null;

            const partNo = String(row.querySelector('.part-no')?.value || '').trim().toUpperCase();
            const idCode = String(row.querySelector('.id-code')?.value || '').trim().toUpperCase();

            if (partNo && materialMasterByCode.has(partNo)) {
                return materialMasterByCode.get(partNo);
            }

            if (idCode && materialMasterByCode.has(idCode)) {
                return materialMasterByCode.get(idCode);
            }

            return null;
        }

        function applyMasterMaterialToRow(row) {
            const master = findMaterialMasterForRow(row);
            if (!master || !row) {
                return false;
            }

            const partNameInput = row.querySelector('.part-name');
            const unitInput = row.querySelector('.unit');
            const supplierInput = row.querySelector('.supplier');
            const amount1Input = row.querySelector('.amount1');
            const qtyMoqInput = row.querySelector('.qty-moq');
            const importTaxInput = row.querySelector('.import-tax');
            const currencySelect = row.querySelector('.currency');
            const cnTypeSelect = row.querySelector('.cn-type');

            if (partNameInput && String(partNameInput.value || '').trim() === '') {
                partNameInput.value = String(master.material_description || '').toUpperCase();
            }

            if (unitInput && String(unitInput.value || '').trim() === '') {
                unitInput.value = String(master.base_uom || 'PCS').toUpperCase();
            }

            if (supplierInput && String(supplierInput.value || '').trim() === '') {
                supplierInput.value = String(master.maker || '').toUpperCase();
            }

            if (currencySelect && String(currencySelect.value || '').trim() === '') {
                const currency = String(master.currency || 'IDR').toUpperCase();
                if (['IDR', 'USD', 'JPY'].includes(currency)) {
                    currencySelect.value = currency;
                }
            }

            if (cnTypeSelect) {
                const current = String(cnTypeSelect.value || '').toUpperCase();
                if (current !== 'C' && current !== 'N') {
                    const fromMaster = String(master.cn || 'N').toUpperCase();
                    cnTypeSelect.value = fromMaster === 'C' ? 'C' : 'N';
                }
            }

            if (qtyMoqInput) {
                const currentMoq = parseInputNumber(qtyMoqInput.value || 0);
                const masterMoq = Number(master.moq || 0);
                if (currentMoq <= 0 && masterMoq > 0) {
                    qtyMoqInput.value = floatToInput(masterMoq);
                }
            }

            if (importTaxInput && String(importTaxInput.value || '').trim() === '' && master.add_cost_import_tax !== null && master.add_cost_import_tax !== undefined) {
                importTaxInput.value = floatToInput(master.add_cost_import_tax || 0);
            }

            if (amount1Input) {
                const currentAmount1 = parseInputNumber(amount1Input.value || 0);
                const masterPrice = Number(master.price || 0);
                if (currentAmount1 <= 0 && masterPrice > 0) {
                    amount1Input.value = floatToInput(masterPrice);
                }
            }

            return true;
        }

        // Calculate row total
        function calculateRow(element, options = {}) {
            const row = element.closest('tr');
            if (!row) return;

            const multiplyFactor = calculateMultiplyFactor(row);
            const multiplyFactorElement = row.querySelector('.multiply-factor');
            if (multiplyFactorElement) {
                multiplyFactorElement.textContent = floatToInput(Number(multiplyFactor.toFixed(4)));
            }

            const priceBase = parseInputNumber(row.querySelector('.amount1')?.value || 0);
            const uom = (row.querySelector('.unit')?.value || '').trim().toUpperCase();
            const priceBasis = (row.querySelector('.unit-price-basis')?.value || '').trim().toUpperCase();
            const importTax = parseInputNumber(row.querySelector('.import-tax')?.value || 0) || 0;

            const extra = priceBase * (importTax / 100);
            const base = priceBase + extra;
            const numerator = multiplyFactor * base;

            let unitDivisor = 1;
            if (priceBasis === 'METER' || priceBasis === 'M' || priceBasis === 'MTR') {
                unitDivisor = 1000;
            }

            const amount2Raw = (unitDivisor !== 0) ? (numerator / unitDivisor) : 0;
            const amount2Display = Number(amount2Raw.toFixed(5));

            const amount2Element = row.querySelector('.amount2');
            if (amount2Element) {
                amount2Element.textContent = floatToInput(amount2Display);
                amount2Element.setAttribute('data-raw-value', String(amount2Raw));
            }

            const qty = parseInputNumber(row.querySelector('.qty-req')?.value || 0) || 0;
            const currency = row.querySelector('.currency')?.value || 'IDR';
            const exchangeRate = getExchangeRate(currency);

            const currency2Element = row.querySelector('.currency2');
            if (currency2Element) {
                currency2Element.textContent = currency;
            }

            const unitPrice2Element = row.querySelector('.unit-price2');
            if (unitPrice2Element) {
                unitPrice2Element.textContent = uom;
            }

            // PENTING:
            // Total Price (IDR) mengikuti Excel: qty * amount2 mentah/full precision * rate.
            // Amount 2 yang tampil boleh 4 desimal, tetapi total tidak memakai angka tampilan tersebut.
            const total = qty * amount2Raw * exchangeRate;

            const totalPriceElement = row.querySelector('.total-price');
            if (totalPriceElement) {
                totalPriceElement.textContent = formatRupiah(total);
                totalPriceElement.setAttribute('data-value', String(total));
            }

            if (!options.silent) {
                calculateTableTotal();
                refreshUnpricedRecap();
            }
        }

        // Calculate table total

        function parseDataValueNumber(value) {
            if (value === null || value === undefined) {
                return 0;
            }

            const raw = String(value).trim();
            if (raw === '') {
                return 0;
            }

            // data-value biasanya disimpan sebagai angka mentah JS/database, contoh: 1138.15
            // Jangan gunakan parseInputNumber untuk kasus ini, karena titik bisa dianggap ribuan.
            if (/^-?\d+(\.\d+)?$/.test(raw)) {
                return Number(raw) || 0;
            }

            return parseInputNumber(raw);
        }

        function calculateTableTotal(syncMaterialCost = true) {
            let total = 0;
            const rows = document.querySelectorAll('#materialTableBody tr');

            rows.forEach(row => {
                const totalElement = row.querySelector('.total-price');
                const dataValue = totalElement ? parseDataValueNumber(totalElement.getAttribute('data-value') || totalElement.textContent || 0) : 0;
                total += dataValue;
            });

            // Update Footer Total using the rendered totals so it stays aligned with Database Costing
            const materialCostInput = document.getElementById('materialCost');
            if (materialCostInput && syncMaterialCost) {
                setResumeMoneyValue(materialCostInput, total);
                calculateTotals(false);
            }

            document.getElementById('tableTotalMaterial').textContent = formatRupiah(total);

            return total;
        }

