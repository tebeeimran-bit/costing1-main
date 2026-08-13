
<form id="statusProjectUpdateForm" method="POST" style="display:none;">
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" id="statusProjectUpdateStatus">
</form>


<script>
    const detailCostingPageSize = 10;
    let detailCostingCurrentPage = 1;

    function applyFilters() {
        const period = document.getElementById('periodFilter').value;
        const businessCategory = document.getElementById('businessCategoryFilter').value;
        const customer = document.getElementById('customerFilter').value;
        const model = document.getElementById('modelFilter').value;
        
        const params = new URLSearchParams();
        params.set('period', period);
        params.set('business_category', businessCategory);
        params.set('customer', customer);
        params.set('model', model);
        
        window.location.href = '{{ route("dashboard") }}?' + params.toString();
    }

    function filterDetailCostingTable(resetPage = true) {
        const searchInput = document.getElementById('detailCostingSearch');
        const table = document.getElementById('detailCostingTable');
        const clearButton = document.getElementById('detailCostingSearchClear');
        const noResultsRow = document.getElementById('detailCostingNoResults');
        const pageInfo = document.getElementById('detailCostingPageInfo');
        const prevButton = document.getElementById('detailCostingPrev');
        const nextButton = document.getElementById('detailCostingNext');
        const paginationContainer = document.getElementById('detailCostingPagination');
        if (!searchInput || !table) {
            return;
        }

        const filter = searchInput.value.toLowerCase().trim();
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const dataRows = rows.filter(function (row) {
            return row.dataset.search;
        });
        const placeholderRows = rows.filter(function (row) {
            return row.id !== 'detailCostingNoResults' && !row.dataset.search;
        });

        if (dataRows.length > 0) {
            placeholderRows.forEach(function (row) {
                row.style.display = 'none';
            });
        }

        const matchedRows = dataRows.filter(function (row) {
            const rowText = row.textContent.toLowerCase();
            const searchText = row.dataset.search || rowText;
            return searchText.indexOf(filter) !== -1;
        });

        const totalMatched = matchedRows.length;
        const totalPages = Math.max(1, Math.ceil(totalMatched / detailCostingPageSize));

        if (resetPage) {
            detailCostingCurrentPage = 1;
        }

        if (detailCostingCurrentPage > totalPages) {
            detailCostingCurrentPage = totalPages;
        }
        if (detailCostingCurrentPage < 1) {
            detailCostingCurrentPage = 1;
        }

        const startIndex = (detailCostingCurrentPage - 1) * detailCostingPageSize;
        const endIndex = startIndex + detailCostingPageSize;

        dataRows.forEach(function (row) {
            row.style.display = 'none';
        });

        matchedRows.forEach(function (row, index) {
            row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
        });

        if (pageInfo) {
            pageInfo.textContent = totalMatched > 0
                ? 'Halaman ' + detailCostingCurrentPage + ' dari ' + totalPages + ' (' + totalMatched + ' baris)'
                : 'Halaman 0 dari 0 (0 baris)';
        }

        if (prevButton) {
            prevButton.disabled = detailCostingCurrentPage <= 1 || totalMatched === 0;
            prevButton.style.opacity = prevButton.disabled ? '0.5' : '1';
            prevButton.style.cursor = prevButton.disabled ? 'not-allowed' : 'pointer';
        }

        if (nextButton) {
            nextButton.disabled = detailCostingCurrentPage >= totalPages || totalMatched === 0;
            nextButton.style.opacity = nextButton.disabled ? '0.5' : '1';
            nextButton.style.cursor = nextButton.disabled ? 'not-allowed' : 'pointer';
        }

        if (paginationContainer) {
            paginationContainer.style.display = dataRows.length > 0 ? 'flex' : 'none';
        }

        if (clearButton) {
            clearButton.style.display = filter !== '' ? 'inline-flex' : 'none';
        }

        if (noResultsRow) {
            noResultsRow.style.display = totalMatched === 0 && dataRows.length > 0 ? '' : 'none';
        }
    }

    function changeDetailCostingPage(step) {
        detailCostingCurrentPage += step;
        filterDetailCostingTable(false);
    }

    function saveStatusProject(selectEl) {
        if (!selectEl) {
            return;
        }

        const revisionId = selectEl.dataset.revisionId;
        const previousStatus = selectEl.dataset.lastSavedStatus || 'A00';
        const status = (selectEl.value || '').trim();

        if (!revisionId) {
            alert('Revision ID tidak ditemukan.');
            selectEl.value = previousStatus;
            updateStatusProjectDropdownColor(selectEl);
            return;
        }

        updateStatusProjectDropdownColor(selectEl);

        /*
         * Untuk A04/A05 gunakan normal form submit, bukan fetch.
         * Alasannya: controller perlu mengirim session flash:
         * - open_document_revision_id
         * - open_document_target_status
         * agar halaman Project Document bisa auto-open modal.
         */
        if (status === 'A04' || status === 'A05') {
            showStatusProjectLoading('Membuka halaman Project Document...');

            const form = document.getElementById('statusProjectUpdateForm');
            const statusInput = document.getElementById('statusProjectUpdateStatus');

            if (!form || !statusInput) {
                hideStatusProjectLoading();
                selectEl.value = previousStatus;
                updateStatusProjectDropdownColor(selectEl);
                alert('Form update status project tidak ditemukan.');
                return;
            }

            statusInput.value = status;
            form.action = '/costing/status-project/' + encodeURIComponent(revisionId);
            form.submit();
            return;
        }

        showStatusProjectLoading('Menyimpan status project...');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value || '';

        selectEl.disabled = true;

        fetch('/costing/status-project/' + encodeURIComponent(revisionId), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status: status }),
        })
        .then(async function(res) {
            const data = await res.json().catch(function () {
                return {};
            });

            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Gagal menyimpan status project.');
            }

            return data;
        })
        .then(function() {
            selectEl.dataset.lastSavedStatus = status;
            selectEl.disabled = false;
            hideStatusProjectLoading();

            if (status === 'A00') {
                window.location.reload();
            }
        })
        .catch(function(error) {
            selectEl.disabled = false;
            selectEl.value = previousStatus;
            updateStatusProjectDropdownColor(selectEl);
            hideStatusProjectLoading();

            alert(error.message || 'Gagal menyimpan status project. Silakan coba lagi.');
        });
    }

    function showStatusProjectLoading(message) {
        let overlay = document.getElementById('statusProjectLoadingOverlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'statusProjectLoadingOverlay';
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.zIndex = '100000';
            overlay.style.background = 'rgba(15, 23, 42, 0.42)';
            overlay.style.backdropFilter = 'blur(2px)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.innerHTML = `
                <div style="background:#fff; border-radius:16px; padding:1.4rem 1.6rem; min-width:220px; box-shadow:0 18px 45px rgba(15,23,42,.20); text-align:center;">
                    <div style="width:42px;height:42px;border:3px solid #dbeafe;border-top-color:#2563eb;border-radius:999px;margin:0 auto .85rem;animation:statusProjectSpin .75s linear infinite;"></div>
                    <div id="statusProjectLoadingText" style="font-size:.86rem;font-weight:800;color:#334155;">Memuat halaman...</div>
                </div>
            `;

            const style = document.createElement('style');
            style.id = 'statusProjectLoadingStyle';
            style.textContent = '@keyframes statusProjectSpin{to{transform:rotate(360deg)}}';
            document.head.appendChild(style);
            document.body.appendChild(overlay);
        }

        const text = document.getElementById('statusProjectLoadingText');
        if (text) {
            text.textContent = message || 'Memuat halaman...';
        }

        overlay.style.display = 'flex';
    }

    function hideStatusProjectLoading() {
        const overlay = document.getElementById('statusProjectLoadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    function updateStatusProjectDropdownColor(selectEl) {
        if (!selectEl) {
            return;
        }

        const statusColors = {
            A00: '#2563eb',
            A04: '#dc2626',
            A05: '#16a34a',
        };

        const selectedValue = (selectEl.value || '').trim();
        const bgColor = statusColors[selectedValue] || '#64748b';
        selectEl.dataset.statusProjectColor = bgColor;
        selectEl.style.backgroundColor = bgColor;
        selectEl.style.borderColor = bgColor;
        selectEl.style.color = '#ffffff';

        // Ensure option colors stay applied
        selectEl.querySelectorAll('option').forEach(function(opt) {
            const optColor = statusColors[opt.value];
            if (optColor) {
                opt.style.backgroundColor = optColor;
                opt.style.color = '#fff';
                opt.style.fontWeight = '700';
            }
        });
    }

    function initializeStatusProjectDropdownColors() {
        const statusDropdowns = document.querySelectorAll('.status-project-select');
        statusDropdowns.forEach(function (dropdown) {
            updateStatusProjectDropdownColor(dropdown);
        });
    }

    function clearDetailCostingSearch() {
        const searchInput = document.getElementById('detailCostingSearch');
        if (!searchInput) {
            return;
        }

        searchInput.value = '';
        searchInput.focus();
        filterDetailCostingTable();
    }

    function toggleDetailCostCell(button) {
        const container = button.closest('.cost-mask-cell');
        if (!container) {
            return;
        }

        const masked = container.querySelector('.cost-masked');
        const value = container.querySelector('.cost-value');
        if (!masked || !value) {
            return;
        }

        const isHidden = value.style.display === 'none';
        if (isHidden) {
            value.style.display = 'inline';
            masked.style.display = 'none';
            button.style.color = 'var(--primary)';
            button.setAttribute('title', 'Sembunyikan nilai');
            button.setAttribute('aria-label', 'Sembunyikan nilai');
        } else {
            value.style.display = 'none';
            masked.style.display = 'inline';
            button.style.color = 'var(--slate-500)';
            button.setAttribute('title', 'Lihat nilai');
            button.setAttribute('aria-label', 'Lihat nilai');
        }
    }
    
    // Number formatting helper
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(number);
    }

    document.addEventListener('DOMContentLoaded', function () {
        filterDetailCostingTable(true);
        initializeStatusProjectDropdownColors();
    });
</script>
