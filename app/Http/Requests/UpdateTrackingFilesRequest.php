<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'partlist_file' => 'nullable|file|mimes:xls,xlsx|max:10240',
            'umh_file' => 'nullable|file|mimes:xls,xlsx|max:10240',
            'change_remark' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'partlist_file.mimes' => 'Dokumen Partlist harus berformat Excel (xls/xlsx).',
            'umh_file.mimes' => 'Dokumen UMH harus berformat Excel (xls/xlsx).',
        ];
    }
}
