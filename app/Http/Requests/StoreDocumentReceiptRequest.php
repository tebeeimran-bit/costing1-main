<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentReceiptRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (!$this->has('items') && $this->filled('assy_no')) {
            $this->merge(['items' => [[
                'model' => $this->input('model'), 'assy_no' => $this->input('assy_no'),
                'assy_name' => $this->input('assy_name'), 'forecast' => $this->input('forecast'),
                'forecast_uom' => $this->input('forecast_uom', 'PCE'),
                'forecast_basis' => $this->input('forecast_basis', 'per_month'),
                'project_period' => $this->input('project_period'), 'spot_order' => $this->boolean('spot_order'),
            ]]]);
        }
    }

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|exists:products,id|required_without:business_category_id',
            'business_category_id' => 'nullable|exists:business_categories,id|required_without:product_id',
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1|max:20',
            'items.*.model' => 'required|string|max:255',
            'items.*.assy_no' => 'required|string|max:255|distinct',
            'items.*.assy_name' => 'required|string|max:255',
            'items.*.forecast' => 'nullable|numeric|min:0',
            'items.*.forecast_uom' => 'required|in:PCE,Set,Unit',
            'items.*.forecast_basis' => 'required|in:per_month,per_year,spot_order',
            'items.*.project_period' => 'nullable|integer|min:1|max:99',
            'items.*.spot_order' => 'nullable|boolean',
            'forecast' => 'nullable|numeric|min:0',
            'forecast_uom' => 'nullable|string|max:20',
            'forecast_basis' => 'nullable|string|max:20',
            'project_period' => 'nullable|integer|min:0',
            'line' => 'nullable|string|max:255',
            'period' => 'nullable|string|max:20',
            'received_date' => 'nullable|date',
            'pic_engineering' => 'required|string|max:255',
            'pic_marketing' => 'required|string|max:255',
            'a00_status' => 'nullable|in:ada,belum_ada',
            'a00_received_date' => 'nullable|date|required_if:a00_status,ada',
            'a00_document_file' => 'nullable|file|mimes:pdf|max:10240|required_if:a00_status,ada',
            'a04_status' => 'nullable|in:ada,belum_ada',
            'a04_received_date' => 'nullable|date|required_if:a04_status,ada',
            'a04_document_file' => 'nullable|file|mimes:pdf|max:10240|required_if:a04_status,ada',
            'a05_status' => 'nullable|in:ada,belum_ada',
            'a05_received_date' => 'nullable|date|required_if:a05_status,ada',
            'a05_document_file' => 'nullable|file|mimes:pdf|max:10240|required_if:a05_status,ada',
            'partlist_file' => 'nullable|file|mimes:xls,xlsx|max:10240',
            'umh_file' => 'nullable|file|mimes:xls,xlsx|max:10240',
            'notes' => 'nullable|string|max:1000',
            'change_remark' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'partlist_file.mimes' => 'Dokumen Partlist harus berformat Excel (xls/xlsx).',
            'umh_file.mimes' => 'Dokumen UMH harus berformat Excel (xls/xlsx).',
            'a00_document_file.mimes' => 'Dokumen A00 harus berformat PDF.',
            'a04_document_file.mimes' => 'Dokumen A04 harus berformat PDF.',
            'a05_document_file.mimes' => 'Dokumen A05 harus berformat PDF.',
        ];
    }
}
