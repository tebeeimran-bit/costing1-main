<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => 'required|string|max:255|unique:plants,code,' . $id,
            'name' => 'required|string|max:255',
        ];
    }
}
