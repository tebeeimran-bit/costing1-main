<?php

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class ImportWiresExcelRequest extends FormRequest
{
    protected $errorBag = 'importWires';

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'import_file.required' => 'File Excel wajib dipilih.',
            'import_file.mimes' => 'Format file harus .xlsx.',
            'import_file.max' => 'Ukuran file maksimal 20MB.',
        ];
    }
}
