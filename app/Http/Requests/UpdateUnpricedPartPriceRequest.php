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
        ];
    }
}
