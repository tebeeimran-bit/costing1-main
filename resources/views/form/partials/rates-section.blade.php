        <!-- Section B: Production Parameters & Actual Costs -->
        <div class="card form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                    <line x1="8" y1="21" x2="16" y2="21" />
                    <line x1="12" y1="17" x2="12" y2="21" />
                </svg>
                Rates
                <div class="section-actions">
                    <button type="submit" class="btn btn-primary btn-sm section-update-btn" name="update_section" value="rates" data-section="rates" formnovalidate>
                        Update
                    </button>
                </div>
            </div>
            <div class="form-grid param-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Rate Aktif</label>
                    <select name="exchange_rate_id" id="exchangeRateSelector" class="form-select"
                        data-selection-key="{{ $rateSelectionKey }}"
                        data-remember-url="{{ route('costing.selected-exchange-rate', absolute: false) }}"
                        onchange="updateRatesFromExchangeRate(this, true)">
                        <option value="" {{ $selectedExchangeRateId === 0 ? 'selected' : '' }}>Input manual</option>
                        @foreach($exchangeRates as $rate)
                            <option value="{{ $rate->id }}"
                                data-period="{{ $rate->period_date->format('Y-m-d') }}"
                                data-usd="{{ $rate->usd_to_idr }}"
                                data-jpy="{{ $rate->jpy_to_idr }}"
                                data-lme="{{ $rate->lme_copper }}"
                                {{ (int) $selectedExchangeRateId === (int) $rate->id ? 'selected' : '' }}>
                                {{ $rate->period_date->translatedFormat('M Y') }} | USD: Rp {{ number_format((float)$rate->usd_to_idr, 0, ',', '.') }} | JPY: Rp {{ rtrim(rtrim(number_format((float)$rate->jpy_to_idr, 2, ',', '.'), '0'), ',') }} | LME: Rp {{ number_format((float)$rate->lme_copper, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    @if($exchangeRates->isEmpty())
                        <small style="display:block; margin-top:0.35rem; color:var(--slate-500);">Belum ada data Rate &amp; Kurs. Isi nilai secara manual atau tambahkan data melalui menu Rate &amp; Kurs.</small>
                    @else
                        <small style="display:block; margin-top:0.35rem; color:var(--slate-500);">Pilih periode untuk mengisi otomatis, atau pilih Input manual untuk mengetik nilai sendiri.</small>
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">USD</label>
                    <input type="text" inputmode="decimal" name="exchange_rate_usd" class="form-input" id="rateUSD"
                        value="{{ $costingData->exchange_rate_usd ?? ($activeWireRate->usd_rate ?? 15500) }}" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">JPY</label>
                    <input type="text" inputmode="decimal" name="exchange_rate_jpy" class="form-input" id="rateJPY"
                        value="{{ $costingData->exchange_rate_jpy ?? ($activeWireRate->jpy_rate ?? 103) }}" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">IDR</label>
                    <input type="text" inputmode="decimal" name="exchange_rate_idr" class="form-input" id="rateIDR" value="1"
                        disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">LME Rate</label>
                    <input type="text" inputmode="decimal" name="lme_rate" class="form-input" id="lmeRate"
                        value="{{ $costingData->lme_rate ?? ($activeWireRate->lme_active ?? '') }}" step="0.01" placeholder="8500">
                </div>
            </div>
        </div>
