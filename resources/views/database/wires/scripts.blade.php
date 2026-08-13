    <script>
        let deleteWireId = null;
        let deleteRateId = null;
        const wirePriceNotesData = @json($wirePriceNotes ?? []);

        function formatNoteNumber(value, maxDecimals = 5) {
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                return '-';
            }

            return numeric.toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: maxDecimals,
            });
        }

        function openPriceNotesModal(wireId, idcode, item, currentPrice) {
            const modal = document.getElementById('price-notes-modal');
            if (!modal) {
                return;
            }

            const note = wirePriceNotesData[String(wireId)] || wirePriceNotesData[wireId] || null;
            const errorBox = document.getElementById('notes-error-box');
            const detailBox = document.getElementById('notes-detail-box');

            document.getElementById('notes-wire-idcode').textContent = idcode || '-';
            document.getElementById('notes-wire-item').textContent = item || '-';
            document.getElementById('notes-current-price').textContent = formatNoteNumber(currentPrice, 2);

            if (!note || note.status !== 'ok') {
                errorBox.textContent = note?.reason || 'Detail perhitungan tidak tersedia.';
                errorBox.classList.remove('is-hidden');
                detailBox.classList.add('is-hidden');
                modal.classList.remove('is-hidden');
                return;
            }

            document.getElementById('notes-rate-label').textContent = note.rate_label || '-';
            document.getElementById('notes-usd-rate').textContent = formatNoteNumber(note.usd_rate, 5);
            document.getElementById('notes-lme-active').textContent = formatNoteNumber(note.lme_active, 5);
            document.getElementById('notes-lme-reference').textContent = formatNoteNumber(note.lme_reference, 5);
            document.getElementById('notes-lookup-value').textContent = formatNoteNumber(note.lookup_value, 5);
            document.getElementById('notes-machine-maintenance').textContent = formatNoteNumber(note.machine_maintenance, 5);
            document.getElementById('notes-fix-cost').textContent = formatNoteNumber(note.fix_cost, 5);
            document.getElementById('notes-base-value').textContent = formatNoteNumber(note.base_value, 5);
            document.getElementById('notes-rounding-label').textContent = note.rounding_label || 'Rounding';
            document.getElementById('notes-rounded-value').textContent = formatNoteNumber(note.rounded_value, 0);
            document.getElementById('notes-markup-factor').textContent = formatNoteNumber(note.markup_factor ?? 1, 2);
            document.getElementById('notes-final-price').textContent = formatNoteNumber(note.final_price, 2);
            document.getElementById('notes-formula-text').textContent = `${note.rounding_label || 'ROUNDING'}((((Lookup + Machine Maintenance) * USD) + Fix Cost), 0) * ${formatNoteNumber(note.markup_factor ?? 1, 2)}`;

            errorBox.classList.add('is-hidden');
            detailBox.classList.remove('is-hidden');
            modal.classList.remove('is-hidden');
        }

        function closePriceNotesModal() {
            document.getElementById('price-notes-modal')?.classList.add('is-hidden');
        }

        function openImportWireModal() {
            document.getElementById('import-wire-modal')?.classList.remove('is-hidden');
        }

        function closeImportWireModal() {
            document.getElementById('import-wire-modal')?.classList.add('is-hidden');
        }

        function openAddWireModal() {
            document.getElementById('add-wire-modal')?.classList.remove('is-hidden');
        }

        function closeAddWireModal() {
            document.getElementById('add-wire-modal')?.classList.add('is-hidden');
        }

        function openAddRateModal() {
            document.getElementById('add-rate-modal')?.classList.remove('is-hidden');
        }

        function closeAddRateModal() {
            document.getElementById('add-rate-modal')?.classList.add('is-hidden');
        }

        function handleWireModalOverlay(event) {
            if (event.target === event.currentTarget) {
                closeAddWireModal();
                closeAddRateModal();
                closeEditWireModal();
                closeEditRateModal();
                closeDeleteWireModal();
                closeDeleteRateModal();
                closePriceNotesModal();
                closeImportWireModal();
            }
        }

        function openEditWireModal(id, idcode, item, machineMaintenance, fixCost, price) {
            const modal = document.getElementById('edit-wire-modal');
            const form = document.getElementById('edit-wire-form');
            if (!modal || !form) {
                return;
            }

            const actionTemplate = form.dataset.actionTemplate || '';
            form.action = actionTemplate.replace('__ID__', String(id));
            document.getElementById('edit-wire-idcode').value = idcode || '';
            document.getElementById('edit-wire-item').value = item || '';
            document.getElementById('edit-wire-machine-maintenance').value = machineMaintenance || '';
            document.getElementById('edit-wire-fix-cost').value = fixCost || '0';

            modal.classList.remove('is-hidden');
        }

        function closeEditWireModal() {
            document.getElementById('edit-wire-modal')?.classList.add('is-hidden');
        }

        function openDeleteWireModal(id, idcode) {
            deleteWireId = id;
            document.getElementById('delete-wire-idcode-text').textContent = idcode || '';
            document.getElementById('delete-wire-modal')?.classList.remove('is-hidden');
        }

        function closeDeleteWireModal() {
            deleteWireId = null;
            document.getElementById('delete-wire-modal')?.classList.add('is-hidden');
        }

        function submitDeleteWireForm() {
            const form = document.getElementById('delete-wire-form');
            if (!form || deleteWireId === null) {
                return;
            }
            const actionTemplate = form.dataset.actionTemplate || '';
            form.action = actionTemplate.replace('__ID__', String(deleteWireId));
            form.submit();
        }

            // Validate decimal inputs - prevent leading zeros except for 0.xx format
            document.querySelectorAll('.wire-decimal-input').forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = this.value.trim();
                    if (value === '') return;

                    // Replace comma with dot for processing
                    value = value.replace(',', '.');
                
                    // If starts with 0 followed by digit (not decimal point), remove leading zero
                    if (/^0\d/.test(value)) {
                        value = value.substring(1);
                    }
                
                    this.value = value.replace('.', ',');
                });
            });

            // Validate number inputs - prevent leading zeros except for 0.xx format
            document.querySelectorAll('.wire-number-input').forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = this.value.trim();
                    if (value === '') return;

                    // If starts with 0 followed by digit (not decimal point)
                    if (/^0\d/.test(value)) {
                        this.value = value.substring(1);
                    }
                });
            });

        function normalizeRawRateValue(value) {
            if (value === null || value === undefined) {
                return '';
            }

            let raw = String(value).trim();

            if (raw === '') {
                return '';
            }

            raw = raw.replace(/\s+/g, '');
            raw = raw.replace(/[^0-9,.\-]/g, '');

            if (raw === '' || raw === '-' || raw === '.' || raw === ',') {
                return '0';
            }

            const hasComma = raw.includes(',');
            const hasDot = raw.includes('.');

            if (hasComma && hasDot) {
                const lastComma = raw.lastIndexOf(',');
                const lastDot = raw.lastIndexOf('.');

                if (lastComma > lastDot) {
                    // Format Indonesia: 15.919,123456
                    raw = raw.replace(/\./g, '');
                    raw = raw.replace(/,/g, '.');
                } else {
                    // Format International: 15,919.123456
                    raw = raw.replace(/,/g, '');
                }
            } else if (hasComma && !hasDot) {
                // Desimal koma dari Excel: 107,678123
                raw = raw.replace(/,/g, '.');
            } else if (hasDot && !hasComma) {
                // Desimal titik dari Excel: 107.678123 atau 0.012
                // Jangan hapus titik.
                raw = raw;
            }

            const numeric = Number(raw);
            if (!Number.isFinite(numeric)) {
                return '0';
            }

            return raw;
        }

        function normalizeNumericInputValue(value) {
            const normalized = normalizeRawRateValue(value);

            return normalized === '' ? '0' : normalized;
        }

        function calculateLmeReference(value) {
            const normalized = normalizeRawRateValue(value);
            const numericValue = Number(normalized);
            if (!Number.isFinite(numericValue) || numericValue <= 0) {
                return '0';
            }

            return String(Math.floor(numericValue / 100) * 100);
        }

        function syncLmeReference(activeInputId, referenceInputId) {
            const activeInput = document.getElementById(activeInputId);
            const referenceInput = document.getElementById(referenceInputId);
            if (!activeInput || !referenceInput) {
                return;
            }

            referenceInput.value = calculateLmeReference(activeInput.value);
        }

        function openEditRateModal(id, period, requestName, jpy, usd, active, reference) {
            const modal = document.getElementById('edit-rate-modal');
            const form = document.getElementById('edit-rate-form');
            if (!modal || !form) {
                return;
            }

            const actionTemplate = form.dataset.actionTemplate || '';
            form.action = actionTemplate.replace('__ID__', String(id));
            document.getElementById('edit-rate-period').value = period || '';
            document.getElementById('edit-rate-request-name').value = requestName || '';
            document.getElementById('edit-rate-jpy').value = normalizeNumericInputValue(jpy);
            document.getElementById('edit-rate-usd').value = normalizeNumericInputValue(usd);
            document.getElementById('edit-rate-active').value = normalizeNumericInputValue(active);
            document.getElementById('edit-rate-reference').value = normalizeNumericInputValue(reference);

            modal.classList.remove('is-hidden');
        }

        function closeEditRateModal() {
            document.getElementById('edit-rate-modal')?.classList.add('is-hidden');
        }

        function openDeleteRateModal(id, periodLabel) {
            deleteRateId = id;
            document.getElementById('delete-rate-period-text').textContent = periodLabel || '';
            document.getElementById('delete-rate-modal')?.classList.remove('is-hidden');
        }

        function closeDeleteRateModal() {
            deleteRateId = null;
            document.getElementById('delete-rate-modal')?.classList.add('is-hidden');
        }

        function submitDeleteRateForm() {
            const form = document.getElementById('delete-rate-form');
            if (!form || deleteRateId === null) {
                return;
            }

            const actionTemplate = form.dataset.actionTemplate || '';
            form.action = actionTemplate.replace('__ID__', String(deleteRateId));
            form.submit();
        }


        document.addEventListener('paste', function (event) {
            const target = event.target;

            if (!target.classList || !target.classList.contains('raw-rate-input')) {
                return;
            }

            event.preventDefault();

            const pasted = (event.clipboardData || window.clipboardData).getData('text');
            target.value = normalizeRawRateValue(pasted);

            if (target.id === 'add-rate-active') {
                syncLmeReference('add-rate-active', 'add-rate-reference');
            }

            if (target.id === 'edit-rate-active') {
                syncLmeReference('edit-rate-active', 'edit-rate-reference');
            }
        });

        document.addEventListener('blur', function (event) {
            const target = event.target;

            if (!target.classList || !target.classList.contains('raw-rate-input')) {
                return;
            }

            target.value = normalizeRawRateValue(target.value);
        }, true);

        document.addEventListener('submit', function (event) {
            const form = event.target;

            if (!form || !form.querySelectorAll) {
                return;
            }

            form.querySelectorAll('.raw-rate-input').forEach(function (input) {
                input.value = normalizeRawRateValue(input.value);
            });
        }, true);

        document.getElementById('add-rate-active')?.addEventListener('input', function() {
            syncLmeReference('add-rate-active', 'add-rate-reference');
        });

        document.getElementById('edit-rate-active')?.addEventListener('input', function() {
            syncLmeReference('edit-rate-active', 'edit-rate-reference');
        });

        syncLmeReference('add-rate-active', 'add-rate-reference');
    </script>
