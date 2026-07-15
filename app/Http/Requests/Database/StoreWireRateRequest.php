<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class StoreWireRateRequest extends FormRequest
{
    protected $errorBag = 'wireRateCreate';

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'period_month' => ['nullable', 'date', 'required_without:request_name', 'unique:wire_rates,period_month'],
            'request_name' => ['nullable', 'string', 'max:255', 'required_without:period_month'],
            'jpy_rate' => ['required', 'numeric', 'min:0'],
            'usd_rate' => ['required', 'numeric', 'min:0'],
            'lme_active' => ['required', 'numeric', 'min:0'],
        ];
    }
}
