<div class="toast-container" id="mainToastContainer">

    @if(session('success'))
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3l-8.47-14.14a2 2 0 0 0-3.42 0z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="15" y1="9" x2="9" y2="15" />
                <line x1="9" y1="9" x2="15" y2="15" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        @php
            $validationTargets = [
                'business_category_id' => '#projectInfoSection', 'customer_id' => '#projectInfoSection',
                'period' => '#projectInfoSection', 'pic_engineering' => '#projectInfoSection',
                'pic_marketing' => '#projectInfoSection', 'forecast' => '#projectInfoSection',
                'project_period' => '#projectInfoSection', 'wire_rate_id' => '#ratesFormSection',
                'exchange_rate_usd' => '#ratesFormSection', 'exchange_rate_jpy' => '#ratesFormSection',
                'lme_rate' => '#ratesFormSection', 'materials' => '#materialFormSection',
                'manual_unpriced_prices' => '#unpricedFormSection', 'cycle_times' => '#cycleTimeFormSection',
                'material_cost' => '#costingResumeSection', 'labor_cost' => '#costingResumeSection',
                'overhead_cost' => '#costingResumeSection', 'scrap_cost' => '#costingResumeSection',
                'revenue' => '#costingResumeSection', 'qty_good' => '#costingResumeSection',
            ];
        @endphp
        <div class="validation-summary" role="alert">
            <div class="validation-summary-title">Ada {{ $errors->count() }} data yang perlu diperbaiki</div>
            <div class="validation-summary-subtitle">Klik tombol di bawah untuk langsung menuju kolom bermasalah.</div>
            <div class="validation-summary-list">
                @foreach($errors->getMessages() as $field => $messages)
                    @php
                        $prefix = explode('.', $field)[0];
                        $target = $validationTargets[$field] ?? $validationTargets[$prefix] ?? '#costingForm';
                    @endphp
                    @foreach($messages as $message)
                        <div class="validation-summary-item">
                            <span>{{ $message }}</span>
                            <button type="button" class="validation-fix-button" data-error-field="{{ $field }}" data-error-target="{{ $target }}">Perbaiki Sekarang</button>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
        <style>
            .validation-summary { width: min(720px, calc(100vw - 36px)); padding: 16px; border: 1px solid #fecaca; border-radius: 12px; background: #fff; box-shadow: 0 12px 28px rgba(127,29,29,.14); color: #7f1d1d; }
            .validation-summary-title { font-size: 15px; font-weight: 800; }
            .validation-summary-subtitle { margin: 3px 0 12px; color: #9f3a3a; font-size: 12px; }
            .validation-summary-list { display: grid; gap: 7px; }
            .validation-summary-item { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 9px 10px; border-radius: 8px; background: #fef2f2; font-size: 13px; }
            .validation-fix-button { flex: 0 0 auto; border: 0; background: transparent; color: #dc2626; font-size: 12px; font-weight: 800; cursor: pointer; }
            .validation-field-error { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239,68,68,.12) !important; }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const fieldName = (key) => key.split('.').reduce((name, part, index) => index === 0 ? part : `${name}[${part}]`, '');
                document.querySelectorAll('.validation-fix-button').forEach((button) => {
                    const field = document.querySelector(`[name="${CSS.escape(fieldName(button.dataset.errorField))}"]`);
                    if (field) { field.classList.add('validation-field-error'); field.setAttribute('aria-invalid', 'true'); }
                    button.addEventListener('click', function () {
                        const section = document.querySelector(button.dataset.errorTarget);
                        (field || section)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        window.setTimeout(() => field?.focus({ preventScroll: true }), 450);
                    });
                });
            });
        </script>
    @endif
</div>
