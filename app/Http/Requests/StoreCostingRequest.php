<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $updateSection = trim((string) $this->input('update_section', ''));

        if ($updateSection === '') {
            if ($this->hasFile('import_partlist_file')) {
                $updateSection = 'material';
            } elseif ($this->hasFile('import_cycle_time_file')) {
                $updateSection = 'cycle_time';
            }
        }

        if ($updateSection !== '') {
            $this->merge(['update_section' => $updateSection]);
        }
    }

    public function resolvedUpdateSection(): string
    {
        return trim((string) $this->input('update_section', ''));
    }

    public function rules(): array
    {
        $updateSection = $this->resolvedUpdateSection();
        $importPartlistFileUploaded = $this->hasFile('import_partlist_file');
        $importCycleTimeFileUploaded = $this->hasFile('import_cycle_time_file');

        $baseRules = [
            'costing_data_id' => 'nullable|exists:costing_data,id',
            'tracking_revision_id' => 'nullable|exists:document_revisions,id',
            'update_section' => 'nullable|string',
            'import_partlist' => 'nullable|boolean',
            'import_cycle_time' => 'nullable|boolean',
            'wire_rate_id' => 'nullable|exists:wire_rates,id',
            'costing_resume_overrides' => 'nullable|string',
        ];

        $fullRules = [
            'business_category_id' => 'required|exists:business_categories,id',
            'customer_id' => 'required|exists:customers,id',
            'period' => 'required|string',
            'line' => 'nullable|string',
            'model' => 'nullable|string',
            'assy_no' => 'nullable|string',
            'assy_name' => 'nullable|string',
            'pic_engineering' => 'required|string|max:255',
            'pic_marketing' => 'required|string|max:255',
            'exchange_rate_usd' => 'required|numeric',
            'exchange_rate_jpy' => 'required|numeric',
            'lme_rate' => 'nullable|numeric',
            'wire_rate_id' => 'nullable|exists:wire_rates,id',
            'forecast' => 'required|integer',
            'project_period' => 'required|integer',
            'material_cost' => 'nullable|numeric',
            'labor_cost' => 'nullable|numeric',
            'overhead_cost' => 'nullable|numeric',
            'scrap_cost' => 'nullable|numeric',
            'revenue' => 'nullable|numeric',
            'qty_good' => 'nullable|integer',
            'materials' => 'nullable|array',
            'materials.*.part_no' => 'nullable|string',
            'materials.*.part_name' => 'nullable|string',
            'materials.*.qty_req' => 'nullable|string',
            'materials.*.unit' => 'nullable|string',
            'materials.*.amount1' => 'nullable|string',
            'materials.*.unit_price_basis' => 'nullable|string',
            'materials.*.qty_moq' => 'nullable|string',
            'materials.*.cn_type' => 'nullable|string',
            'materials.*.supplier' => 'nullable|string',
            'materials.*.import_tax' => 'nullable|string',
            'manual_unpriced_prices' => 'nullable|array',
            'cycle_times' => 'nullable|array',
            'cycle_times.*.process' => 'nullable|string',
            'cycle_times.*.qty' => 'nullable|numeric',
            'cycle_times.*.time_hour' => 'nullable|numeric',
            'cycle_times.*.time_sec' => 'nullable|numeric',
            'cycle_times.*.time_sec_per_qty' => 'nullable|numeric',
            'cycle_times.*.cost_per_sec' => 'nullable|numeric',
            'cycle_times.*.cost_per_unit' => 'nullable|numeric',
            'cycle_times.*.area_of_process' => 'nullable|in:PP - Preparation,FA - Final Assy',
        ];

        $sectionRules = [
            'informasi_project' => [
                'business_category_id' => 'required|exists:business_categories,id',
                'customer_id' => 'required|exists:customers,id',
                'period' => 'required|string',
                'line' => 'nullable|string',
                'model' => 'nullable|string',
                'assy_no' => 'nullable|string',
                'assy_name' => 'nullable|string',
                'pic_engineering' => 'required|string|max:255',
                'pic_marketing' => 'required|string|max:255',
                'forecast' => 'required|integer',
                'project_period' => 'required|integer',
            ],
            'rates' => [
                'wire_rate_id' => 'required|exists:wire_rates,id',
                'exchange_rate_usd' => 'required|numeric',
                'exchange_rate_jpy' => 'required|numeric',
                'lme_rate' => 'nullable|numeric',
            ],
            'material' => [
                'forecast' => 'required|integer',
                'project_period' => 'required|integer',
                'materials' => 'nullable|array',
                'materials.*.part_no' => 'nullable|string',
                'materials.*.part_name' => 'nullable|string',
                'materials.*.qty_req' => 'nullable|string',
                'materials.*.unit' => 'nullable|string',
                'materials.*.amount1' => 'nullable|string',
                'materials.*.unit_price_basis' => 'nullable|string',
                'materials.*.qty_moq' => 'nullable|string',
                'materials.*.cn_type' => 'nullable|string',
                'materials.*.supplier' => 'nullable|string',
                'materials.*.import_tax' => 'nullable|string',
                'manual_unpriced_prices' => 'nullable|array',
            ],
            'unpriced_parts' => [
                'manual_unpriced_prices' => 'nullable|array',
            ],
            'cycle_time' => [
                'cycle_times' => 'nullable|array',
                'cycle_times.*.process' => 'nullable|string',
                'cycle_times.*.qty' => 'nullable|numeric',
                'cycle_times.*.time_hour' => 'nullable|numeric',
                'cycle_times.*.time_sec' => 'nullable|numeric',
                'cycle_times.*.time_sec_per_qty' => 'nullable|numeric',
                'cycle_times.*.cost_per_sec' => 'nullable|numeric',
                'cycle_times.*.cost_per_unit' => 'nullable|numeric',
                'cycle_times.*.area_of_process' => 'nullable|in:PP - Preparation,FA - Final Assy',
            ],
            'resume_cogm' => [
                'material_cost' => 'nullable|numeric',
                'labor_cost' => 'nullable|numeric',
                'overhead_cost' => 'nullable|numeric',
                'scrap_cost' => 'nullable|numeric',
                'revenue' => 'nullable|numeric',
                'qty_good' => 'nullable|integer',
            ],
        ];

        if ($updateSection !== '' && !array_key_exists($updateSection, $sectionRules)) {
            return [
                '__invalid_update_section' => 'required',
            ];
        }

        $rules = $updateSection !== ''
            ? array_merge($baseRules, $sectionRules[$updateSection])
            : array_merge($baseRules, $fullRules);

        if ($updateSection === 'material' && ($this->boolean('import_partlist') || $importPartlistFileUploaded)) {
            $rules['import_partlist_file'] = 'nullable';
        }

        if ($updateSection === 'cycle_time' && ($this->boolean('import_cycle_time') || $importCycleTimeFileUploaded)) {
            $rules['import_cycle_time_file'] = 'nullable';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            '__invalid_update_section.required' => 'Section update tidak valid.',
            'import_partlist_file.required' => 'File partlist wajib dipilih.',
            'import_partlist_file.file' => 'File partlist tidak valid.',
            'import_partlist_file.uploaded' => 'Upload gagal. Kemungkinan ukuran file melebihi batas server. Naikkan upload_max_filesize dan post_max_size di PHP.',
            'import_partlist_file.mimes' => 'Format file harus .xlsx atau .xls sesuai template partlist.',
            'import_partlist_file.max' => 'Ukuran file partlist terlalu besar (maks 20MB).',
            'import_cycle_time_file.required' => 'File Cycle Time wajib dipilih.',
            'import_cycle_time_file.file' => 'File Cycle Time tidak valid.',
            'import_cycle_time_file.uploaded' => 'Upload file Cycle Time gagal. Kemungkinan ukuran file melebihi batas server. Naikkan upload_max_filesize dan post_max_size di PHP.',
            'import_cycle_time_file.mimes' => 'Format file Cycle Time harus .xlsx atau .xls.',
            'import_cycle_time_file.max' => 'Ukuran file Cycle Time terlalu besar (maks 20MB).',
        ];
    }
}
