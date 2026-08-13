        <!-- Section D: Material Breakdown Table -->
        <div class="card form-section" id="materialFormSection">
            {{-- Toolbar dipisahkan tanpa mengubah tabel maupun event JavaScript. --}}
            @include('form.partials.material-section-header')

            @php
                $importEditFileName = Str::startsWith($trackingRevision?->costing_edit_original_name ?? '', 'Import-Hasil-Edit-')
                    ? Str::after($trackingRevision->costing_edit_original_name, 'Import-Hasil-Edit-')
                    : $trackingRevision?->costing_edit_original_name;
            @endphp
            <div class="material-file-info" aria-label="Informasi file Excel Material">
                <span class="material-file-name" title="Nama file Excel yang terakhir diexport">
                    Export: <strong id="materialExportFileName">Belum diexport</strong>
                </span>
                @if($trackingRevision?->costing_edit_file_path)
                <span role="button" tabindex="0" class="material-file-name material-file-name-link" data-download-url="{{ route('marketing.costing-edit.download', $trackingRevision, absolute:false) }}" title="Download {{ $importEditFileName }}" onclick="openMaterialDownloadConfirm(this)" onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); openMaterialDownloadConfirm(this); }">
                    Import: <strong>{{ $importEditFileName }}</strong>
                </span>
                @else
                <span class="material-file-name" title="File hasil edit belum tersimpan pada submission ini">
                    Import: <strong>Belum tersedia</strong>
                </span>
                @endif
            </div>

            <div class="material-table-container">
                <table class="material-table" id="materialTable">
                    <thead>
                        <tr>
                            <th>
                                <span class="material-row-no-header">
                                    <input type="checkbox" id="materialSelectAllRows" title="Pilih semua baris"
                                        onchange="toggleAllMaterialRowCheckboxes(this.checked)">
                                    <span>No</span>
                                </span>
                            </th>
                            <th>Part No</th>
                            <th>ID Code</th>
                            <th>Part Name</th>
                            <th style="width: 7rem;">Qty Req</th>
                            <th>Unit</th>
                            <th>Pro Code</th>
                            <th>Amount 1</th>
                            <th>Unit Price (Basis)</th>
                            <th>Currency</th>
                            <th style="width: 7rem;">Qty MOQ</th>
                            <th>C/N</th>
                            <th>Supplier</th>
                            <th>Import Tax (%)</th>
                            <th>Multiply Factor</th>
                            <th>Amount 2</th>
                            <th>Currency 2</th>
                            <th>Unit Price 2</th>
                            <th>Total Price (IDR)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="materialTableBody">
                        @php
                            $oldMaterialRows = (!$costingData && $errors->any()) ? old('materials') : null;
                        @endphp

                        @if(is_array($oldMaterialRows) && count($oldMaterialRows) > 0)
                            @foreach($oldMaterialRows as $index => $row)
                            <tr data-row="{{ $index }}">
                                <td>
                                    <span class="material-row-no-cell">
                                        <input type="checkbox" class="material-row-select" title="Pilih baris">
                                        <span class="material-row-number">{{ $index + 1 }}</span>
                                    </span>
                                </td>
                                <td><input type="text" class="form-input part-no" name="materials[{{ $index }}][part_no]"
                                    value="{{ $row['part_no'] ?? '' }}" placeholder="Part No"></td>
                                <td><input type="text" class="form-input id-code" name="materials[{{ $index }}][id_code]"
                                    value="{{ $row['id_code'] ?? '' }}" placeholder="ID Code"></td>
                                <td><input type="text" class="form-input part-name" name="materials[{{ $index }}][part_name]"
                                    value="{{ $row['part_name'] ?? '' }}" placeholder="Part Name"></td>
                                <td><input type="text" class="form-input w-28 qty-req number-format" name="materials[{{ $index }}][qty_req]" autocomplete="off"
                                    value="{{ number_format((float) ($row['qty_req'] ?? 0), 0, ',', '.') }}" data-original-qty-req="{{ intval($row['qty_req'] ?? 0) }}" onchange="calculateRow(this)"></td>
                                <td><input type="text" class="form-input unit" name="materials[{{ $index }}][unit]"
                                    value="{{ isset($row['unit']) ? strtoupper(trim((string) $row['unit'])) : '' }}" placeholder="Unit"></td>
                                <td><input type="text" class="form-input pro-code" name="materials[{{ $index }}][pro_code]"
                                    value="{{ $row['pro_code'] ?? '' }}" placeholder="Pro Code"></td>
                                <td><input type="text" class="form-input amount1 number-format" name="materials[{{ $index }}][amount1]" autocomplete="off" value="{{ rtrim(rtrim(number_format((float) ($row['amount1'] ?? 0), 4, ',', '.'), '0'), ',') }}" data-original-amount1="{{ $row['amount1'] ?? 0 }}"
                                    step="0.0001" onchange="calculateRow(this)"></td>
                                <td><input type="text" class="form-input unit-price-basis" name="materials[{{ $index }}][unit_price_basis]"
                                    value="{{ $row['unit_price_basis_text'] ?? $row['unit_price_basis'] ?? '' }}" placeholder="Unit Price"
                                    onchange="calculateRow(this)"></td>
                                <td>
                                @php $rowCurrency = $row['currency'] ?? 'IDR'; @endphp
                                <select class="form-select currency" name="materials[{{ $index }}][currency]" onchange="calculateRow(this)">
                                    <option value="" {{ $rowCurrency === '' ? 'selected' : '' }}></option>
                                    <option value="IDR" {{ $rowCurrency == 'IDR' ? 'selected' : '' }}>IDR</option>
                                    <option value="USD" {{ $rowCurrency == 'USD' ? 'selected' : '' }}>USD</option>
                                    <option value="JPY" {{ $rowCurrency == 'JPY' ? 'selected' : '' }}>JPY</option>
                                </select>
                                </td>
                                <td><input type="text" class="form-input w-28 qty-moq number-format" name="materials[{{ $index }}][qty_moq]" value="{{ rtrim(rtrim(number_format((float) ($row['qty_moq'] ?? 0), 6, ',', '.'), '0'), ',') }}" data-original-moq="{{ $row['qty_moq'] ?? 0 }}"
                                    step="0.0001" onchange="calculateRow(this)"></td>
                                <td>
                                @php $rowCn = $row['cn_type'] ?? 'N'; @endphp
                                <select class="form-select cn-type" name="materials[{{ $index }}][cn_type]" onchange="calculateRow(this)">
                                    <option value="" {{ $rowCn === '' ? 'selected' : '' }}></option>
                                    <option value="N" {{ $rowCn == 'N' ? 'selected' : '' }}>N</option>
                                    <option value="C" {{ $rowCn == 'C' ? 'selected' : '' }}>C</option>
                                    <option value="E" {{ $rowCn == 'E' ? 'selected' : '' }}>E</option>
                                </select>
                                </td>
                                <td><input type="text" class="form-input supplier" name="materials[{{ $index }}][supplier]"
                                    value="{{ $row['supplier'] ?? '' }}" placeholder="Supplier"></td>
                                <td><input type="text" class="form-input import-tax number-format" name="materials[{{ $index }}][import_tax]"
                                    value="{{ rtrim(rtrim(number_format((float) ($row['import_tax'] ?? 0), 2, ',', '.'), '0'), ',') ?: '0' }}" onchange="calculateRow(this)"></td>
                                <td class="calculated multiply-factor">1</td>
                                <td class="calculated amount2" data-original-amount2="{{ $row['amount2'] ?? 0 }}">{{ rtrim(rtrim(number_format((float) ($row['amount2'] ?? 0), 5, ',', '.'), '0'), ',') ?: '0' }}</td>
                                <td class="calculated currency2">{{ $rowCurrency }}</td>
                                <td class="calculated unit-price2">{{ isset($row['unit']) ? strtoupper(trim((string) $row['unit'])) : '' }}</td>
                                <td class="calculated total-price">Rp {{ rtrim(rtrim(number_format((float) ($row['amount1'] ?? 0), 4, ',', '.'), '0'), ',') }}</td>
                                <td>
                                <button type="button" class="btn btn-secondary" onclick="removeRow(this)"
                                    style="padding: 0.5rem;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <polyline points="3 6 5 6 21 6" />
                                    <path
                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                                </td>
                            </tr>
                            @endforeach
                        @elseif($materialBreakdowns->count() > 0)
                            @foreach($materialBreakdowns as $index => $breakdown)
                                @php
                                    $partNoDisplay = trim((string) ($breakdown->part_no ?? ''));
                                    if ($partNoDisplay === '') {
                                        $partNoDisplay = $breakdown->material->material_code ?? '';
                                        if (str_starts_with((string) $partNoDisplay, '__ROW_') || $partNoDisplay === '__PLACEHOLDER__') {
                                            $partNoDisplay = '-';
                                        }
                                    }
                                    $partNameDisplay = trim((string) ($breakdown->part_name ?? ''));
                                    if ($partNameDisplay === '') {
                                        $partNameDisplay = $breakdown->material->material_description ?? '';
                                    }
                                    $unitDisplay = strtoupper(trim((string) ($breakdown->unit ?? $breakdown->material?->base_uom ?? '')));
                                    $rowCurrencyValue = strtoupper(trim((string) ($breakdown->currency ?? 'IDR')));
                                    $rowExchangeRate = match ($rowCurrencyValue) {
                                        'USD' => (float) ($costingData?->exchange_rate_usd ?? 0),
                                        'JPY' => (float) ($costingData?->exchange_rate_jpy ?? 0),
                                        default => 1.0,
                                    };
                                    $rowServerTotal = (float) ($breakdown->qty_req ?? 0)
                                        * (float) ($breakdown->amount2 ?? 0)
                                        * $rowExchangeRate;
                                @endphp
                                <tr data-row="{{ $index }}" data-server-total="{{ $rowServerTotal }}">
                                    <td>
                                        <span class="material-row-no-cell">
                                            <input type="checkbox" class="material-row-select" title="Pilih baris">
                                            <span class="material-row-number">{{ $index + 1 }}</span>
                                        </span>
                                    </td>
                                    <td><input type="text" class="form-input part-no" name="materials[{{ $index }}][part_no]"
                                    value="{{ $partNoDisplay }}" placeholder="Part No"></td>
                                    <td><input type="text" class="form-input id-code" name="materials[{{ $index }}][id_code]"
                                    value="{{ $breakdown->id_code ?? '' }}" placeholder="ID Code"></td>
                                    <td><input type="text" class="form-input part-name" name="materials[{{ $index }}][part_name]"
                                    value="{{ $partNameDisplay }}" placeholder="Part Name"></td>
                                            <td><input type="text" class="form-input w-28 qty-req number-format" name="materials[{{ $index }}][qty_req]" autocomplete="off"
                                            value="{{ number_format((float) ($breakdown->qty_req), 0, ',', '.') }}" data-original-qty-req="{{ intval($breakdown->qty_req) }}" onchange="calculateRow(this)"></td>
                                    <td><input type="text" class="form-input unit" name="materials[{{ $index }}][unit]"
                                    value="{{ $unitDisplay }}" placeholder="Unit"></td>
                                    <td><input type="text" class="form-input pro-code" name="materials[{{ $index }}][pro_code]"
                                            value="{{ $breakdown->pro_code ?? '' }}" placeholder="Pro Code"></td>
                                            <td><input type="text" class="form-input amount1 number-format" name="materials[{{ $index }}][amount1]" autocomplete="off" value="{{ $breakdown->amount1 === null ? '' : rtrim(rtrim(number_format((float) $breakdown->amount1, 4, ',', '.'), '0'), ',') }}" data-original-amount1="{{ $breakdown->amount1 }}"
                                            step="0.0001" onchange="calculateRow(this)"></td>
                                        <td><input type="text" class="form-input unit-price-basis" name="materials[{{ $index }}][unit_price_basis]"
                                            value="{{ $breakdown->unit_price_basis_text ?? $breakdown->unit_price_basis }}" placeholder="Unit Price"
                                            onchange="calculateRow(this)">
                                    </td>
                                    <td>
                                        <select class="form-select currency" name="materials[{{ $index }}][currency]" onchange="calculateRow(this)">
                                            <option value="" {{ $breakdown->currency === null || $breakdown->currency === '' ? 'selected' : '' }}></option>
                                            <option value="IDR" {{ $breakdown->currency == 'IDR' ? 'selected' : '' }}>IDR</option>
                                            <option value="USD" {{ $breakdown->currency == 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="JPY" {{ $breakdown->currency == 'JPY' ? 'selected' : '' }}>JPY</option>
                                        </select>
                                    </td>
                                        <td><input type="text" class="form-input w-28 qty-moq number-format" name="materials[{{ $index }}][qty_moq]" value="{{ $breakdown->qty_moq === null ? '' : rtrim(rtrim(number_format((float) $breakdown->qty_moq, 6, ',', '.'), '0'), ',') }}" data-original-moq="{{ $breakdown->qty_moq }}"
                                            step="0.0001" onchange="calculateRow(this)"></td>
                                    <td>
                                        <select class="form-select cn-type" name="materials[{{ $index }}][cn_type]" onchange="calculateRow(this)">
                                            <option value="" {{ $breakdown->cn_type === null || $breakdown->cn_type === '' ? 'selected' : '' }}></option>
                                            <option value="N" {{ $breakdown->cn_type == 'N' ? 'selected' : '' }}>N</option>
                                            <option value="C" {{ $breakdown->cn_type == 'C' ? 'selected' : '' }}>C</option>
                                            <option value="E" {{ $breakdown->cn_type == 'E' ? 'selected' : '' }}>E</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-input supplier" name="materials[{{ $index }}][supplier]"
                                            value="{{ $breakdown->supplier ?? '' }}" placeholder="Supplier"></td>
                                    <td><input type="text" class="form-input import-tax number-format" name="materials[{{ $index }}][import_tax]"
                                            value="{{ $breakdown->import_tax_percent === null ? '' : (rtrim(rtrim(number_format((float) $breakdown->import_tax_percent, 2, ',', '.'), '0'), ',') ?: '0') }}" onchange="calculateRow(this)">
                                    </td>
                                    <td class="calculated multiply-factor">1</td>
                                    <td class="calculated amount2" data-original-amount2="{{ $breakdown->amount2 ?? 0 }}">{{ rtrim(rtrim(number_format($breakdown->amount2 ?? 0, 5, ',', '.'), '0'), ',') }}</td>
                                    <td class="calculated currency2">{{ $breakdown->currency ?? 'IDR' }}</td>
                                        <td class="calculated unit-price2">{{ isset($breakdown->material?->base_uom) ? strtoupper(trim((string) $breakdown->material->base_uom)) : '' }}</td>
                                    <td class="calculated total-price">Rp {{ rtrim(rtrim(number_format((float) ($breakdown->amount1 ?? 0), 4, ',', '.'), '0'), ',') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-secondary" onclick="removeRow(this)"
                                            style="padding: 0.5rem;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <!-- Default empty rows -->
                            @for($i = 0; $i < 5; $i++)
                                <tr data-row="{{ $i }}">
                                    <td>
                                        <span class="material-row-no-cell">
                                            <input type="checkbox" class="material-row-select" title="Pilih baris">
                                            <span class="material-row-number">{{ $i + 1 }}</span>
                                        </span>
                                    </td>
                                    <td><input type="text" class="form-input part-no" name="materials[{{ $i }}][part_no]" value=""
                                            placeholder="Part No"></td>
                                    <td><input type="text" class="form-input id-code" name="materials[{{ $i }}][id_code]" value=""
                                            placeholder="ID Code"></td>
                                    <td><input type="text" class="form-input part-name" name="materials[{{ $i }}][part_name]"
                                            value="" placeholder="Part Name"></td>
                                            <td><input type="text" class="form-input w-28 qty-req number-format" name="materials[{{ $i }}][qty_req]" autocomplete="off"
                                            value="0" data-original-qty-req="0" onchange="calculateRow(this)"></td>
                                    <td><input type="text" class="form-input unit" name="materials[{{ $i }}][unit]" value="PCS"
                                            placeholder="Unit"></td>
                                    <td><input type="text" class="form-input pro-code" name="materials[{{ $i }}][pro_code]" value=""
                                            placeholder="Pro Code"></td>
                                            <td><input type="text" class="form-input amount1 number-format" name="materials[{{ $i }}][amount1]" autocomplete="off" value="0" data-original-amount1="0" step="0.0001"
                                            onchange="calculateRow(this)"></td>
                                    <td><input type="text" class="form-input unit-price-basis" name="materials[{{ $i }}][unit_price_basis]" value="" placeholder="Unit Price"
                                            onchange="calculateRow(this)"></td>
                                    <td>
                                        <select class="form-select currency" name="materials[{{ $i }}][currency]" onchange="calculateRow(this)">
                                            <option value="IDR">IDR</option>
                                            <option value="USD">USD</option>
                                            <option value="JPY">JPY</option>
                                        </select>
                                    </td>
                                        <td><input type="text" class="form-input w-28 qty-moq number-format" name="materials[{{ $i }}][qty_moq]" value="0" data-original-moq="0" step="0.0001"
                                            onchange="calculateRow(this)"></td>
                                    <td>
                                        <select class="form-select cn-type" name="materials[{{ $i }}][cn_type]" onchange="calculateRow(this)">
                                            <option value="N">N</option>
                                            <option value="C">C</option>
                                            <option value="E">E</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-input supplier" name="materials[{{ $i }}][supplier]" value=""
                                            placeholder="Supplier"></td>
                                        <td><input type="text" class="form-input import-tax number-format" name="materials[{{ $i }}][import_tax]" value="0"
                                            onchange="calculateRow(this)"></td>
                                    <td class="calculated multiply-factor">1</td>
                                    <td class="calculated amount2" data-original-amount2="0">0.0000</td>
                                    <td class="calculated currency2">IDR</td>
                                    <td class="calculated unit-price2">PCS</td>
                                    <td class="calculated total-price">Rp 0</td>
                                    <td>
                                        <button type="button" class="btn btn-secondary" onclick="removeRow(this)"
                                            style="padding: 0.5rem;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--slate-700);">
                            <td colspan="18" style="text-align: right; font-weight: 600;">Total Material dari Tabel:</td>
                            <td class="calculated" id="tableTotalMaterial"
                                style="font-weight: 700; color: var(--blue-300);">Rp 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>


        </div>

        <div class="card form-section" id="unpricedPartsSection">
            @include('form.partials.unpriced-section-header')

            <div class="material-table-container">
                <table class="material-table">
                    <thead>
                        <tr>
                            <th rowspan="2">
                                <span style="display:inline-flex;align-items:center;gap:0.3rem;">
                                    <input type="checkbox" id="unpricedSelectAll" title="Pilih semua baris"
                                        onchange="toggleAllUnpricedRowCheckboxes(this.checked)">
                                    <span>No</span>
                                </span>
                            </th>
                            <th rowspan="2">Part No</th>
                            <th rowspan="2">ID Code</th>
                            <th rowspan="2">Part Name</th>
                            <th colspan="9">Price</th>
                            <th rowspan="2">Input Harga (Manual)</th>
                            <th rowspan="2">Aksi</th>
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
                    <tbody id="unpricedRecapBody">
                        @if(isset($openUnpricedParts) && $openUnpricedParts->count() > 0)
                            @foreach($openUnpricedParts as $unpricedIdx => $item)
                                @php
                                    $matchedMaterials = collect($item->matched_materials ?? []);
                                    $matchedWires = collect($item->matched_wires ?? []);
                                    $matchedSource = $item->matched_source ?? null;
                                @endphp
                                <tr data-unpriced-part="{{ $item->part_number }}" data-unpriced-open="{{ is_null($item->resolved_at) ? '1' : '0' }}">
                                    <td>
                                        <span style="display:inline-flex;align-items:center;gap:0.3rem;">
                                            <input type="checkbox" class="unpriced-row-select" data-part-number="{{ $item->part_number }}">
                                            <span>{{ $unpricedIdx + 1 }}</span>
                                        </span>
                                    </td>
                                    <td>
                                        <div>{{ $item->part_number }}</div>
                                        @if(!empty($item->matched_material_description))
                                            <div style="font-size: 0.8rem; color: var(--slate-500); margin-top: 0.25rem;">
                                                {{ $item->matched_material_description }}
                                                @if($matchedSource === 'wire')
                                                    <span style="background: #dbeafe; color: #1e40af; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.7rem; margin-left: 0.3rem;">WIRE</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($item->id_code))
                                            {{ $item->id_code }}
                                        @elseif($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->material_code ?: '-' }}</div>
                                            @endforeach
                                        @elseif($matchedSource === 'wire' && !empty($item->matched_wire_idcode))
                                            {{ $item->matched_wire_idcode }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @php
                                        $displayPartName = trim((string) ($item->part_name ?? ''));
                                        if (isset($materialBreakdowns) && is_iterable($materialBreakdowns)) {
                                            $matBreakdown = collect($materialBreakdowns)->firstWhere('part_no', trim($item->part_number));
                                            if ($matBreakdown && !empty(trim((string) ($matBreakdown->part_name ?? '')))) {
                                                $displayPartName = trim((string) $matBreakdown->part_name);
                                            }
                                        }
                                    @endphp
                                    <td>{{ $displayPartName ?: '-' }}</td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                @php
                                                    $matchedPrice = (float) ($matched->price ?? 0);
                                                    $selectedDetectedPrice = (float) ($item->detected_price ?? 0);
                                                    $isMatchedChecked = $selectedDetectedPrice > 0 && abs($matchedPrice - $selectedDetectedPrice) < 0.0001;
                                                @endphp
                                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                                    <input type="checkbox"
                                                        class="matched-price-select"
                                                        data-part-number="{{ $item->part_number }}"
                                                        data-price="{{ $matchedPrice }}"
                                                        data-currency="{{ $matched->currency ?? '' }}"
                                                        data-unit="{{ $matched->purchase_unit ?? '' }}"
                                                        data-moq="{{ $matched->moq ?? 0 }}"
                                                        data-cn="{{ $matched->cn ?? 'N' }}"
                                                        data-supplier="{{ $matched->maker ?? '' }}"
                                                        data-import-tax="{{ $matched->add_cost_import_tax ?? 0 }}"
                                                        {{ $isMatchedChecked ? 'checked' : '' }}>
                                                    <span>{{ rtrim(rtrim(number_format($matchedPrice, 4, ',', '.'), '0'), ',') }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            @if($matchedSource === 'wire' && isset($item->matched_price))
                                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                                    <input type="checkbox"
                                                        class="matched-price-select"
                                                        data-part-number="{{ $item->part_number }}"
                                                        data-price="{{ (float) $item->matched_price }}"
                                                        data-currency="{{ $item->matched_currency ?? 'IDR' }}"
                                                        data-unit="{{ $matchedSource === 'wire' ? 'm' : ($item->matched_purchase_unit ?? '') }}"
                                                        data-moq="{{ $item->matched_moq ?? 0 }}"
                                                        data-cn="{{ $item->matched_cn ?? 'N' }}"
                                                        data-supplier="{{ $item->matched_maker ?? '' }}"
                                                        data-import-tax="{{ $item->matched_add_cost_import_tax ?? 0 }}"
                                                        checked>
                                                    <span>{{ rtrim(rtrim(number_format((float) $item->matched_price, 4, ',', '.'), '0'), ',') }}</span>
                                                </div>
                                            @else
                                                {{ isset($item->matched_price) && $item->matched_price !== null ? rtrim(rtrim(number_format((float) $item->matched_price, 4, ',', '.'), '0'), ',') : rtrim(rtrim(number_format((float) ($item->detected_price ?? 0), 4, ',', '.'), '0'), ',') }}
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->purchase_unit ?: '-' }}</div>
                                            @endforeach
                                        @elseif($matchedSource === 'wire')
                                            m
                                        @else
                                            {{ $item->matched_purchase_unit ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->currency ?: '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ $item->matched_currency ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ isset($matched->moq) && $matched->moq !== null ? rtrim(rtrim(number_format((float) $matched->moq, 2, ',', '.'), '0'), ',') : '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ isset($item->matched_moq) && $item->matched_moq !== null ? rtrim(rtrim(number_format((float) $item->matched_moq, 2, ',', '.'), '0'), ',') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->cn ?: '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ $item->matched_cn ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ $matched->maker ?: '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ $item->matched_maker ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ isset($matched->add_cost_import_tax) && $matched->add_cost_import_tax !== null ? rtrim(rtrim(number_format((float) $matched->add_cost_import_tax, 2, ',', '.'), '0'), ',') : '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ isset($item->matched_add_cost_import_tax) && $item->matched_add_cost_import_tax !== null ? rtrim(rtrim(number_format((float) $item->matched_add_cost_import_tax, 2, ',', '.'), '0'), ',') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ !empty($matched->price_update) ? \Illuminate\Support\Carbon::parse($matched->price_update)->format('Y-m-d') : '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ !empty($item->matched_price_update) ? \Illuminate\Support\Carbon::parse($item->matched_price_update)->format('Y-m-d') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($matchedMaterials->isNotEmpty())
                                            @foreach($matchedMaterials as $matched)
                                                <div>{{ isset($matched->price_before) && $matched->price_before !== null ? rtrim(rtrim(number_format((float) $matched->price_before, 2, ',', '.'), '0'), ',') : '-' }}</div>
                                            @endforeach
                                        @else
                                            {{ isset($item->matched_price_before) && $item->matched_price_before !== null ? rtrim(rtrim(number_format((float) $item->matched_price_before, 2, ',', '.'), '0'), ',') : '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" class="form-input unpriced-manual-price number-format"
                                            name="manual_unpriced_prices[{{ $item->part_number }}]"
                                            data-part-number="{{ $item->part_number }}"
                                            value="{{ $item->manual_price ?? '' }}" placeholder="Isi harga jika sudah ada">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm unpriced-add-price-btn"
                                            data-part-number="{{ $item->part_number }}"
                                            data-price="{{ $item->matched_price ?? $item->detected_price ?? 0 }}"
                                            data-unit="{{ $item->matched_purchase_unit ?? '' }}"
                                            data-currency="{{ $item->matched_currency ?? '' }}"
                                            data-moq="{{ $item->matched_moq ?? '' }}"
                                            data-cn="{{ $item->matched_cn ?? '' }}"
                                            data-supplier="{{ $item->matched_maker ?? '' }}"
                                            data-import-tax="{{ $item->matched_add_cost_import_tax ?? '' }}">
                                            Tambah
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm unpriced-delete-btn" data-part-number="{{ $item->part_number }}">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="15" style="text-align: center; color: var(--slate-500);">
                                    Belum ada part tanpa harga untuk versi dokumen ini.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

