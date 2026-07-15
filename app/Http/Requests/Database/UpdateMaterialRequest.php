<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'plant' => 'nullable|string|max:255',
            'material_code' => 'required|string|max:255|unique:materials,material_code,' . $id,
            'material_description' => 'nullable|string|max:255',
            'material_type' => 'nullable|string|max:255',
            'material_group' => 'nullable|string|max:255',
            'base_uom' => 'required|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'purchase_unit' => 'nullable|string|max:50',
            'currency' => 'required|string|max:10',
            'moq' => 'nullable|numeric|min:0',
            'cn' => 'nullable|string|max:255',
            'maker' => 'nullable|string|max:255',
            'add_cost_import_tax' => 'nullable|numeric|min:0|max:100',
            'price_update' => 'nullable|date',
            'price_before' => 'nullable|numeric|min:0',
        ];
    }
}
