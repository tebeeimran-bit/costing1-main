<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingProjectInfoRequest extends FormRequest
{
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
            'model' => 'required|string|max:255',
            'part_number' => 'required|string|max:255',
            'part_name' => 'required|string|max:255',
            'forecast' => 'nullable',
            'forecast_uom' => 'nullable|string|max:50',
            'forecast_basis' => 'nullable|in:per_month,per_year',
            'project_period' => 'nullable|numeric|min:0',
            'line' => 'nullable|string|max:255',
            'period' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'pic_engineering' => 'required|string|max:255',
            'pic_marketing' => 'required|string|max:255',
            'a00' => 'nullable|in:ada,belum_ada',
            'a00_document_file' => 'nullable|file|mimes:pdf|max:10240|required_if:a00,ada',
            'a04' => 'nullable|in:ada,belum_ada',
            'a04_document_file' => 'nullable|file|mimes:pdf|max:10240|required_if:a04,ada',
            'a05' => 'nullable|in:ada,belum_ada',
            'a05_document_file' => 'nullable|file|mimes:pdf|max:10240|required_if:a05,ada',
        ];
    }

    public function messages(): array
    {
        return [
            'a00_document_file.mimes' => 'Dokumen A00 harus berformat PDF.',
            'a04_document_file.mimes' => 'Dokumen A04 harus berformat PDF.',
            'a05_document_file.mimes' => 'Dokumen A05 harus berformat PDF.',
        ];
    }
}
