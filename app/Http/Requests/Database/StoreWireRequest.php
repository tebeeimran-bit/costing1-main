<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class StoreWireRequest extends FormRequest
{
    protected $errorBag = 'wireCreate';

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'idcode' => ['required', 'string', 'max:255', 'unique:wires,idcode'],
            'item' => ['required', 'string', 'max:255'],
            'machine_maintenance' => ['required', 'string', 'max:255'],
            'fix_cost' => ['nullable', 'numeric', 'decimal:0,5', 'min:0'],
            'price' => ['nullable', 'numeric', 'decimal:0,5', 'min:0'],
        ];
    }
}
