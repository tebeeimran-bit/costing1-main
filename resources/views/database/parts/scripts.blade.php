    <script>
        (function () {
            const modal = document.getElementById('materialModal');
            if (!modal) return;

            const titleEl = document.getElementById('materialModalTitle');
            const formEl = document.getElementById('materialModalForm');
            const submitEl = document.getElementById('materialModalSubmitBtn');
            const methodEl = document.getElementById('materialFormMethod');
            const materialIdEl = document.getElementById('materialFormMaterialId');

            const createAction = "{{ route('database.parts.store', absolute: false) }}";
            const updateActionTemplate = "{{ route('database.parts.update', ['id' => '__ID__'], false) }}";

            const fieldIds = [
                'plant',
                'material_code',
                'material_description',
                'material_type',
                'material_group',
                'base_uom',
                'price',
                'purchase_unit',
                'currency',
                'moq',
                'cn',
                'maker',
                'add_cost_import_tax',
                'price_update',
                'price_before',
            ];

            const fieldMap = {
                plant: document.getElementById('material_form_plant'),
                material_code: document.getElementById('material_form_code'),
                material_description: document.getElementById('material_form_desc'),
                material_type: document.getElementById('material_form_type'),
                material_group: document.getElementById('material_form_group'),
                base_uom: document.getElementById('material_form_uom'),
                price: document.getElementById('material_form_price'),
                purchase_unit: document.getElementById('material_form_purchase_unit'),
                currency: document.getElementById('material_form_currency'),
                moq: document.getElementById('material_form_moq'),
                cn: document.getElementById('material_form_cn'),
                maker: document.getElementById('material_form_maker'),
                add_cost_import_tax: document.getElementById('material_form_tax'),
                price_update: document.getElementById('material_form_price_update'),
                price_before: document.getElementById('material_form_price_before'),
            };

            function showModal() {
                modal.classList.remove('is-hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function hideModal() {
                modal.classList.add('is-hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            function fillForm(data) {
                fieldIds.forEach((key) => {
                    const el = fieldMap[key];
                    if (!el) return;
                    el.value = data[key] ?? '';
                });
            }

            function openCreateModal() {
                titleEl.textContent = 'Tambah Material Baru';
                submitEl.textContent = 'Tambah Material';
                formEl.action = createAction;
                methodEl.value = '';
                materialIdEl.value = '';
                fillForm({
                    plant: '',
                    material_code: '',
                    material_description: '',
                    material_type: '',
                    material_group: '',
                    base_uom: 'PCS',
                    price: '0',
                    purchase_unit: '',
                    currency: 'IDR',
                    moq: '',
                    cn: '',
                    maker: '',
                    add_cost_import_tax: '',
                    price_update: '',
                    price_before: '',
                });
                showModal();
            }

            function openEditModal(button) {
                const id = button.dataset.id || '';
                if (!id) return;

                titleEl.textContent = 'Edit Material';
                submitEl.textContent = 'Simpan Perubahan';
                formEl.action = updateActionTemplate.replace('__ID__', id);
                methodEl.value = 'PUT';
                materialIdEl.value = id;

                fillForm({
                    plant: button.dataset.plant || '',
                    material_code: button.dataset.material_code || '',
                    material_description: button.dataset.material_description || '',
                    material_type: button.dataset.material_type || '',
                    material_group: button.dataset.material_group || '',
                    base_uom: button.dataset.base_uom || 'PCS',
                    price: button.dataset.price || '0',
                    purchase_unit: button.dataset.purchase_unit || '',
                    currency: button.dataset.currency || 'IDR',
                    moq: button.dataset.moq || '',
                    cn: button.dataset.cn || '',
                    maker: button.dataset.maker || '',
                    add_cost_import_tax: button.dataset.add_cost_import_tax || '',
                    price_update: button.dataset.price_update || '',
                    price_before: button.dataset.price_before || '',
                });

                showModal();
            }

            const createBtn = document.getElementById('openCreateMaterialBtn');
            if (createBtn) {
                createBtn.addEventListener('click', openCreateModal);
            }

            const importModal = document.getElementById('importMaterialModal');
            const openImportBtn = document.getElementById('openImportMaterialBtn');

            function showImportModal() {
                if (!importModal) return;
                importModal.classList.remove('is-hidden');
                importModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function hideImportModal() {
                if (!importModal) return;
                importModal.classList.add('is-hidden');
                importModal.setAttribute('aria-hidden', 'true');
                if (modal.classList.contains('is-hidden')) {
                    document.body.style.overflow = '';
                }
            }

            if (openImportBtn) {
                openImportBtn.addEventListener('click', showImportModal);
            }

            const selectAll = document.getElementById('selectAllMaterials');
            const rowCheckboxes = () => Array.from(document.querySelectorAll('.row-material-checkbox'));

            function syncSelectAllState() {
                if (!selectAll) return;
                const rows = rowCheckboxes();
                if (rows.length === 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                    return;
                }

                const checkedCount = rows.filter((cb) => cb.checked).length;
                selectAll.checked = checkedCount === rows.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < rows.length;
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    rowCheckboxes().forEach((cb) => {
                        cb.checked = this.checked;
                    });
                    syncSelectAllState();
                });
            }

            rowCheckboxes().forEach((cb) => {
                cb.addEventListener('change', syncSelectAllState);
            });
            syncSelectAllState();

            // Delete all materials handler
            const deleteAllBtn = document.getElementById('deleteAllBtn');
            const deleteAllConfirmModal = document.getElementById('deleteAllConfirmModal');
            const deleteAllConfirmBtn = document.getElementById('deleteAllConfirmBtn');
            const deleteAllMessage = document.getElementById('deleteAllMessage');

            function showDeleteAllConfirmModal(totalCount) {
                if (!deleteAllConfirmModal) return;
                deleteAllMessage.textContent = `Apakah Anda yakin ingin menghapus SEMUA ${totalCount} data material secara permanen? Data yang dihapus tidak dapat dipulihkan.`;
                deleteAllConfirmModal.classList.remove('is-hidden');
                deleteAllConfirmModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function hideDeleteAllConfirmModal() {
                if (!deleteAllConfirmModal) return;
                deleteAllConfirmModal.classList.add('is-hidden');
                deleteAllConfirmModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            if (deleteAllBtn && deleteAllConfirmModal && deleteAllConfirmBtn) {
                deleteAllBtn.addEventListener('click', function () {
                    const totalCount = document.querySelector('[data-total-count]')?.getAttribute('data-total-count') || '0';
                    showDeleteAllConfirmModal(parseInt(totalCount, 10) || 0);
                });

                deleteAllConfirmBtn.addEventListener('click', function () {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("database.parts.destroy-all", absolute: false) }}';
                    form.innerHTML = '@csrf @method("DELETE")';
                    document.body.appendChild(form);
                    form.submit();
                });
            }

            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            const bulkDeleteConfirmModal = document.getElementById('bulkDeleteConfirmModal');
            const bulkDeleteConfirmBtn = document.getElementById('bulkDeleteConfirmBtn');
            const bulkDeleteMessage = document.getElementById('bulkDeleteMessage');
            const bulkDeleteForm = document.getElementById('bulkDeleteForm');
            const bulkIdsContainer = document.getElementById('bulkDeleteIdsContainer');
            let pendingBulkDeleteIds = [];

            function showBulkDeleteConfirmModal() {
                if (!bulkDeleteConfirmModal) return;
                bulkDeleteConfirmModal.classList.remove('is-hidden');
                bulkDeleteConfirmModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function hideBulkDeleteConfirmModal() {
                if (!bulkDeleteConfirmModal) return;
                bulkDeleteConfirmModal.classList.add('is-hidden');
                bulkDeleteConfirmModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            if (bulkDeleteBtn && bulkDeleteConfirmModal && bulkDeleteConfirmBtn) {
                bulkDeleteBtn.addEventListener('click', function () {
                    const selectedIds = rowCheckboxes()
                        .filter((cb) => cb.checked)
                        .map((cb) => cb.value)
                        .filter((value) => value !== '');

                    if (selectedIds.length === 0) {
                        window.alert('Pilih minimal satu material untuk dihapus.');
                        return;
                    }

                    pendingBulkDeleteIds = selectedIds;
                    bulkDeleteMessage.textContent = `Apakah Anda yakin ingin menghapus ${selectedIds.length} material terpilih?`;
                    showBulkDeleteConfirmModal();
                });

                bulkDeleteConfirmBtn.addEventListener('click', function () {
                    bulkIdsContainer.innerHTML = '';
                    pendingBulkDeleteIds.forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'material_ids[]';
                        input.value = id;
                        bulkIdsContainer.appendChild(input);
                    });
                    hideBulkDeleteConfirmModal();
                    bulkDeleteForm.submit();
                });
            }

            document.querySelectorAll('[data-close-import-modal]').forEach((el) => {
                el.addEventListener('click', hideImportModal);
            });

            document.querySelectorAll('[data-close-bulk-delete-modal]').forEach((el) => {
                el.addEventListener('click', hideBulkDeleteConfirmModal);
            });

            document.querySelectorAll('[data-close-delete-all-modal]').forEach((el) => {
                el.addEventListener('click', hideDeleteAllConfirmModal);
            });

            document.querySelectorAll('.js-open-edit-material').forEach((button) => {
                button.addEventListener('click', function () {
                    openEditModal(this);
                });
            });

            document.querySelectorAll('.js-delete-material-btn').forEach((button) => {
                button.addEventListener('click', function (event) {
                    event.preventDefault();

                    const form = this.closest('form.js-delete-material-form');
                    if (!form) return;

                    const message = form.dataset.confirmMessage || 'Apakah Anda yakin ingin menghapus material ini?';
                    openAppConfirm(message, function () {
                        showAppLoading('Menghapus material...');

                        const formData = new FormData(form);
                        const encoded = new URLSearchParams();
                        formData.forEach((value, key) => encoded.append(key, String(value)));

                        fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: encoded.toString(),
                        })
                        .then(function (resp) {
                            if (resp.ok || resp.redirected || resp.status === 302) {
                                window.location.reload();
                                return;
                            }

                            return resp.text().then(function () {
                                hideAppLoading();
                                openAppNotify('Gagal menghapus material. Silakan coba lagi.');
                            });
                        })
                        .catch(function () {
                            hideAppLoading();
                            openAppNotify('Terjadi gangguan jaringan saat menghapus material.');
                        });
                    });
                });
            });

            document.querySelectorAll('[data-close-material-modal]').forEach((el) => {
                el.addEventListener('click', hideModal);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('is-hidden')) {
                    hideModal();
                }

                if (event.key === 'Escape' && importModal && !importModal.classList.contains('is-hidden')) {
                    hideImportModal();
                }

                if (event.key === 'Escape' && bulkDeleteConfirmModal && !bulkDeleteConfirmModal.classList.contains('is-hidden')) {
                    hideBulkDeleteConfirmModal();
                }

                if (event.key === 'Escape' && deleteAllConfirmModal && !deleteAllConfirmModal.classList.contains('is-hidden')) {
                    hideDeleteAllConfirmModal();
                }
            });

            if (!modal.classList.contains('is-hidden')) {
                showModal();
            }

            if (importModal && !importModal.classList.contains('is-hidden')) {
                showImportModal();
            }
        })();
    </script>
