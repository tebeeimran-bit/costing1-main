        function refreshUnpricedRecap() {
            const tbody = document.getElementById('unpricedRecapBody');
            if (!tbody) return;

            // Only show server-persisted unpriced data. Export New Part Request
            // synchronizes this recap from the latest imported costing-edit file.
            if (hasServerUnpricedData) {
                const visibleRows = tbody.querySelectorAll('tr[data-unpriced-part]').length;
                const openRows = tbody.querySelectorAll('tr[data-unpriced-part][data-unpriced-open="1"]').length;
                const banner = document.getElementById('unpricedTopBanner');
                const bannerText = document.getElementById('unpricedTopBannerText');

                if (banner) {
                    banner.style.display = openRows > 0 ? 'flex' : 'none';
                }
                if (bannerText && openRows > 0) {
                    bannerText.textContent = `Terdapat ${openRows} part yang belum memiliki harga pada versi dokumen ini.`;
                }

                bindUnpricedManualPriceInputs();
                bindUnpricedDeleteButtons();
                bindMatchedPriceSelectors();
                bindUnpricedAddPriceButtons();
                updateUnpricedSelectAllState();
                return;
            }

            // No server data — show empty message
            tbody.innerHTML = '<tr><td colspan="15" style="text-align: center; color: var(--slate-500);">Tidak ada part yang menunggu harga.</td></tr>';
            const banner = document.getElementById('unpricedTopBanner');
            if (banner) banner.style.display = 'none';
        }

        function bindMatchedPriceSelectors() {
            const selectors = document.querySelectorAll('.matched-price-select');
            selectors.forEach((selector) => {
                if (!(selector instanceof HTMLInputElement)) {
                    return;
                }

                if (selector.dataset.boundMatchedPrice === '1') {
                    return;
                }

                selector.dataset.boundMatchedPrice = '1';

                selector.addEventListener('change', function () {
                    const partNumber = this.dataset.partNumber || '';
                    if (!partNumber) {
                        return;
                    }

                    const escapedPart = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function')
                        ? CSS.escape(partNumber)
                        : partNumber.replace(/([\\[\\]\\.\\:\\#\"'])/g, '\\\\$1');

                    const siblingSelectors = document.querySelectorAll(`.matched-price-select[data-part-number="${escapedPart}"]`);

                    if (this.checked) {
                        siblingSelectors.forEach((sibling) => {
                            if (sibling !== this && sibling instanceof HTMLInputElement) {
                                sibling.checked = false;
                            }
                        });
                    }
                });
            });
        }

        function bindUnpricedAddPriceButtons() {
            const buttons = document.querySelectorAll('.unpriced-add-price-btn');
            buttons.forEach((button) => {
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }

                if (button.dataset.boundAddPrice === '1') {
                    return;
                }

                button.dataset.boundAddPrice = '1';

                button.addEventListener('click', function () {
                    const row = this.closest('tr');
                    const item = getUnpricedRowPriceData(row);
                    if (!item?.partNumber) return;

                    if (item.price <= 0) {
                        window.alert('Harga belum tersedia. Import New Part Request terlebih dahulu.');
                        return;
                    }

                    applySelectedMatchedPrice(item.partNumber, item.price, item.currency, item.unit, item.moq, item.cn, item.supplier, item.importTax);
                });
            });
        }

        function getUnpricedRowPriceData(row) {
            if (!(row instanceof HTMLTableRowElement)) return null;

            const button = row.querySelector('.unpriced-add-price-btn');
            if (!(button instanceof HTMLButtonElement)) return null;

            const selectedOption = row.querySelector('.matched-price-select:checked');
            const source = selectedOption instanceof HTMLInputElement ? selectedOption : button;
            const manualInput = row.querySelector('.unpriced-manual-price');
            const manualPrice = manualInput instanceof HTMLInputElement ? parseInputNumber(manualInput.value) : 0;

            return {
                partNumber: button.dataset.partNumber || '',
                price: manualPrice > 0 ? manualPrice : (parseFloat(source.dataset.price || '0') || 0),
                currency: source.dataset.currency || '',
                unit: source.dataset.unit || '',
                moq: source.dataset.moq === '' ? null : (parseFloat(source.dataset.moq || '0') || 0),
                cn: source.dataset.cn || '',
                supplier: source.dataset.supplier || '',
                importTax: source.dataset.importTax === '' ? null : (parseFloat(source.dataset.importTax || '0') || 0),
            };
        }

        function normalizePartKey(value) {
            return String(value || '').trim().toLowerCase();
        }

        async function applySelectedMatchedPrice(partNumber, selectedPrice, selectedCurrency, selectedUnit, selectedMoq, selectedCn, selectedSupplier, selectedImportTax, manageLoading = true, persistPrice = true) {
            const escapedPart = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function')
                ? CSS.escape(partNumber)
                : partNumber.replace(/([\\[\\]\\.\\:\\#\"'])/g, '\\\\$1');

            const manualInput = document.querySelector(`#unpricedRecapBody .unpriced-manual-price[data-part-number="${escapedPart}"]`);
            if (manualInput instanceof HTMLInputElement) {
                manualInput.value = selectedPrice > 0 ? floatToInput(selectedPrice) : '';
            }

            const targetKey = normalizePartKey(partNumber);
            let updatedRows = 0;

            document.querySelectorAll('#materialTableBody tr').forEach((row) => {
                const partInput = row.querySelector('.part-no');
                const amountInput = row.querySelector('.amount1');
                const currencySelect = row.querySelector('.currency');
                const unitInput = row.querySelector('.unit-price-basis');
                const moqInput = row.querySelector('.qty-moq');
                const cnSelect = row.querySelector('.cn-type');
                const supplierInput = row.querySelector('.supplier');
                const importTaxInput = row.querySelector('.import-tax');

                if (!(partInput instanceof HTMLInputElement) || !(amountInput instanceof HTMLInputElement)) {
                    return;
                }

                if (normalizePartKey(partInput.value) !== targetKey) {
                    return;
                }

                amountInput.value = selectedPrice > 0 ? floatToInput(selectedPrice) : '0';

                if (currencySelect instanceof HTMLSelectElement && selectedCurrency) {
                    const hasOption = Array.from(currencySelect.options).some((opt) => opt.value === selectedCurrency);
                    if (hasOption) {
                        currencySelect.value = selectedCurrency;
                    }
                }
                
                if (unitInput && selectedUnit) {
                    unitInput.value = selectedUnit;
                }
                if (moqInput) {
                    moqInput.value = selectedMoq === null ? '' : floatToInput(selectedMoq);
                }
                if (cnSelect instanceof HTMLSelectElement && selectedCn) {
                    const hasOption = Array.from(cnSelect.options).some((opt) => opt.value === selectedCn);
                    if (hasOption) {
                        cnSelect.value = selectedCn;
                    }
                }
                if (supplierInput) {
                    supplierInput.value = selectedSupplier || '';
                }
                if (importTaxInput) {
                    importTaxInput.value = selectedImportTax === null ? '' : floatToInput(selectedImportTax);
                }

                calculateRow(amountInput);
                updatedRows += 1;
            });

            calculateTableTotal();
            if (!persistPrice) return true;

            if (manageLoading) showAppLoading('Menambahkan harga ke Material dan file hasil edit...');
            const saved = await syncManualPriceToServer(partNumber, selectedPrice, {
                purchase_unit: selectedUnit,
                currency: selectedCurrency,
                moq: selectedMoq,
                cn_type: selectedCn,
                maker: selectedSupplier,
                add_cost_percent: selectedImportTax,
                update_costing_edit: true,
            });
            if (saved) {
                const completedRow = document.querySelector(`#unpricedRecapBody tr[data-unpriced-part="${escapedPart}"]`);
                if (completedRow) completedRow.remove();
                renumberUnpricedRows();
                updateUnpricedSelectAllState();
            }

            if (manageLoading) {
                hideAppLoading();
                if (saved) openAppNotify(`Harga ${partNumber} berhasil ditambahkan.`, 'success');
            }

            return saved;
        }

        async function persistMaterialDraftBeforeBulk() {
            const form = document.getElementById('costingForm');
            if (!form) throw new Error('Form Costing tidak ditemukan.');

            const payload = buildMaterialSectionPayload(form);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: payload,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success !== true) {
                throw new Error(data.message || 'Baris Material gagal disimpan sebagai draft.');
            }

            const costingDataId = Number(data?.meta?.costing_data_id || 0);
            const costingDataInput = form.querySelector('[name="costing_data_id"]');
            if (costingDataInput && costingDataId > 0) costingDataInput.value = String(costingDataId);

            return costingDataId;
        }

        async function addSelectedUnpricedRows() {
            const selectedRows = Array.from(document.querySelectorAll('#unpricedRecapBody .unpriced-row-select:checked'))
                .map(checkbox => checkbox.closest('tr'))
                .filter(row => row instanceof HTMLTableRowElement);

            if (selectedRows.length === 0) {
                openAppNotify('Pilih minimal satu part yang akan ditambahkan.', 'warning');
                return;
            }

            const items = selectedRows.map(getUnpricedRowPriceData).filter(Boolean);
            const unavailable = items.filter(item => !item.partNumber || item.price <= 0);
            if (unavailable.length > 0) {
                const labels = unavailable.map(item => item.partNumber || '(tanpa nomor part)').join(', ');
                openAppNotify(`Harga belum tersedia untuk: ${labels}. Import New Part Request atau pilih harga terlebih dahulu.`, 'error');
                return;
            }

            openAppConfirm(`Tambahkan harga untuk ${items.length} part yang dipilih?`, async function () {
                showAppLoading('Menyimpan baris Material sebagai draft...');
                let savedCount = 0;
                const failedParts = [];

                try {
                    // Fill every matching Material row first, then persist the
                    // whole editor in one transaction. This guarantees there
                    // is a real MaterialBreakdown target before a part is
                    // marked resolved.
                    for (const item of items) {
                        await applySelectedMatchedPrice(
                            item.partNumber,
                            item.price,
                            item.currency,
                            item.unit,
                            item.moq,
                            item.cn,
                            item.supplier,
                            item.importTax,
                            false,
                            false
                        );
                    }
                    await persistMaterialDraftBeforeBulk();
                } catch (error) {
                    hideAppLoading();
                    openAppNotify(error.message || 'Material gagal disimpan. Tidak ada part yang dihilangkan.', 'error');
                    return;
                }

                for (const [index, item] of items.entries()) {
                    showAppLoading(`Menambahkan ${index + 1} dari ${items.length} part...`);
                    if (unpricedSyncTimers[item.partNumber]) {
                        clearTimeout(unpricedSyncTimers[item.partNumber]);
                        delete unpricedSyncTimers[item.partNumber];
                    }
                    const saved = await applySelectedMatchedPrice(
                        item.partNumber,
                        item.price,
                        item.currency,
                        item.unit,
                        item.moq,
                        item.cn,
                        item.supplier,
                        item.importTax,
                        false
                    );

                    if (saved) savedCount += 1;
                    else failedParts.push(item.partNumber);
                }

                if (failedParts.length === 0) {
                    hideAppLoading();
                    openAppNotify(`${savedCount} part berhasil ditambahkan.`, 'success');
                    return;
                }

                hideAppLoading();
                openAppNotify(`${savedCount} part berhasil. Gagal: ${failedParts.join(', ')}.`, 'error');
            });
        }

        function bindUnpricedManualPriceInputs() {
            const inputs = document.querySelectorAll('.unpriced-manual-price');
            inputs.forEach((input) => {
                if (input.dataset.boundRealtime === '1') {
                    return;
                }

                input.dataset.boundRealtime = '1';
                input.addEventListener('input', function () {
                    const partNumber = this.dataset.partNumber || '';
                    if (!partNumber) return;

                    if (unpricedSyncTimers[partNumber]) {
                        clearTimeout(unpricedSyncTimers[partNumber]);
                    }

                    unpricedSyncTimers[partNumber] = setTimeout(() => {
                        syncManualPriceToServer(partNumber, this.value);
                    }, 450);
                });
            });
        }

        function bindUnpricedDeleteButtons() {
            const buttons = document.querySelectorAll('.unpriced-delete-btn');
            buttons.forEach((button) => {
                if (button.dataset.boundDelete === '1') {
                    return;
                }

                button.dataset.boundDelete = '1';
                button.addEventListener('click', function () {
                    const partNumber = this.dataset.partNumber || '';
                    if (!partNumber) return;

                    openAppConfirm(`Hapus part tanpa harga "${partNumber}"?`, function() {
                        removeUnpricedRow(partNumber);
                    });
                });
            });

            document.querySelectorAll('#unpricedRecapBody .unpriced-row-select').forEach(cb => {
                if (cb.dataset.boundChange === '1') return;
                cb.dataset.boundChange = '1';
                cb.addEventListener('change', updateUnpricedSelectAllState);
            });
        }

        function removeUnpricedRow(partNumber) {
            const row = document.querySelector(`#unpricedRecapBody tr[data-unpriced-part="${CSS.escape(partNumber)}"]`);
            if (row) {
                row.remove();
            }
            renumberUnpricedRows();
            updateUnpricedSelectAllState();

            // Also sync to server if URL available
            deleteUnpricedPartFromServer(partNumber);
        }

        function deleteUnpricedPartFromServer(partNumber) {
            const trackingRevisionId = document.getElementById('trackingRevisionId')?.value || '';
            const url = document.getElementById('deleteUnpricedPartUrl')?.value || '';
            if (!trackingRevisionId || !url) return Promise.resolve();

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ part_number: partNumber })
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.ok) {
                    updateUnpricedBanner(data.open_unpriced_count || 0);
                }
            })
            .catch(() => {});
        }

        function updateUnpricedBanner(openCount) {
            const banner = document.getElementById('unpricedTopBanner');
            const bannerText = document.getElementById('unpricedTopBannerText');
            if (banner) banner.style.display = openCount > 0 ? 'flex' : 'none';
            if (bannerText) bannerText.textContent = `Terdapat ${openCount} part yang belum memiliki harga pada versi dokumen ini.`;
        }

        function toggleAllUnpricedRowCheckboxes(checked) {
            document.querySelectorAll('#unpricedRecapBody .unpriced-row-select').forEach(cb => {
                cb.checked = checked;
            });
            updateUnpricedSelectAllState();
        }

        function updateUnpricedSelectAllState() {
            const all = document.querySelectorAll('#unpricedRecapBody .unpriced-row-select');
            const checked = document.querySelectorAll('#unpricedRecapBody .unpriced-row-select:checked');
            const selectAll = document.getElementById('unpricedSelectAll');
            if (selectAll) {
                selectAll.checked = all.length > 0 && all.length === checked.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }

            const addButton = document.getElementById('unpricedAddSelectedBtn');
            const deleteButton = document.getElementById('unpricedDeleteSelectedBtn');
            if (addButton) {
                addButton.textContent = checked.length > 0 ? `Tambah Terpilih (${checked.length})` : 'Tambah Terpilih';
                addButton.disabled = checked.length === 0;
            }
            if (deleteButton) {
                deleteButton.textContent = checked.length > 0 ? `Hapus Terpilih (${checked.length})` : 'Hapus Terpilih';
                deleteButton.disabled = checked.length === 0;
            }
        }

        function renumberUnpricedRows() {
            const rows = document.querySelectorAll('#unpricedRecapBody tr[data-unpriced-part]');
            rows.forEach((row, idx) => {
                const numSpan = row.querySelector('.unpriced-row-select')?.parentElement?.querySelector('span');
                if (numSpan) numSpan.textContent = idx + 1;
            });
            if (rows.length === 0) {
                const tbody = document.getElementById('unpricedRecapBody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="15" style="text-align: center; color: var(--slate-500);">Belum ada part tanpa harga untuk versi dokumen ini.</td></tr>';
                }
            }
        }

        function deleteSelectedUnpricedRows() {
            const selectedRows = Array.from(document.querySelectorAll('#unpricedRecapBody .unpriced-row-select:checked'))
                .map(cb => cb.closest('tr'))
                .filter(row => row instanceof HTMLTableRowElement);

            if (selectedRows.length === 0) return;

            openAppConfirm(
                `Hapus ${selectedRows.length} baris yang dipilih?`,
                function() {
                    showAppLoading('Menghapus...');
                    const partNumbers = selectedRows
                        .map(row => row.dataset.unpricedPart || '')
                        .filter(p => p !== '');

                    // Optimistically remove rows from DOM immediately
                    selectedRows.forEach(row => row.remove());
                    renumberUnpricedRows();
                    updateUnpricedSelectAllState();

                    const bulkUrl = document.getElementById('bulkDeleteUnpricedUrl')?.value || '';
                    const deleteUrl = document.getElementById('deleteUnpricedPartUrl')?.value || '';

                    if (bulkUrl && partNumbers.length > 0) {
                        // Single bulk request
                        fetch(bulkUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ part_numbers: partNumbers })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.ok) {
                                updateUnpricedBanner(data.open_unpriced_count || 0);
                            }
                            hideAppLoading();
                        })
                        .catch(() => {
                            hideAppLoading();
                        });
                    } else if (deleteUrl && partNumbers.length > 0) {
                        // Fallback: single delete for each (old endpoint)
                        Promise.all(partNumbers.map(pn =>
                            fetch(deleteUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ part_number: pn })
                            }).catch(() => {})
                        )).then(() => hideAppLoading());
                    } else {
                        hideAppLoading();
                    }
                }
            );
        }

        function syncManualPriceToServer(partNumber, value, details = {}) {
            const trackingRevisionId = document.getElementById('trackingRevisionId')?.value || '';
            const url = document.getElementById('updateUnpricedPriceUrl')?.value || '';

            if (!trackingRevisionId || !url) {
                return Promise.resolve(false);
            }

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    part_number: partNumber,
                    manual_price: value === '' ? null : Number(value),
                    ...details,
                })
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data || data.ok !== true) {
                        throw new Error(data?.message || 'Harga gagal disimpan.');
                    }

                    const banner = document.getElementById('unpricedTopBanner');
                    const bannerText = document.getElementById('unpricedTopBannerText');
                    const openCount = Number(data.open_unpriced_count || 0);

                    if (banner) {
                        banner.style.display = openCount > 0 ? 'flex' : 'none';
                    }

                    if (bannerText) {
                        bannerText.textContent = `Terdapat ${openCount} part yang belum memiliki harga pada versi dokumen ini.`;
                    }
                    return true;
                })
                .catch((error) => {
                    if (details.update_costing_edit) {
                        openAppNotify(error.message || 'Harga gagal disimpan.', 'error');
                    }
                    return false;
                });
        }

        // deleteUnpricedPart replaced by removeUnpricedRow + deleteUnpricedPartFromServer



