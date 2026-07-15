<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWireRateRequest extends FormRequest
{
    protected $errorBag = 'wireRateEdit';

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = (string) $this->route('id');

        return [
            'period_month' => ['nullable', 'date', 'required_without:request_name', Rule::unique('wire_rates', 'period_month')->ignore($id)],
            'request_name' => ['nullable', 'string', 'max:255', 'required_without:period_month'],
            'jpy_rate' => ['required', 'numeric', 'min:0'],
            'usd_rate' => ['required', 'numeric', 'min:0'],
            'lme_active' => ['required', 'numeric', 'min:0'],
        ];
    }
}
