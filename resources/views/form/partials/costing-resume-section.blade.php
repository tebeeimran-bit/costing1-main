<!-- Section C: Resume COGM -->
<div class="card form-section">
    <div class="form-section-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 1v22" />
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
        </svg>
        Resume COGM
        <div class="section-actions">
            <button type="submit" class="btn btn-primary btn-sm section-update-btn" name="update_section" value="resume_cogm" data-section="resume_cogm" formnovalidate>
                Update
            </button>
        </div>
    </div>
    <div class="form-grid cost-grid">
        <div class="form-group">
            <label class="form-label">Total Material Cost (IDR)</label>
            <input type="text" inputmode="decimal" name="material_cost" class="form-input resume-money-input" id="materialCost"
                value="{{ isset($costingData->material_cost) ? number_format((float) $costingData->material_cost, 2, ',', '.') : '' }}" required placeholder="0"
                readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Process Cost (IDR)</label>
            <input type="text" inputmode="decimal" name="labor_cost" class="form-input resume-money-input" id="laborCost"
                value="{{ isset($costingData->labor_cost) ? number_format((float) $costingData->labor_cost, 2, ',', '.') : '' }}" required placeholder="0"
                readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Depresiasi Tooling Cost (IDR)</label>
            <input type="text" inputmode="decimal" name="overhead_cost" class="form-input resume-money-input" id="overheadCost"
                value="{{ isset($costingData->overhead_cost) ? number_format((float) $costingData->overhead_cost, 2, ',', '.') : '' }}" placeholder="Contoh: =TOTAL_MATERIAL_COST*0,05"
                title="Bisa diisi angka atau formula dengan +, -, *, / dan TOTAL_MATERIAL_COST"
                onchange="applyResumeCostFormula(this); calculateTotals()">
            <small class="form-text">Bisa diisi manual atau formula, contoh: <code>=TOTAL_MATERIAL_COST*0,05</code></small>
        </div>
        <div class="form-group">
            <label class="form-label">Administrasi Cost (IDR)</label>
            <input type="text" inputmode="decimal" name="scrap_cost" class="form-input resume-money-input" id="scrapCost"
                value="{{ isset($costingData->scrap_cost) ? number_format((float) $costingData->scrap_cost, 2, ',', '.') : '' }}" placeholder="0"
                onchange="formatResumeMoneyInput(this); calculateTotals()">
        </div>
    </div>

    <input type="hidden" name="revenue" id="revenue" value="{{ $costingData->revenue ?? 0 }}">
    <input type="hidden" name="qty_good" id="qtyGood" value="{{ $costingData->qty_good ?? 0 }}">
    <input type="hidden" name="costing_resume_overrides" id="costingResumeOverridesInput" value="{{ e(json_encode($costingData->costing_resume_overrides ?? [], JSON_UNESCAPED_UNICODE)) }}">

    <div class="calc-box" style="margin-top: 1.5rem;">
        <div class="calc-item"><span class="calc-label">Total Material Cost</span><span class="calc-value" id="calcTotalMaterialCost">Rp 0</span></div>
        <div class="calc-item"><span class="calc-label">Process Cost</span><span class="calc-value" id="calcProcessCost">Rp 0</span></div>
        <div class="calc-item"><span class="calc-label">Depresiasi Tooling Cost</span><span class="calc-value" id="calcToolingCost">Rp 0</span></div>
        <div class="calc-item"><span class="calc-label">Administrasi Cost</span><span class="calc-value" id="calcAdministrasiCost">Rp 0</span></div>
        <div class="calc-item"><span class="calc-label">COGM</span><span class="calc-value" id="calcCogsTotal">Rp 0</span></div>
    </div>
    <div class="costing-resume-preview" aria-label="Costing resume preview">
        <div class="costing-resume-sheet">
            <div class="costing-resume-side">Costing Resume</div>
            <div class="costing-resume-main">
                <div class="costing-resume-title"><span>Cost Of Good Manufacturing Resume</span><button type="button" class="costing-resume-reset" onclick="resetCostingResumeOverrides()">Reset Manual Edit</button></div>
                <div class="costing-resume-top">
                    <div class="costing-resume-info">
                        <span>Customer</span><span>:</span><strong id="crCustomer" data-cr-field="info.customer">-</strong>
                        <span>Model</span><span>:</span><strong id="crModel" data-cr-field="info.model">-</strong>
                        <span>Assy No</span><span>:</span><strong id="crAssyNo" data-cr-field="info.assy_no">-</strong>
                        <span>Assy Name</span><span>:</span><strong id="crAssyName" data-cr-field="info.assy_name">-</strong>
                        <span>CCT</span><span>:</span><strong id="crCct" data-cr-field="info.cct">0</strong>
                        <span>Forecast / month</span><span>:</span><strong id="crForecast" data-cr-field="info.forecast">0</strong>
                        <span>Periode / month</span><span>:</span><strong id="crPeriod" data-cr-field="info.period">0</strong>
                    </div>
                    <div>
                        <div class="costing-resume-rate">
                            <div class="costing-resume-rate-title" id="crRateTitle" data-cr-field="rate.title">Rate Request</div>
                            <div class="costing-resume-rate-row"><strong id="crRateUsd" data-cr-field="rate.usd">0</strong><span>: USD</span></div>
                            <div class="costing-resume-rate-row"><strong id="crRateJpy" data-cr-field="rate.jpy">0</strong><span>: JPY</span></div>
                            <div class="costing-resume-rate-row"><strong id="crRateLme" data-cr-field="rate.lme">0</strong><span>: LME</span></div>
                        </div>
                    </div>
                </div>
                <div class="costing-resume-table-wrap"><table class="costing-resume-table"><thead><tr><th style="text-align:left;">Part Name</th><th style="width:110px;">Qty</th><th style="width:72px;">Unit</th><th style="width:150px;">Amount</th><th style="width:70px;">%</th></tr></thead><tbody id="costingResumeMaterialBody"><tr><td colspan="5" class="costing-resume-empty">Material belum tersedia.</td></tr></tbody></table></div>
                <div class="costing-resume-summary">
                    <div class="costing-resume-summary-row band"><span>TOTAL MATERIAL COST</span><span>Rp</span><span class="text-right" id="crTotalMaterialCost" data-cr-field="summary.material_cost">0,00</span><span class="text-right" id="crTotalMaterialPct" data-cr-field="summary.material_pct">0%</span></div>
                    <div class="costing-resume-summary-row band"><span>PROCESS COST</span><span id="crProcessHour" data-cr-field="summary.process_hour">0 hour</span><span class="text-right" id="crProcessCost" data-cr-field="summary.process_cost">0,00</span><span class="text-right" id="crProcessPct" data-cr-field="summary.process_pct">0%</span></div>
                    <div class="costing-resume-summary-row band"><span>DEPRESIASI TOOLING COST</span><span>Rp</span><span class="text-right" id="crToolingCost" data-cr-field="summary.tooling_cost">0,00</span><span class="text-right" id="crToolingPct" data-cr-field="summary.tooling_pct">0%</span></div>
                    <div class="costing-resume-summary-row band"><span>ADMINISTRATION COST</span><span>Rp</span><span class="text-right" id="crAdminCost" data-cr-field="summary.admin_cost">0,00</span><span class="text-right" id="crAdminPct" data-cr-field="summary.admin_pct">0%</span></div>
                    <div class="costing-resume-summary-row total"><span>COGM</span><span>Rp</span><span class="text-right" id="crCogmTotal" data-cr-field="summary.cogm_total">0,00</span><span class="text-right" id="crCogmPct" data-cr-field="summary.cogm_pct">100%</span></div>
                </div>
                <div class="costing-resume-metrics">
                    <div class="costing-resume-metric-row"><span>POTENTIAL SALES / MONTH</span><span>Rp</span><strong class="text-right" id="crPotentialSalesMonth" data-cr-field="metrics.potential_sales_month">0,00</strong></div>
                    <div class="costing-resume-metric-row"><span>POTENTIAL SALES / Periode Life Time Product</span><span>Rp</span><strong class="text-right" id="crPotentialSalesLifetime" data-cr-field="metrics.potential_sales_lifetime">0,00</strong></div>
                    <div class="costing-resume-metric-row"><span>Rp / cct</span><span>Rp</span><strong class="text-right" id="crRpPerCct" data-cr-field="metrics.rp_per_cct">0,00</strong></div>
                    <div class="costing-resume-metric-row"><span>Est. Jumlah MP / Product / Month</span><span></span><strong class="text-right" id="crEstimatedMp" data-cr-field="metrics.estimated_mp">0</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
