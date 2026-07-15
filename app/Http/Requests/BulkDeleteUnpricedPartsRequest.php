<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteUnpricedPartsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'part_numbers' => 'required|array|min:1',
            'part_numbers.*' => 'required|string|max:255',
        ];
    }
}
