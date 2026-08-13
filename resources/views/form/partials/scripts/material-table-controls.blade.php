        function getMaterialStateSnapshot() {
            const tbody = document.getElementById('materialTableBody');
            if (!tbody) {
                return null;
            }

            const clone = tbody.cloneNode(true);
            const liveControls = Array.from(tbody.querySelectorAll('input, select, textarea'));
            const cloneControls = Array.from(clone.querySelectorAll('input, select, textarea'));

            cloneControls.forEach((control, index) => {
                const liveControl = liveControls[index];
                if (!liveControl) {
                    return;
                }

                if (control instanceof HTMLInputElement) {
                    control.value = liveControl.value;
                    if (control.type === 'checkbox' || control.type === 'radio') {
                        control.checked = liveControl.checked;
                    }
                } else if (control instanceof HTMLSelectElement) {
                    control.value = liveControl.value;
                    Array.from(control.options).forEach((option) => {
                        option.selected = option.value === liveControl.value;
                    });
                } else if (control instanceof HTMLTextAreaElement) {
                    control.value = liveControl.value;
                    control.textContent = liveControl.value;
                }
            });

            return {
                html: clone.innerHTML,
                rowCounter,
            };
        }

        function updateMaterialUndoButtonState() {
            const undoBtn = document.getElementById('materialUndoBtn');
            const redoBtn = document.getElementById('materialRedoBtn');
            if (!undoBtn) return;
            undoBtn.disabled = materialUndoHistory.length === 0;
            if (redoBtn) {
                redoBtn.disabled = materialRedoHistory.length === 0;
            }
        }

        function pushMaterialHistoryAction(action) {
            if (!action || materialHistoryApplying) {
                return;
            }

            materialUndoHistory.push(action);
            if (materialUndoHistory.length > materialUndoLimit) {
                materialUndoHistory.shift();
            }

            // Flag as dirty to stop section jumping
            isMaterialDirty = true;
            materialRedoHistory = [];

            updateMaterialUndoButtonState();
        }

        function commitActiveMaterialFieldChange() {
            const active = document.activeElement;
            if (!(active instanceof HTMLElement)) {
                return;
            }

            if (!active.matches('#materialTableBody input.form-input, #materialTableBody select.form-select')) {
                return;
            }

            const previousValue = active.dataset.undoValue ?? '';
            const currentValue = active.value ?? '';
            if (previousValue === currentValue) {
                return;
            }

            pushMaterialHistoryAction({
                type: 'field',
                name: active.name,
                oldValue: previousValue,
                newValue: currentValue,
            });

            active.dataset.undoValue = currentValue;
        }

        function applyMaterialFieldValueByName(name, value) {
            if (!name) {
                return;
            }

            const escapedName = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function')
                ? CSS.escape(name)
                : name.replace(/([\[\]\.\:\#])/g, '\\$1');

            const target = document.querySelector(`#materialTableBody [name="${escapedName}"]`);
            if (!(target instanceof HTMLElement)) {
                return;
            }

            target.value = value ?? '';

            if (target instanceof HTMLInputElement && target.type === 'text') {
                target.value = String(target.value || '').toUpperCase();
            }

            target.dataset.undoValue = target.value ?? '';

            recalculateAllRows();
            refreshUnpricedRecap();

            const focused = document.activeElement;
            if (focused instanceof HTMLElement && focused.matches('#materialTableBody input.form-input, #materialTableBody select.form-select')) {
                focused.dataset.undoValue = focused.value ?? '';
            }
        }

        function markMaterialControlsUndoBase() {
            const controls = document.querySelectorAll('#materialTableBody input.form-input, #materialTableBody select.form-select');
            controls.forEach((control) => {
                control.dataset.undoValue = control.value ?? '';
            });
        }

        function applyMaterialAction(action, direction) {
            if (!action) {
                return;
            }

            materialHistoryApplying = true;

            if (action.type === 'field') {
                const targetValue = direction === 'undo' ? action.oldValue : action.newValue;
                applyMaterialFieldValueByName(action.name, targetValue);
            } else if (action.type === 'snapshot') {
                const snapshot = direction === 'undo' ? action.before : action.after;
                restoreMaterialSnapshot(snapshot);
            }

            materialHistoryApplying = false;

            markMaterialControlsUndoBase();

            updateMaterialUndoButtonState();
        }

        function restoreMaterialSnapshot(snapshot) {
            if (!snapshot) {
                return;
            }

            const tbody = document.getElementById('materialTableBody');
            if (!tbody) {
                return;
            }

            tbody.innerHTML = snapshot.html;
            rowCounter = snapshot.rowCounter;

            renumberRows();
            normalizeMaterialTextInputs();
            recalculateAllRows();
            refreshUnpricedRecap();
            bindUnpricedManualPriceInputs();
            bindUnpricedDeleteButtons();
            bindMatchedPriceSelectors();
            bindUnpricedAddPriceButtons();
            applyMaterialFilters();
        }

        function getMaterialRowFilterValue(row, columnIndex) {
            const cell = row.cells[columnIndex];
            if (!cell) return '';

            const control = cell.querySelector('input, select, textarea');
            if (control) {
                return String(control.value ?? '').trim();
            }

            return String(cell.textContent ?? '').trim();
        }

        function getMaterialColumnValues(columnIndex) {
            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const values = new Set();

            rows.forEach((row) => {
                const value = getMaterialRowFilterValue(row, columnIndex);
                values.add(value === '' ? '(Blanks)' : value);
            });

            return Array.from(values).sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));
        }

        function applyMaterialFilters() {
            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));

            rows.forEach((row) => {
                let visible = true;

                for (const [columnKey, selectedValues] of Object.entries(materialFilterState)) {
                    if (!(selectedValues instanceof Set) || selectedValues.size === 0) {
                        continue;
                    }

                    const columnIndex = Number(columnKey);
                    const rawValue = getMaterialRowFilterValue(row, columnIndex);
                    const normalizedValue = rawValue === '' ? '(Blanks)' : rawValue;

                    if (!selectedValues.has(normalizedValue)) {
                        visible = false;
                        break;
                    }
                }

                row.style.display = visible ? '' : 'none';
            });

            if (materialSortState.column !== null && materialSortState.direction) {
                const columnIndex = Number(materialSortState.column);
                const direction = materialSortState.direction === 'desc' ? -1 : 1;
                const tbody = document.getElementById('materialTableBody');

                if (tbody) {
                    rows.sort((a, b) => {
                        const va = getMaterialRowFilterValue(a, columnIndex);
                        const vb = getMaterialRowFilterValue(b, columnIndex);

                        const na = Number(va);
                        const nb = Number(vb);
                        const bothNumeric = !Number.isNaN(na) && !Number.isNaN(nb) && va !== '' && vb !== '';

                        if (bothNumeric) {
                            if (na === nb) return 0;
                            return (na < nb ? -1 : 1) * direction;
                        }

                        return va.localeCompare(vb, undefined, { sensitivity: 'base', numeric: true }) * direction;
                    });

                    rows.forEach((row) => tbody.appendChild(row));
                    renumberRows();
                }
            }

            updateMaterialFilterButtonsState();
        }

        function updateMaterialFilterButtonsState() {
            const table = document.getElementById('materialTable');
            if (!table) return;

            table.querySelectorAll('.material-filter-btn').forEach((btn) => {
                const col = Number(btn.dataset.col || -1);
                const activeSet = materialFilterState[col];
                const hasFilter = activeSet instanceof Set && activeSet.size > 0;
                const hasSort = materialSortState.column === col && !!materialSortState.direction;
                btn.classList.toggle('is-active', hasFilter || hasSort);
            });
        }

        function setMaterialSort(columnIndex, direction) {
            if (materialSortState.column === columnIndex && materialSortState.direction === direction) {
                materialSortState = { column: null, direction: null };
            } else {
                materialSortState = { column: columnIndex, direction };
            }

            applyMaterialFilters();

            if (activeMaterialFilterColumn !== null) {
                const sortAscBtn = materialFilterPopup?.querySelector('.material-filter-sort-asc');
                const sortDescBtn = materialFilterPopup?.querySelector('.material-filter-sort-desc');
                const ascActive = materialSortState.column === activeMaterialFilterColumn && materialSortState.direction === 'asc';
                const descActive = materialSortState.column === activeMaterialFilterColumn && materialSortState.direction === 'desc';
                sortAscBtn?.classList.toggle('is-active', ascActive);
                sortDescBtn?.classList.toggle('is-active', descActive);
            }
        }

        function closeMaterialFilterPopup() {
            if (!materialFilterPopup) return;
            materialFilterPopup.classList.remove('show');
            activeMaterialFilterColumn = null;
        }

        function renderMaterialFilterOptions(columnIndex, keyword = '') {
            if (!materialFilterPopup) return;

            const list = materialFilterPopup.querySelector('.material-filter-popup-list');
            if (!list) return;

            const selected = materialFilterState[columnIndex] instanceof Set
                ? new Set(materialFilterState[columnIndex])
                : null;
            const values = getMaterialColumnValues(columnIndex).filter((v) => v.toLowerCase().includes(keyword.toLowerCase()));

            list.innerHTML = '';

            const selectAllItem = document.createElement('label');
            selectAllItem.className = 'material-filter-popup-item';
            const selectAllCheckbox = document.createElement('input');
            selectAllCheckbox.type = 'checkbox';
            selectAllCheckbox.className = 'material-filter-select-all-checkbox';
            selectAllCheckbox.checked = values.length > 0 && values.every((v) => !selected || selected.has(v));
            const selectAllText = document.createElement('span');
            selectAllText.textContent = '(Select All)';
            selectAllItem.appendChild(selectAllCheckbox);
            selectAllItem.appendChild(selectAllText);
            list.appendChild(selectAllItem);

            selectAllCheckbox.addEventListener('change', function () {
                const checked = this.checked;
                list.querySelectorAll('.material-filter-value-checkbox').forEach((cb) => {
                    cb.checked = checked;
                });
            });

            values.forEach((value) => {
                const item = document.createElement('label');
                item.className = 'material-filter-popup-item';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'material-filter-value-checkbox';
                checkbox.value = value;
                checkbox.checked = !selected || selected.has(value);

                const text = document.createElement('span');
                text.textContent = value;

                item.appendChild(checkbox);
                item.appendChild(text);
                list.appendChild(item);
            });
        }

        function openMaterialFilterPopup(columnIndex, title, anchorElement) {
            if (!materialFilterPopup || !anchorElement) return;

            activeMaterialFilterColumn = columnIndex;
            materialFilterPopup.querySelector('.material-filter-popup-head').textContent = `Filter: ${title}`;

            const searchInput = materialFilterPopup.querySelector('.material-filter-search-input');
            if (searchInput) {
                searchInput.value = '';
            }

            const sortAscBtn = materialFilterPopup.querySelector('.material-filter-sort-asc');
            const sortDescBtn = materialFilterPopup.querySelector('.material-filter-sort-desc');
            const clearLineBtn = materialFilterPopup.querySelector('.material-filter-clear-line-btn');
            const ascActive = materialSortState.column === columnIndex && materialSortState.direction === 'asc';
            const descActive = materialSortState.column === columnIndex && materialSortState.direction === 'desc';
            sortAscBtn?.classList.toggle('is-active', ascActive);
            sortDescBtn?.classList.toggle('is-active', descActive);
            if (clearLineBtn) {
                clearLineBtn.textContent = `Clear Filter From "${title.toUpperCase()}"`;
            }

            renderMaterialFilterOptions(columnIndex, '');

            const rect = anchorElement.getBoundingClientRect();
            materialFilterPopup.style.top = `${Math.min(window.innerHeight - 380, rect.bottom + 8)}px`;
            materialFilterPopup.style.left = `${Math.max(8, Math.min(window.innerWidth - 280, rect.left))}px`;
            materialFilterPopup.classList.add('show');
        }

        function initMaterialFilterPopup() {
            if (materialFilterPopup) return;

            materialFilterPopup = document.createElement('div');
            materialFilterPopup.className = 'material-filter-popup';
            materialFilterPopup.innerHTML = `
                <div class="material-filter-popup-head">Filter</div>
                <div class="material-filter-popup-sort">
                    <button type="button" class="btn btn-secondary btn-sm material-filter-sort-asc">Sort A to Z</button>
                    <button type="button" class="btn btn-secondary btn-sm material-filter-sort-desc">Sort Z to A</button>
                </div>
                <div class="material-filter-separator"></div>
                <div class="material-filter-clear-line">
                    <button type="button" class="btn btn-secondary btn-sm material-filter-clear-line-btn">Clear Filter</button>
                </div>
                <div class="material-filter-popup-search">
                    <input type="text" class="material-filter-search-input" placeholder="Search...">
                </div>
                <div class="material-filter-popup-list"></div>
                <div class="material-filter-popup-actions">
                    <button type="button" class="btn btn-secondary btn-sm material-filter-cancel-btn">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm material-filter-apply-btn">OK</button>
                </div>
            `;

            document.body.appendChild(materialFilterPopup);

            const searchInput = materialFilterPopup.querySelector('.material-filter-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    if (activeMaterialFilterColumn === null) return;
                    renderMaterialFilterOptions(activeMaterialFilterColumn, this.value || '');
                });
            }

            const sortAscBtn = materialFilterPopup.querySelector('.material-filter-sort-asc');
            sortAscBtn?.addEventListener('click', function () {
                if (activeMaterialFilterColumn === null) return;
                setMaterialSort(activeMaterialFilterColumn, 'asc');
            });

            const sortDescBtn = materialFilterPopup.querySelector('.material-filter-sort-desc');
            sortDescBtn?.addEventListener('click', function () {
                if (activeMaterialFilterColumn === null) return;
                setMaterialSort(activeMaterialFilterColumn, 'desc');
            });

            const clearLineBtn = materialFilterPopup.querySelector('.material-filter-clear-line-btn');
            clearLineBtn?.addEventListener('click', function () {
                if (activeMaterialFilterColumn === null) return;
                delete materialFilterState[activeMaterialFilterColumn];
                applyMaterialFilters();
                closeMaterialFilterPopup();
            });

            const cancelBtn = materialFilterPopup.querySelector('.material-filter-cancel-btn');
            cancelBtn?.addEventListener('click', function () {
                closeMaterialFilterPopup();
            });

            const applyBtn = materialFilterPopup.querySelector('.material-filter-apply-btn');
            applyBtn?.addEventListener('click', function () {
                if (activeMaterialFilterColumn === null) return;

                const checked = Array.from(materialFilterPopup.querySelectorAll('.material-filter-popup-list .material-filter-value-checkbox:checked'))
                    .map((el) => el.value);

                const allValues = getMaterialColumnValues(activeMaterialFilterColumn);
                if (checked.length === 0 || checked.length === allValues.length) {
                    delete materialFilterState[activeMaterialFilterColumn];
                } else {
                    materialFilterState[activeMaterialFilterColumn] = new Set(checked);
                }

                applyMaterialFilters();
                closeMaterialFilterPopup();
            });

            document.addEventListener('click', function (event) {
                if (!materialFilterPopup || !materialFilterPopup.classList.contains('show')) return;
                const target = event.target;
                if (!(target instanceof Node)) return;

                const clickedFilterBtn = target instanceof Element && target.closest('.material-filter-btn');
                if (materialFilterPopup.contains(target) || clickedFilterBtn) {
                    return;
                }

                closeMaterialFilterPopup();
            });
        }

        function initMaterialHeaderFilters() {
            const table = document.getElementById('materialTable');
            if (!table) return;

            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach((th, index) => {
                if (!materialFilterableColumns.includes(index)) {
                    return;
                }

                if (th.dataset.filterReady === '1') {
                    return;
                }

                const title = (th.textContent || '').trim();
                th.dataset.filterReady = '1';
                th.dataset.filterTitle = title;

                const wrap = document.createElement('span');
                wrap.className = 'material-header-filter';

                const label = document.createElement('span');
                label.textContent = title;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'material-filter-btn';
                btn.dataset.col = String(index);
                btn.title = `Filter ${title}`;
                btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 4 21 4 14 12 14 19 10 21 10 12 3 4"></polygon></svg>';

                btn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    if (materialFilterPopup?.classList.contains('show') && activeMaterialFilterColumn === index) {
                        closeMaterialFilterPopup();
                        return;
                    }
                    openMaterialFilterPopup(index, title, btn);
                });

                th.textContent = '';
                wrap.appendChild(label);
                wrap.appendChild(btn);
                th.appendChild(wrap);
            });

            updateMaterialFilterButtonsState();
        }

        function undoMaterialTable() {
            // Capture pending active-cell edit first, then revert exactly one action.
            commitActiveMaterialFieldChange();

            if (materialUndoHistory.length === 0) {
                return;
            }

            const action = materialUndoHistory.pop();
            if (!action) {
                updateMaterialUndoButtonState();
                return;
            }

            applyMaterialAction(action, 'undo');

            materialRedoHistory.push(action);
            if (materialRedoHistory.length > materialUndoLimit) {
                materialRedoHistory.shift();
            }

            updateMaterialUndoButtonState();
        }

        function redoMaterialTable() {
            if (materialRedoHistory.length === 0) {
                return;
            }

            const next = materialRedoHistory.pop();
            if (!next) {
                updateMaterialUndoButtonState();
                return;
            }

            applyMaterialAction(next, 'redo');

            materialUndoHistory.push(next);
            if (materialUndoHistory.length > materialUndoLimit) {
                materialUndoHistory.shift();
            }

            updateMaterialUndoButtonState();
        }

        function normalizeMaterialTextInputs(scope = document) {
            const textInputs = scope.querySelectorAll('#materialTableBody input[type="text"]');
            textInputs.forEach((input) => {
                const value = String(input.value || '');
                const upper = value.toUpperCase();
                if (upper !== value) {
                    input.value = upper;
                }
            });
        }

        function moveMaterialFocusByArrow(currentElement, key) {
            const currentRow = currentElement.closest('tr');
            if (!currentRow) return;

            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const currentRowIndex = rows.indexOf(currentRow);
            if (currentRowIndex < 0) return;

            const getEditableCells = (row) => Array.from(row.querySelectorAll('input.form-input, select.form-select'));
            const currentCells = getEditableCells(currentRow);
            const currentCellIndex = currentCells.indexOf(currentElement);
            if (currentCellIndex < 0) return;

            let nextRowIndex = currentRowIndex;
            let nextCellIndex = currentCellIndex;

            if (key === 'ArrowLeft') nextCellIndex -= 1;
            if (key === 'ArrowRight') nextCellIndex += 1;
            if (key === 'ArrowUp') nextRowIndex -= 1;
            if (key === 'ArrowDown') nextRowIndex += 1;

            if (key === 'ArrowLeft' || key === 'ArrowRight') {
                if (nextCellIndex < 0 || nextCellIndex >= currentCells.length) {
                    return;
                }

                const target = currentCells[nextCellIndex];
                if (!target) return;

                target.focus();
                if (target.tagName === 'INPUT') {
                    target.select();
                }
                return;
            }

            if (nextRowIndex < 0 || nextRowIndex >= rows.length) {
                return;
            }

            const nextRow = rows[nextRowIndex];
            const nextRowCells = getEditableCells(nextRow);
            if (!nextRowCells.length) return;

            const target = nextRowCells[Math.min(currentCellIndex, nextRowCells.length - 1)];
            if (!target) return;

            target.focus();
            if (target.tagName === 'INPUT') {
                target.select();
            }
        }

        function moveMaterialFocusLinear(currentElement, step) {
            const currentRow = currentElement.closest('tr');
            if (!currentRow) return;

            const rows = Array.from(document.querySelectorAll('#materialTableBody tr'));
            const currentRowIndex = rows.indexOf(currentRow);
            if (currentRowIndex < 0) return;

            const getEditableCells = (row) => Array.from(row.querySelectorAll('input.form-input, select.form-select'));
            const currentCells = getEditableCells(currentRow);
            const currentCellIndex = currentCells.indexOf(currentElement);
            if (currentCellIndex < 0) return;

            let nextRowIndex = currentRowIndex;
            let nextCellIndex = currentCellIndex + step;

            if (nextCellIndex >= currentCells.length) {
                nextRowIndex += 1;
                if (nextRowIndex >= rows.length) {
                    return;
                }
                nextCellIndex = 0;
            } else if (nextCellIndex < 0) {
                nextRowIndex -= 1;
                if (nextRowIndex < 0) {
                    return;
                }
                const prevCells = getEditableCells(rows[nextRowIndex]);
                nextCellIndex = Math.max(prevCells.length - 1, 0);
            }

            const nextCells = getEditableCells(rows[nextRowIndex]);
            if (!nextCells.length) return;

            const target = nextCells[Math.min(nextCellIndex, nextCells.length - 1)];
            if (!target) return;

            target.focus();
            if (target.tagName === 'INPUT') {
                target.select();
            }
        }

        function bindMaterialTableBehaviors() {
            const materialBody = document.getElementById('materialTableBody');
            if (!materialBody || materialBody.dataset.boundBehavior === '1') {
                return;
            }

            materialBody.dataset.boundBehavior = '1';

            materialBody.addEventListener('input', function (event) {
                isMaterialDirty = true;
                const dirtyRow = event.target?.closest ? event.target.closest('tr') : null;
                if (dirtyRow && dirtyRow.closest('#materialTableBody')) {
                    dirtyRow.dataset.materialDirty = '1';
                }
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) {
                    return;
                }

                if (target.type !== 'text') {
                    return;
                }

                const currentValue = String(target.value || '');
                const upperValue = currentValue.toUpperCase();
                if (upperValue === currentValue) {
                    return;
                }

                const start = target.selectionStart;
                const end = target.selectionEnd;
                target.value = upperValue;
                if (start !== null && end !== null) {
                    target.setSelectionRange(start, end);
                }
            });

            materialBody.addEventListener('focusin', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (!target.matches('input.form-input, select.form-select')) {
                    return;
                }

                target.dataset.undoValue = target.value ?? '';
            });

            materialBody.addEventListener('change', function (event) {
                const target = event.target;
                const dirtyRow = target?.closest ? target.closest('tr') : null;
                if (dirtyRow && dirtyRow.closest('#materialTableBody')) {
                    dirtyRow.dataset.materialDirty = '1';
                }
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.matches('.part-no, .id-code')) {
                    const row = target.closest('tr');
                    if (row instanceof HTMLTableRowElement) {
                        const updated = applyMasterMaterialToRow(row);
                        if (updated) {
                            const input = row.querySelector('.qty-req') || row.querySelector('.amount1') || row.querySelector('.unit-price-basis');
                            if (input) {
                                calculateRow(input, { silent: true });
                            }
                        }
                    }
                }

                if (target.matches('.material-row-select')) {
                    updateMaterialSelectAllRowsState();
                    return;
                }

                if (!target.matches('input.form-input, select.form-select')) {
                    return;
                }

                const previousValue = target.dataset.undoValue ?? '';
                const currentValue = target.value ?? '';
                if (previousValue === currentValue) {
                    return;
                }

                pushMaterialHistoryAction({
                    type: 'field',
                    name: target.name,
                    oldValue: previousValue,
                    newValue: currentValue,
                });

                target.dataset.undoValue = currentValue;
                applyMaterialFilters();
            });

            materialBody.addEventListener('keydown', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (!target.matches('input.form-input, select.form-select')) {
                    return;
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    moveMaterialFocusLinear(target, event.shiftKey ? -1 : 1);
                    return;
                }

                // Excel-like F2 behavior: enter edit mode
                if (event.key === 'F2') {
                    event.preventDefault();
                    if (target.tagName === 'INPUT') {
                        target.dataset.isEditing = '1';
                        // Move cursor to the end of the text
                        const len = target.value.length;
                        target.setSelectionRange(len, len);
                    }
                    return;
                }

                // If in edit mode, allow Left/Right arrows to move cursor naturally
                if (['ArrowLeft', 'ArrowRight'].includes(event.key) && target.dataset.isEditing === '1') {
                    return;
                }

                if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
                    return;
                }

                event.preventDefault();
                moveMaterialFocusByArrow(target, event.key);
            });

            // Double click to also enter edit mode like Excel
            materialBody.addEventListener('dblclick', function(event) {
                const target = event.target;
                if (target instanceof HTMLInputElement && target.matches('input.form-input')) {
                    target.dataset.isEditing = '1';
                }
            });

            // Clear edit mode when leaving the cell
            materialBody.addEventListener('focusout', function(event) {
                const target = event.target;
                if (target instanceof HTMLInputElement) {
                    target.dataset.isEditing = '0';
                }
            });

            const masterSelectAll = document.getElementById('materialSelectAllRows');
            if (masterSelectAll && masterSelectAll.dataset.boundSelectAll !== '1') {
                masterSelectAll.dataset.boundSelectAll = '1';
                masterSelectAll.addEventListener('change', function () {
                    const checked = !!this.checked;
                    document.querySelectorAll('#materialTableBody .material-row-select').forEach((cb) => {
                        if (cb instanceof HTMLInputElement) {
                            cb.checked = checked;
                        }
                    });
                    updateMaterialSelectAllRowsState();
                });
            }
        }




