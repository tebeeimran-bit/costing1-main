@extends('layouts.app')

@section('title', 'Database Part')
@section('page-title', 'Database Part (Material)')

@section('breadcrumb')
    <a href="{{ route('database.products', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Parts</span>
@endsection

@section('content')
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

    <style>
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--blue-700) 0%, var(--blue-800) 100%);
            transform: translateY(-2px);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-edit {
            background: var(--blue-100);
            color: var(--blue-600);
        }

        .btn-edit:hover {
            background: var(--blue-200);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        .aksi-col,
        .aksi-cell {
            width: 110px;
            min-width: 110px;
            text-align: center;
        }

        .material-table-container {
            overflow-x: hidden;
            width: 100%;
        }

        .material-table-container .data-table {
            table-layout: fixed;
            width: 100%;
        }

        .material-table-container .data-table th,
        .material-table-container .data-table td {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .aksi-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            flex-wrap: nowrap;
            width: 100%;
        }

        .aksi-actions form {
            margin: 0;
        }

        .material-modal {
            position: fixed;
            inset: 0;
            z-index: 1100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .material-modal.is-hidden {
            display: none;
        }

        .material-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
        }

        .material-modal-dialog {
            position: relative;
            width: min(980px, 100%);
            max-height: calc(100vh - 2rem);
            overflow: auto;
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 0.75rem;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.28);
            padding: 1rem 1rem 1.25rem;
        }

        .material-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .material-modal-title {
            margin: 0;
            font-size: 1rem;
            color: var(--slate-800);
            font-weight: 700;
        }

        .material-modal-close {
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            border: 1px solid var(--slate-300);
            background: #fff;
            color: var(--slate-700);
            cursor: pointer;
            font-size: 1.25rem;
            line-height: 1;
        }

        .material-errors {
            margin-bottom: 0.75rem;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        .material-errors ul {
            margin: 0;
            padding-left: 1rem;
        }

        .material-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .material-span-2 {
            grid-column: span 2;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .form-group label {
            font-size: 0.83rem;
            color: var(--slate-700);
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            padding: 0.6rem 0.75rem;
            font-size: 0.85rem;
            color: var(--slate-800);
            background: #fff;
        }

        .material-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            margin-top: 0.9rem;
            padding-top: 0.9rem;
            border-top: 1px solid var(--slate-200);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--slate-300);
            background: #fff;
            color: var(--slate-700);
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .parts-pagination {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        .parts-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 0.55rem;
            border: 1px solid var(--slate-300);
            border-radius: 0.45rem;
            background: #fff;
            color: var(--slate-700);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .parts-page-link.is-active {
            background: var(--blue-600);
            border-color: var(--blue-600);
            color: #fff;
        }

        .parts-page-dots {
            color: var(--slate-500);
            font-size: 0.8rem;
            padding: 0 0.15rem;
        }

        @media (max-width: 768px) {
            .parts-search-input {
                min-width: 0 !important;
                width: 100%;
            }

            .material-table-container .data-table th,
            .material-table-container .data-table td {
                padding: 0.45rem 0.4rem;
                font-size: 0.73rem;
                line-height: 1.2;
            }

            .btn-action {
                width: 30px;
                height: 30px;
            }

            .aksi-col,
            .aksi-cell {
                width: 85px;
                min-width: 85px;
            }

            .material-form-grid {
                grid-template-columns: 1fr;
            }

            .material-span-2 {
                grid-column: span 1;
            }
        }
    </style>

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
@endsection