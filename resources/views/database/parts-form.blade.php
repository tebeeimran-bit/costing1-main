@extends('layouts.app')

@section('title', $material ? 'Edit Material' : 'Tambah Material')
@section('page-title', $material ? 'Edit Material' : 'Tambah Material Baru')

@section('breadcrumb')
    <a href="{{ route('database.parts', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <a href="{{ route('database.parts', absolute: false) }}">Parts</a>
    <span class="breadcrumb-separator">/</span>
    <span>{{ $material ? 'Edit' : 'Tambah' }}</span>
@endsection

@section('content')
    <div class="form-container">
        <form action="{{ $material ? route('database.parts.update', $material->id, false) : route('database.parts.store', absolute: false) }}"
            method="POST">
            @csrf
            @if($material)
                @method('PUT')
            @endif

            @if($errors->any())
                <div
                    style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
                    <ul style="margin: 0; padding-left: 1rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Basic Information -->
            <div class="form-section">
                <h3 class="form-section-title">Informasi Dasar</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="plant">Plant</label>
                        <input type="text" id="plant" name="plant" value="{{ old('plant', $material->plant ?? '') }}"
                            placeholder="Masukkan Plant">
                    </div>
                    <div class="form-group">
                        <label for="material_code">Material Code <span style="color: #dc2626;">*</span></label>
                        <input type="text" id="material_code" name="material_code"
                            value="{{ old('material_code', $material->material_code ?? '') }}" placeholder="Contoh: MAT-001"
                            required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="material_description">Material Description</label>
                        <input type="text" id="material_description" name="material_description"
                            value="{{ old('material_description', $material->material_description ?? '') }}"
                            placeholder="Deskripsi material">
                    </div>
                    <div class="form-group">
                        <label for="material_type">Material Type</label>
                        <input type="text" id="material_type" name="material_type"
                            value="{{ old('material_type', $material->material_type ?? '') }}" placeholder="Tipe material">
                    </div>
                    <div class="form-group">
                        <label for="material_group">Material Group</label>
                        <input type="text" id="material_group" name="material_group"
                            value="{{ old('material_group', $material->material_group ?? '') }}"
                            placeholder="Grup material">
                    </div>
                    <div class="form-group">
                        <label for="base_uom">Base Unit of Measure <span style="color: #dc2626;">*</span></label>
                        <select id="base_uom" name="base_uom" required>
                            <option value="PCS" {{ old('base_uom', $material->base_uom ?? '') == 'PCS' ? 'selected' : '' }}>
                                PCS</option>
                            <option value="KG" {{ old('base_uom', $material->base_uom ?? '') == 'KG' ? 'selected' : '' }}>KG
                            </option>
                            <option value="MM" {{ old('base_uom', $material->base_uom ?? '') == 'MM' ? 'selected' : '' }}>MM
                            </option>
                            <option value="M" {{ old('base_uom', $material->base_uom ?? '') == 'M' ? 'selected' : '' }}>M
                            </option>
                            <option value="L" {{ old('base_uom', $material->base_uom ?? '') == 'L' ? 'selected' : '' }}>L
                            </option>
                            <option value="SET" {{ old('base_uom', $material->base_uom ?? '') == 'SET' ? 'selected' : '' }}>
                                SET</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Price Information -->
            <div class="form-section">
                <h3 class="form-section-title">Informasi Harga</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" id="price" name="price" value="{{ old('price', $material->price ?? 0) }}"
                            placeholder="0" step="0.00001" min="0">
                    </div>
                    <div class="form-group">
                        <label for="purchase_unit">Purchase Unit</label>
                        <input type="text" id="purchase_unit" name="purchase_unit"
                            value="{{ old('purchase_unit', $material->purchase_unit ?? '') }}" placeholder="Unit pembelian">
                    </div>
                    <div class="form-group">
                        <label for="currency">Currency <span style="color: #dc2626;">*</span></label>
                        <select id="currency" name="currency" required>
                            <option value="IDR" {{ old('currency', $material->currency ?? '') == 'IDR' ? 'selected' : '' }}>
                                IDR</option>
                            <option value="USD" {{ old('currency', $material->currency ?? '') == 'USD' ? 'selected' : '' }}>
                                USD</option>
                            <option value="JPY" {{ old('currency', $material->currency ?? '') == 'JPY' ? 'selected' : '' }}>
                                JPY</option>
                            <option value="EUR" {{ old('currency', $material->currency ?? '') == 'EUR' ? 'selected' : '' }}>
                                EUR</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="moq">MOQ (Minimum Order Qty)</label>
                        <input type="number" id="moq" name="moq" value="{{ old('moq', $material->moq ?? '') }}"
                            placeholder="0" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="cn">C/N</label>
                        <input type="text" id="cn" name="cn" value="{{ old('cn', $material->cn ?? '') }}" placeholder="C/N">
                    </div>
                    <div class="form-group">
                        <label for="maker">Maker / Original Source</label>
                        <input type="text" id="maker" name="maker" value="{{ old('maker', $material->maker ?? '') }}"
                            placeholder="Pembuat/sumber">
                    </div>
                    <div class="form-group">
                        <label for="add_cost_import_tax">Add Cost / Import Tax (%)</label>
                        <input type="number" id="add_cost_import_tax" name="add_cost_import_tax"
                            value="{{ old('add_cost_import_tax', $material->add_cost_import_tax ?? '') }}" placeholder="0"
                            step="0.01" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="price_update">Price Update Date</label>
                        <input type="date" id="price_update" name="price_update"
                            value="{{ old('price_update', $material->price_update ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label for="price_before">Price Before</label>
                        <input type="number" id="price_before" name="price_before"
                            value="{{ old('price_before', $material->price_before ?? '') }}" placeholder="0" step="0.00001"
                            min="0">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
                <a href="{{ route('database.parts', absolute: false) }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">
                    {{ $material ? 'Simpan Perubahan' : 'Tambah Material' }}
                </button>
            </div>
        </form>
    </div>

    <style>
        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 0.75rem;
            border: 1px solid var(--slate-200);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--slate-800);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--blue-600);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--slate-700);
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: var(--slate-800);
            background: white;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--blue-500);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder {
            color: var(--slate-400);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--slate-200);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
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

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            color: var(--slate-700);
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: var(--slate-50);
            border-color: var(--slate-400);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group[style*="span 2"] {
                grid-column: span 1;
            }
        }
    </style>
@endsection