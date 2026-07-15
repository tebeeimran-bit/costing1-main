<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class SwitchWireRateRequest extends FormRequest
{
    protected $errorBag = 'wireRateSwitch';

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'rate_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
