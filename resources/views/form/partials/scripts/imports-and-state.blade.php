        document.addEventListener('DOMContentLoaded', () => {
            const materialBody = document.getElementById('materialTableBody');
            if (!materialBody) return;

            const activateMaterialRow = (target) => {
                const row = target.closest('tr');
                if (!row || !materialBody.contains(row)) return;
                materialBody.querySelectorAll('tr.material-row-active').forEach(activeRow => {
                    if (activeRow !== row) activeRow.classList.remove('material-row-active');
                });
                row.classList.add('material-row-active');
            };

            materialBody.addEventListener('pointerdown', event => activateMaterialRow(event.target));
            materialBody.addEventListener('focusin', event => activateMaterialRow(event.target));
        });

        const initialCostingResumeOverrides = @json($costingData->costing_resume_overrides ?? []);
        const submittedCogmValue = @json($readOnlyMode && $cogmSubmission ? (float) $cogmSubmission->cogm_value : null);
        let costingResumeOverrides = { ...(initialCostingResumeOverrides || {}) };

        // Material ownership is now consolidated under DEM. Discard saved display
        // overrides from the previous split so they cannot restore stale totals.
        Object.keys(costingResumeOverrides).forEach((key) => {
            if (key.startsWith('material.dem.') || key.startsWith('material.customer.')) {
                delete costingResumeOverrides[key];
            }
        });
        syncCostingResumeOverridesInput();

        function triggerUmhImport() {
            const input = document.getElementById('importUmhFileInput');

            if (!input) {
                alert('Input file Import UMH belum ditemukan.');
                return;
            }

            input.value = '';
            input.click();
        }

        function submitUmhImport() {
            const form = document.getElementById('umhImportForm');

            if (!form) {
                alert('Form Import UMH belum ditemukan.');
                return;
            }

            form.submit();
        }

        function triggerMaterialImport() {
            const input = document.getElementById('importCogmFileInput');

            if (!input) {
                alert('Input file Import COGM belum ditemukan.');
                return;
            }

            input.value = '';
            input.click();
        }

        function submitCogmImport() {
            const form = document.getElementById('cogmImportForm');

            if (!form) {
                alert('Form Import COGM belum ditemukan.');
                return;
            }

            syncForecastHidden();

            const forecastHidden = document.getElementById('forecast');
            const projectPeriod = document.getElementById('projectPeriod');
            const wireRateSelector = document.getElementById('wireRateSelector');

            const cogmForecast = document.getElementById('cogmImportForecast');
            const cogmProjectPeriod = document.getElementById('cogmImportProjectPeriod');
            const cogmWireRateId = document.getElementById('cogmImportWireRateId');

            if (cogmForecast && forecastHidden) {
                cogmForecast.value = forecastHidden.value || '0';
            }

            if (cogmProjectPeriod && projectPeriod) {
                cogmProjectPeriod.value = projectPeriod.value || '0';
            }

            if (cogmWireRateId && wireRateSelector) {
                cogmWireRateId.value = wireRateSelector.value || '';
            }

            const syncFields = {
                'cogmImportBusinessCategoryId': 'select[name="business_category_id"]',
                'cogmImportCustomerId': 'select[name="customer_id"]',
                'cogmImportPeriod': '#periodInput',
                'cogmImportLine': 'select[name="line"]',
                'cogmImportModel': 'input[name="model"]',
                'cogmImportAssyNo': 'input[name="assy_no"]',
                'cogmImportAssyName': 'input[name="assy_name"]',
                'cogmImportRateUsd': '#rateUSD',
                'cogmImportRateJpy': '#rateJPY',
                'cogmImportLmeRate': '#lmeRate',
            };

            for (const [hiddenId, mainSelector] of Object.entries(syncFields)) {
                const hidden = document.getElementById(hiddenId);
                const main = document.querySelector('#costingForm ' + mainSelector);

                if (hidden && main) {
                    hidden.value = main.value || '';
                }
            }

            showAppLoading('Mengimport COGM...');

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            form.submit();
        }

        window.COSTING_FORM_FAST_UPDATE_VERSION = 'fire-and-forget-update-material-v1';
