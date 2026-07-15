@extends('layouts.app')

@section('title', 'New Project')
@section('page-title', 'New Project')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="{{ route('project', absolute: false) }}">Project</a>
    <span class="breadcrumb-separator">/</span>
    <span>New Project</span>
@endsection

@section('content')
    <style>
        .new-project-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .receipt-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .file-input-wrap {
            position: relative;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            background: #fff;
            min-height: 2.7rem;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .file-input-wrap input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .file-input-content {
            display: flex;
            align-items: center;
            width: 100%;
            min-width: 0;
            padding: 0.45rem 0.55rem;
            gap: 0.6rem;
        }

        .file-input-btn {
            flex: 0 0 auto;
            border: 1px solid var(--slate-300);
            border-radius: 0.4rem;
            background: var(--slate-50);
            color: var(--slate-700);
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.32rem 0.6rem;
            line-height: 1.2;
        }

        .file-input-name {
            min-width: 0;
            font-size: 0.82rem;
            color: var(--slate-600);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .field-help {
            display: block;
            margin-top: 0.4rem;
            font-size: 0.76rem;
            color: var(--slate-500);
        }

        .field-error {
            display: none;
            margin-top: 0.35rem;
            font-size: 0.76rem;
            color: #dc2626;
            font-weight: 600;
        }

        .field-error.is-visible {
            display: block;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 1000px) {
            .receipt-grid,
            .receipt-grid-2 {
                grid-template-columns: 1fr;
            }

            .form-actions {
                justify-content: stretch;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>

    <div class="new-project-page">
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-warning">
                <strong>Terdapat kesalahan:</strong>
                <ul style="margin-left: 1rem; margin-top: 0.5rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="page-head" style="margin-bottom: 1rem;">
                <h3 class="card-title" style="margin: 0;">Form New Project</h3>
                <a href="{{ route('project', absolute: false) }}" class="btn btn-secondary">Kembali ke Project</a>
            </div>

            <form id="receiptForm" action="{{ route('tracking-documents.store-receipt', absolute: false) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="receipt-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Business Categories <span style="color: var(--red-500);">*</span></label>
                        <select name="business_category_id" class="form-select" required>
                            <option value="">-- Pilih Business Categories --</option>
                            @foreach($businessCategories as $businessCategory)
                                <option value="{{ $businessCategory->id }}" {{ (string) old('business_category_id') === (string) $businessCategory->id ? 'selected' : '' }}>
                                    {{ $businessCategory->code }} - {{ $businessCategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Customer <span style="color: var(--red-500);">*</span></label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">-- Pilih Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                    {{ $customer->code }} - {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model <span style="color: var(--red-500);">*</span></label>
                        <input type="text" name="model" class="form-input" value="{{ old('model') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assy No. <span style="color: var(--red-500);">*</span></label>
                        <input type="text" name="assy_no" class="form-input" value="{{ old('assy_no') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assy Name <span style="color: var(--red-500);">*</span></label>
                        <input type="text" name="assy_name" class="form-input" value="{{ old('assy_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <div style="display: grid; grid-template-columns: 1.2fr 1fr 1.2fr; gap: 0.45rem;">
                            <input type="number" name="forecast" class="form-input" min="0" value="{{ old('forecast', 2000) }}" placeholder="2000">
                            <select name="forecast_uom" class="form-select">
                                <option value="PCE" {{ old('forecast_uom', 'PCE') === 'PCE' ? 'selected' : '' }}>PCE</option>
                                <option value="Set" {{ old('forecast_uom') === 'Set' ? 'selected' : '' }}>Set</option>
                            </select>
                            <select name="forecast_basis" class="form-select">
                                <option value="per_month" {{ old('forecast_basis', 'per_month') === 'per_month' ? 'selected' : '' }}>Per Bulan</option>
                                <option value="per_year" {{ old('forecast_basis') === 'per_year' ? 'selected' : '' }}>Per Tahun</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Product's Life</label>
                        <input type="number" name="project_period" class="form-input" min="0" value="{{ old('project_period', 2) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plant</label>
                        <select name="line" class="form-select">
                            <option value="">-- Pilih Plant --</option>
                            @foreach($plants as $plant)
                                <option value="{{ $plant->code }}" {{ old('line') === $plant->code ? 'selected' : '' }}>{{ $plant->code }} - {{ $plant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Periode</label>
                        <select name="period" class="form-select">
                            <option value="">-- Pilih Periode --</option>
                            @for($i = 0; $i < 12; $i++)
                                @php $date = now()->subMonths($i); @endphp
                                <option value="{{ $date->format('Y-m') }}" {{ old('period') === $date->format('Y-m') ? 'selected' : '' }}>
                                    {{ $date->format('M Y') }}
                                </option>
                            @endfor
                            @foreach($periods as $period)
                                @if(!collect(range(0, 11))->contains(fn ($idx) => now()->subMonths($idx)->format('Y-m') === $period))
                                    <option value="{{ $period }}" {{ old('period') === $period ? 'selected' : '' }}>{{ $period }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Diterima</label>
                        <input type="date" name="received_date" class="form-input"
                            value="{{ old('received_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC Engineering <span style="color: var(--red-500);">*</span></label>
                        <select name="pic_engineering" class="form-select" required>
                            <option value="">-- Pilih PIC Engineering --</option>
                            @foreach($picsEngineering as $pic)
                                <option value="{{ $pic->name }}" {{ old('pic_engineering') === $pic->name ? 'selected' : '' }}>{{ $pic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC Marketing <span style="color: var(--red-500);">*</span></label>
                        <select name="pic_marketing" class="form-select" required>
                            <option value="">-- Pilih PIC Marketing --</option>
                            @foreach($picsMarketing as $pic)
                                <option value="{{ $pic->name }}" {{ old('pic_marketing') === $pic->name ? 'selected' : '' }}>{{ $pic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">A00</label>
                        @php $a00Status = old('a00_status', 'belum_ada'); @endphp
                        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.35rem;">
                            <label style="display: inline-flex; gap: 0.4rem; align-items: center; font-size: 0.82rem; color: var(--slate-700);">
                                <input type="radio" name="a00_status" value="ada" {{ $a00Status === 'ada' ? 'checked' : '' }}>
                                Ada
                            </label>
                            <label style="display: inline-flex; gap: 0.4rem; align-items: center; font-size: 0.82rem; color: var(--slate-700);">
                                <input type="radio" name="a00_status" value="belum_ada" {{ $a00Status !== 'ada' ? 'checked' : '' }}>
                                Belum ada
                            </label>
                        </div>
                        <div id="a00ReceivedDateWrap" style="margin-top: 0.6rem; {{ $a00Status === 'ada' ? '' : 'display:none;' }}">
                            <label class="form-label">Tanggal Diterima A00</label>
                            <input type="date" name="a00_received_date" class="form-input" value="{{ old('a00_received_date') }}">

                            <label class="form-label" style="margin-top: 0.6rem;">Dokumen A00 (PDF)</label>
                            <div class="file-input-wrap">
                                <input type="file" name="a00_document_file" accept=".pdf"
                                    onchange="updateFileLabel(this, 'a00DocumentFileName')">
                                <div class="file-input-content" aria-hidden="true">
                                    <span class="file-input-btn">Pilih File</span>
                                    <span id="a00DocumentFileName" class="file-input-name">Belum ada file dipilih</span>
                                </div>
                            </div>
                            <small class="field-help">Format: .pdf, maksimal 10MB</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">A05</label>
                        @php $a05Status = old('a05_status', 'belum_ada'); @endphp
                        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.35rem;">
                            <label style="display: inline-flex; gap: 0.4rem; align-items: center; font-size: 0.82rem; color: var(--slate-700);">
                                <input type="radio" name="a05_status" value="ada" {{ $a05Status === 'ada' ? 'checked' : '' }}>
                                Ada
                            </label>
                            <label style="display: inline-flex; gap: 0.4rem; align-items: center; font-size: 0.82rem; color: var(--slate-700);">
                                <input type="radio" name="a05_status" value="belum_ada" {{ $a05Status !== 'ada' ? 'checked' : '' }}>
                                Belum ada
                            </label>
                        </div>
                        <div id="a05ReceivedDateWrap" style="margin-top: 0.6rem; {{ $a05Status === 'ada' ? '' : 'display:none;' }}">
                            <label class="form-label">Tanggal Diterima A05</label>
                            <input type="date" name="a05_received_date" class="form-input" value="{{ old('a05_received_date') }}">

                            <label class="form-label" style="margin-top: 0.6rem;">Dokumen A05 (PDF)</label>
                            <div class="file-input-wrap">
                                <input type="file" name="a05_document_file" accept=".pdf"
                                    onchange="updateFileLabel(this, 'a05DocumentFileName')">
                                <div class="file-input-content" aria-hidden="true">
                                    <span class="file-input-btn">Pilih File</span>
                                    <span id="a05DocumentFileName" class="file-input-name">Belum ada file dipilih</span>
                                </div>
                            </div>
                            <small class="field-help">Format: .pdf, maksimal 10MB</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">A04</label>
                        @php $a04Status = old('a04_status', 'belum_ada'); @endphp
                        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.35rem;">
                            <label style="display: inline-flex; gap: 0.4rem; align-items: center; font-size: 0.82rem; color: var(--slate-700);">
                                <input type="radio" name="a04_status" value="ada" {{ $a04Status === 'ada' ? 'checked' : '' }}>
                                Ada
                            </label>
                            <label style="display: inline-flex; gap: 0.4rem; align-items: center; font-size: 0.82rem; color: var(--slate-700);">
                                <input type="radio" name="a04_status" value="belum_ada" {{ $a04Status !== 'ada' ? 'checked' : '' }}>
                                Belum ada
                            </label>
                        </div>
                        <div id="a04ReceivedDateWrap" style="margin-top: 0.6rem; {{ $a04Status === 'ada' ? '' : 'display:none;' }}">
                            <label class="form-label">Tanggal Diterima A04</label>
                            <input type="date" name="a04_received_date" class="form-input" value="{{ old('a04_received_date') }}">

                            <label class="form-label" style="margin-top: 0.6rem;">Dokumen A04 (PDF)</label>
                            <div class="file-input-wrap">
                                <input type="file" name="a04_document_file" accept=".pdf"
                                    onchange="updateFileLabel(this, 'a04DocumentFileName')">
                                <div class="file-input-content" aria-hidden="true">
                                    <span class="file-input-btn">Pilih File</span>
                                    <span id="a04DocumentFileName" class="file-input-name">Belum ada file dipilih</span>
                                </div>
                            </div>
                            <small class="field-help">Format: .pdf, maksimal 10MB</small>
                        </div>
                    </div>
                </div>

                <div class="receipt-grid-2" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Partlist (Excel)</label>
                        <div class="file-input-wrap">
                            <input type="file" name="partlist_file" accept=".xls,.xlsx"
                                onchange="handleFileChange(this, 'partlistFileName', 'partlistFileError')">
                            <div class="file-input-content" aria-hidden="true">
                                <span class="file-input-btn">Pilih File</span>
                                <span id="partlistFileName" class="file-input-name">Belum ada file dipilih</span>
                            </div>
                        </div>
                        <small class="field-help">Format: .xls atau .xlsx, maksimal 10MB</small>
                        <small id="partlistFileError" class="field-error"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">UMH (Excel)</label>
                        <div class="file-input-wrap">
                            <input type="file" name="umh_file" accept=".xls,.xlsx"
                                onchange="handleFileChange(this, 'umhFileName', 'umhFileError')">
                            <div class="file-input-content" aria-hidden="true">
                                <span class="file-input-btn">Pilih File</span>
                                <span id="umhFileName" class="file-input-name">Belum ada file dipilih</span>
                            </div>
                        </div>
                        <small class="field-help">Format: .xls atau .xlsx, maksimal 10MB</small>
                        <small id="umhFileError" class="field-error"></small>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Catatan opsional">{{ old('notes') }}</textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Project</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const RECEIPT_ALLOWED_EXTENSIONS = ['xls', 'xlsx'];
        const RECEIPT_MAX_FILE_SIZE = 10 * 1024 * 1024;

        function updateFileLabel(input, labelId) {
            const label = document.getElementById(labelId);
            if (!label) {
                return;
            }

            if (input.files && input.files.length > 0) {
                label.textContent = input.files[0].name;
                return;
            }

            label.textContent = 'Belum ada file dipilih';
        }

        function setFieldError(errorId, message) {
            const errorEl = document.getElementById(errorId);
            if (!errorEl) {
                return;
            }

            if (!message) {
                errorEl.textContent = '';
                errorEl.classList.remove('is-visible');
                return;
            }

            errorEl.textContent = message;
            errorEl.classList.add('is-visible');
        }

        function validateReceiptFile(input) {
            if (!input || !input.files || input.files.length === 0) {
                return { valid: true, message: '' };
            }

            const file = input.files[0];
            const fileName = file.name || '';
            const extension = fileName.includes('.') ? fileName.split('.').pop().toLowerCase() : '';

            if (!RECEIPT_ALLOWED_EXTENSIONS.includes(extension)) {
                return { valid: false, message: 'Format file harus .xls atau .xlsx.' };
            }

            if (file.size > RECEIPT_MAX_FILE_SIZE) {
                return { valid: false, message: 'Ukuran file maksimal 10MB.' };
            }

            return { valid: true, message: '' };
        }

        function handleFileChange(input, labelId, errorId) {
            updateFileLabel(input, labelId);
            const result = validateReceiptFile(input);
            setFieldError(errorId, result.valid ? '' : result.message);
        }

        function toggleAStatusDate(prefix) {
            const selected = document.querySelector('input[name="' + prefix + '_status"]:checked');
            const wrapper = document.getElementById(prefix + 'ReceivedDateWrap');
            const dateInput = document.querySelector('input[name="' + prefix + '_received_date"]');
            const docInput = document.querySelector('input[name="' + prefix + '_document_file"]');

            if (!wrapper || !dateInput || !docInput) {
                return;
            }

            const isAda = selected && selected.value === 'ada';

            wrapper.style.display = isAda ? '' : 'none';
            dateInput.required = !!isAda;
            docInput.required = !!isAda;

            if (!isAda) {
                dateInput.value = '';
                docInput.value = '';
                updateFileLabel(docInput, prefix + 'DocumentFileName');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('receiptForm');
            if (!form) {
                return;
            }

            const a00Radios = document.querySelectorAll('input[name="a00_status"]');
            const a04Radios = document.querySelectorAll('input[name="a04_status"]');
            const a05Radios = document.querySelectorAll('input[name="a05_status"]');

            a00Radios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    toggleAStatusDate('a00');
                });
            });

            a05Radios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    toggleAStatusDate('a05');
                });
            });

            a04Radios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    toggleAStatusDate('a04');
                });
            });

            toggleAStatusDate('a00');
            toggleAStatusDate('a04');
            toggleAStatusDate('a05');

            form.addEventListener('submit', function (event) {
                const partlistInput = form.querySelector('input[name="partlist_file"]');
                const umhInput = form.querySelector('input[name="umh_file"]');

                const partlistValidation = validateReceiptFile(partlistInput);
                const umhValidation = validateReceiptFile(umhInput);

                setFieldError('partlistFileError', partlistValidation.valid ? '' : partlistValidation.message);
                setFieldError('umhFileError', umhValidation.valid ? '' : umhValidation.message);

                if (!partlistValidation.valid || !umhValidation.valid) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endsection
