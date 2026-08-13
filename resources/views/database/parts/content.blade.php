    @if(session('success'))
        <div
            style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div
            style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fde68a;">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div
            style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->importParts->any())
        <div
            style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            <ul style="margin: 0; padding-left: 1rem;">
                @foreach($errors->importParts->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center;">
        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
        <form method="GET" action="{{ route('database.parts', absolute: false) }}" style="display: inline-flex; align-items: center; gap: 0.45rem;">
            <input type="hidden" name="per_page" value="{{ (int) request('per_page', 100) }}">
                <input
                type="text"
                name="q"
                value="{{ request('q', '') }}"
                placeholder="Cari material code / description..."
                    class="form-input parts-search-input"
                style="min-width: 280px;">
            <button type="submit" class="btn-secondary">Cari</button>
            @if(request()->filled('q'))
                <a href="{{ route('database.parts', ['per_page' => (int) request('per_page', 100)], false) }}" class="btn-secondary">Reset</a>
            @endif
        </form>

        <form method="GET" action="{{ route('database.parts', absolute: false) }}" style="display: inline-flex; align-items: center; gap: 0.45rem;">
            @if(request()->filled('q'))
                <input type="hidden" name="q" value="{{ request('q') }}">
            @endif
            <label for="perPageSelect" style="font-size: 0.82rem; color: var(--slate-600);">Baris per halaman</label>
            <select id="perPageSelect" name="per_page" class="form-input" style="width: auto; min-width: 90px;" onchange="this.form.submit()">
                @php
                    $selectedPerPage = (int) request('per_page', 100);
                @endphp
                <option value="50" {{ $selectedPerPage === 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $selectedPerPage === 100 ? 'selected' : '' }}>100</option>
                <option value="200" {{ $selectedPerPage === 200 ? 'selected' : '' }}>200</option>
                <option value="500" {{ $selectedPerPage === 500 ? 'selected' : '' }}>500</option>
            </select>
        </form>
        </div>

        <div style="display: inline-flex; justify-content: flex-end; gap: 0.6rem; flex-wrap: wrap;">
        <a href="{{ route('database.parts.template', absolute: false) }}" class="btn-secondary">
            Download Template Excel
        </a>
        <button type="button" class="btn-secondary" id="openImportMaterialBtn">
            Update via Excel
        </button>
        <button type="button" class="btn-secondary" id="bulkDeleteBtn">
            Hapus Terpilih
        </button>
        <button type="button" class="btn-secondary" id="deleteAllBtn" style="color: #991b1b; border-color: #fecaca;">
            Hapus Semua Data
        </button>
        <button type="button" class="btn-primary" id="openCreateMaterialBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Tambah Material
        </button>
        </div>
    </div>

    <div id="importMaterialModal" class="material-modal {{ $errors->importParts->any() ? '' : 'is-hidden' }}" aria-hidden="{{ $errors->importParts->any() ? 'false' : 'true' }}">
        <div class="material-modal-backdrop" data-close-import-modal></div>
        <div class="material-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="importMaterialModalTitle" style="width: min(560px, 100%);">
            <div class="material-modal-head">
                <h3 id="importMaterialModalTitle" class="material-modal-title">Update Database Part via Excel</h3>
                <button type="button" class="material-modal-close" data-close-import-modal>&times;</button>
            </div>

            <form action="{{ route('database.parts.import', absolute: false) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 0.7rem;">
                    <div style="font-size: 0.85rem; color: var(--slate-600);">
                        Gunakan file .xlsx sesuai template. Baris dengan <strong>material_code</strong> sama akan diupdate, sisanya ditambahkan sebagai material baru.
                    </div>
                    <input type="file" name="import_file" accept=".xlsx" required class="form-input" style="padding: 0.6rem;">
                </div>
                <div class="material-modal-actions">
                    <button type="button" class="btn-secondary" data-close-import-modal>Batal</button>
                    <button type="submit" class="btn-primary">Import Excel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="material-table-container" data-total-count="{{ $materials->total() }}">
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align: middle; width: 36px; text-align: center;">
                        <input type="checkbox" id="selectAllMaterials">
                    </th>
                    <th rowspan="2" style="vertical-align: middle;">No.</th>
                    <th rowspan="2" style="vertical-align: middle;">Plant</th>
                    <th rowspan="2" style="vertical-align: middle;">Material (ID Code)</th>
                    <th rowspan="2" style="vertical-align: middle;">Material Description</th>
                    <th rowspan="2" style="vertical-align: middle;">Material Type</th>
                    <th rowspan="2" style="vertical-align: middle;">Material Group</th>
                    <th rowspan="2" style="vertical-align: middle;">Base UoM</th>
                    <th colspan="9" style="text-align: center;">Price
                    </th>
                    <th rowspan="2" class="aksi-col" style="vertical-align: middle;">Aksi</th>
                </tr>
                <tr>
                    <th>Price</th>
                    <th>Purchase Unit</th>
                    <th>Currency</th>
                    <th>MOQ</th>
                    <th>C/N</th>
                    <th>Maker</th>
                    <th>Add Cost (%)</th>
                    <th>Price Update</th>
                    <th>Price Before</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $index => $material)
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" class="row-material-checkbox" value="{{ $material->id }}">
                        </td>
                        <td>{{ ($materials->firstItem() ?? 1) + $index }}</td>
                        <td>{{ $material->plant ?? '-' }}</td>
                        <td>{{ $material->material_code ?? '-' }}</td>
                        <td>{{ $material->material_description ?? '-' }}</td>
                        <td>{{ $material->material_type ?? '-' }}</td>
                        <td>{{ $material->material_group ?? '-' }}</td>
                        <td>{{ $material->base_uom ?? '-' }}</td>
                        <td>{{ $material->price ? rtrim(rtrim(number_format($material->price, 6, ',', '.'), '0'), ',') : '0' }}</td>
                        <td>{{ $material->purchase_unit ?? '-' }}</td>
                        <td>{{ $material->currency ?? '-' }}</td>
                        <td>{{ $material->moq ? number_format($material->moq, 0, ',', '.') : '-' }}</td>
                        <td>{{ $material->cn ?? '-' }}</td>
                        <td>{{ $material->maker ?? '-' }}</td>
                        <td>{{ $material->add_cost_import_tax ? number_format($material->add_cost_import_tax, 2) . '%' : '-' }}
                        </td>
                        <td>{{ $material->price_update ? $material->price_update->format('d M Y') : '-' }}</td>
                        <td>{{ $material->price_before ? rtrim(rtrim(number_format($material->price_before, 6, ',', '.'), '0'), ',') : '-' }}</td>
                        <td class="aksi-cell" style="white-space: nowrap;">
                            <div class="aksi-actions">
                                <button type="button" class="btn-action btn-edit js-open-edit-material" title="Edit"
                                    data-id="{{ $material->id }}"
                                    data-plant="{{ $material->plant ?? '' }}"
                                    data-material_code="{{ $material->material_code ?? '' }}"
                                    data-material_description="{{ $material->material_description ?? '' }}"
                                    data-material_type="{{ $material->material_type ?? '' }}"
                                    data-material_group="{{ $material->material_group ?? '' }}"
                                    data-base_uom="{{ $material->base_uom ?? 'PCS' }}"
                                    data-price="{{ (string) ($material->price ?? 0) }}"
                                    data-purchase_unit="{{ $material->purchase_unit ?? '' }}"
                                    data-currency="{{ $material->currency ?? 'IDR' }}"
                                    data-moq="{{ (string) ($material->moq ?? '') }}"
                                    data-cn="{{ $material->cn ?? '' }}"
                                    data-maker="{{ $material->maker ?? '' }}"
                                    data-add_cost_import_tax="{{ (string) ($material->add_cost_import_tax ?? '') }}"
                                    data-price_update="{{ $material->price_update ? $material->price_update->format('Y-m-d') : '' }}"
                                    data-price_before="{{ (string) ($material->price_before ?? '') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        style="width: 16px; height: 16px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <form action="{{ route('database.parts.destroy', ['id' => $material->id], false) }}" method="POST"
                                    class="js-delete-material-form" data-confirm-message="Apakah Anda yakin ingin menghapus material ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action btn-delete js-delete-material-btn" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            style="width: 16px; height: 16px;">
                                            <polyline points="3 6 5 6 21 6" />
                                            <path
                                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="18" style="text-align: center;">Tidak ada material ditemukan</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form id="bulkDeleteForm" action="{{ route('database.parts.destroy-bulk', absolute: false) }}" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
        <div id="bulkDeleteIdsContainer"></div>
    </form>

    <div id="bulkDeleteConfirmModal" class="material-modal is-hidden" aria-hidden="true">
        <div class="material-modal-backdrop" data-close-bulk-delete-modal></div>
        <div class="material-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="bulkDeleteConfirmTitle" style="width: min(360px, 100%);">
            <div class="material-modal-head">
                <h3 id="bulkDeleteConfirmTitle" class="material-modal-title">Hapus Material</h3>
                <button type="button" class="material-modal-close" data-close-bulk-delete-modal>&times;</button>
            </div>
            <div style="padding: 1rem; color: var(--slate-700);">
                <p id="bulkDeleteMessage" style="margin: 0; font-size: 0.95rem; line-height: 1.5;"></p>
            </div>
            <div class="material-modal-actions">
                <button type="button" class="btn-secondary" data-close-bulk-delete-modal>Batal</button>
                <button type="button" class="btn-primary" id="bulkDeleteConfirmBtn">Hapus</button>
            </div>
        </div>
    </div>

    <div id="deleteAllConfirmModal" class="material-modal is-hidden" aria-hidden="true">
        <div class="material-modal-backdrop" data-close-delete-all-modal></div>
        <div class="material-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteAllConfirmTitle" style="width: min(380px, 100%);">
            <div class="material-modal-head">
                <h3 id="deleteAllConfirmTitle" class="material-modal-title" style="color: #991b1b;">Hapus Semua Data</h3>
                <button type="button" class="material-modal-close" data-close-delete-all-modal>&times;</button>
            </div>
            <div style="padding: 1rem; color: var(--slate-700);">
                <p style="margin: 0 0 0.5rem 0; font-size: 0.95rem; line-height: 1.5;">
                    <strong style="color: #991b1b;">⚠️ Perhatian!</strong>
                </p>
                <p id="deleteAllMessage" style="margin: 0; font-size: 0.90rem; line-height: 1.5; color: #7f1d1d;"></p>
            </div>
            <div class="material-modal-actions">
                <button type="button" class="btn-secondary" data-close-delete-all-modal>Batal</button>
                <button type="button" class="btn-primary" id="deleteAllConfirmBtn" style="background-color: #991b1b; border-color: #991b1b;">Hapus Semua</button>
            </div>
        </div>
    </div>

    @if($materials->lastPage() > 1)
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.9rem; gap: 0.6rem; flex-wrap: wrap;">
            <div style="font-size: 0.82rem; color: var(--slate-600);">
                Menampilkan {{ $materials->firstItem() ?? 0 }} - {{ $materials->lastItem() ?? 0 }} dari {{ $materials->total() }} data
            </div>
            <div class="parts-pagination">
                @php
                    $currentPage = $materials->currentPage();
                    $lastPage = $materials->lastPage();
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($lastPage, $currentPage + 2);
                    $paginationQuery = request()->query();
                    $buildPageUrl = function (int $page) use ($paginationQuery) {
                        $query = array_merge($paginationQuery, ['page' => $page]);
                        return '/' . ltrim(request()->path(), '/') . '?' . http_build_query($query);
                    };
                @endphp

                @if($currentPage > 1)
                    <a class="parts-page-link" href="{{ $buildPageUrl($currentPage - 1) }}">&laquo; Prev</a>
                @endif

                @if($startPage > 1)
                    <a class="parts-page-link" href="{{ $buildPageUrl(1) }}">1</a>
                    @if($startPage > 2)
                        <span class="parts-page-dots">...</span>
                    @endif
                @endif

                @for($page = $startPage; $page <= $endPage; $page++)
                    @if($page === $currentPage)
                        <span class="parts-page-link is-active">{{ $page }}</span>
                    @else
                        <a class="parts-page-link" href="{{ $buildPageUrl($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if($endPage < $lastPage)
                    @if($endPage < $lastPage - 1)
                        <span class="parts-page-dots">...</span>
                    @endif
                    <a class="parts-page-link" href="{{ $buildPageUrl($lastPage) }}">{{ $lastPage }}</a>
                @endif

                @if($currentPage < $lastPage)
                    <a class="parts-page-link" href="{{ $buildPageUrl($currentPage + 1) }}">Next &raquo;</a>
                @endif
            </div>
        </div>
    @endif

    <div id="materialModal" class="material-modal {{ $errors->any() ? '' : 'is-hidden' }}" aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">
        <div class="material-modal-backdrop" data-close-material-modal></div>
        <div class="material-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="materialModalTitle">
            <div class="material-modal-head">
                <h3 id="materialModalTitle" class="material-modal-title">{{ old('_method') === 'PUT' ? 'Edit Material' : 'Tambah Material Baru' }}</h3>
                <button type="button" class="material-modal-close" data-close-material-modal>&times;</button>
            </div>

            @if($errors->any())
                <div class="material-errors">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="materialModalForm" action="{{ old('_method') === 'PUT' ? route('database.parts.update', old('material_id', 0), false) : route('database.parts.store', absolute: false) }}" method="POST">
                @csrf
                <input type="hidden" name="material_id" id="materialFormMaterialId" value="{{ old('material_id', '') }}">
                <input type="hidden" name="_method" id="materialFormMethod" value="{{ old('_method') === 'PUT' ? 'PUT' : '' }}">

                <div class="material-form-grid">
                    <div class="form-group">
                        <label for="material_form_plant">Plant</label>
                        <input type="text" id="material_form_plant" name="plant" value="{{ old('plant', '') }}" placeholder="Masukkan Plant">
                    </div>
                    <div class="form-group">
                        <label for="material_form_code">Material Code <span style="color: #dc2626;">*</span></label>
                        <input type="text" id="material_form_code" name="material_code" value="{{ old('material_code', '') }}" placeholder="Contoh: MAT-001" required>
                    </div>
                    <div class="form-group material-span-2">
                        <label for="material_form_desc">Material Description</label>
                        <input type="text" id="material_form_desc" name="material_description" value="{{ old('material_description', '') }}" placeholder="Deskripsi material">
                    </div>
                    <div class="form-group">
                        <label for="material_form_type">Material Type</label>
                        <input type="text" id="material_form_type" name="material_type" value="{{ old('material_type', '') }}" placeholder="Tipe material">
                    </div>
                    <div class="form-group">
                        <label for="material_form_group">Material Group</label>
                        <input type="text" id="material_form_group" name="material_group" value="{{ old('material_group', '') }}" placeholder="Grup material">
                    </div>
                    <div class="form-group">
                        <label for="material_form_uom">Base Unit of Measure <span style="color: #dc2626;">*</span></label>
                        <select id="material_form_uom" name="base_uom" required>
                            @php
                                $oldUom = old('base_uom', 'PCS');
                            @endphp
                            <option value="PCS" {{ $oldUom === 'PCS' ? 'selected' : '' }}>PCS</option>
                            <option value="KG" {{ $oldUom === 'KG' ? 'selected' : '' }}>KG</option>
                            <option value="MM" {{ $oldUom === 'MM' ? 'selected' : '' }}>MM</option>
                            <option value="M" {{ $oldUom === 'M' ? 'selected' : '' }}>M</option>
                            <option value="L" {{ $oldUom === 'L' ? 'selected' : '' }}>L</option>
                            <option value="SET" {{ $oldUom === 'SET' ? 'selected' : '' }}>SET</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="material_form_price">Price</label>
                        <input type="number" id="material_form_price" name="price" value="{{ old('price', 0) }}" placeholder="0" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="material_form_purchase_unit">Purchase Unit</label>
                        <input type="text" id="material_form_purchase_unit" name="purchase_unit" value="{{ old('purchase_unit', '') }}" placeholder="Unit pembelian">
                    </div>
                    <div class="form-group">
                        <label for="material_form_currency">Currency <span style="color: #dc2626;">*</span></label>
                        <select id="material_form_currency" name="currency" required>
                            @php
                                $oldCurrency = old('currency', 'IDR');
                            @endphp
                            <option value="IDR" {{ $oldCurrency === 'IDR' ? 'selected' : '' }}>IDR</option>
                            <option value="USD" {{ $oldCurrency === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="JPY" {{ $oldCurrency === 'JPY' ? 'selected' : '' }}>JPY</option>
                            <option value="EUR" {{ $oldCurrency === 'EUR' ? 'selected' : '' }}>EUR</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="material_form_moq">MOQ (Minimum Order Qty)</label>
                        <input type="number" id="material_form_moq" name="moq" value="{{ old('moq', '') }}" placeholder="0" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="material_form_cn">C/N</label>
                        <input type="text" id="material_form_cn" name="cn" value="{{ old('cn', '') }}" placeholder="C/N">
                    </div>
                    <div class="form-group">
                        <label for="material_form_maker">Maker / Original Source</label>
                        <input type="text" id="material_form_maker" name="maker" value="{{ old('maker', '') }}" placeholder="Pembuat/sumber">
                    </div>
                    <div class="form-group">
                        <label for="material_form_tax">Add Cost / Import Tax (%)</label>
                        <input type="number" id="material_form_tax" name="add_cost_import_tax" value="{{ old('add_cost_import_tax', '') }}" placeholder="0" step="0.01" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="material_form_price_update">Price Update Date</label>
                        <input type="date" id="material_form_price_update" name="price_update" value="{{ old('price_update', '') }}">
                    </div>
                    <div class="form-group">
                        <label for="material_form_price_before">Price Before</label>
                        <input type="number" id="material_form_price_before" name="price_before" value="{{ old('price_before', '') }}" placeholder="0" step="0.01" min="0">
                    </div>
                </div>

                <div class="material-modal-actions">
                    <button type="button" class="btn-secondary" data-close-material-modal>Batal</button>
                    <button type="submit" class="btn-primary" id="materialModalSubmitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
