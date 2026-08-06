<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnpricedPartPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'part_number' => 'required|string|max:255',
            'manual_price' => 'nullable|numeric|min:0',
            'use_database_lookup' => 'nullable|boolean',
            'purchase_unit' => 'nullable|string|max:50',
            'currency' => 'nullable|string|in:IDR,USD,JPY',
            'moq' => 'nullable|numeric|min:0',
            'cn_type' => 'nullable|string|in:C,N,E',
            'maker' => 'nullable|string|max:255',
            'add_cost_percent' => 'nullable|numeric|min:0',
            'update_costing_edit' => 'nullable|boolean',
        ];
    }
}
