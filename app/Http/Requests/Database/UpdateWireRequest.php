<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWireRequest extends FormRequest
{
    protected $errorBag = 'wireEdit';

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = (string) $this->route('id');

        return [
            'idcode' => ['required', 'string', 'max:255', Rule::unique('wires', 'idcode')->ignore($id)],
            'item' => ['required', 'string', 'max:255'],
            'machine_maintenance' => ['required', 'string', 'max:255'],
            'fix_cost' => ['nullable', 'numeric', 'decimal:0,5', 'min:0'],
            'price' => ['nullable', 'numeric', 'decimal:0,5', 'min:0'],
        ];
    }
}
