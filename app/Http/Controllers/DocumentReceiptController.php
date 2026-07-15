<?php

namespace App\Http\Controllers;

use App\Models\DocumentReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentReceiptController extends Controller
{
    public function index()
    {
        $receipts = DocumentReceipt::latest()->get();

        return view('document-receipts.index', compact('receipts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'nullable|string|max:100',
            'cust' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:255',
            'part_name' => 'nullable|string|max:255',
            'pl_date' => 'nullable|date',
            'umh_date' => 'nullable|date',
            'pic_eng' => 'nullable|string|max:255',
            'pic_mkt' => 'nullable|string|max:255',
            'send_1_date' => 'nullable|date',
            'send_2_date' => 'nullable|date',
            'keterangan' => 'nullable|string|max:1000',
            'received_date' => 'required|date',
            'partlist_file' => 'required|file|mimes:pdf,xls,xlsx|max:10240',
            'umh_file' => 'required|file|mimes:pdf,xls,xlsx|max:10240',
            'notes' => 'nullable|string|max:1000',
        ], [
            'partlist_file.mimes' => 'Dokumen Partlist harus berformat PDF atau Excel (xls/xlsx).',
            'umh_file.mimes' => 'Dokumen UMH harus berformat PDF atau Excel (xls/xlsx).',
        ]);

        $partlistFile = $request->file('partlist_file');
        $umhFile = $request->file('umh_file');

        $partlistPath = $partlistFile->storeAs(
            'document-receipts/partlist',
            now()->format('YmdHis') . '-' . Str::uuid() . '.' . $partlistFile->getClientOriginalExtension()
        );

        $umhPath = $umhFile->storeAs(
            'document-receipts/umh',
            now()->format('YmdHis') . '-' . Str::uuid() . '.' . $umhFile->getClientOriginalExtension()
        );

        DocumentReceipt::create([
            'document_number' => $validated['document_number'] ?? null,
            'cust' => $validated['cust'] ?? null,
            'model' => $validated['model'] ?? null,
            'part_number' => $validated['part_number'] ?? null,
            'part_name' => $validated['part_name'] ?? null,
            'pl_date' => $validated['pl_date'] ?? null,
            'umh_date' => $validated['umh_date'] ?? null,
            'pic_eng' => $validated['pic_eng'] ?? null,
            'pic_mkt' => $validated['pic_mkt'] ?? null,
            'send_1_date' => $validated['send_1_date'] ?? null,
            'send_2_date' => $validated['send_2_date'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'received_date' => $validated['received_date'],
            'partlist_original_name' => $partlistFile->getClientOriginalName(),
            'partlist_file_path' => $partlistPath,
            'umh_original_name' => $umhFile->getClientOriginalName(),
            'umh_file_path' => $umhPath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->to(route('document-receipts.index', [], false))->with('success', 'Dokumen berhasil diterima dan disimpan.');
    }

    public function download(DocumentReceipt $documentReceipt, string $type)
    {
        if (!in_array($type, ['partlist', 'umh'], true)) {
            abort(404);
        }

        $pathField = $type . '_file_path';
        $nameField = $type . '_original_name';

        $path = $documentReceipt->{$pathField};
        $name = $documentReceipt->{$nameField};

        if (!$path || !Storage::exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::download($path, $name);
    }
}
