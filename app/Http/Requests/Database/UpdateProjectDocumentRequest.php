<?php

namespace App\Http\Requests\Database;

use App\Models\DocumentRevision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProjectDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $a04 = $this->input('a04');
        $a05 = $this->input('a05');

        if ($a04 === 'ada' || $a05 === 'ada') {
            $this->merge([
                'a00' => 'ada',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'return_to_dashboard' => ['nullable', 'boolean'],

            'a00' => ['required', 'in:ada,belum_ada'],
            'a00_received_date' => ['nullable', 'date'],
            'a00_document_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],

            'a04' => ['required', 'in:ada,belum_ada'],
            'a04_received_date' => ['nullable', 'date'],
            'a04_reason' => ['nullable', 'string', 'max:2000'],
            'a04_document_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],

            'a05' => ['required', 'in:ada,belum_ada'],
            'a05_received_date' => ['nullable', 'date'],
            'a05_document_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],

            'partlist' => ['required', 'in:ada,belum_ada'],
            'partlist_received_date' => ['nullable', 'date'],
            'partlist_revision_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'partlist_document_file' => ['nullable', 'file', 'mimes:xlsx,xls,csv,pdf', 'max:20480'],

            'umh' => ['required', 'in:ada,belum_ada'],
            'umh_received_date' => ['nullable', 'date'],
            'umh_revision_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'umh_document_file' => ['nullable', 'file', 'mimes:xlsx,xls,csv,pdf', 'max:20480'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $a04 = $this->input('a04');
            $a05 = $this->input('a05');

            if ($a04 === 'ada' && $a05 === 'ada') {
                $validator->errors()->add(
                    'a04',
                    'A04 dan A05 tidak bisa keduanya "Ada". Project hanya bisa menjadi salah satu: A04 (Canceled/Failed) atau A05 (Die Go).'
                );
            }

            $revision = DocumentRevision::find($this->route('id'));

            if ($a04 === 'ada') {
                if (trim((string) $this->input('a04_reason')) === '') {
                    $validator->errors()->add('a04_reason', 'Alasan Canceled/Failed wajib diisi untuk status A04.');
                }

                $hasExistingA04Document = $revision && !empty($revision->a04_document_file_path);
                if (!$this->hasFile('a04_document_file') && !$hasExistingA04Document) {
                    $validator->errors()->add('a04_document_file', 'Dokumen A04 wajib diupload.');
                }
            }

            if ($a05 === 'ada') {
                $hasExistingA05Document = $revision && !empty($revision->a05_document_file_path);
                if (!$this->hasFile('a05_document_file') && !$hasExistingA05Document) {
                    $validator->errors()->add('a05_document_file', 'Dokumen A05 wajib diupload.');
                }
            }

            if ($this->input('partlist') === 'ada') {
                $hasExistingPartlist = $revision && !empty($revision->partlist_file_path);
                if (!$this->hasFile('partlist_document_file') && !$hasExistingPartlist) {
                    $validator->errors()->add('partlist_document_file', 'Dokumen Partlist wajib diupload jika status Partlist = Ada.');
                }
            }

            if ($this->input('umh') === 'ada') {
                $hasExistingUmh = $revision && !empty($revision->umh_file_path);
                if (!$this->hasFile('umh_document_file') && !$hasExistingUmh) {
                    $validator->errors()->add('umh_document_file', 'Dokumen UMH wajib diupload jika status UMH = Ada.');
                }
            }
        });
    }
}
