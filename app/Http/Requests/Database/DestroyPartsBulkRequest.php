<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class DestroyPartsBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'material_ids' => 'required|array|min:1',
            'material_ids.*' => 'integer|exists:materials,id',
        ];
    }

    public function messages(): array
    {
        return [
            'material_ids.required' => 'Pilih minimal satu material untuk dihapus.',
            'material_ids.array' => 'Format data hapus massal tidak valid.',
            'material_ids.min' => 'Pilih minimal satu material untuk dihapus.',
        ];
    }
}
