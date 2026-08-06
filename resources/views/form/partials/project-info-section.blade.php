        <!-- Section A: Filter & Header -->
        <div class="card form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                </svg>
                Informasi Project
                <div class="section-actions">
                    <button type="submit" class="btn btn-primary btn-sm section-update-btn" name="update_section" value="informasi_project" data-section="informasi_project" formnovalidate>
                        Update
                    </button>
                </div>
            </div>
            <div class="form-grid project-info-grid">
                <div class="form-group">
                    <label class="form-label">Business Categories</label>
                    <select name="business_category_id" class="form-select" id="productInput" required>
                        @php
                            $selectedBusinessCategoryId = old('business_category_id', $trackingProjectPrefill['business_category_id'] ?? '');
                            if ($selectedBusinessCategoryId === '' && isset($costingData) && $costingData && $costingData->product) {
                                $matchedCategory = $businessCategories->first(function ($category) use ($costingData) {
                                    return trim((string) $category->code) === trim((string) $costingData->product->code)
                                        || trim((string) $category->name) === trim((string) $costingData->product->name);
                                });
                                $selectedBusinessCategoryId = $matchedCategory?->id ?? '';
                            }
                        @endphp
                        <option value="">-- Pilih Business Categories --</option>
                        @foreach($businessCategories as $businessCategory)
                            <option value="{{ $businessCategory->id }}" {{ (string) $selectedBusinessCategoryId === (string) $businessCategory->id ? 'selected' : '' }}>
                                {{ $businessCategory->code }} - {{ $businessCategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" id="customerInput" required>
                        @php
                            $selectedCustomerId = old('customer_id', $costingData->customer_id ?? ($trackingProjectPrefill['customer_id'] ?? ''));
                        @endphp
                        <option value="">-- Pilih Customer --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" data-code="{{ $customer->code }}" {{ (string) $selectedCustomerId === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->code }} - {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Model</label>
                    <input type="text" name="model" class="form-input" placeholder="Model"
                        value="{{ old('model', $costingData->model ?? ($trackingProjectPrefill['model'] ?? '')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Assy No.</label>
                    <input type="text" name="assy_no" class="form-input" placeholder="Assy No."
                        value="{{ old('assy_no', $costingData->assy_no ?? ($trackingProjectPrefill['assy_no'] ?? '')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Assy Name</label>
                    <input type="text" name="assy_name" class="form-input" placeholder="Assy Name"
                        value="{{ old('assy_name', $costingData->assy_name ?? ($trackingProjectPrefill['assy_name'] ?? '')) }}">
                </div>
                <div class="form-group quantity-group">
                    <label class="form-label">Quantity</label>
                    <div class="quantity-with-options">
                        @php
                            $forecastValue = (int) old('forecast', $costingData->forecast ?? 2000);
                        @endphp
                        <input type="hidden" name="forecast" id="forecast" value="{{ $forecastValue }}">
                        <input type="text" class="form-input quantity-value" id="forecastDisplay"
                            value="{{ number_format($forecastValue, 0, ',', '.') }}" inputmode="numeric"
                            required placeholder="2.000">
                        <select name="forecast_uom" class="form-select quantity-uom">
                            <option value="PCE" {{ old('forecast_uom', 'PCE') == 'PCE' ? 'selected' : '' }}>PCE</option>
                            <option value="Set" {{ old('forecast_uom') == 'Set' ? 'selected' : '' }}>Set</option>
                        </select>
                        <select name="forecast_basis" class="form-select quantity-basis">
                            <option value="per_month" {{ old('forecast_basis', 'per_month') == 'per_month' ? 'selected' : '' }}>Per Bulan</option>
                            <option value="per_year" {{ old('forecast_basis') == 'per_year' ? 'selected' : '' }}>Per Tahun</option>
                        </select>
                    </div>
                </div>
                <div class="form-group project-life-group">
                    <label class="form-label">Product's Life</label>
                    <input type="number" name="project_period" class="form-input" id="projectPeriod"
                        value="{{ $costingData->project_period ?? 2 }}" required>
                </div>
                <div class="form-group plant-group">
                    <label class="form-label">Plant</label>
                    <select name="line" class="form-select">
                        @php
                            $selectedPlant = old('line', $costingData->line ?? ($trackingProjectPrefill['plant_code'] ?? ($plants->first()?->code ?? '')));
                        @endphp
                        <option value="">-- Pilih Plant --</option>
                        @foreach($plants as $plant)
                            <option value="{{ $plant->code }}" {{ (string) $selectedPlant === (string) $plant->code ? 'selected' : '' }}>
                                {{ $plant->code }} - {{ $plant->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group period-group">
                    <label class="form-label">Periode</label>
                    <select name="period" class="form-select" id="periodInput">
                        @php
                            $defaultPeriod = $activeWireRate && $activeWireRate->period_month
                                ? $activeWireRate->period_month->format('Y-m')
                                : '';
                            $selectedPeriod = old('period', $costingData->period ?? ($trackingProjectPrefill['period'] ?? $defaultPeriod));
                        @endphp
                        <option value="">-- Pilih Periode --</option>
                        @foreach($periods as $period)
                            @php
                                $periodLabel = preg_match('/^\d{4}-\d{2}$/', (string) $period)
                                    ? \Carbon\Carbon::createFromFormat('Y-m', (string) $period)->translatedFormat('M Y')
                                    : $period;
                            @endphp
                            <option value="{{ $period }}" {{ (string) $selectedPeriod === (string) $period ? 'selected' : '' }}>
                                {{ $periodLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group pic-marketing-group">
                    <label class="form-label">PIC Marketing</label>
                    <select name="pic_marketing" class="form-select" required>
                        @php
                            $selectedPicMarketing = old('pic_marketing', $trackingRevision->pic_marketing ?? ($trackingProjectPrefill['pic_marketing'] ?? ''));
                        @endphp
                        <option value="">-- Pilih PIC Marketing --</option>
                        @foreach($picsMarketing as $pic)
                            <option value="{{ $pic->name }}" {{ (string) $selectedPicMarketing === (string) $pic->name ? 'selected' : '' }}>{{ $pic->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group pic-engineering-group">
                    <label class="form-label">PIC Engineering</label>
                    <select name="pic_engineering" class="form-select" required>
                        @php
                            $selectedPicEngineering = old('pic_engineering', $trackingRevision->pic_engineering ?? ($trackingProjectPrefill['pic_engineering'] ?? ''));
                        @endphp
                        <option value="">-- Pilih PIC Engineering --</option>
                        @foreach($picsEngineering as $pic)
                            <option value="{{ $pic->name }}" {{ (string) $selectedPicEngineering === (string) $pic->name ? 'selected' : '' }}>{{ $pic->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
