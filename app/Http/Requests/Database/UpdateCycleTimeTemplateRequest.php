<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCycleTimeTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'process' => 'required|string|max:255|unique:cycle_time_templates,process,' . $id,
        ];
    }
}
