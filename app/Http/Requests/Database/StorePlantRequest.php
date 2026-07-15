<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255|unique:plants,code',
            'name' => 'required|string|max:255',
        ];
    }
}
