<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class StorePicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:engineering,marketing',
        ];
    }
}
